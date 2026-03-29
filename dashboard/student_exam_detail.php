<?php 
require_once '../config/db.php';
session_start();

// ضبط المنطقة الزمنية
date_default_timezone_set('Africa/Cairo');

// الحماية (المعلم والأدمن فقط)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php"); exit();
}

$student_id = $_GET['student_id'] ?? null;
$exam_id = $_GET['exam_id'] ?? null;

if (!$student_id || !$exam_id) die("بيانات ناقصة!");

// 1. جلب بيانات الطالب والامتحان
$user_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$user_stmt->execute([$student_id]);
$student_name = $user_stmt->fetchColumn();

$exam_stmt = $pdo->prepare("SELECT exam_title FROM exams WHERE id = ?");
$exam_stmt->execute([$exam_id]);
$exam_title = $exam_stmt->fetchColumn();

// 2. جلب تفاصيل إجابات الطالب من جدول student_answers اللي عملناه
$stmt = $pdo->prepare("
    SELECT q.*, sa.student_answer, sa.is_correct 
    FROM questions q
    JOIN student_answers sa ON q.id = sa.question_id
    WHERE sa.student_id = ? AND sa.exam_id = ?
");
$stmt->execute([$student_id, $exam_id]);
$answers = $stmt->fetchAll();

// 3. جلب النتيجة الإجمالية من جدول exam_results
$res_stmt = $pdo->prepare("SELECT * FROM exam_results WHERE student_id = ? AND exam_id = ? ORDER BY id DESC LIMIT 1");
$res_stmt->execute([$student_id, $exam_id]);
$result = $res_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الطالب: <?= htmlspecialchars($student_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 p-6">

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-indigo-950"><?= htmlspecialchars($student_name) ?></h2>
            <p class="text-slate-400 font-bold text-xs mt-1">تقرير أداء في: <?= htmlspecialchars($exam_title) ?></p>
        </div>
        <div class="text-left">
            <span class="text-4xl font-black text-indigo-600"><?= $result['score'] ?? 0 ?></span>
            <span class="text-slate-400 font-bold text-sm">/ <?= $result['total_marks'] ?? 0 ?></span>
        </div>
    </div>

    <div class="space-y-6">
        <?php foreach($answers as $index => $row): ?>
            <div class="bg-white p-6 rounded-[2rem] border-2 <?= $row['is_correct'] ? 'border-emerald-100' : 'border-rose-100' ?> relative overflow-hidden">
                <div class="absolute top-4 left-4">
                    <?php if($row['is_correct']): ?>
                        <span class="bg-emerald-100 text-emerald-600 px-3 py-1 rounded-full text-[10px] font-black uppercase">صحيحة ✓</span>
                    <?php else: ?>
                        <span class="bg-rose-100 text-rose-600 px-3 py-1 rounded-full text-[10px] font-black uppercase">خاطئة ✗</span>
                    <?php endif; ?>
                </div>

                <p class="text-[10px] text-slate-400 font-black mb-2 uppercase italic">سؤال رقم <?= $index + 1 ?></p>
                <h4 class="font-bold text-indigo-950 mb-6 max-w-[85%]"><?= htmlspecialchars($row['question_text']) ?></h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="p-4 rounded-2xl border <?= $row['is_correct'] ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' ?>">
                        <p class="text-[9px] font-black opacity-60 mb-1 uppercase tracking-widest">إجابة الطالب:</p>
                        <p class="font-bold text-sm"><?= htmlspecialchars($row['option_'.strtolower($row['student_answer'])]) ?></p>
                    </div>

                    <?php if(!$row['is_correct']): ?>
                    <div class="p-4 rounded-2xl border bg-indigo-50 border-indigo-200 text-indigo-700">
                        <p class="text-[9px] font-black opacity-60 mb-1 uppercase tracking-widest">الإجابة الصحيحة:</p>
                        <p class="font-bold text-sm"><?= htmlspecialchars($row['option_'.strtolower($row['correct_option'])]) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4 pt-4 border-t border-slate-50 flex justify-between items-center text-[10px] font-black text-slate-400">
                    <span>درجة السؤال المخصصة: <?= $row['question_mark'] ?></span>
                    <span>معرف السؤال: #<?= $row['id'] ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-10 flex justify-center">
        <a href="exam_reports.php?exam_id=<?= $exam_id ?>" class="bg-slate-900 text-white px-10 py-4 rounded-2xl font-black shadow-xl hover:bg-indigo-600 transition-all">العودة لجدول النتائج</a>
    </div>
</div>

</body>
</html>