<?php 
require_once '../config/db.php';
session_start();

// حماية الصفحة: للمعلم والادمن فقط
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = $_POST['course_name'];
    $teacher_id = $_SESSION['user_id'];
    
    // توليد "كود المادة" المتغير (6 رموز عشوائية)
    $course_code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    try {
        $sql = "INSERT INTO courses (course_name, course_code, teacher_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$course_name, $course_code, $teacher_id])) {
            $last_id = $pdo->lastInsertId(); // جلب "رقم المادة" الثابت
            $message = "success";
        }
    } catch (PDOException $e) {
        $message = "error";
    }
}

include '../includes/header.php'; 
?>

<main class="lg:mr-72 p-6 md:p-10 min-h-screen">
    
    <header class="mb-12 mt-16 lg:mt-0 animate__animated animate__fadeIn">
        <h2 class="text-4xl font-black text-indigo-950">إنشاء مادة جديدة 📚</h2>
        <p class="text-slate-500 mt-2">سيتم توليد رقم ثابت للمادة وكود متغير للطلاب تلقائياً.</p>
    </header>

    <div class="max-w-2xl mx-auto">
        <?php if($message == "success"): ?>
            <div class="animate__animated animate__backInDown bg-emerald-500 text-white p-6 rounded-[2rem] mb-8 shadow-xl shadow-emerald-200">
                <h4 class="font-black text-xl mb-2">تم إنشاء المادة بنجاح! 🎉</h4>
                <p class="opacity-90">رقم المادة الثابت: <b class="text-white text-2xl ml-2">#<?= $last_id ?></b></p>
                <p class="opacity-90">كود الانضمام للطلاب: <b class="text-white text-2xl ml-2"><?= $course_code ?></b></p>
                <p class="mt-2 text-xs italic">* اطلب من الطلاب إدخال الرقم والكود معاً للانضمام.</p>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100 animate__animated animate__fadeInUp">
            <div class="space-y-6">
                <div>
                    <label class="block text-indigo-950 font-bold mb-3 mr-2">اسم المادة الدراسية</label>
                    <input type="text" name="course_name" placeholder="مثال: لغة برمجة PHP - المستوى الأول" required 
                    class="w-full bg-slate-50 border-none p-5 rounded-2xl focus:ring-4 focus:ring-indigo-100 transition-all outline-none text-lg font-bold">
                </div>

                <div class="bg-indigo-50 p-6 rounded-2xl border-r-4 border-indigo-600">
                    <p class="text-indigo-900 text-sm font-bold">💡 ملاحظة للمُعلم:</p>
                    <p class="text-indigo-700 text-xs mt-1 leading-relaxed">
                        بمجرد الضغط على "إنشاء"، سيقوم النظام بتخصيص <b>رقم هوية</b> لا يتغير للمادة، و<b>كود سري</b> يمكنك تحديثه لاحقاً لمنع دخول طلاب جدد.
                    </p>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white font-black py-5 rounded-2xl shadow-xl shadow-indigo-200 hover:bg-indigo-700 hover:scale-[1.02] active:scale-95 transition-all text-xl">
                    تأكيد وإنشاء المادة 🚀
                </button>
            </div>
        </form>
    </div>

</main>

</body>
</html>