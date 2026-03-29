<?php 
require_once '../config/db.php';
session_start();

// 1. حماية وتحديد الصلاحيات
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php"); exit();
}

$course_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

// التأكد من اشتراك الطالب
$check = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
$check->execute([$user_id, $course_id]);
if (!$check->fetch()) { die("عذراً، أنت غير مشترك في هذه المادة."); }

// 2. استعلام الإحصائيات الحقيقي (لتبويب التقارير والهيدر)
$stmt_stats = $pdo->prepare("
    SELECT 
        c.course_name,
        (SELECT COUNT(*) FROM lessons WHERE course_id = ?) as total_lessons,
        (SELECT COUNT(*) FROM exams WHERE course_id = ?) as total_exams,
        (SELECT COUNT(*) FROM assignments WHERE course_id = ?) as total_assignments,
        (SELECT COUNT(*) FROM enrollments WHERE course_id = ?) as total_students,
        (SELECT SUM(score) FROM exam_results WHERE student_id = ? AND course_id = ?) as total_points
    FROM courses c WHERE c.id = ?
");
$stmt_stats->execute([$course_id, $course_id, $course_id, $course_id, $user_id, $course_id, $course_id]);
$data = $stmt_stats->fetch();

include '../includes/header.php';
?>

<style>
    .tab-btn.active { background: #4f46e5 !important; color: white !important; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4); transform: translateY(-2px); }
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.4s ease; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<main class="lg:mr-72 p-6 md:p-10 min-h-screen">
    
    <header class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 mb-8 mt-16 lg:mt-0">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <span class="bg-indigo-50 text-indigo-600 px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">مادتك الحالية:</span>
                <h2 class="text-3xl font-black text-indigo-950 mt-2"><?= htmlspecialchars($data['course_name']) ?></h2>
            </div>
            <div class="bg-indigo-600 px-6 py-3 rounded-2xl text-center text-white shadow-lg shadow-indigo-100">
                <p class="text-[10px] opacity-80 font-bold uppercase">مجموع نقاطك</p>
                <p class="text-xl font-black"><?= $data['total_points'] ?? 0 ?> ⭐</p>
            </div>
        </div>
    </header>

    <nav class="flex flex-wrap gap-2 mb-8 overflow-x-auto pb-4 no-scrollbar">
        <button onclick="openTab(event, 'lessons')" class="tab-btn active px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">📖 الدروس (<?= $data['total_lessons'] ?>)</button>
        <button onclick="openTab(event, 'exams')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">📝 الامتحانات (<?= $data['total_exams'] ?>)</button>
        <button onclick="openTab(event, 'assignments')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">📑 الواجبات (<?= $data['total_assignments'] ?>)</button>
        <button onclick="openTab(event, 'students')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">👥 الزملاء</button>
        <button onclick="openTab(event, 'chat')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">💬 الشات</button>
        <button onclick="openTab(event, 'reports')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">📊 تقاريري</button>
        <button onclick="openTab(event, 'leaderboard')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">🏆 لوحة الشرف</button>
    </nav>

    <div id="lessons" class="tab-content active">
        <div class="grid grid-cols-1 gap-3">
            <?php
            $lessons = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY id ASC");
            $lessons->execute([$course_id]);
            while($l = $lessons->fetch()): ?>
                <div class="bg-white p-5 rounded-3xl border border-slate-50 flex justify-between items-center group">
                    <span class="font-bold text-indigo-950">🎥 <?= htmlspecialchars($l['lesson_title']) ?></span>
                    <a href="watch_lesson.php?id=<?= $l['id'] ?>" class="bg-indigo-50 text-indigo-600 px-5 py-2 rounded-xl text-xs font-black">فتح الفيديو</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="exams" class="tab-content">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php
            $exams = $pdo->prepare("SELECT * FROM exams WHERE course_id = ?");
            $exams->execute([$course_id]);
            while($ex = $exams->fetch()): ?>
                <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 flex justify-between items-center">
                    <div>
                        <h5 class="font-black text-indigo-950"><?= htmlspecialchars($ex['exam_title']) ?></h5>
                        <p class="text-[10px] text-slate-400 font-bold italic"><?= $ex['duration_minutes'] ?> دقيقة</p>
                    </div>
                    <a href="start_exam.php?id=<?= $ex['id'] ?>" class="bg-red-50 text-red-600 px-6 py-3 rounded-2xl font-black text-xs">بدء الاختبار</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <div id="assignments" class="tab-content">
    <?php
    // جلب الواجبات الخاصة بالمادة
    $assigns = $pdo->prepare("SELECT * FROM assignments WHERE course_id = ?");
    $assigns->execute([$course_id]);
    
    while($as = $assigns->fetch()): 
        // 1. فحص حالة التسليم لهذا الطالب بالتحديد
        $sub_check = $pdo->prepare("SELECT grade FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
        $sub_check->execute([$as['id'], $_SESSION['user_id']]);
        $submission = $sub_check->fetch();
        
        // 2. فحص هل موعد التسليم انتهى؟
        $is_expired = (strtotime($as['due_date']) < time());
    ?>
        <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 mb-4 shadow-sm">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <p class="font-black text-indigo-950 text-lg"><?= htmlspecialchars($as['title']) ?></p>
                        
                        <?php if($submission): ?>
                            <span class="bg-emerald-50 text-emerald-600 text-[9px] font-black px-3 py-1 rounded-full border border-emerald-100">تم التسليم ✓</span>
                        <?php else: ?>
                            <span class="bg-rose-50 text-rose-600 text-[9px] font-black px-3 py-1 rounded-full border border-rose-100">لم يتم التسليم</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-4">
                        <p class="text-[11px] <?= $is_expired && !$submission ? 'text-rose-500' : 'text-slate-400' ?> font-bold">
                            <span class="opacity-70">الموعد:</span> 
                            <?= date('Y-m-d H:i', strtotime($as['due_date'])) ?>
                        </p>

                        <?php if($submission): ?>
                            <p class="text-[11px] font-black">
                                <span class="text-slate-400">النتيجة:</span>
                                <?php if($submission['grade'] !== null): ?>
                                    <span class="text-indigo-600"><?= $submission['grade'] ?> / <?= $as['max_mark'] ?></span>
                                <?php else: ?>
                                    <span class="text-amber-500">جاري التصحيح...</span>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <?php if(!$submission && !$is_expired): ?>
                        <a href="submit_assignment.php?id=<?= $as['id'] ?>" class="bg-amber-500 text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-amber-100">رفع الملف</a>
                    <?php elseif($submission): ?>
                        <div class="bg-slate-50 text-slate-400 px-6 py-3 rounded-2xl text-xs font-black border border-slate-100">تم الإرسال</div>
                    <?php else: ?>
                        <div class="bg-rose-50 text-rose-400 px-6 py-3 rounded-2xl text-xs font-black border border-rose-100 italic">انتهى الوقت</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>
    
    <?php if ($assigns->rowCount() == 0): ?>
        <p class="text-center text-slate-400 font-bold py-10">لا توجد واجبات مضافة لهذه المادة حالياً.</p>
    <?php endif; ?>
</div>
    <div id="students" class="tab-content">
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
        <?php
        // استعلام لجلب أسماء وصور الزملاء المشتركين في هذه المادة فقط
        $st_stmt = $pdo->prepare("
            SELECT u.full_name, u.role 
            FROM enrollments e 
            JOIN users u ON e.student_id = u.id 
            WHERE e.course_id = ? 
            AND u.id != ?  -- عشان الطالب ميشوفش نفسه وسط الزملاء
        ");
        $st_stmt->execute([$course_id, $user_id]);
        $classmates = $st_stmt->fetchAll();

        if (empty($classmates)): ?>
            <div class="col-span-full py-10 text-center text-slate-400 font-bold italic">
                لا يوجد زملاء مسجلين في هذه المادة حالياً.
            </div>
        <?php else: 
            foreach ($classmates as $mate): ?>
                <div class="bg-white p-6 rounded-[2rem] border border-slate-50 text-center shadow-sm hover:shadow-md transition-all group">
                    <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full mx-auto mb-4 flex items-center justify-center text-xl font-black group-hover:scale-110 transition-transform">
                        <?= mb_substr($mate['full_name'], 0, 1, 'utf-8') ?>
                    </div>
                    <p class="text-xs font-black text-indigo-950 truncate"><?= htmlspecialchars($mate['full_name']) ?></p>
                    <span class="inline-block mt-2 px-3 py-1 bg-slate-100 text-[9px] text-slate-400 rounded-full font-bold uppercase">طالب</span>
                </div>
            <?php endforeach; 
        endif; ?>
    </div>
 </div>






    <div id="chat" class="tab-content">
        <div class="bg-white rounded-[3rem] border border-slate-100 p-6 h-[500px] flex flex-col shadow-inner">
            <div id="chatBox" class="flex-1 overflow-y-auto space-y-4 p-4 bg-slate-50 rounded-3xl mb-4">
                <?php
                $msgs = $pdo->prepare("SELECT m.*, u.full_name, u.role FROM messages m JOIN users u ON m.user_id = u.id WHERE m.course_id = ? ORDER BY m.created_at ASC LIMIT 20");
                $msgs->execute([$course_id]);
                while($m = $msgs->fetch()): 
                    $is_me = ($m['user_id'] == $user_id);
                ?>
                    <div class="<?= $is_me ? 'ml-auto bg-indigo-600 text-white' : 'mr-auto bg-white border border-slate-100' ?> p-4 rounded-2xl max-w-sm shadow-sm">
                        <p class="text-[9px] font-black <?= $is_me ? 'text-indigo-200' : 'text-indigo-600' ?> mb-1"><?= htmlspecialchars($m['full_name']) ?> (<?= $m['role'] ?>)</p>
                        <p class="text-sm font-medium"><?= htmlspecialchars($m['message_text']) ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="flex gap-2">
                <input type="text" id="msgInput" class="flex-1 bg-slate-100 p-4 rounded-2xl outline-none" placeholder="اكتب رسالتك...">
                <button class="bg-indigo-600 text-white px-8 rounded-2xl font-black">إرسال</button>
            </div>
        </div>
    </div>
    <div id="reports" class="tab-content">
    <?php
    // --- 1. حساب إحصائيات الامتحانات ---
    $total_exams_stmt = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE course_id = ?");
    $total_exams_stmt->execute([$course_id]);
    $total_exams_count = $total_exams_stmt->fetchColumn() ?: 0;

    $done_exams_stmt = $pdo->prepare("SELECT COUNT(DISTINCT exam_id) FROM exam_results WHERE student_id = ? AND exam_id IN (SELECT id FROM exams WHERE course_id = ?)");
    $done_exams_stmt->execute([$_SESSION['user_id'], $course_id]);
    $done_exams_count = $done_exams_stmt->fetchColumn() ?: 0;

    // --- 2. حساب إحصائيات الواجبات ---
    $total_assign_stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE course_id = ?");
    $total_assign_stmt->execute([$course_id]);
    $total_assign_count = $total_assign_stmt->fetchColumn() ?: 0;

    $done_assign_stmt = $pdo->prepare("SELECT COUNT(*) FROM assignment_submissions WHERE student_id = ? AND assignment_id IN (SELECT id FROM assignments WHERE course_id = ?)");
    $done_assign_stmt->execute([$_SESSION['user_id'], $course_id]);
    $done_assign_count = $done_assign_stmt->fetchColumn() ?: 0;

    // --- 3. حساب ترتيب الطالب في المادة ---
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
        if($r['student_id'] == $_SESSION['user_id']) { $my_rank = $index + 1; break; }
    }

    // --- 4. جلب وقت المذاكرة المسجل ---
    $timer_stmt = $pdo->prepare("SELECT total_seconds FROM course_timer WHERE student_id = ? AND course_id = ?");
    $timer_stmt->execute([$_SESSION['user_id'], $course_id]);
    $seconds = $timer_stmt->fetchColumn() ?: 0;
    $hours = floor($seconds / 3600);
    $mins = floor(($seconds % 3600) / 60);

    // --- 5. جلب الواجبات التي لم تُسلم بعد ---
    $pending_assigns = $pdo->prepare("
        SELECT title, due_date FROM assignments 
        WHERE course_id = ? AND id NOT IN (SELECT assignment_id FROM assignment_submissions WHERE student_id = ?)
        AND due_date > NOW() LIMIT 3
    ");
    $pending_assigns->execute([$course_id, $_SESSION['user_id']]);
    $missed_list = $pending_assigns->fetchAll();

    // حساب نسبة الإنجاز الكلية
    $total_tasks = $total_exams_count + $total_assign_count;
    $done_tasks = $done_exams_count + $done_assign_count;
    $progress = ($total_tasks > 0) ? round(($done_tasks / $total_tasks) * 100) : 0;
    ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm text-center">
            <p class="text-slate-400 font-black text-[9px] uppercase tracking-widest">الامتحانات</p>
            <h4 class="text-3xl font-black text-indigo-600 mt-2"><?= $done_exams_count ?> / <?= $total_exams_count ?></h4>
        </div>

        <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm text-center">
            <p class="text-slate-400 font-black text-[9px] uppercase tracking-widest">الواجبات</p>
            <h4 class="text-3xl font-black text-amber-500 mt-2"><?= $done_assign_count ?> / <?= $total_assign_count ?></h4>
        </div>

        <div class="bg-indigo-600 p-6 rounded-[2.5rem] shadow-xl text-center text-white lg:col-span-2">
            <p class="text-[9px] font-black uppercase opacity-60">معدل الإنجاز العام</p>
            <h4 class="text-3xl font-black mt-1"><?= $progress ?>%</h4>
            <div class="w-full bg-indigo-400 h-1 rounded-full mt-3 overflow-hidden">
                <div class="bg-white h-full transition-all duration-1000" style="width: <?= $progress ?>%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm flex items-center gap-6 relative overflow-hidden">
                <div class="text-4xl bg-emerald-50 p-4 rounded-2xl">⏱️</div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase">وقت التفاعل مع المادة</p>
                    <h4 class="text-2xl font-black text-indigo-950 mt-1">
                        <?php echo ($hours > 0) ? "$hours ساعة و $mins دقيقة" : "$mins دقيقة"; ?>
                    </h4>
                </div>
            </div>

            <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <div class="text-4xl bg-amber-50 p-4 rounded-2xl">🏆</div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase">ترتيبك الحالي</p>
                        <h4 class="text-2xl font-black text-indigo-950 mt-1">المركز #<?= $my_rank ?: '--' ?></h4>
                    </div>
                </div>
                <p class="text-xs font-black text-indigo-600 italic">بناءً على مجموع الدرجات</p>
            </div>
        </div>

        <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm">
            <h3 class="text-lg font-black text-indigo-950 mb-6 flex items-center gap-2">
                <span class="w-2 h-2 bg-rose-500 rounded-full animate-pulse"></span> هام جداً
            </h3>
            <?php if(count($missed_list) > 0): ?>
                <div class="space-y-4">
                    <?php foreach($missed_list as $m): ?>
                        <div class="p-4 bg-rose-50 rounded-2xl border border-rose-100">
                            <p class="text-xs font-black text-rose-700"><?= htmlspecialchars($m['title']) ?></p>
                            <p class="text-[9px] text-rose-400 font-bold mt-1 italic text-left">Deadline: <?= $m['due_date'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-10">
                    <p class="text-slate-300 text-5xl mb-4">✨</p>
                    <p class="text-slate-400 font-bold text-xs">كل المهام مكتملة!</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="mt-8 p-6 bg-slate-900 rounded-[2.5rem] text-center">
        <p class="text-indigo-400 font-black text-[10px] uppercase tracking-widest mb-2">نصيحة المنصة الذكية 💡</p>
        <p class="text-slate-300 text-sm font-bold italic">
            "<?= $progress < 50 ? 'لاحظنا تأخرك قليلاً، خصص 30 دقيقة يومياً لرفع معدل إنجازك.' : 'أداءك مبهر جداً، استمر في الحفاظ على هذا المستوى للوصول للمركز الأول!' ?>"
        </p>
    </div>
</div>
    
    
    <div id="leaderboard" class="tab-content">
        <div class="bg-indigo-950 rounded-[3rem] p-10 text-white shadow-2xl">
            <h3 class="text-2xl font-black mb-8 text-center text-indigo-300">⭐ المتصدرون في <?= htmlspecialchars($data['course_name']) ?> ⭐</h3>
            <div class="space-y-4">
                <?php
                $top_students = $pdo->prepare("
                    SELECT u.full_name, SUM(er.score) as total 
                    FROM exam_results er 
                    JOIN users u ON er.student_id = u.id 
                    WHERE er.course_id = ? 
                    GROUP BY er.student_id 
                    ORDER BY total DESC LIMIT 5");
                $top_students->execute([$course_id]);
                $rank = 1;
                while($top = $top_students->fetch()): ?>
                    <div class="flex items-center justify-between bg-white/5 p-5 rounded-2xl border border-white/5">
                        <div class="flex items-center gap-4">
                            <span class="text-xl font-bold"><?= $rank++ ?>#</span>
                            <span class="font-black text-lg"><?= htmlspecialchars($top['full_name']) ?></span>
                        </div>
                        <span class="bg-indigo-500 text-white px-4 py-1 rounded-full font-black text-xs"><?= $top['total'] ?> نقطة</span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

</main>

<script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) { tabcontent[i].style.display = "none"; tabcontent[i].classList.remove("active"); }
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) { tablinks[i].classList.remove("active"); }
        document.getElementById(tabName).style.display = "block";
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }
</script>
<script>
// تحديث الوقت كل 60 ثانية (دقيقة)
setInterval(function() {
    fetch('update_timer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'course_id=<?= $course_id ?>'
    });
}, 60000); 
</script>