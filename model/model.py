import pandas as pd
import numpy as np
import mysql.connector
from sklearn.ensemble import RandomForestClassifier, IsolationForest
from sklearn.preprocessing import StandardScaler
import sys

# --- CONFIGURATION ---
RISK_THRESHOLD = 0.5
ATTENDANCE_THRESHOLD = 0.85
GRADE_THRESHOLD = 75
BEHAVIOR_POINTS_THRESHOLD = 10 # If sum of behavior points > 10, flag as risk

# ============================================
# KNOWLEDGE BASE: DISABILITY-SPECIFIC STRATEGIES
# ============================================
STRATEGY_MAP = {
    'ADHD': {
        'Grades': ["Break tasks into micro-steps with checklists.", "Use gamified learning (Kahoot).", "Allow standing desks."],
        'Attendance': ["Schedule soft starts (low pressure mornings).", "Morning classroom jobs."]
    },
    'ASD - General': { 
        'Grades': ["Use visual schedules/graphic organizers.", "Incorporate special interests into lessons."],
        'Attendance': ["Visual countdowns for transitions.", "Predictable morning routine."]
    },
    'ASD - L1': { # Level 1 (Requiring Support)
        'Grades': ["Social scripts for group work.", "Noise-canceling headphones during testing."],
        'Attendance': ["Peer buddy system for arrival."]
    },
    'ASD - L2': { # Level 2 (Substantial Support)
        'Grades': ["Simplified instructions with pictures.", "One-step commands only.", "Reduced workload."],
        'Attendance': ["Direct hand-off from parent to aide.", "Sensory-friendly entry route."]
    },
    'ASD - ID': { # ASD + Intellectual Disability
        'Grades': ["Focus on functional life skills.", "Concrete objects for math (manipulatives).", "High repetition."],
        'Attendance': ["Reward chart for daily attendance."]
    },
    'SLD': { # Specific Learning Disability
        'Grades': ["Provide audiobooks/Text-to-Speech.", "No penalty for spelling errors.", "Extended time on tests."],
        'Attendance': ["Check for anxiety regarding reading aloud."]
    },
    'V.I.': { # Visual Impairment
        'Grades': ["Large print or Braille materials.", "High-contrast handouts.", "Seat in front row."],
        'Attendance': ["Orientation and mobility support for hallways."]
    },
    'H.I.': { # Hearing Impairment
        'Grades': ["Captions on all videos.", "Face student when speaking (lip reading).", "Visual cues for bells."],
        'Attendance': ["Flashing light alerts for schedule changes."]
    },
    'Osteogenesis Imperfecta': { # Brittle Bone Disease
        'Grades': ["Ergonomic seating is critical.", "Allow elevator use (no stairs).", "Extra time for writing (hand fatigue)."],
        'Attendance': ["Plan for medical absences.", "Avoid crowded hallways during transitions."]
    },
    'CP': { # Cerebral Palsy
        'Grades': ["Assistive technology (speech-to-text).", "Adaptive writing tools.", "Digital submission of homework."],
        'Attendance': ["Accessible transport coordination.", "Allow late arrival to avoid crowds."]
    },
    'Seizure/Epilepsy': {
        'Grades': ["Stop testing during aura/prodrome.", "Provide recovery time (naps) without penalty.", "Notes provided if class missed."],
        'Attendance': ["Monitor for medication side-effects (drowsiness)."]
    },
    'Bipolar/Schizophrenia': {
        'Grades': ["Flexible deadlines during episodes.", "Quiet space for grounding.", "Reduce homework during instability."],
        'Attendance': ["Afternoon check-ins.", "Allow 'mental health days' without academic penalty."]
    },
    'General': { 
        'Grades': ["Peer tutoring.", "Study guides provided early."],
        'Attendance': ["Parent meetings.", "Transport checks."]
    },
    'Enrichment': { 
        'General': ["Leadership roles.", "Advanced projects.", "Mentoring others."]
    }
}

# --- BEHAVIOR INTERVENTIONS (Applies to all) ---
BEHAVIOR_INTERVENTIONS = {
    'Aggression': ["Teach replacement behaviors.", "Identify antecedents.", "Safety first protocol."],
    'Elopement': ["Seat away from doors.", "Visual Stop/Go signs.", "Request break cards."],
    'Meltdown': ["Reduce sensory input.", "First-Then board.", "Quiet corner access."],
    'Non-Compliance': ["Offer two choices.", "Neutral tone.", "Focus on start requests."],
    'Social Withdrawal': ["Assign a buddy.", "Small group roles.", "Private praise."],
    'Self-Injury': ["Block sensory triggers.", "Safe alternatives (stress ball).", "Counselor referral."]
}

