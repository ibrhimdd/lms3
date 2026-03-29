<?php
session_start();
include '../config/db.php';

if ($_SESSION['user']['role'] != 'teacher') {
    die("غير مصرح");
}

$teacher_id = $_SESSION['user']['id'];

echo "<h2>👨‍🏫 لوحة تحكم المعلم</h2>";

echo "<a href='add_course.php'>➕ إضافة كورس جديد</a><hr>";

$result = $conn->query("SELECT * FROM courses WHERE teacher_id=$teacher_id");

while ($row = $result->fetch_assoc()) {

    echo "<div style='border:1px solid #ccc; padding:10px; margin:10px;'>";

    echo "<h3>".$row['title']."</h3>";
    echo "<p>".$row['description']."</p>";

    echo "<a href='course.php?id=".$row['id']."'>📘 دخول الكورس</a><br>";

    echo "<a href='add_lesson.php?course_id=".$row['id']."'>➕ إضافة درس</a> | ";
    echo "<a href='add_assignment.php?course_id=".$row['id']."'>➕ إضافة واجب</a>";

    echo "</div>";
}