import pandas as pd
import numpy as np
import mysql.connector
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, confusion_matrix, accuracy_score, recall_score
from sklearn.preprocessing import StandardScaler
import sys

# --- CONFIGURATION ---
RISK_THRESHOLD = 0.5  # Testing the custom sensitivity threshold

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

# Fetch Data
query = f"""
SELECT 
    s.StudentID, s.Disability, ar.Score, ar.Term, ar.AttendanceDays, 
    bd.AttentionSpanMinutes, bd.ClassParticipation,
    sph.Grade AS HistoricalGrade, sph.AttendanceRate AS HistoryAttendanceRate, sph.BehaviorScore AS HistoryBehaviorScore
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
    "HistoryAttendance", "HistoryBehaviorScore"
]
df_raw = pd.DataFrame(raw_data, columns=columns)

if df_raw.empty:
    print("No data found to evaluate.")
    sys.exit()

# ============================================
# 2. FEATURE ENGINEERING (Must Match Model.py)
# ============================================
participation_map = {"Poor": 1, "Average": 2, "Good": 3, "Excellent": 4}
df_raw["ParticipationScore"] = df_raw["Participation"].map(participation_map)

agg_features = df_raw.groupby('StudentID').agg(
    GPA_Current=('Score', 'mean'),
    Grade_Volatility=('Score', 'std'),
    Attendance_Rate_Current=('AttendanceDays', lambda x: x.iloc[0] / x.iloc[0] if x.iloc[0] > 0 else 0)
).reset_index()

df_final = df_raw.drop_duplicates(subset=['StudentID']).set_index('StudentID').join(
    agg_features.set_index('StudentID'), how='left'
)

df_final["HistoryGrade"] = pd.to_numeric(df_final["HistoryGrade"], errors='coerce').fillna(df_final["GPA_Current"])
df_final["Grade_Trend"] = df_final["GPA_Current"] - df_final["HistoryGrade"]

df_features = df_final[[
    "GPA_Current", "Grade_Volatility", "Attendance_Rate_Current",
    "HistoryGrade", "HistoryAttendance", "HistoryBehaviorScore",
    "ParticipationScore", "AttentionSpan", "Grade_Trend"
]].fillna(0).infer_objects(copy=False)

# ============================================
# 3. TRAINING & EVALUATION
# ============================================

X = df_features.values
# Ground Truth: 1 if GPA < 75 (At Risk), 0 otherwise
y = (df_final["GPA_Current"] < 75).astype(int) 

# Scale
scaler = StandardScaler()
X_scaled = scaler.fit_transform(X)

# SPLIT: 70% for Training, 30% for Testing
X_train, X_test, y_train, y_test = train_test_split(X_scaled, y, test_size=0.3, random_state=42)

# Train
rf = RandomForestClassifier(n_estimators=200, random_state=42)
rf.fit(X_train, y_train)

# --- STANDARD METRICS (0.5 Threshold) ---
print("\n" + "="*40)
print("  STANDARD EVALUATION (Threshold 0.5)")
print("="*40)
y_pred_standard = rf.predict(X_test)
print(classification_report(y_test, y_pred_standard, target_names=['Safe', 'At-Risk']))
print("Confusion Matrix (Standard):")
print(confusion_matrix(y_test, y_pred_standard))

# --- CUSTOM THRESHOLD METRICS (0.3 Threshold) ---
print("\n" + "="*40)
print(f"  CUSTOM SENSITIVITY EVALUATION (Threshold {RISK_THRESHOLD})")
print("  (Optimized for High Recall/Safety)")
print("="*40)

# Get probabilities instead of hard labels
probs = rf.predict_proba(X_test)[:, 1]
# Apply custom threshold
y_pred_custom = (probs >= RISK_THRESHOLD).astype(int)

print(classification_report(y_test, y_pred_custom, target_names=['Safe', 'At-Risk']))
print(f"Accuracy: {accuracy_score(y_test, y_pred_custom):.2f}")
print(f"Recall (At-Risk Catch Rate): {recall_score(y_test, y_pred_custom):.2f}")
print("\nConfusion Matrix (Custom):")
cm = confusion_matrix(y_test, y_pred_custom)
print(f"True Negatives (Correctly Safe): {cm[0][0]}")
print(f"False Positives (False Alarm):   {cm[0][1]}")
print(f"False Negatives (Missed Risk):   {cm[1][0]}")
print(f"True Positives (Caught Risk):    {cm[1][1]}")

cursor.close()
db.close()