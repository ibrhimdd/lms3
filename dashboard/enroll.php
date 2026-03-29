<?php
session_start();
include '../config/db.php';

$course_id = $_GET['id'];
$student_id = $_SESSION['user']['id'];

// منع التكرار
$check = $conn->query("SELECT * FROM enrollments 
                       WHERE student_id=$student_id 
                       AND course_id=$course_id");

if ($check->num_rows == 0) {
    $conn->query("INSERT INTO enrollments (student_id, course_id)
                  VALUES ('$student_id', '$course_id')");
}

header("Location: courses.php");