<?php 
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];
$exam_id = $_GET['exam_id'] ?? null;

// 1. جلب آخر نتيجة للطالب في هذا الامتحان
$stmt = $pdo->prepare("SELECT * FROM exam_results WHERE student_id = ? AND exam_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id, $exam_id]);
$result = $stmt->fetch();

if (!$result) die("لم يتم العثور على نتيجة لهذا الامتحان.");

// 2. جلب بيانات الامتحان والمادة
$exam_stmt = $pdo->prepare("SELECT e.*, c.course_name FROM exams e JOIN courses c ON e.course_id = c.id WHERE e.id = ?");
$exam_stmt->execute([$exam_id]);
$exam = $exam_stmt->fetch();

// 3. جلب تفاصيل الإجابات (السؤال + إجابة الطالب + الإجابة الصحيحة)
$ans_stmt = $pdo->prepare("
    SELECT q.*, sa.student_answer, sa.is_correct 
    FROM questions q 
    LEFT JOIN student_answers sa ON q.id = sa.question_id 
    WHERE sa.student_id = ? AND sa.exam_id = ?
");
$ans_stmt->execute([$user_id, $exam_id]);
$details = $ans_stmt->fetchAll();

// حساب النسبة المئوية
$percentage = ($result['total_marks'] > 0) ? round(($result['score'] / $result['total_marks']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نتيجتك: <?= htmlspecialchars($exam['exam_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 p-4 md:p-10">

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-[3.5rem] p-10 shadow-2xl shadow-indigo-100 border border-slate-100 text-center relative overflow-hidden mb-10">
        <div class="absolute top-0 left-0 w-full h-3 <?= $percentage >= 50 ? 'bg-emerald-500' : 'bg-rose-500' ?>"></div>
        
        <h1 class="text-slate-400 font-black text-xs uppercase tracking-widest mb-4 italic">نتيجة اختبار: <?= htmlspecialchars($exam['course_name']) ?></h1>
        <h2 class="text-3xl font-black text-indigo-950 mb-8"><?= htmlspecialchars($exam['exam_title']) ?></h2>

        <div class="inline-flex items-center justify-center w-48 h-48 rounded-full border-[12px] <?= $percentage >= 50 ? 'border-emerald-50' : 'border-rose-50' ?> mb-6 relative">
            <div class="flex flex-col">
                <span class="text-5xl font-black <?= $percentage >= 50 ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $percentage ?>%</span>
                <span class="text-[10px] text-slate-400 font-bold uppercase">الدرجة النهائية</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 max-w-sm mx-auto">
            <div class="bg-slate-50 p-4 rounded-2xl">
                <p class="text-[10px] text-slate-400 font-black mb-1">نقاطك</p>
                <p class="text-xl font-black text-indigo-950"><?= $result['score'] ?></p>
            </div>
            <div class="bg-slate-50 p-4 rounded-2xl">
                <p class="text-[10px] text-slate-400 font-black mb-1">من إجمالي</p>
                <p class="text-xl font-black text-indigo-950"><?= $result['total_marks'] ?></p>
            </div>
        </div>
    </div>

    <h3 class="text-xl font-black text-indigo-950 mb-6 mr-6">مراجعة إجاباتك 🔍</h3>
    <div class="space-y-4">
        <?php foreach($details as $d): ?>
            <div class="bg-white p-6 rounded-[2rem] border-2 <?= $d['is_correct'] ? 'border-emerald-100' : 'border-rose-100' ?> shadow-sm">
                <div class="flex justify-between mb-4">
                    <span class="text-[10px] font-black <?= $d['is_correct'] ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50' ?> px-3 py-1 rounded-full uppercase">
                        <?= $d['is_correct'] ? 'إجابة صحيحة ✓' : 'إجابة خاطئة ✗' ?>
                    </span>
                    <span class="text-slate-400 font-bold text-[10px]"><?= $d['question_mark'] ?> درجة</span>
                </div>
                
                <p class="font-bold text-indigo-950 mb-4"><?= htmlspecialchars($d['question_text']) ?></p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                    <div class="p-3 rounded-xl <?= $d['student_answer'] == $d['correct_option'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?> border">
                        <b>إجابتك:</b> <?= htmlspecialchars($d['option_'.strtolower($d['student_answer'])]) ?>
                    </div>
                    <?php if(!$d['is_correct']): ?>
                    <div class="p-3 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-100">
                        <b>الإجابة الصحيحة:</b> <?= htmlspecialchars($d['option_'.strtolower($d['correct_option'])]) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-10 flex gap-4">
        <a href="manage_content.php?id=<?= $exam['course_id'] ?>" class="flex-1 bg-indigo-600 text-white text-center p-5 rounded-2xl font-black shadow-lg">العودة للمادة</a>
        <button onclick="window.print()" class="bg-slate-900 text-white px-8 rounded-2xl font-black">طباعة النتيجة 🖨️</button>
    </div>
</div>

</body>
</html>