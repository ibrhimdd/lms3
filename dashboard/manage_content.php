<?php 
require_once '../config/db.php';
session_start();

// 1. الحماية (للمعلم والأدمن فقط)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php"); exit();
}

$course_id = $_GET['id'] ?? null;
if (!$course_id) die("المادة غير محددة!");

// 2. جلب بيانات المادة وإحصائيات التقارير الشاملة
$stmt_stats = $pdo->prepare("
    SELECT 
        c.course_name,
        (SELECT COUNT(*) FROM lessons WHERE course_id = ?) as total_lessons,
        (SELECT COUNT(*) FROM exams WHERE course_id = ?) as total_exams,
        (SELECT COUNT(*) FROM assignments WHERE course_id = ?) as total_assignments,
        (SELECT COUNT(*) FROM enrollments WHERE course_id = ?) as total_students,
        (SELECT COUNT(*) FROM course_tokens WHERE course_id = ? AND is_used = 0) as free_tokens
    FROM courses c WHERE c.id = ?
");
$stmt_stats->execute([$course_id, $course_id, $course_id, $course_id, $course_id, $course_id]);
$data = $stmt_stats->fetch();

if (!$data) die("المادة غير موجودة!");

include '../includes/header.php';
?>

<style>
    .tab-btn { transition: all 0.3s ease; border-bottom: 4px solid transparent; }
    .tab-btn.active { 
        background: #4f46e5 !important; color: white !important; 
        box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.3);
        transform: translateY(-2px);
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.4s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .no-scrollbar::-webkit-scrollbar { display: none; }
</style>

<main class="lg:mr-72 p-6 md:p-10 min-h-screen">
    
    <header class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 mb-8 mt-16 lg:mt-0">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="text-3xl font-black text-indigo-950 italic"><?= htmlspecialchars($data['course_name']) ?> 🎓</h2>
                <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">إدارة المحتوى | إبراهيم الخولي 2026</p>
            </div>
            <div class="flex gap-3">
                <a href="generate_tokens.php?id=<?= $course_id ?>" class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-black text-xs shadow-lg hover:bg-indigo-700 transition-all">🎫 توليد أكواد</a>
                <a href="courses.php" class="bg-slate-100 text-slate-500 px-6 py-3 rounded-2xl font-black text-xs hover:bg-slate-200">رجوع</a>
            </div>
        </div>
    </header>

    <nav class="flex flex-wrap gap-2 mb-8 overflow-x-auto pb-4 no-scrollbar">
        <button onclick="openTab(event, 'lessons')" class="tab-btn active px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">📖 الدروس (<?= $data['total_lessons'] ?>)</button>
        <button onclick="openTab(event, 'exams')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">📝 الامتحانات (<?= $data['total_exams'] ?>)</button>
        <button onclick="openTab(event, 'assignments')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">📑 الواجبات (<?= $data['total_assignments'] ?>)</button>
        <button onclick="openTab(event, 'students')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">👥 الطلاب (<?= $data['total_students'] ?>)</button>
        <button onclick="openTab(event, 'chat')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">💬 الشات</button>
        <button onclick="openTab(event, 'reports')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">📊 التقارير</button>
        <button onclick="openTab(event, 'leaderboard')" class="tab-btn px-6 py-4 rounded-2xl font-black text-[11px] bg-white border">🏆 لوحة الشرف</button>
    </nav>

    <div id="lessons" class="tab-content active">
        <div class="bg-indigo-600 p-8 rounded-[2.5rem] mb-6 flex justify-between items-center text-white shadow-xl shadow-indigo-100">
            <div><h3 class="font-black text-xl">المحتوى المرئي</h3><p class="text-xs opacity-80">أضف فيديوهات الدروس لطلابك</p></div>
            <a href="add_lesson.php?course_id=<?= $course_id ?>" class="bg-white text-indigo-600 px-6 py-3 rounded-xl font-black text-xs hover:scale-105 transition-all">+ إضافة درس</a>
        </div>
        <div class="space-y-3">
            <?php
            $lessons = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ?");
            $lessons->execute([$course_id]);
            while($l = $lessons->fetch()): ?>
                <div class="bg-white p-5 rounded-2xl border border-slate-50 flex justify-between items-center group hover:border-indigo-500 transition-all">
                    <span class="font-bold text-slate-700">▶️ <?= htmlspecialchars($l['lesson_title']) ?></span>
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a href="edit_lesson.php?id=<?= $l['id'] ?>" class="p-2 bg-indigo-50 text-indigo-600 rounded-lg text-xs">✏️</a>
                        <button onclick="deleteItem('lesson', <?= $l['id'] ?>)" class="p-2 bg-red-50 text-red-600 rounded-lg text-xs">🗑️</button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="exams" class="tab-content">
        <div class="bg-rose-500 p-8 rounded-[2.5rem] mb-6 flex justify-between items-center text-white">
            <div><h3 class="font-black text-xl">الاختبارات</h3><p class="text-xs opacity-80">إدارة الأسئلة والنتائج</p></div>
            <a href="create_exam.php?course_id=<?= $course_id ?>" class="bg-white text-rose-600 px-6 py-3 rounded-xl font-black text-xs">+ إنشاء امتحان</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php
            $exams = $pdo->prepare("SELECT * FROM exams WHERE course_id = ?");
            $exams->execute([$course_id]);
            while($ex = $exams->fetch()): ?>
                <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100">
                    <h5 class="font-black text-indigo-950 mb-4"><?= htmlspecialchars($ex['exam_title']) ?></h5>
                    <div class="flex gap-2">
                        <a href="add_questions.php?exam_id=<?= $ex['id'] ?>" class="flex-1 bg-indigo-50 text-indigo-600 py-3 rounded-xl font-black text-[10px] text-center">الأسئلة</a>
                        <a href="exam_reports.php?exam_id=<?= $ex['id'] ?>" class="flex-1 bg-emerald-50 text-emerald-600 py-3 rounded-xl font-black text-[10px] text-center">النتائج</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="assignments" class="tab-content">
    <div class="flex justify-between items-center mb-6">
        <h3 class="font-black text-indigo-950">المهام الدراسية (الواجبات)</h3>
        <a href="add_assignment.php?course_id=<?= $course_id ?>" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-[10px] font-black hover:bg-slate-900 transition-all shadow-lg shadow-indigo-100">+ إضافة واجب جديد</a>
    </div>
    
    <?php
    $assigns = $pdo->prepare("SELECT * FROM assignments WHERE course_id = ?");
    $assigns->execute([$course_id]);
    
    if ($assigns->rowCount() > 0):
        while($as = $assigns->fetch()): ?>
            <div class="bg-white p-6 rounded-[2rem] border border-slate-100 mb-4 flex justify-between items-center shadow-sm hover:shadow-md transition-shadow">
                <div>
                    <p class="font-black text-indigo-950 text-md"><?= htmlspecialchars($as['title']) ?></p>
                    
                    <p class="text-[10px] text-rose-500 font-bold italic mt-1">
                        آخر موعد: <?= date('Y-m-d H:i', strtotime($as['due_date'])) ?>
                    </p>
                </div>
                
                <a href="view_submissions.php?id=<?= $as['id'] ?>" class="bg-indigo-50 text-indigo-600 px-6 py-2 rounded-xl text-[10px] font-black border border-indigo-100 hover:bg-indigo-600 hover:text-white transition-all">
                    عرض تسليمات الطلاب 👥
                </a>
            </div>
        <?php endwhile; 
    else: ?>
        <div class="text-center py-10 bg-slate-50 rounded-[2rem] border-2 border-dashed border-slate-200">
            <p class="text-slate-400 font-bold text-sm italic">لا توجد واجبات مضافة بعد.</p>
        </div>
    <?php endif; ?>
</div>
<div id="students" class="tab-content">
    <div class="bg-white rounded-[2.5rem] overflow-hidden border border-slate-100 shadow-sm">
        <table class="w-full text-right">
            <tr class="bg-slate-50 text-indigo-950 font-black text-xs uppercase">
                <th class="p-5">الطالب</th>
                <th class="p-5">تاريخ التسجيل</th>
                <th class="p-5 text-center">الإجراء</th>
            </tr>
            <?php
            // تعديل الاستعلام لجلب id الطالب (u.id) لتمكين الربط
            $st_stmt = $pdo->prepare("SELECT u.id, u.full_name, e.enrolled_at FROM enrollments e JOIN users u ON e.student_id = u.id WHERE e.course_id = ?");
            $st_stmt->execute([$course_id]);
            while($student = $st_stmt->fetch()): ?>
                <tr class="border-t border-slate-50">
                    <td class="p-5 font-bold text-slate-700">
                        <?= htmlspecialchars($student['full_name']) ?>
                    </td>
                    <td class="p-5 text-xs text-slate-400">
                        <?= $student['enrolled_at'] ?>
                    </td>
                    <td class="p-5 text-center">
                        <a href="student_report.php?student_id=<?= $student['id'] ?>&course_id=<?= $course_id ?>" 
                           class="inline-block bg-indigo-50 text-indigo-600 px-4 py-2 rounded-xl text-[10px] font-black hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                            عرض التقرير الشامل 📊
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

    <div id="chat" class="tab-content">
        <div class="bg-white rounded-[3rem] border border-slate-100 p-6 h-[500px] flex flex-col">
            <div id="chatBox" class="flex-1 overflow-y-auto space-y-4 p-4 bg-slate-50 rounded-3xl mb-4">
                <?php
                $msgs = $pdo->prepare("SELECT m.*, u.full_name FROM messages m JOIN users u ON m.user_id = u.id WHERE m.course_id = ? ORDER BY m.created_at ASC LIMIT 30");
                $msgs->execute([$course_id]);
                while($m = $msgs->fetch()):
                    $is_me = ($m['user_id'] == $_SESSION['user_id']);
                ?>
                    <div class="<?= $is_me ? 'ml-auto bg-indigo-600 text-white' : 'mr-auto bg-white border' ?> p-4 rounded-2xl max-w-sm shadow-sm">
                        <p class="text-[9px] font-black <?= $is_me ? 'text-indigo-200' : 'text-indigo-600' ?> mb-1"><?= htmlspecialchars($m['full_name']) ?></p>
                        <p class="text-sm"><?= htmlspecialchars($m['message_text']) ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="flex gap-2">
                <input type="text" id="msgInput" class="flex-1 bg-slate-100 p-4 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-600" placeholder="أرسل رسالة للطلاب...">
                <button onclick="sendMessage()" class="bg-indigo-600 text-white px-8 rounded-2xl font-black">إرسال</button>
            </div>
        </div>
    </div>
    <div id="reports" class="tab-content">
    <?php
    // --- 1. إحصائيات التفاعل مع الواجبات والامتحانات ---
    // إجمالي عدد الواجبات المرفوعة في المادة دي
    $total_subs_stmt = $pdo->prepare("SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = ?)");
    $total_subs_stmt->execute([$course_id]);
    $total_submissions = $total_subs_stmt->fetchColumn() ?: 0;

    // --- 2. جلب أوائل المادة (Top 5) بناءً على مجموع درجات الامتحانات ---
    $top_students = $pdo->prepare("
        SELECT u.full_name, SUM(er.score) as total_score 
        FROM exam_results er 
        JOIN users u ON er.student_id = u.id 
        WHERE er.exam_id IN (SELECT id FROM exams WHERE course_id = ?)
        GROUP BY u.id 
        ORDER BY total_score DESC LIMIT 5
    ");
    $top_students->execute([$course_id]);
    $leaders = $top_students->fetchAll();

    // --- 3. الطلاب الأكثر تفاعلاً (بالوقت) ---
    $time_leaders = $pdo->prepare("
        SELECT u.full_name, ct.total_seconds 
        FROM course_timer ct 
        JOIN users u ON ct.student_id = u.id 
        WHERE ct.course_id = ? 
        ORDER BY ct.total_seconds DESC LIMIT 3
    ");
    $time_leaders->execute([$course_id]);
    $active_times = $time_leaders->fetchAll();
    ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 text-center shadow-sm">
            <p class="text-slate-400 font-black text-[10px] uppercase">إجمالي الطلاب</p>
            <h4 class="text-5xl font-black text-indigo-600 mt-2"><?= $data['total_students'] ?></h4>
        </div>
        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 text-center shadow-sm text-indigo-950">
            <p class="text-slate-400 font-black text-[10px] uppercase">إجمالي التسليمات</p>
            <h4 class="text-5xl font-black mt-2"><?= $total_submissions ?></h4>
        </div>
        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 text-center shadow-sm">
            <p class="text-slate-400 font-black text-[10px] uppercase">نسبة استهلاك الأكواد</p>
            <?php $percent = ($data['total_students'] + $data['free_tokens'] > 0) ? round(($data['total_students'] / ($data['total_students'] + $data['free_tokens'])) * 100) : 0; ?>
            <h4 class="text-5xl font-black text-emerald-500 mt-2"><?= $percent ?>%</h4>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
        <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm">
            <h3 class="text-lg font-black text-indigo-950 mb-6 flex items-center gap-2">
                <span class="text-2xl">🏆</span> أوائل المادة (Leaderboard)
            </h3>
            <div class="space-y-4">
                <?php if(count($leaders) > 0): 
                    foreach($leaders as $index => $student): ?>
                    <div class="flex justify-between items-center p-4 <?= $index == 0 ? 'bg-amber-50 border border-amber-100' : 'bg-slate-50' ?> rounded-2xl">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 flex items-center justify-center rounded-full bg-white text-[10px] font-black"><?= $index + 1 ?></span>
                            <span class="font-black text-sm text-slate-700"><?= htmlspecialchars($student['full_name']) ?></span>
                        </div>
                        <span class="font-black text-indigo-600"><?= $student['total_score'] ?> نقطة</span>
                    </div>
                <?php endforeach; 
                else: ?>
                    <p class="text-center text-slate-400 py-4 font-bold italic text-xs">لا توجد نتائج مسجلة بعد.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-indigo-950 p-8 rounded-[3rem] shadow-xl text-white">
            <h3 class="text-lg font-black mb-6 flex items-center gap-2">
                <span class="text-2xl">⏱️</span> الأكثر تفاعلاً (بالوقت)
            </h3>
            <div class="space-y-6">
                <?php foreach($active_times as $at): ?>
                    <div>
                        <div class="flex justify-between text-xs font-black mb-2 px-1">
                            <span><?= htmlspecialchars($at['full_name']) ?></span>
                            <span class="text-indigo-300"><?= round($at['total_seconds'] / 60) ?> دقيقة</span>
                        </div>
                        <div class="w-full bg-indigo-900 h-2 rounded-full overflow-hidden">
                            <?php 
                                $max_time = $active_times[0]['total_seconds'] ?: 1;
                                $p_width = ($at['total_seconds'] / $max_time) * 100;
                            ?>
                            <div class="bg-indigo-400 h-full transition-all duration-1000" style="width: <?= $p_width ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if(count($active_times) == 0): ?>
                    <p class="text-center text-indigo-400 py-10 font-bold italic text-xs">لا يوجد بيانات تفاعل حالياً.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="flex flex-col md:flex-row items-center justify-center gap-4">
        <a href="full_export.php?course_id=<?= $course_id ?>" class="bg-slate-900 text-white px-10 py-5 rounded-[2rem] font-black text-xs hover:bg-indigo-600 transition-all shadow-xl shadow-slate-200">
             📥 تصدير شيت درجات المادة (Excel)
        </a>
        <a href="manage_students.php?course_id=<?= $course_id ?>" class="bg-white text-indigo-600 border-2 border-indigo-50 px-10 py-5 rounded-[2rem] font-black text-xs hover:bg-indigo-50 transition-all">
             👥 إدارة الطلاب المشتركين
        </a>
    </div>
</div>
    <div id="leaderboard" class="tab-content">
        <div class="bg-indigo-950 rounded-[3rem] p-10 text-white shadow-2xl">
            <h3 class="text-2xl font-black mb-8 text-center text-indigo-300">⭐ نجوم المادة ⭐</h3>
            <div class="space-y-4 max-w-xl mx-auto">
                <?php
                $tops = $pdo->prepare("SELECT u.full_name, SUM(er.score) as total FROM exam_results er JOIN users u ON er.student_id = u.id WHERE er.course_id = ? GROUP BY er.student_id ORDER BY total DESC LIMIT 5");
                $tops->execute([$course_id]);
                while($t = $tops->fetch()): ?>
                    <div class="flex items-center justify-between bg-white/10 p-5 rounded-2xl border border-white/5">
                        <span class="font-black text-lg"><?= htmlspecialchars($t['full_name']) ?></span>
                        <span class="bg-yellow-500 text-black px-4 py-1 rounded-full font-black text-xs"><?= $t['total'] ?> نقطة</span>
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

    function sendMessage() {
        const input = document.getElementById('msgInput');
        if(input.value.trim() !== "") {
            const chatBox = document.getElementById('chatBox');
            chatBox.innerHTML += `<div class="ml-auto bg-indigo-600 text-white p-4 rounded-2xl max-w-sm shadow-sm"><p class="text-[9px] font-black text-indigo-200 mb-1">أنت (الآن)</p><p class="text-sm">${input.value}</p></div>`;
            input.value = "";
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }
</script>