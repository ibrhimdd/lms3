<?php 
require_once '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $c_id = $_POST['course_id'];
    $t_code = $_POST['token_code'];
    $u_id = $_SESSION['user_id'];

    // 1. التأكد من صحة الكود وأنه غير مستخدم ومربوط بالمادة الصحيحة
    $stmt = $pdo->prepare("SELECT id FROM course_tokens WHERE course_id = ? AND token_code = ? AND is_used = 0");
    $stmt->execute([$c_id, $t_code]);
    $token = $stmt->fetch();

    if ($token) {
        // 2. تحديث الكود ليصبح "مستخدماً"
        $update = $pdo->prepare("UPDATE course_tokens SET is_used = 1, used_by_student_id = ?, used_at = NOW() WHERE id = ?");
        $update->execute([$u_id, $token['id']]);

        // 3. تسجيل الطالب في المادة (Enrollment)
        $enroll = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
        $enroll->execute([$u_id, $c_id]);

        $success = "مبروك! تم تفعيل المادة بنجاح 🚀";
    } else {
        $error = "عذراً، رقم المادة أو كود الاشتراك غير صحيح أو تم استخدامه مسبقاً.";
    }
}

include '../includes/header.php';
?>

<main class="lg:mr-72 p-6 md:p-10 min-h-screen flex items-center justify-center">
    <div class="bg-white p-12 rounded-[3.5rem] shadow-2xl border border-slate-100 w-full max-w-xl animate__animated animate__zoomIn">
        <h2 class="text-3xl font-black text-indigo-950 text-center mb-8">تسجيل المادة بالكود السري 🔑</h2>

        <?php if(isset($success)): ?>
            <div class="bg-emerald-500 text-white p-6 rounded-3xl mb-6 text-center animate__animated animate__flipInX">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="bg-red-500 text-white p-6 rounded-3xl mb-6 text-center animate__animated animate__shakeX">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <input type="number" name="course_id" placeholder="أدخل رقم المادة (ID)" required 
            class="w-full bg-slate-50 p-5 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-indigo-100">
            
            <input type="text" name="token_code" placeholder="أدخل كود الاشتراك السري" required 
            class="w-full bg-slate-50 p-5 rounded-2xl font-bold outline-none focus:ring-4 focus:ring-indigo-100 text-center uppercase tracking-widest">

            <button type="submit" class="w-full bg-indigo-600 text-white font-black py-5 rounded-2xl shadow-xl hover:bg-indigo-700 transition-all text-xl">
                تفعيل الاشتراك الآن ⚡
            </button>
        </form>
    </div>
</main>