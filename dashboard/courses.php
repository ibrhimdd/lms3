<?php
session_start();
include '../config/db.php';

$user_id = $_SESSION['user']['id'];

$result = $conn->query("SELECT * FROM courses");

while ($row = $result->fetch_assoc()) {

    echo "<div style='border:1px solid #ccc; padding:10px; margin:10px;'>";

    echo "<h3>".$row['title']."</h3>";
    echo "<p>".$row['description']."</p>";

    // التحقق هل الطالب مسجل
    $check = $conn->query("SELECT * FROM enrollments 
                           WHERE student_id=$user_id 
                           AND course_id=".$row['id']);

    if ($check->num_rows > 0) {
        echo "<a href='course.php?id=".$row['id']."'>📘 دخول الكورس</a>";
    } else {
        echo "<a href='enroll.php?id=".$row['id']."'>✅ تسجيل في الكورس</a>";
    }

    echo "</div>";
}
?>