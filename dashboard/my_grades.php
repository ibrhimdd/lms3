<?php
session_start();
include '../config/db.php';

$student_id = $_SESSION['user']['id'];

$result = $conn->query("
    SELECT assignments.title, submissions.grade
    FROM submissions
    JOIN assignments ON submissions.assignment_id = assignments.id
    WHERE submissions.student_id = $student_id
");

while ($row = $result->fetch_assoc()) {
    echo "<h4>".$row['title']."</h4>";
    echo "<p>درجتك: ".$row['grade']."</p><hr>";
}