# ============================================
# 1. CONNECT & FETCH
# ============================================
try:
    db = mysql.connector.connect(host="localhost", user="root", password="", database="sagad_sis")
    cursor = db.cursor()
except mysql.connector.Error as err:
    print(f"Error: {err}")
    sys.exit(1)

# Get Term
cursor.execute("SELECT DISTINCT Term FROM AcademicRecords ORDER BY Term DESC LIMIT 1")
term_row = cursor.fetchone()
if not term_row: sys.exit(0)
CURRENT_TERM = term_row[0]

# Previous Term Helper
def get_prev_term(t):
    try:
        y, q = t.split('-Q')
        return f"{int(y)-1}-Q4" if int(q)==1 else f"{y}-Q{int(q)-1}"
    except: return None
PREVIOUS_TERM = get_prev_term(CURRENT_TERM)

print(f"--- AI ENGINE STARTED: {CURRENT_TERM} ---")

# --- FETCH DATA ---
# Negative points are high positive numbers in 'behavior_records' usually, 
# but based on your PHP, Aggression=5. So High Sum = Bad.
query = f"""
SELECT 
    s.StudentID, s.Disability, ar.Score, ar.AttendanceDays,
    COALESCE(SUM(br.points), 0) as TotalBehaviorPoints,
    (SELECT behavior_type FROM behavior_records br2 WHERE br2.student_id = s.StudentID AND br2.category = 'Negative' GROUP BY behavior_type ORDER BY COUNT(*) DESC LIMIT 1) as FreqIssue,
    sph.Grade AS HistGrade
FROM Students s
LEFT JOIN AcademicRecords ar ON s.StudentID = ar.StudentID AND ar.Term = '{CURRENT_TERM}'
LEFT JOIN behavior_records br ON s.StudentID = br.student_id 
LEFT JOIN StudentPerformanceHistory sph ON s.StudentID = sph.StudentID AND sph.Term = '{PREVIOUS_TERM}'
GROUP BY s.StudentID
"""
cursor.execute(query)
raw_data = cursor.fetchall()

if not raw_data: sys.exit(0)

cols = ["StudentID", "Disability", "Score", "Attendance", "BehPoints", "FreqIssue", "HGrade"]
df = pd.DataFrame(raw_data, columns=cols)

# ============================================
# 2. PRE-PROCESSING
# ============================================
df['Score'] = df['Score'].fillna(0)
df['Attendance'] = df['Attendance'].fillna(0)
df['HGrade'] = pd.to_numeric(df['HGrade'], errors='coerce').fillna(df['Score'])
df['AttRate'] = (df['Attendance'] / 60.0).clip(upper=1.0) # Assumes 60 days/term

# Model Features
X = df[["Score", "AttRate", "HGrade", "BehPoints"]].fillna(0).values
scaler = StandardScaler()
X_scaled = scaler.fit_transform(X)

# ============================================
# 3. AI ANALYSIS
# ============================================
rf = RandomForestClassifier(n_estimators=100, random_state=42)
# Train model to predict if student is "At Risk" (Grades < 75 OR High Behavior Points)
rf.fit(X_scaled, ((df["Score"] < GRADE_THRESHOLD) | (df["BehPoints"] > BEHAVIOR_POINTS_THRESHOLD)).astype(int))
probs = rf.predict_proba(X_scaled)[:, 1]

iso = IsolationForest(contamination=0.1, random_state=42)
anomalies = iso.fit_predict(X_scaled)

df["RiskProb"] = probs
df["IsAnomaly"] = anomalies == -1

# ============================================
# 4. GENERATE ALERTS & RECOMMENDATIONS
# ============================================
cursor.execute("TRUNCATE TABLE AI_PerformanceAlerts")
cursor.execute("TRUNCATE TABLE AI_TeachingRecommendations")

