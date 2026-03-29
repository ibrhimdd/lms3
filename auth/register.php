<?php 
require_once '../config/db.php';
session_start();

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // التحقق من عدم تكرار البريد الإلكتروني
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    
    if ($check->rowCount() > 0) {
        $message = "هذا البريد مسجل مسبقاً!";
    } else {
        $sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$name, $email, $pass, $role])) {
            $message = "تم إنشاء الحساب بنجاح! <a href='login.php' class='underline font-bold'>سجل دخولك الآن</a>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>انضم إلينا | LMS 2026</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); /* ألوان مبهجة ومحفزة للنجاح */
            font-family: 'Cairo', sans-serif;
            overflow-x: hidden;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .input-morph:focus {
            transform: scale(1.02);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="animate__animated animate__fadeInDown glass-panel p-8 md:p-12 rounded-[2rem] shadow-2xl w-full max-w-lg">
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-white/30 rounded-full mb-4 animate__animated animate__bounceIn animate__delay-1s">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="id-18" /> <path d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                </svg>
            </div>
            <h1 class="text-3xl font-extrabold text-white">افتح آفاقك التعليمية ✨</h1>
            <p class="text-emerald-900/70 font-semibold mt-2">انضم لمجتمع "إبراهيم الخولي" للتميز</p>
        </div>

        <?php if($message): ?>
            <div class="bg-white/40 text-emerald-900 p-4 rounded-xl mb-6 text-center animate__animated animate__headShake">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="grid grid-cols-1 gap-5">
            <input type="text" name="full_name" placeholder="الاسم الرباعي" required 
            class="input-morph w-full bg-white/20 border border-white/30 p-4 rounded-2xl text-emerald-900 placeholder-emerald-800 focus:outline-none transition-all outline-none">
            
            <input type="email" name="email" placeholder="بريدك الإلكتروني" required 
            class="input-morph w-full bg-white/20 border border-white/30 p-4 rounded-2xl text-emerald-900 placeholder-emerald-800 focus:outline-none transition-all outline-none">
            
            <input type="password" name="password" placeholder="كلمة المرور القوية" required 
            class="input-morph w-full bg-white/20 border border-white/30 p-4 rounded-2xl text-emerald-900 placeholder-emerald-800 focus:outline-none transition-all outline-none">

            <div class="relative">
                <select name="role" class="w-full bg-white/20 border border-white/30 p-4 rounded-2xl text-emerald-900 appearance-none focus:outline-none cursor-pointer">
                    <option value="student">🎓 أنا طالب أسعى للقمة</option>
                    <option value="teacher">👨‍🏫 أنا معلم ملهم</option>
                </select>
                <div class="absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none text-emerald-900">
                    ▼
                </div>
            </div>

            <button type="submit" class="mt-4 w-full bg-emerald-700 text-white font-black py-4 rounded-2xl shadow-xl hover:bg-emerald-800 hover:-translate-y-1 active:scale-95 transition-all duration-300 uppercase tracking-wider">
                انطلق الآن 🚀
            </button>
        </form>

        <div class="text-center mt-8 text-emerald-900/80">
            لديك حساب بالفعل؟ <a href="login.php" class="font-black text-emerald-800 hover:underline">سجل الدخول</a>
        </div>
    </div>

</body>
</html>