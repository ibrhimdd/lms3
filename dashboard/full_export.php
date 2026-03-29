<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    die("دخول غير مصرح");
}

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) die("المادة غير محددة!");

$c_stmt = $pdo->prepare("SELECT course_name FROM courses WHERE id = ?");
$c_stmt->execute([$course_id]);
$course = $c_stmt->fetch();
$filename = "Report_" . str_replace(' ', '_', $course['course_name'] ?? 'Course') . "_" . date('Y-m-d') . ".xls";

// تم حذف u.phone من هنا أيضاً
$students_stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name 
    FROM users u 
    JOIN course_tokens ct ON u.id = ct.used_by_student_id 
    WHERE ct.course_id = ? AND ct.is_used = 1
");
$students_stmt->execute([$course_id]);
$students = $students_stmt->fetchAll();

$exams = $pdo->prepare("SELECT id, exam_title FROM exams WHERE course_id = ?");
$exams->execute([$course_id]);
$all_exams = $exams->fetchAll();

$assigns = $pdo->prepare("SELECT id, title FROM assignments WHERE course_id = ?");
$assigns->execute([$course_id]);
$all_assigns = $assigns->fetchAll();

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
?>

<table border="1">
    <thead>
        <tr style="background-color: #4f46e5; color: #ffffff; font-weight: bold;">
            <th>اسم الطالب</th>
            <?php foreach($all_exams as $ex): ?> <th>امتحان: <?= htmlspecialchars($ex['exam_title']) ?></th> <?php endforeach; ?>
            <?php foreach($all_assigns as $as): ?> <th>واجب: <?= htmlspecialchars($as['title']) ?></th> <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach($students as $st): ?>
            <tr>
                <td style="font-weight: bold;"><?= htmlspecialchars($st['full_name']) ?></td>
                <?php foreach($all_exams as $ex): 
                    $score_stmt = $pdo->prepare("SELECT score FROM exam_results WHERE exam_id = ? AND student_id = ?");
                    $score_stmt->execute([$ex['id'], $st['id']]);
                    $score = $score_stmt->fetchColumn();
                    echo "<td align='center'>" . ($score !== false ? $score : '-') . "</td>";
                endforeach; ?>
                <?php foreach($all_assigns as $as): 
                    $sub_stmt = $pdo->prepare("SELECT grade FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
                    $sub_stmt->execute([$as['id'], $st['id']]);
                    $grade = $sub_stmt->fetchColumn();
                    echo "<td align='center'>" . ($grade !== false ? $grade : 'لم يسلم') . "</td>";
                endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>