alert_sql = """INSERT INTO AI_PerformanceAlerts (StudentID, RiskLevel, PredictedIssue, ModelVersion, DateGenerated, RiskProbability, AnomalyScore) VALUES (%s, %s, %s, 'RF-V2-DisabilityAware', NOW(), %s, 0)"""
rec_sql = """INSERT INTO AI_TeachingRecommendations (StudentID, TeacherID, LearningNeed, RecommendedStrategy, Source, DateGenerated) VALUES (%s, 1, %s, %s, 'SmartEngine-V2', NOW())"""

count_alerts = 0
count_recs = 0

for sid in df.index:
    row = df.iloc[sid]
    student_id = row['StudentID']
    
    # --- A. DIAGNOSE ISSUES ---
    issues = []
    if row['Score'] < GRADE_THRESHOLD: issues.append('Grades')
    if row['AttRate'] < ATTENDANCE_THRESHOLD: issues.append('Attendance')
    if row['BehPoints'] > BEHAVIOR_POINTS_THRESHOLD: issues.append('Behavior')
    
    is_risk = row["RiskProb"] >= RISK_THRESHOLD or row["IsAnomaly"]
    
    # --- B. SAVE ALERTS ---
    if is_risk:
        risk_lvl = "High" if row["RiskProb"] >= 0.7 else "Medium"
        issue_txt = f"At Risk: {', '.join(issues)}" if issues else "At Risk: Prediction"
        if row["IsAnomaly"] and not issues: issue_txt = "Monitor Performance (Anomaly Detected)"
        cursor.execute(alert_sql, (int(student_id), risk_lvl, issue_txt, float(row["RiskProb"])))
        count_alerts += 1
    
    # --- C. GENERATE STRATEGIES ---
    d_raw = str(row['Disability']).upper()
    d_key = 'General'

    # STRICT MATCHING LOGIC
    if 'ASD' in d_raw or 'AUTISM' in d_raw:
        if 'ID' in d_raw: d_key = 'ASD - ID'
        elif 'L2' in d_raw: d_key = 'ASD - L2'
        elif 'L1' in d_raw: d_key = 'ASD - L1'
        else: d_key = 'ASD - General'
    elif 'ADHD' in d_raw: d_key = 'ADHD'
    elif 'SLD' in d_raw or 'DYSLEXIA' in d_raw: d_key = 'SLD'
    elif 'V.I.' in d_raw or 'VISUAL' in d_raw: d_key = 'V.I.'
    elif 'H.I.' in d_raw or 'HEARING' in d_raw: d_key = 'H.I.'
    elif 'OSTEOGENESIS' in d_raw or 'IMPERPECTA' in d_raw: d_key = 'Osteogenesis Imperfecta'
    elif 'CP' in d_raw or 'CEREBRAL' in d_raw: d_key = 'CP'
    elif 'BIPOLAR' in d_raw or 'SCHIZO' in d_raw or 'BI POLAR' in d_raw: d_key = 'Bipolar/Schizophrenia'
    elif 'SEIZURE' in d_raw or 'EPILEPSY' in d_raw: d_key = 'Seizure/Epilepsy'

    recs_to_add = []
    
    # 1. Behavior specific
    freq_issue = row['FreqIssue']
    if freq_issue and freq_issue in BEHAVIOR_INTERVENTIONS:
         recs_to_add.append(f"<b>Beh: {freq_issue}</b>")
         recs_to_add.extend(BEHAVIOR_INTERVENTIONS[freq_issue][:2])

    # 2. Disability specific (Grades/Attendance)
    if 'Grades' in issues:
        strategies = STRATEGY_MAP.get(d_key, STRATEGY_MAP['General']).get('Grades', [])
        recs_to_add.extend(strategies)
    if 'Attendance' in issues:
        strategies = STRATEGY_MAP.get(d_key, STRATEGY_MAP['General']).get('Attendance', [])
        recs_to_add.extend(strategies)
    
    # 3. Enrichment
    if not issues and not is_risk:
        recs_to_add.extend(STRATEGY_MAP['Enrichment']['General'][:2])

    # SAVE
    if recs_to_add:
        final_strategy_text = "• " + "\n• ".join(list(dict.fromkeys(recs_to_add)))
        learning_need_txt = f"{d_key}"
        if issues: learning_need_txt += f" ({', '.join(issues)})"
        
        cursor.execute(rec_sql, (int(student_id), learning_need_txt, final_strategy_text))
        count_recs += 1

db.commit()
print(f"Done. Saved {count_alerts} alerts and {count_recs} recommendations.")
cursor.close()
db.close()