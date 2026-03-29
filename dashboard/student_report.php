<?php 
require_once '../config/db.php';
session_start();

// 1. حماية الصفحة
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php"); exit();
}

$student_id = $_GET['student_id'] ?? null;
$course_id = $_GET['course_id'] ?? null;

if (!$student_id || !$course_id) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>بيانات ناقصة في الرابط!</h2></div>");
}

// 2. جلب بيانات الطالب الأساسية
$user_stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$user_stmt->execute([$student_id]);
$student = $user_stmt->fetch();

// 3. حساب ترتيب الطالب في المادة
$rank_stmt = $pdo->prepare("
    SELECT student_id, SUM(score) as total 
    FROM exam_results 
    WHERE exam_id IN (SELECT id FROM exams WHERE course_id = ?) 
    GROUP BY student_id ORDER BY total DESC
");
$rank_stmt->execute([$course_id]);
$ranks = $rank_stmt->fetchAll(PDO::FETCH_ASSOC);
$my_rank = 0;
foreach($ranks as $index => $r) {
    if($r['student_id'] == $student_id) { $my_rank = $index + 1; break; }
}

// 4. إحصائيات الوقت (course_timer)
$timer_stmt = $pdo->prepare("SELECT total_seconds FROM course_timer WHERE student_id = ? AND course_id = ?");
$timer_stmt->execute([$student_id, $course_id]);
$seconds = $timer_stmt->fetchColumn() ?: 0;
$hours = floor($seconds / 3600);
$mins = floor(($seconds % 3600) / 60);

// 5. جلب كافة الامتحانات وحالة الطالب
$all_exams_stmt = $pdo->prepare("
    SELECT e.id, e.exam_title, er.score, er.submitted_at
    FROM exams e
    LEFT JOIN exam_results er ON e.id = er.exam_id AND er.student_id = ?
    WHERE e.course_id = ?
    ORDER BY e.id ASC
");
$all_exams_stmt->execute([$student_id, $course_id]);
$exams_status = $all_exams_stmt->fetchAll();

// تحضير بيانات الرسم البياني
$chart_labels = []; $chart_data = [];
foreach($exams_status as $ex) {
    if($ex['submitted_at']) {
        $chart_labels[] = $ex['exam_title'];
        $chart_data[] = $ex['score'];
    }
}

// 6. جلب كافة الواجبات وحالة الطالب
$all_assigns_stmt = $pdo->prepare("
    SELECT a.id, a.title, s.grade, s.submitted_at
    FROM assignments a
    LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
    WHERE a.course_id = ?
    ORDER BY a.id ASC
");
$all_assigns_stmt->execute([$student_id, $course_id]);
$assigns_status = $all_assigns_stmt->fetchAll();

// --- حساب نسب الإنجاز والتحليل الذكي ---
$exams_done = count($chart_data);
$total_exams = count($exams_status);
$assigns_done = 0;
foreach($assigns_status as $as) { if($as['submitted_at']) $assigns_done++; }
$total_assigns = count($assigns_status);

$total_items = $total_exams + $total_assigns;
$done_items = $exams_done + $assigns_done;
$progress = ($total_items > 0) ? round(($done_items / $total_items) * 100) : 0;

// نظام الرسالة الذكية
$analysis_text = ""; $analysis_color = ""; $analysis_icon = "";
if ($progress < 35) {
    $analysis_text = "⚠️ طالب منقطع: الطالب متأخر جداً في تسليم المهام وحل الامتحانات، يحتاج متابعة فورية.";
    $analysis_color = "bg-rose-50 text-rose-700 border-rose-200"; $analysis_icon = "🚨";
} elseif ($hours < 1 && $progress > 60) {
    $analysis_text = "🔍 تنبيه: الطالب أنجز مهام كثيرة في وقت قصير جداً، يرجى التأكد من نزاهة الحل.";
    $analysis_color = "bg-amber-50 text-amber-700 border-amber-200"; $analysis_icon = "🧐";
} elseif ($hours > 5 && $progress < 50) {
    $analysis_text = "💡 يحتاج توجيه: الطالب يقضي وقتاً طويلاً لكن إنتاجيته ضعيفة، قد يحتاج لشرح إضافي.";
    $analysis_color = "bg-indigo-50 text-indigo-700 border-indigo-200"; $analysis_icon = "📘";
} else {
    $analysis_text = "✅ أداء منتظم: الطالب يسير بمعدل جيد جداً ومستقر في المادة.";
    $analysis_color = "bg-emerald-50 text-emerald-700 border-emerald-200"; $analysis_icon = "🌟";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تحليل أداء | <?= htmlspecialchars($student['full_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 p-4 md:p-10 text-right">

<div class="max-w-6xl mx-auto">
    <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 flex flex-col md:flex-row justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-black text-indigo-950"><?= htmlspecialchars($student['full_name']) ?></h1>
            <p class="text-slate-400 font-bold text-xs mt-1 uppercase tracking-widest">إحصائيات المعلم الذكية</p>
        </div>
        <div class="flex gap-4 mt-6 md:mt-0">
            <div class="bg-amber-500 text-white px-6 py-4 rounded-3xl text-center shadow-lg shadow-amber-100">
                <p class="text-[9px] font-black opacity-80 uppercase">الترتيب</p>
                <p class="text-3xl font-black">#<?= $my_rank ?: '--' ?></p>
            </div>
            <div class="bg-indigo-950 text-white px-6 py-4 rounded-3xl text-center shadow-lg shadow-indigo-100">
                <p class="text-[9px] font-black opacity-80 uppercase">وقت المذاكرة</p>
                <p class="text-xl font-black mt-1"><?= $hours ?>س <?= $mins ?>د</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-indigo-600 p-8 rounded-[2.5rem] shadow-xl text-white text-center flex flex-col justify-center">
            <p class="text-xs font-black opacity-60">نسبة الإنجاز الكلية</p>
            <h2 class="text-6xl font-black my-4"><?= $progress ?>%</h2>
            <div class="w-full bg-white/20 h-2 rounded-full overflow-hidden">
                <div class="bg-white h-full" style="width: <?= $progress ?>%"></div>
            </div>
        </div>
        <div class="lg:col-span-2 p-8 rounded-[2.5rem] border-2 <?= $analysis_color ?> flex items-center gap-6 shadow-sm">
            <span class="text-5xl"><?= $analysis_icon ?></span>
            <div>
                <h4 class="text-[10px] font-black uppercase tracking-widest opacity-60">التحليل التلقائي للنظام</h4>
                <p class="text-lg font-black mt-2 leading-relaxed"><?= $analysis_text ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 mb-8">
        <h3 class="text-sm font-black text-slate-400 mb-6 uppercase tracking-widest">مخطط تطور الدرجات (الامتحانات)</h3>
        <canvas id="performanceChart" height="100"></canvas>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100">
            <h3 class="text-lg font-black text-indigo-950 mb-6">📝 سجل الامتحانات (<?= $exams_done ?>/<?= $total_exams ?>)</h3>
            <div class="space-y-4">
                <?php foreach($exams_status as $ex): ?>
                    <div class="p-5 rounded-2xl border <?= $ex['submitted_at'] ? 'bg-white border-slate-100' : 'bg-rose-50 border-rose-100 animate-pulse' ?> flex justify-between items-center">
                        <div>
                            <p class="text-sm font-black text-slate-800"><?= htmlspecialchars($ex['exam_title']) ?></p>
                            <p class="text-[9px] font-bold <?= $ex['submitted_at'] ? 'text-emerald-600' : 'text-rose-500' ?>">
                                <?= $ex['submitted_at'] ? 'تم الحل بنجاح' : 'لم يتم الدخول للامتحان ⚠️' ?>
                            </p>
                        </div>
                        <div class="text-left font-black text-indigo-600 text-2xl"><?= $ex['score'] ?? '--' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100">
            <h3 class="text-lg font-black text-indigo-950 mb-6">📁 سجل الواجبات (<?= $assigns_done ?>/<?= $total_assigns ?>)</h3>
            <div class="space-y-4">
                <?php foreach($assigns_status as $as): ?>
                    <div class="p-5 rounded-2xl border <?= $as['submitted_at'] ? 'bg-white border-slate-100' : 'bg-amber-50 border-amber-100' ?> flex justify-between items-center">
                        <div>
                            <p class="text-sm font-black text-slate-800"><?= htmlspecialchars($as['title']) ?></p>
                            <p class="text-[9px] font-bold <?= $as['submitted_at'] ? 'text-emerald-600' : 'text-amber-600' ?>">
                                <?= $as['submitted_at'] ? 'تم التسليم' : 'لم يتم رفع الملف بعد ❌' ?>
                            </p>
                        </div>
                        <div class="text-left font-black text-emerald-600 text-2xl"><?= $as['grade'] ?: ($as['submitted_at'] ? 'قيد التصحيح' : '0') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="mt-12 text-center">
        <a href="manage_students.php?course_id=<?= $course_id ?>" class="bg-slate-900 text-white px-12 py-4 rounded-2xl font-black text-xs hover:bg-indigo-600 transition-all shadow-xl">العودة لقائمة الطلاب</a>
    </div>
</div>

<script>
const ctx = document.getElementById('performanceChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'درجة الطالب',
            data: <?= json_encode($chart_data) ?>,
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            fill: true, tension: 0.4, borderWidth: 4, pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
    }
});
</script>
</body>
</html>