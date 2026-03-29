<?php
// نتأكد أن الجلسة بدأت لجلب بيانات المستخدم
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? 'student';
$name = $_SESSION['name'] ?? 'مستخدم';

// دالة بسيطة لمعرفة الصفحة الحالية لتحديد الرابط النشط (Active)
function isActive($pageName) {
    return strpos($_SERVER['PHP_SELF'], $pageName) !== false ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'text-indigo-200 hover:bg-white/10 hover:text-white';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8fafc; }
        .sidebar-transition { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .nav-item { transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-slate-50">

    <div class="lg:hidden fixed top-5 right-5 z-[100]">
        <button onclick="toggleSidebar()" class="p-3 bg-indigo-600 text-white rounded-2xl shadow-xl hover:scale-110 active:scale-95 transition-all">
            <svg id="menuIcon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
        </button>
    </div>

    <aside id="mainSidebar" class="sidebar-transition fixed inset-y-0 right-0 z-[90] w-72 bg-indigo-950 text-white p-6 transform translate-x-full lg:translate-x-0 shadow-2xl flex flex-col">
        <div class="mb-10 text-center">
            <h1 class="text-3xl font-black tracking-tighter text-indigo-400">LMS <span class="text-white">2026</span></h1>
            <p class="text-[10px] text-indigo-300 mt-1 uppercase tracking-widest">Ibrahim Elkhooly</p>
        </div>

        <nav class="space-y-3 flex-1 overflow-y-auto">
            <a href="../dashboard/index.php" class="nav-item flex items-center p-4 rounded-2xl font-bold <?= isActive('index.php') ?>">
                <span class="ml-3 text-xl">🏠</span> الرئيسية
            </a>
            
            <?php if($role == 'admin'): ?>
                <div class="pt-4 pb-2 px-4 text-[10px] text-indigo-400 font-black uppercase tracking-widest border-t border-white/10 mt-4">إدارة المنصة</div>
                <a href="../dashboard/users_manage.php" class="nav-item flex items-center p-4 rounded-2xl font-bold <?= isActive('users_manage.php') ?>">
                    <span class="ml-3 text-xl">👥</span> إدارة المستخدمين
                </a>
            <?php endif; ?>

            <?php if($role == 'teacher' || $role == 'admin'): ?>
                <div class="pt-4 pb-2 px-4 text-[10px] text-indigo-400 font-black uppercase tracking-widest border-t border-white/10 mt-4">أدوات المعلم</div>
                <a href="../dashboard/add_course.php" class="nav-item flex items-center p-4 rounded-2xl font-bold <?= isActive('add_course.php') ?>">
                    <span class="ml-3 text-xl">➕</span> إنشاء مادة جديدة
                </a>
                <a href="../dashboard/courses_list.php" class="nav-item flex items-center p-4 rounded-2xl font-bold <?= isActive('courses_list.php') ?>">
                    <span class="ml-3 text-xl">📚</span> موادي الدراسية
                </a>
            <?php endif; ?>

            <?php if($role == 'student'): ?>
                <div class="pt-4 pb-2 px-4 text-[10px] text-indigo-400 font-black uppercase tracking-widest border-t border-white/10 mt-4">بوابة الطالب</div>
                <a href="../dashboard/join_course.php" class="nav-item flex items-center p-4 rounded-2xl font-bold <?= isActive('join_course.php') ?>">
                    <span class="ml-3 text-xl">🔑</span> تسجيل مادة بالكود
                </a>
                <a href="../dashboard/my_courses.php" class="nav-item flex items-center p-4 rounded-2xl font-bold <?= isActive('my_courses.php') ?>">
                    <span class="ml-3 text-xl">📖</span> موادي المشترك بها
                </a>
                <a href="../dashboard/leaderboard.php" class="nav-item flex items-center p-4 rounded-2xl font-bold <?= isActive('leaderboard.php') ?>">
                    <span class="ml-3 text-xl">🏆</span> لوحة الشرف
                </a>
            <?php endif; ?>
        </nav>

        <a href="../auth/logout.php" class="mt-auto p-4 bg-red-500/10 text-red-400 rounded-2xl text-center font-bold hover:bg-red-600 hover:text-white transition-all duration-300">
            تسجيل الخروج
        </a>
    </aside>

    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-[80] hidden lg:hidden backdrop-blur-sm"></div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const icon = document.getElementById('menuIcon');

            if (sidebar.classList.contains('translate-x-full')) {
                sidebar.classList.remove('translate-x-full');
                overlay.classList.remove('hidden');
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />';
            } else {
                sidebar.classList.add('translate-x-full');
                overlay.classList.add('hidden');
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />';
            }
        }
    </script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<script src="https://cdn.tailwindcss.com"></script>