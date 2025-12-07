import pandas as pd
import numpy as np
import mysql.connector
from sklearn.ensemble import RandomForestClassifier, IsolationForest
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, roc_auc_score
from sklearn.preprocessing import StandardScaler
from collections import defaultdict # Used for manual aggregation
import json # Used for saving job details

# ============================================
# 1. CONNECT TO DATABASE & DATA FETCH
# ============================================
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="admin",
    database="sagad_sis"
    port=3307
)
cursor = db.cursor()

# Query pulls the core data needed from the latest term and historical data
query = """
SELECT 
    s.StudentID,
    s.Disability,
    ar.Score,
    ar.Term,
    ar.AttendanceDays,
    ar.TotalPossibleDays,
    bd.AttentionSpanMinutes,
    bd.ClassParticipation,
    sph.Term AS HistoryTerm,
    sph.Grade AS HistoricalGrade,
    sph.AttendanceRate AS HistoryAttendanceRate,
    sph.BehaviorScore AS HistoryBehaviorScore
FROM Students s
LEFT JOIN AcademicRecords ar ON s.StudentID = ar.StudentID
LEFT JOIN BehavioralData bd ON s.StudentID = bd.StudentID AND bd.DateObserved = (
    SELECT MAX(DateObserved) FROM BehavioralData WHERE StudentID = s.StudentID
)
LEFT JOIN StudentPerformanceHistory sph ON s.StudentID = sph.StudentID AND sph.Term = '2023-Q4'
WHERE ar.Term = '2024-Q1' -- Focus only on the latest term data for current scores
"""
cursor.execute(query)
raw_data = cursor.fetchall()

columns = [
    "StudentID", "Disability", "Score", "Term", "AttendanceDays", "TotalPossibleDays",
    "AttentionSpan", "Participation", "HistoryTerm", "HistoryGrade", 
    "HistoryAttendance", "HistoryBehaviorScore"
]

df_raw = pd.DataFrame(raw_data, columns=columns)

# ============================================
# 2. FEATURE ENGINEERING & AGGREGATION (PANDAS FIX)
# ============================================

# Map Participation to a numerical score
participation_map = {"Poor": 1, "Average": 2, "Good": 3, "Excellent": 4}
df_raw["ParticipationScore"] = df_raw["Participation"].map(participation_map)

# 2.1 Calculate Aggregated Academic Features for 2024-Q1
agg_features = df_raw.groupby('StudentID').agg(
    # GPA (Mean Score)
    GPA_Current=('Score', 'mean'),
    # Grade Volatility (Standard Deviation of Scores)
    Grade_Volatility=('Score', 'std'),
    # Subject Imbalance (Max Score - Min Score)
    Subject_Imbalance=('Score', lambda x: x.max() - x.min()),
    # Overall Attendance (using the last recorded attendance days)
    Attendance_Rate_Current=('AttendanceDays', lambda x: x.iloc[0] / x.iloc[0] if x.iloc[0] > 0 else 0)
).reset_index()

# 2.2 Merge in Historical and Behavioral Features 
df_final = df_raw.drop_duplicates(subset=['StudentID']).set_index('StudentID').join(
    agg_features.set_index('StudentID'), how='left'
)

# 2.3 Calculate Grade Trend (Current GPA - Historical Grade)
# *** FIX APPLIED HERE: Convert decimal.Decimal to float for arithmetic ***
df_final["HistoryGrade"] = df_final["HistoryGrade"].astype(float)
df_final["Grade_Trend"] = df_final["GPA_Current"] - df_final["HistoryGrade"]

# 2.4 Final Feature Selection and Cleanup
df_features = df_final[[
    "GPA_Current", 
    "Grade_Volatility", 
    "Subject_Imbalance", 
    "Attendance_Rate_Current",
    "HistoryGrade", 
    "HistoryAttendance", 
    "HistoryBehaviorScore",
    "ParticipationScore",
    "AttentionSpan",
    "Grade_Trend"
]]

# Replace NaNs with 0 after trend/volatility calculation (important for Isolation Forest)
df_features = df_features.fillna(0)
# Ensure infinite values (from 0 division in aggregation) are handled
df_features.replace([np.inf, -np.inf], 0, inplace=True) 

X = df_features.values
X_ids = df_features.index.values # Keep IDs separate for later saving

