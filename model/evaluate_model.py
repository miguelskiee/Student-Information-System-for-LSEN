import pandas as pd
import numpy as np
import mysql.connector
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, confusion_matrix, accuracy_score, recall_score
from sklearn.preprocessing import StandardScaler
import sys

# --- CONFIGURATION ---
RISK_THRESHOLD = 0.3          # Lower = Safer (Catches more risks)
GRADE_THRESHOLD = 75          # Failing Grade
BEHAVIOR_POINTS_THRESHOLD = 10 # Behavior Risk Limit

# ============================================
# HELPER: PREVIOUS TERM
# ============================================
def get_previous_term(term_str):
    try:
        parts = term_str.split('-Q')
        year = int(parts[0])
        quarter = int(parts[1])
        return f"{year - 1}-Q4" if quarter == 1 else f"{year}-Q{quarter - 1}"
    except:
        return None 

# ============================================
# 1. CONNECT & FETCH DATA
# ============================================
try:
    db = mysql.connector.connect(
        host="localhost", user="root", password="", database="sagad_sis"
    )
    cursor = db.cursor()
except mysql.connector.Error as err:
    print(f"Error: {err}")
    sys.exit(1)

# Get Terms
cursor.execute("SELECT DISTINCT Term FROM AcademicRecords ORDER BY Term DESC LIMIT 1")
term_row = cursor.fetchone()
if not term_row: sys.exit(0)
CURRENT_TERM = term_row[0]
PREVIOUS_TERM = get_previous_term(CURRENT_TERM)

print(f"--- MODEL EVALUATION MODE ---")
print(f"Testing on Data from: {CURRENT_TERM}")
print(f"Risk Definition: Grade < {GRADE_THRESHOLD} OR Behavior > {BEHAVIOR_POINTS_THRESHOLD} Points")

# Fetch Data (ALIGNED WITH ACTUAL MODEL: Includes Behavior Records)
query = f"""
SELECT 
    s.StudentID, s.Disability, ar.Score, ar.Term, ar.AttendanceDays, 
    bd.AttentionSpanMinutes, bd.ClassParticipation,
    sph.Grade AS HistoryGrade, sph.AttendanceRate AS HistoryAttendance, sph.BehaviorScore AS HistoryBehaviorScore,
    -- Fetch Total Behavior Points (Live Data)
    COALESCE((SELECT SUM(points) FROM behavior_records WHERE student_id = s.StudentID), 0) as CurrentBehaviorPoints
FROM Students s
LEFT JOIN AcademicRecords ar ON s.StudentID = ar.StudentID
LEFT JOIN BehavioralData bd ON s.StudentID = bd.StudentID AND bd.DateObserved = (
    SELECT MAX(DateObserved) FROM BehavioralData WHERE StudentID = s.StudentID
)
LEFT JOIN StudentPerformanceHistory sph ON s.StudentID = sph.StudentID AND sph.Term = '{PREVIOUS_TERM}'
WHERE ar.Term = '{CURRENT_TERM}' 
"""
cursor.execute(query)
raw_data = cursor.fetchall()

columns = [
    "StudentID", "Disability", "Score", "Term", "AttendanceDays", 
    "AttentionSpan", "Participation", "HistoryGrade", 
    "HistoryAttendance", "HistoryBehaviorScore", "CurrentBehaviorPoints"
]
df_raw = pd.DataFrame(raw_data, columns=columns)

if df_raw.empty:
    print("No data found to evaluate.")
    sys.exit()

# ============================================
# 2. FEATURE ENGINEERING
# ============================================
participation_map = {"Poor": 1, "Average": 2, "Good": 3, "Excellent": 4}
df_raw["ParticipationScore"] = df_raw["Participation"].map(participation_map)

# Aggregate current term stats
agg_features = df_raw.groupby('StudentID').agg(
    GPA_Current=('Score', 'mean'),
    Total_Behavior=('CurrentBehaviorPoints', 'max'), # Take max/sum to be safe
    Attendance_Rate_Current=('AttendanceDays', lambda x: x.iloc[0] / x.iloc[0] if x.iloc[0] > 0 else 0)
).reset_index()

# Merge back
df_final = df_raw.drop_duplicates(subset=['StudentID']).set_index('StudentID').join(
    agg_features.set_index('StudentID'), how='left'
)

# Handle Missing History
df_final["HistoryGrade"] = pd.to_numeric(df_final["HistoryGrade"], errors='coerce').fillna(80)
df_final["HistoryAttendance"] = pd.to_numeric(df_final["HistoryAttendance"], errors='coerce').fillna(1.0)

# --- CALCULATE DRIFT ---
df_final["Attendance_Drop"] = df_final["Attendance_Rate_Current"] - df_final["HistoryAttendance"]

# --- SELECT FEATURES FOR TRAINING ---
# NOTE: We keep GPA_Current OUT of X to keep it predictive (no cheating).
# We add Total_Behavior to X because that is observable live.
df_features = df_final[[
    "Attendance_Rate_Current", 
    "Attendance_Drop",          
    "Total_Behavior",           # <--- ALIGNED: Uses live behavior points
    "HistoryGrade", 
    "HistoryAttendance", 
    "ParticipationScore", 
    "AttentionSpan"
]].fillna(0).infer_objects(copy=False)

# ============================================
# 3. TRAINING & EVALUATION
# ============================================

X = df_features.values

# --- ALIGNED TARGET DEFINITION ---
# Risk = Failing Grade OR High Behavior Points (Same as Actual Model)
y = ((df_final["GPA_Current"] < GRADE_THRESHOLD) | (df_final["Total_Behavior"] > BEHAVIOR_POINTS_THRESHOLD)).astype(int)

# Scale
scaler = StandardScaler()
X_scaled = scaler.fit_transform(X)

# SPLIT
X_train, X_test, y_train, y_test = train_test_split(X_scaled, y, test_size=0.3, random_state=42)

# Train with Balanced Weights (Forces AI to catch the few At-Risk cases)
rf = RandomForestClassifier(n_estimators=200, class_weight='balanced', random_state=42)
rf.fit(X_train, y_train)

# --- STANDARD METRICS (0.5) ---
print("\n" + "="*40)
print("  STANDARD EVALUATION (Threshold 0.5)")
print("="*40)
y_pred_standard = rf.predict(X_test)
print(classification_report(y_test, y_pred_standard, target_names=['Safe', 'At-Risk']))
print(confusion_matrix(y_test, y_pred_standard))

# --- CUSTOM SENSITIVITY METRICS ---
print("\n" + "="*40)
print(f"  CUSTOM SENSITIVITY EVALUATION (Threshold {RISK_THRESHOLD})")
print("  (Optimized for Early Warning)")
print("="*40)

probs = rf.predict_proba(X_test)[:, 1]
y_pred_custom = (probs >= RISK_THRESHOLD).astype(int)

print(classification_report(y_test, y_pred_custom, target_names=['Safe', 'At-Risk']))
print(f"Recall (At-Risk Catch Rate): {recall_score(y_test, y_pred_custom):.2f}")
print("\nConfusion Matrix (Custom):")
cm = confusion_matrix(y_test, y_pred_custom)
try:
    print(f"True Negatives (Correctly Safe): {cm[0][0]}")
    print(f"False Positives (False Alarm):   {cm[0][1]}")
    print(f"False Negatives (Missed Risk):   {cm[1][0]}")
    print(f"True Positives (Caught Risk):    {cm[1][1]}")
except:
    print(cm)

cursor.close()
db.close()