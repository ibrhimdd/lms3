<?php 
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$exam_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

// 1. جلب بيانات الامتحان والتحقق من المواعيد
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();
date_default_timezone_set('Africa/Cairo');
if (!$exam) die("الامتحان غير موجود!");

$now = new DateTime();
$start_time = new DateTime($exam['start_time']);
$end_time = new DateTime($exam['end_time']);

if ($now < $start_time) die("عذراً، الامتحان لم يبدأ بعد. يبدأ في: " . $exam['start_time']);
if ($now > $end_time) die("عذراً، انتهى وقت صلاحية الدخول للامتحان.");

// 2. التحقق من عدد المحاولات (نفترض وجود جدول exam_attempts)
$check_attempts = $pdo->prepare("SELECT COUNT(*) FROM exam_results WHERE student_id = ? AND exam_id = ?");
$check_attempts->execute([$user_id, $exam_id]);
$attempts_done = $check_attempts->fetchColumn();

if ($attempts_done >= $exam['attempts_count'] && $exam['attempts_count'] != 999) {
    die("لقد استنفدت جميع محاولاتك لهذا الامتحان.");
}

// 3. جلب الأسئلة
$q_stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY RAND()"); // RAND لضمان عدم الغش
$q_stmt->execute([$exam_id]);
$questions = $q_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بدء الاختبار: <?= htmlspecialchars($exam['exam_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Almarai', sans-serif; user-select: none; } /* منع النسخ */
        .timer-fixed { position: sticky; top: 0; z-index: 50; }
    </style>
</head>
<body class="bg-slate-50 pb-20">

<div class="timer-fixed bg-white border-b p-4 shadow-sm flex justify-between items-center px-6 md:px-20">
    <h2 class="font-black text-indigo-950"><?= htmlspecialchars($exam['exam_title']) ?></h2>
    <div id="timer" class="bg-rose-600 text-white px-6 py-2 rounded-full font-black text-xl tabular-nums">
        --:--
    </div>
</div>

<main class="max-w-4xl mx-auto mt-10 p-4">
    <form id="examForm" action="submit_exam.php" method="POST">
        <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
        
        <?php foreach($questions as $index => $q): ?>
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 mb-8 animate__animated animate__fadeInUp">
                <div class="flex justify-between items-start mb-6">
                    <span class="bg-indigo-50 text-indigo-600 px-4 py-1 rounded-full text-[10px] font-black italic">سؤال <?= $index + 1 ?></span>
                    <span class="text-slate-400 font-bold text-[10px]"><?= $q['question_mark'] ?> درجة</span>
                </div>
                
                <p class="text-lg font-black text-indigo-950 mb-8 leading-relaxed">
                    <?= htmlspecialchars($q['question_text']) ?>
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach(['A', 'B', 'C', 'D'] as $opt): ?>
                        <label class="relative flex items-center p-5 border-2 border-slate-50 rounded-2xl cursor-pointer hover:bg-indigo-50 hover:border-indigo-200 transition-all group">
                            <input type="radio" name="answer[<?= $q['id'] ?>]" value="<?= $opt ?>" required class="w-5 h-5 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                            <span class="mr-4 font-bold text-slate-700 group-hover:text-indigo-950">
                                <?= htmlspecialchars($q['option_'.strtolower($opt)]) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="w-full bg-indigo-600 text-white p-6 rounded-[2rem] font-black text-xl shadow-xl shadow-indigo-100 hover:scale-[1.02] transition-all">
            تسليم الإجابات وإنهاء الامتحان ✅
        </button>
    </form>
</main>

<script>
    // نظام العداد التنازلي
    let timeLeft = <?= $exam['duration_minutes'] * 60 ?>;
    const timerDisplay = document.getElementById('timer');
    const form = document.getElementById('examForm');

    const countdown = setInterval(() => {
        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;
        
        timerDisplay.innerHTML = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        
        if (timeLeft <= 0) {
            clearInterval(countdown);
            alert("انتهى الوقت! سيتم تسليم إجاباتك تلقائياً.");
            form.submit();
        }
        
        if (timeLeft <= 60) { timerDisplay.classList.add('animate-pulse'); }
        timeLeft--;
    }, 1000);

    // منع الخروج من الصفحة بالخطأ
    window.onbeforeunload = function() {
        return "هل أنت متأكد؟ سيتم فقدان تقدمك!";
    };
    
    // إزالة التحذير عند الإرسال الفعلي
    form.onsubmit = () => { window.onbeforeunload = null; };
</script>

</body>
</html>