<?php 
require_once '../config/db.php';
session_start();

// 1. حماية الصفحة: للطالب فقط
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// 2. استعلام لجلب المواد التي اشترك فيها الطالب + اسم المعلم اللي أنشأها
try {
    $sql = "SELECT c.*, u.full_name as teacher_name 
            FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            JOIN users u ON c.teacher_id = u.id 
            WHERE e.student_id = ? 
            ORDER BY e.enrolled_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $my_courses = $stmt->fetchAll();
} catch (PDOException $e) {
    die("خطأ في جلب بيانات موادك: " . $e->getMessage());
}

include '../includes/header.php'; 
?>

<main class="lg:mr-72 p-6 md:p-10 min-h-screen">
    
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 mt-16 lg:mt-0 animate__animated animate__fadeIn">
        <div>
            <h2 class="text-4xl font-black text-indigo-950 tracking-tight">موادي الدراسية 📖</h2>
            <p class="text-slate-500 mt-2 font-medium">هنا تجد جميع المواد التي قمت بالتسجيل فيها بنجاح.</p>
        </div>
        <a href="join_course.php" class="mt-4 md:mt-0 bg-indigo-600 text-white px-8 py-4 rounded-2xl font-black shadow-lg shadow-indigo-200 hover:scale-105 transition-all">
            + تسجيل مادة جديدة
        </a>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
        
        <?php if (empty($my_courses)): ?>
            <div class="col-span-full bg-white p-20 rounded-[3rem] text-center border-2 border-dashed border-slate-200 animate__animated animate__pulse">
                <div class="text-6 mb-4">📂</div>
                <h3 class="text-2xl font-black text-indigo-950">لا يوجد مواد حالياً</h3>
                <p class="text-slate-400 mt-2">يبدو أنك لم تشترك في أي مادة بعد. اطلب الكود من معلمك وابدأ الآن!</p>
                <a href="join_course.php" class="inline-block mt-6 text-indigo-600 font-black border-b-2 border-indigo-600">سجل أول مادة لك من هنا</a>
            </div>
        <?php else: ?>
            
            <?php foreach ($my_courses as $course): ?>
                <div class="group bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden transition-all duration-500 hover:shadow-2xl hover:-translate-y-2 animate__animated animate__fadeInUp">
                    
                    <div class="h-32 bg-gradient-to-r from-indigo-500 to-purple-600 p-6 flex items-end">
                        <span class="bg-white/20 backdrop-blur-md text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                            ID: #<?= $course['id'] ?>
                        </span>
                    </div>

                    <div class="p-8">
                        <h3 class="text-2xl font-black text-indigo-950 mb-2 group-hover:text-indigo-600 transition-colors">
                            <?= htmlspecialchars($course['course_name']) ?>
                        </h3>
                        
                        <div class="flex items-center gap-2 mb-6 text-slate-400 text-sm">
                            <div class="w-6 h-6 bg-slate-100 rounded-full flex items-center justify-center text-[10px]">👤</div>
                            <span>المعلم: <b class="text-slate-600"><?= htmlspecialchars($course['teacher_name']) ?></b></span>
                        </div>

                        <div class="mb-6">
                            <div class="flex justify-between text-[10px] font-black uppercase tracking-widest text-indigo-600 mb-2">
                                <span>التقدم في المادة</span>
                                <span>0%</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="w-[0%] h-full bg-indigo-600 rounded-full transition-all duration-1000"></div>
                            </div>
                        </div>

                        <a href="view_course.php?id=<?= $course['id'] ?>" 
                           class="flex items-center justify-center w-full bg-indigo-50 text-indigo-600 font-black py-4 rounded-2xl group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-sm">
                           فتح محتوى المادة 🚀
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</main>

</body>
</html>