# ============================================
# 3. RANDOM FOREST PREDICTION MODEL
# ============================================

# Target variable: "At-Risk" if current GPA < 75 (Rule-Based Labeling)
df_final["RiskLabel"] = (df_final["GPA_Current"] < 75).astype(int)
y = df_final["RiskLabel"]

# 3.1 Scaling Features 
scaler = StandardScaler()
X_scaled = scaler.fit_transform(X)

# 3.2 Stratified Split 
# NOTE: Since this is run on the full dataset, the split/report is just for validation/testing
X_train, X_test, y_train, y_test = train_test_split(
    X_scaled, y, 
    test_size=0.3, 
    random_state=42,
    stratify=y 
)

rf = RandomForestClassifier(n_estimators=200, random_state=42)
rf.fit(X_train, y_train)

y_pred = rf.predict(X_test)

# Evaluation (Printed output for local testing/monitoring)
print("=== RANDOM FOREST REPORT ===")
print(classification_report(y_test, y_pred, zero_division=0))

# Get predictions on the full scaled dataset
df_final["PredictedRisk"] = rf.predict(X_scaled)
df_final["RiskProbability"] = rf.predict_proba(X_scaled)[:, 1]

# Feature Importance
feature_importances = pd.Series(rf.feature_importances_, index=df_features.columns)
print("\n=== TOP 5 FEATURE IMPORTANCE ===")
print(feature_importances.sort_values(ascending=False).head(5))

# ============================================
# 4. ISOLATION FOREST ANOMALY DETECTION
# ============================================

# Use the scaled data for anomaly detection
iso = IsolationForest(contamination=0.1, random_state=42) 
df_final["Anomaly"] = iso.fit_predict(X_scaled)

# Convert to readable format
df_final["AnomalyFlag"] = df_final["Anomaly"].map({1: 0, -1: 1}) # 1 = anomaly/outlier
df_final["AnomalyScore"] = iso.decision_function(X_scaled)

# ============================================
# 5. SAVE RESULTS BACK TO DATABASE
# ============================================

# Clear previous alerts before inserting new ones
cursor.execute("TRUNCATE TABLE AI_PerformanceAlerts") 
db.commit()

save_query = """
INSERT INTO AI_PerformanceAlerts 
(StudentID, RiskLevel, PredictedIssue, ModelVersion, DateGenerated, RiskProbability, AnomalyScore) 
VALUES (%s, %s, %s, 'RF-iF-v1.1', NOW(), %s, %s)
"""

# Count alerts and anomalies for SystemJobs logging
alerts_generated = 0
anomalies_detected = 0

for student_id in df_final.index:
    row = df_final.loc[student_id]
    risk_level = "High" if row["PredictedRisk"] == 1 else ("Medium" if row["RiskProbability"] > 0.6 else "Low")
    predicted_issue = f"At Risk ({row['RiskProbability']:.2f})" if row["PredictedRisk"] == 1 else "Normal"
    
    # Override issue if anomaly is detected
    if row["AnomalyFlag"] == 1:
        predicted_issue = f"Anomaly Detected (Score: {row['AnomalyScore']:.2f})"
        risk_level = "High" # Anomalies are often high risk for follow-up
        anomalies_detected += 1
    
    if row["PredictedRisk"] == 1:
        alerts_generated += 1
        
    cursor.execute(save_query, (
        int(student_id),
        risk_level,
        predicted_issue,
        float(row["RiskProbability"]),
        float(row["AnomalyScore"])
    ))

db.commit()
print("\nResults successfully saved to AI_PerformanceAlerts table.")

# ============================================
# 6. UPDATE SYSTEM JOB STATUS (NEW FOR PHP CONCURRENCY)
# ============================================

job_details = {
    'alerts_generated': alerts_generated,
    'anomalies_detected': anomalies_detected,
    'total_students_processed': len(df_final)
}

# Insert a record indicating the successful completion of the job
job_query = """
INSERT INTO SystemJobs 
(JobName, JobType, Status, StartedAt, CompletedAt, Details) 
VALUES (%s, %s, %s, NOW(), NOW(), %s)
"""

cursor.execute(job_query, (
    'ML_Inference', 
    'Inference', 
    'Completed', 
    json.dumps(job_details) # Store job_details as JSON string
))

db.commit()
print("SystemJobs status updated.")
cursor.close()
db.close()