<?php
require_once '../config/db.php';
session_start();

if (isset($_SESSION['user_id']) && isset($_POST['course_id'])) {
    $uid = $_SESSION['user_id'];
    $cid = $_POST['course_id'];

    // فحص هل يوجد سجل للم طالب في هذه المادة؟
    $check = $pdo->prepare("SELECT id FROM course_timer WHERE student_id = ? AND course_id = ?");
    $check->execute([$uid, $cid]);
    
    if ($check->rowCount() > 0) {
        // تحديث بإضافة 60 ثانية (دقيقة)
        $pdo->prepare("UPDATE course_timer SET total_seconds = total_seconds + 60 WHERE student_id = ? AND course_id = ?")
            ->execute([$uid, $cid]);
    } else {
        // إنشاء سجل جديد لأول مرة
        $pdo->prepare("INSERT INTO course_timer (student_id, course_id, total_seconds) VALUES (?, ?, 60)")
            ->execute([$uid, $cid]);
    }
}