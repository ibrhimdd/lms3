<?php 
require_once '../config/db.php';
session_start();

// 1. حماية الصفحة والتحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = $_SESSION['name'];

// 2. جلب البيانات الحقيقية من قاعدة البيانات بناءً على الرتبة
try {
    if ($role == 'student') {
        // جلب نقاط الطالب الحقيقية
        $stmt = $pdo->prepare("SELECT total_points FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $total_points = $stmt->fetchColumn() ?: 0;

        // عدد المواد التي انضم إليها الطالب فعلياً
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ?");
        $stmt->execute([$user_id]);
        $courses_count = $stmt->fetchColumn();

    } elseif ($role == 'teacher') {
        // عدد المواد التي أنشأها هذا المعلم
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE teacher_id = ?");
        $stmt->execute([$user_id]);
        $my_courses_count = $stmt->fetchColumn();

        // إجمالي عدد الطلاب المشتركين في جميع مواد هذا المعلم
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM enrollments 
                               JOIN courses ON enrollments.course_id = courses.id 
                               WHERE courses.teacher_id = ?");
        $stmt->execute([$user_id]);
        $total_students = $stmt->fetchColumn();

    } elseif ($role == 'admin') {
        // إحصائيات المنصة كاملة للأدمن
        $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $total_all_courses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    }
} catch (PDOException $e) {
    die("خطأ برمي في جلب البيانات: " . $e->getMessage());
}

// 3. استدعاء الهيدر الثابت (الذي يحتوي على السايد بار وزر القائمة)
include '../includes/header.php'; 
?>

<main class="lg:mr-72 p-6 md:p-10 min-h-screen transition-all duration-500">
    
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 mt-16 lg:mt-0 animate__animated animate__fadeInDown">
        <div>
            <h2 class="text-4xl font-black text-indigo-950 tracking-tight">أهلاً بك، <?= htmlspecialchars($name) ?> ✨</h2>
            <p class="text-slate-500 mt-2 font-medium">إليك نظرة سريعة على إحصائياتك اليوم في منصة المبدع.</p>
        </div>
        
        <div class="hidden md:flex bg-white p-4 rounded-[2rem] shadow-sm border border-gray-100 items-center gap-4">
            <div class="text-left">
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest">اليوم</p>
                <p class="text-sm font-bold text-indigo-600"><?= date('d M, Y') ?></p>
            </div>
            <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-2xl">
                📅
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        
        <?php if($role == 'student'): ?>
            <div class="group cursor-pointer bg-gradient-to-br from-indigo-600 to-indigo-800 p-8 rounded-[3rem] text-white shadow-2xl transition-all duration-500 hover:scale-[1.03] hover:shadow-indigo-500/40 animate__animated animate__fadeInUp">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-indigo-100 font-bold text-sm">رصيد النقاط 🏆</p>
                        <h3 class="text-6xl font-black mt-4 tracking-tighter"><?= number_format($total_points) ?></h3>
                    </div>
                    <span class="text-4xl opacity-20 group-hover:opacity-100 transition-opacity">✨</span>
                </div>
            </div>

            <div class="group cursor-pointer bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 transition-all duration-500 hover:scale-[1.03] hover:shadow-xl animate__animated animate__fadeInUp animate__delay-1s">
                <p class="text-slate-400 font-bold text-sm uppercase tracking-widest">المواد المنضم إليها</p>
                <h3 class="text-6xl font-black text-indigo-950 mt-4 tracking-tighter"><?= $courses_count ?></h3>
                <div class="mt-4 flex items-center text-emerald-500 font-bold text-sm">
                    <span>تصفح المواد الآن ←</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if($role == 'teacher'): ?>
            <div class="group cursor-pointer bg-gradient-to-br from-emerald-500 to-teal-700 p-8 rounded-[3rem] text-white shadow-2xl transition-all duration-500 hover:scale-[1.03] hover:shadow-emerald-500/40 animate__animated animate__fadeInUp">
                <p class="text-emerald-100 font-bold text-sm uppercase tracking-widest">إجمالي طلابك 👨‍🎓</p>
                <h3 class="text-6xl font-black mt-4 tracking-tighter"><?= number_format($total_students) ?></h3>
            </div>

            <div class="group cursor-pointer bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 transition-all duration-500 hover:scale-[1.03] hover:shadow-xl animate__animated animate__fadeInUp animate__delay-1s">
                <p class="text-slate-400 font-bold text-sm uppercase tracking-widest">كورساتك المفعّلة</p>
                <h3 class="text-6xl font-black text-indigo-950 mt-4 tracking-tighter"><?= $my_courses_count ?></h3>
            </div>
        <?php endif; ?>

        <?php if($role == 'admin'): ?>
            <div class="group cursor-pointer bg-gradient-to-br from-purple-600 to-pink-700 p-8 rounded-[3rem] text-white shadow-2xl transition-all duration-500 hover:scale-[1.03] animate__animated animate__fadeInUp">
                <p class="text-purple-100 font-bold text-sm uppercase tracking-widest">إجمالي المستخدمين</p>
                <h3 class="text-6xl font-black mt-4 tracking-tighter"><?= number_format($total_users) ?></h3>
            </div>

            <div class="group cursor-pointer bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 transition-all duration-500 hover:scale-[1.03] animate__animated animate__fadeInUp animate__delay-1s">
                <p class="text-slate-400 font-bold text-sm uppercase tracking-widest">إجمالي الكورسات</p>
                <h3 class="text-6xl font-black text-indigo-950 mt-4 tracking-tighter"><?= $total_all_courses ?></h3>
            </div>
        <?php endif; ?>

        <div class="group cursor-pointer bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 transition-all duration-500 hover:scale-[1.03] hover:shadow-xl animate__animated animate__fadeInUp animate__delay-2s">
            <p class="text-slate-400 font-bold text-sm uppercase tracking-widest">تنبيهات جديدة 🔔</p>
            <h3 class="text-6xl font-black text-indigo-950 mt-4 tracking-tighter">0</h3>
        </div>
    </div>

    <section class="mt-16 animate__animated animate__fadeIn animate__delay-3s">
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-2xl font-black text-indigo-950">آخر النشاطات</h3>
            <button class="text-indigo-600 font-bold text-sm hover:underline">سجل النشاط بالكامل</button>
        </div>
        <div class="bg-white rounded-[3.5rem] p-16 shadow-sm border border-slate-50 text-center flex flex-col items-center">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center text-3xl mb-6">
                🚀
            </div>
            <h4 class="text-xl font-bold text-slate-800">ابدأ رحلتك اليوم!</h4>
            <p class="text-slate-400 mt-2 max-w-sm">لا توجد بيانات حالية للعرض، بمجرد تفاعلك مع المنصة ستظهر أنشطتك هنا بشكل تلقائي.</p>
        </div>
    </section>

</main>

</body>
</html>