<?php 
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php"); exit();
}

$user_id = $_SESSION['user_id'];
$exam_id = $_POST['exam_id'];
$answers = $_POST['answer'] ?? []; // المصفوفة اللي جاية من الراديو بوتون

// 1. جلب الأسئلة الصحيحة والدرجات من القاعدة للمقارنة
$stmt = $pdo->prepare("SELECT id, correct_option, question_mark FROM questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_score = 0;
$obtained_score = 0;

try {
    $pdo->beginTransaction(); // بدء "Transaction" لضمان حفظ كل شيء أو لا شيء

    foreach ($questions as $q) {
        $q_id = $q['id'];
        $correct_opt = $q['correct_option'];
        $mark = $q['question_mark'];
        $total_score += $mark;

        $student_opt = $answers[$q_id] ?? null; // إجابة الطالب
        $is_correct = ($student_opt === $correct_opt) ? 1 : 0;

        if ($is_correct) {
            $obtained_score += $mark;
        }

        // 2. حفظ إجابة كل سؤال (عشان التقارير المفصلة اللي طلبتها)
        $ins_ans = $pdo->prepare("INSERT INTO student_answers (student_id, exam_id, question_id, student_answer, is_correct) VALUES (?, ?, ?, ?, ?)");
        $ins_ans->execute([$user_id, $exam_id, $q_id, $student_opt, $is_correct]);
    }

    // 3. حفظ النتيجة الإجمالية في جدول exam_results
    // تأكد أن جدول exam_results يحتوي على (student_id, exam_id, score, total_marks, submitted_at)
    $ins_res = $pdo->prepare("INSERT INTO exam_results (student_id, exam_id, score, total_marks) VALUES (?, ?, ?, ?)");
    $ins_res->execute([$user_id, $exam_id, $obtained_score, $total_score]);

    $pdo->commit(); // اعتماد الحفظ
    
    // التحويل لصفحة النتيجة
    header("Location: exam_result_view.php?exam_id=" . $exam_id);

} catch (Exception $e) {
    $pdo->rollBack();
    die("حدث خطأ أثناء حفظ الإجابات: " . $e->getMessage());
}