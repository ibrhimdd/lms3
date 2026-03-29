<?php 
require_once '../config/db.php';
session_start();

// 1. حماية الصفحة: للمعلم والأدمن فقط
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// 2. جلب جميع المواد الخاصة بهذا المعلم مع عدد الطلاب المشتركين في كل مادة
try {
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as student_count,
            (SELECT COUNT(*) FROM course_tokens ct WHERE ct.course_id = c.id AND ct.is_used = 0) as available_tokens
            FROM courses c 
            WHERE c.teacher_id = ? 
            ORDER BY c.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teacher_id]);
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    die("خطأ في جلب المواد: " . $e->getMessage());
}

include '../includes/header.php'; 
?>

<main class="lg:mr-72 p-6 md:p-10 min-h-screen">
    
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 mt-16 lg:mt-0 animate__animated animate__fadeIn">
        <div>
            <h2 class="text-4xl font-black text-indigo-950">موادي الدراسية 📚</h2>
            <p class="text-slate-500 mt-2 font-medium">إدارة المحتوى، الطلاب، وأكواد التسجيل لجميع موادك.</p>
        </div>
        <a href="add_course.php" class="mt-4 md:mt-0 bg-indigo-600 text-white px-8 py-4 rounded-[1.5rem] font-black shadow-lg shadow-indigo-200 hover:scale-105 transition-all">
            + إنشاء مادة جديدة
        </a>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
        <?php if (empty($courses)): ?>
            <div class="col-span-full bg-white p-20 rounded-[3rem] text-center border border-dashed border-slate-300">
                <p class="text-slate-400 text-xl font-bold">لم تقم بإضافة أي مواد بعد. ابدأ الآن!</p>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <div class="group bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-8 transition-all duration-500 hover:shadow-2xl hover:-translate-y-2 relative overflow-hidden">
                    
                    <div class="absolute top-0 left-0 bg-indigo-50 text-indigo-600 px-6 py-2 rounded-bl-[1.5rem] font-black text-xs">
                        ID: #<?= $course['id'] ?>
                    </div>

                    <div class="mt-4">
                        <h3 class="text-2xl font-black text-indigo-950 mb-2 group-hover:text-indigo-600 transition-colors">
                            <?= htmlspecialchars($course['course_name']) ?>
                        </h3>
                        <div class="flex items-center gap-4 text-slate-500 text-sm font-bold">
                            <span>👥 <?= $course['student_count'] ?> طالب</span>
                            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                            <span>🎫 <?= $course['available_tokens'] ?> كود متاح</span>
                        </div>
                    </div>

                    <div class="mt-8 space-y-3">
                        <a href="generate_tokens.php?id=<?= $course['id'] ?>" 
                           class="flex items-center justify-center w-full bg-slate-50 text-indigo-600 font-black py-4 rounded-2xl border-2 border-transparent hover:border-indigo-600 hover:bg-white transition-all">
                           🎫 إدارة أكواد التسجيل
                        </a>
                        
                        <a href="manage_content.php?id=<?= $course['id'] ?>" 
                           class="flex items-center justify-center w-full bg-indigo-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                           📝 إدارة الدروس والملفات
                        </a>
                    </div>

                    <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-indigo-50 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-700 blur-2xl"></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>

</body>
</html>