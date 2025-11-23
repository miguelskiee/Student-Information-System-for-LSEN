<?php
// model/data_submit.php
include 'db.php'; 

// Ensure this script is only accessed via POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

$formType = $_POST['form_type'] ?? die("Invalid form type.");

try {
    $conn->beginTransaction();

    if ($formType === 'student') {
        $studentID = $_POST['student_id'];
        $isUpdate = !empty($studentID);

        $data = [
            $_POST['first_name'], $_POST['last_name'], $_POST['middle_name'], $_POST['birth_date'], 
            $_POST['sex'], $_POST['grade_level'], $_POST['section'], $_POST['disability'], $_POST['notes']
        ];
        
        if ($isUpdate) {
            $sql = "UPDATE Students SET FirstName=?, LastName=?, MiddleName=?, BirthDate=?, Sex=?, GradeLevel=?, Section=?, Disability=?, Notes=? WHERE StudentID=?";
            $data[] = $studentID;
        } else {
            $sql = "INSERT INTO Students (FirstName, LastName, MiddleName, BirthDate, Sex, GradeLevel, Section, Disability, Notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        $message = $isUpdate ? "Student updated successfully." : "Student added successfully.";
        $redirect = "../pages/Students.php";

    } elseif ($formType === 'teacher') {
        $teacherID = $_POST['teacher_id'];
        $isUpdate = !empty($teacherID);

        $isSped = isset($_POST['is_sped_certified']) ? 1 : 0;
        
        $data = [
            $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], 
            $_POST['specializations'], $_POST['certifications'], $isSped, $_POST['user_role']
        ];

        if ($isUpdate) {
            $sql = "UPDATE Teachers SET FirstName=?, LastName=?, Email=?, Phone=?, Specializations=?, Certifications=?, IsSpecialEdCertified=?, UserRole=? WHERE TeacherID=?";
            $data[] = $teacherID;
        } else {
            $sql = "INSERT INTO Teachers (FirstName, LastName, Email, Phone, Specializations, Certifications, IsSpecialEdCertified, UserRole) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        $message = $isUpdate ? "Teacher updated successfully." : "Teacher added successfully.";
        $redirect = "../pages/Teacher_Page.php";
        
    } elseif ($formType === 'subject') {
        $subjectID = $_POST['subject_id'];
        $isUpdate = !empty($subjectID);
        
        $data = [$_POST['subject_name'], $_POST['code'], $_POST['grade_level']];
        
        if ($isUpdate) {
            $sql = "UPDATE Subjects SET SubjectName=?, Code=?, GradeLevel=? WHERE SubjectID=?";
            $data[] = $subjectID;
        } else {
            $sql = "INSERT INTO Subjects (SubjectName, Code, GradeLevel) VALUES (?, ?, ?)";
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        $message = $isUpdate ? "Subject updated successfully." : "Subject added successfully.";
        $redirect = "../pages/Classes.php";
    }

    $conn->commit();
    // Use a redirect parameter to display success message later
    header("Location: " . $redirect . "?status=success&msg=" . urlencode($message));
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $message = "Database Error: " . $e->getMessage();
    header("Location: ../pages/error_page.php?status=error&msg=" . urlencode($message));
    exit;
}
?>