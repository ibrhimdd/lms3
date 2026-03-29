<?php
include '../config/db.php';

$assignment_id = $_GET['id'];

$result = $conn->query("
    SELECT submissions.*, users.name 
    FROM submissions 
    JOIN users ON submissions.student_id = users.id
    WHERE assignment_id = $assignment_id
");

while ($row = $result->fetch_assoc()) {
    echo "<h4>".$row['name']."</h4>";
    echo "<a href='../uploads/".$row['file']."'>تحميل الحل</a>";

    echo "
    <form method='POST'>
        <input type='hidden' name='sub_id' value='".$row['id']."'>
        <input type='number' name='grade' placeholder='الدرجة'>
        <button>حفظ</button>
    </form>
    <hr>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sub_id = $_POST['sub_id'];
    $grade = $_POST['grade'];

    $conn->query("UPDATE submissions SET grade='$grade' WHERE id='$sub_id'");
}