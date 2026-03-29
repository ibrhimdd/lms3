<?php 
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php"); exit();
}

$user_id = $_SESSION['user_id'];

// 1. إحصائيات سريعة
// عدد الامتحانات اللي دخلها
$exam_count = $pdo->prepare("SELECT COUNT(DISTINCT exam_id) FROM exam_results WHERE student_id = ?");
$exam_count->execute([$user_id]);
$total_exams = $exam_count->fetchColumn();

// عدد الواجبات اللي سلمها
$sub_count = $pdo->prepare("SELECT COUNT(*) FROM assignment_submissions WHERE student_id = ?");
$sub_count->execute([$user_id]);
$total_subs = $sub_count->fetchColumn();

// متوسط الدرجات (نسبة مئوية)
$avg_score = $pdo->prepare("SELECT AVG((score/total_marks)*100) FROM exam_results WHERE student_id = ?");
$avg_score->execute([$user_id]);
$performance = round($avg_score->fetchColumn() ?? 0);

// 2. جلب آخر النتائج
$recent_results = $pdo->prepare("
    SELECT er.*, e.exam_title 
    FROM exam_results er 
    JOIN exams e ON er.exam_id = e.id 
    WHERE er.student_id = ? 
    ORDER BY er.submitted_at DESC LIMIT 3
");
$recent_results->execute([$user_id]);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم | إبراهيم الخولي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 p-6">

<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-black text-indigo-950 mb-8 italic">مرحباً بك، <?= $_SESSION['full_name'] ?> 👋</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-indigo-50 rounded-full"></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">الامتحانات المكتملة</p>
            <h4 class="text-4xl font-black text-indigo-600 mt-2"><?= $total_exams ?></h4>
        </div>
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">واجبات تم تسليمها</p>
            <h4 class="text-4xl font-black text-amber-500 mt-2"><?= $total_subs ?></h4>
        </div>
        <div class="bg-indigo-600 p-8 rounded-[2.5rem] shadow-xl text-white">
            <p class="text-[10px] font-black uppercase opacity-60">مستوى الأداء العام</p>
            <h4 class="text-4xl font-black mt-2"><?= $performance ?>%</h4>
            <div class="w-full bg-indigo-400 h-1.5 rounded-full mt-4 overflow-hidden">
                <div class="bg-white h-full" style="width: <?= $performance ?>%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100">
            <h3 class="text-xl font-black text-indigo-950 mb-6">آخر نتائجك 🏆</h3>
            <div class="space-y-4">
                <?php while($res = $recent_results->fetch()): ?>
                    <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl">
                        <span class="font-bold text-sm text-slate-700"><?= $res['exam_title'] ?></span>
                        <span class="font-black text-indigo-600"><?= $res['score'] ?> / <?= $res['total_marks'] ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100">
            <h3 class="text-xl font-black text-indigo-950 mb-6">تنبيهات هامة 🔔</h3>
            <div class="p-5 bg-rose-50 rounded-2xl border border-rose-100 flex items-start gap-4">
                <span class="text-2xl">⚠️</span>
                <div>
                    <p class="font-black text-rose-700 text-sm">لديك واجبات لم تُسلم!</p>
                    <p class="text-[10px] text-rose-500 font-bold mt-1 text-justify">تأكد من مراجعة المواد الدراسية ورفع الملفات المطلوبة قبل انتهاء الموعد النهائي.</p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>