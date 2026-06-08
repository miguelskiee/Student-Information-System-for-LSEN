<?php
// model/deped_formulas.php

class DepEdGrading {

    // Official Weights per DepEd Order No. 8, s. 2015
    private static $weights = [
        'Languages'    => ['WW' => 0.30, 'PT' => 0.50, 'QA' => 0.20], // Eng, Fil, AP, EsP
        'Math/Science' => ['WW' => 0.40, 'PT' => 0.40, 'QA' => 0.20], // Math, Science
        'MAPEH/TLE'    => ['WW' => 0.20, 'PT' => 0.60, 'QA' => 0.20]  // MAPEH, EPP, TLE
    ];

    // Map your database AssignmentTypes to DepEd Components
    public static function mapTypeToComponent($type) {
        switch($type) {
            case 'Homework':
            case 'Quiz':
            case 'Essay':
                return 'WW'; // Written Work
            case 'Project':
                return 'PT'; // Performance Task
            case 'Exam':
                return 'QA'; // Quarterly Assessment
            default:
                return 'WW';
        }
    }

    // Calculate Initial Grade
    public static function calculateInitialGrade($assignments, $subjectCategory = 'Math/Science') {
        $components = ['WW' => ['score'=>0, 'max'=>0], 'PT' => ['score'=>0, 'max'=>0], 'QA' => ['score'=>0, 'max'=>0]];

        // 1. Aggregate Scores
        foreach ($assignments as $a) {
            $comp = self::mapTypeToComponent($a['AssignmentType']);
            $components[$comp]['score'] += $a['StudentScore'];
            $components[$comp]['max'] += $a['MaxScore'];
        }

        // 2. Calculate Weighted Average
        $initialGrade = 0;
        $w = self::$weights[$subjectCategory] ?? self::$weights['Math/Science'];

        foreach ($components as $key => $data) {
            if ($data['max'] > 0) {
                // Percentage Score * Weight
                $percentage = ($data['score'] / $data['max']) * 100;
                $initialGrade += $percentage * $w[$key];
            } else {
                // If no assignments in this category (e.g. no Exam yet), we assume 100% or 0%? 
                // In standard practice, it's 0, but for interim grades, we might re-normalize. 
                // For this system, we treat missing components as 0.
            }
        }

        return $initialGrade;
    }

    // DepEd Transmutation Table (Simplified Logic for Lookup)
    public static function transmuteGrade($initialGrade) {
        // Standard Transmutation (Initial -> Transmuted)
        // 0-3.99 -> 60 ... 100 -> 100
        // This is a simplified version of the full table for brevity
        $initial = round($initialGrade, 2);

        if ($initial >= 100) return 100;
        if ($initial <= 0) return 60;

        // Formula approximation for K-12: (Initial * 0.625) + 60 ?? No, that's not official.
        // Let's use the explicit ranges for common passing
        if ($initial >= 98.40) return 99;
        if ($initial >= 96.80) return 98;
        if ($initial >= 95.20) return 97;
        if ($initial >= 93.60) return 96;
        if ($initial >= 92.00) return 95;
        if ($initial >= 90.40) return 94;
        if ($initial >= 88.80) return 93;
        if ($initial >= 87.20) return 92;
        if ($initial >= 85.60) return 91;
        if ($initial >= 84.00) return 90;
        if ($initial >= 82.40) return 89;
        if ($initial >= 80.80) return 88;
        if ($initial >= 79.20) return 87;
        if ($initial >= 77.60) return 86;
        if ($initial >= 76.00) return 85;
        if ($initial >= 74.40) return 84;
        if ($initial >= 72.80) return 83;
        if ($initial >= 71.20) return 82;
        if ($initial >= 69.60) return 81;
        if ($initial >= 68.00) return 80;
        if ($initial >= 66.40) return 79;
        if ($initial >= 64.80) return 78;
        if ($initial >= 63.20) return 77;
        if ($initial >= 61.60) return 76;
        if ($initial >= 60.00) return 75; // PASSING MARK
        if ($initial >= 56.00) return 74;
        if ($initial >= 52.00) return 73;
        if ($initial >= 48.00) return 72;
        if ($initial >= 44.00) return 71;
        if ($initial >= 40.00) return 70;
        if ($initial >= 36.00) return 69;
        if ($initial >= 32.00) return 68;
        if ($initial >= 28.00) return 67;
        if ($initial >= 24.00) return 66;
        if ($initial >= 20.00) return 65;
        if ($initial >= 16.00) return 64;
        if ($initial >= 12.00) return 63;
        if ($initial >= 8.00) return 62;
        if ($initial >= 4.00) return 61;
        
        return 60;
    }
}
?>