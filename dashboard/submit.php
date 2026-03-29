<?php
session_start();
include '../config/db.php';

$assignment_id = $_GET['id'];
$student_id = $_SESSION['user']['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $file_name = time() . "_" . $_FILES['file']['name'];
    move_uploaded_file($_FILES['file']['tmp_name'], "../uploads/" . $file_name);

    $sql = "INSERT INTO submissions (assignment_id, student_id, file)
            VALUES ('$assignment_id', '$student_id', '$file_name')";

    if ($conn->query($sql)) {
        echo "تم رفع الحل";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file" required><br>
    <button type="submit">رفع الحل</button>
</form>