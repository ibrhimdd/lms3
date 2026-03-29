<?php
session_start();
include '../config/db.php';

$id = $_GET['id'];

if ($_SESSION['user']['role'] == 'student') {

    $student_id = $_SESSION['user']['id'];

    $check = $conn->query("SELECT * FROM enrollments 
                           WHERE student_id=$student_id 
                           AND course_id=$id");

    if ($check->num_rows == 0) {
        die("❌ غير مسموح لك دخول هذا الكورس");
    }
}
// بيانات الكورس
$course = $conn->query("SELECT * FROM courses WHERE id=$id")->fetch_assoc();

echo "<h2>".$course['title']."</h2>";

// أزرار المعلم
if ($_SESSION['user']['role'] == 'teacher') {
    echo "<a href='add_lesson.php?course_id=".$id."'>➕ إضافة درس</a> | ";
    echo "<a href='add_assignment.php?course_id=".$id."'>➕ إضافة واجب</a><hr>";
    echo "<a href='generate_codes.php?course_id=".$id."'>🎟️ توليد أكواد</a><hr>";
}

// عرض الدروس
echo "<h3>📚 الدروس:</h3>";

$lessons = $conn->query("SELECT * FROM lessons WHERE course_id=$id");

while ($lesson = $lessons->fetch_assoc()) {
    echo "<h4>".$lesson['title']."</h4>";
    echo "<p>".$lesson['content']."</p>";

    if ($lesson['file']) {
        echo "<a href='../uploads/".$lesson['file']."'>📥 تحميل الملف</a>";
    }

    echo "<hr>";
}

// عرض الواجبات
echo "<h3>📄 الواجبات:</h3>";

$assignments = $conn->query("SELECT * FROM assignments WHERE course_id=$id");

while ($a = $assignments->fetch_assoc()) {
    echo "<h4>".$a['title']."</h4>";
    echo "<p>".$a['description']."</p>";
    echo "<p>📅 آخر موعد: ".$a['due_date']."</p>";

    // للطالب
    if ($_SESSION['user']['role'] == 'student') {
        echo "<a href='submit.php?id=".$a['id']."'>📤 رفع الحل</a>";
    }

    // للمعلم
    if ($_SESSION['user']['role'] == 'teacher') {
        echo " | <a href='grade.php?id=".$a['id']."'>📊 تصحيح</a>";
    }

    echo "<hr>";
}
?>