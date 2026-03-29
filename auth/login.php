<?php 
require_once '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['full_name'];
        header("Location: ../dashboard/index.php");
    } else {
        $error = "بيانات الدخول غير صحيحة";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول | منصة المبدع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Cairo', sans-serif;
        }
        .morph-card {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center">
    <div class="animate__animated animate__zoomIn bg-white/20 backdrop-blur-lg p-10 rounded-3xl shadow-2xl w-full max-w-md border border-white/30 morph-card">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-extrabold text-white mb-2">مرحباً بك 🚀</h1>
            <p class="text-indigo-100">رحلتك التعليمية تبدأ من هنا</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-500/50 text-white p-3 rounded-lg mb-4 text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div class="relative">
                <input type="email" name="email" placeholder="بريدك الإلكتروني" required 
                class="w-full bg-white/10 border border-white/20 p-4 rounded-xl text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all">
            </div>
            <div class="relative">
                <input type="password" name="password" placeholder="كلمة المرور" required 
                class="w-full bg-white/10 border border-white/20 p-4 rounded-xl text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all">
            </div>
            <button type="submit" class="w-full bg-white text-indigo-700 font-bold py-4 rounded-xl shadow-lg hover:scale-105 active:scale-95 transition-all duration-300">
                دخول للمنصة
            </button>
        </form>
        
        <p class="text-center mt-8 text-white/80">
            ليس لديك حساب؟ <a href="register.php" class="font-bold underline">انضم إلينا الآن</a>
        </p>
    </div>
</body>
</html>