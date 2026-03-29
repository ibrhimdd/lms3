<?php 
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php"); exit();
}

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) die("المادة غير محددة!");

$course_stmt = $pdo->prepare("SELECT course_name FROM courses WHERE id = ?");
$course_stmt->execute([$course_id]);
$course = $course_stmt->fetch();

// تم حذف u.phone من الاستعلام هنا
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name, u.email 
    FROM users u
    JOIN course_tokens ct ON u.id = ct.used_by_student_id
    WHERE ct.course_id = ? AND ct.is_used = 1 AND u.role = 'student'
");
$stmt->execute([$course_id]);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة طلاب | <?= htmlspecialchars($course['course_name'] ?? 'المادة') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 p-6 md:p-12 text-right">

<div class="max-w-6xl mx-auto">
    <div class="mb-10 flex justify-between items-end">
        <div class="text-right">
            <h1 class="text-3xl font-black text-indigo-950">إدارة طلاب المادة 👥</h1>
            <p class="text-slate-400 font-bold text-xs mt-2 italic"><?= htmlspecialchars($course['course_name'] ?? '') ?></p>
        </div>
        <a href="view_course.php?id=<?= $course_id ?>" class="bg-white text-indigo-600 px-6 py-3 rounded-2xl text-xs font-black border border-indigo-50 shadow-sm hover:bg-indigo-600 hover:text-white transition-all">العودة للمادة</a>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="p-6 font-black text-indigo-950 text-sm italic">اسم الطالب</th>
                    <th class="p-6 font-black text-indigo-950 text-sm italic">البريد الإلكتروني</th>
                    <th class="p-6 font-black text-indigo-950 text-sm italic text-center">العمليات</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($students) > 0): ?>
                    <?php foreach($students as $st): ?>
                        <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-all">
                            <td class="p-6 font-bold text-indigo-950"><?= htmlspecialchars($st['full_name']) ?></td>
                            <td class="p-6 text-xs text-slate-500 font-bold"><?= htmlspecialchars($st['email']) ?></td>
                            <td class="p-6 text-center">
                                <a href="student_report.php?student_id=<?= $st['id'] ?>&course_id=<?= $course_id ?>" 
                                   class="inline-block bg-indigo-50 text-indigo-600 px-5 py-2 rounded-xl text-[10px] font-black hover:bg-indigo-600 hover:text-white transition-all">
                                    عرض التقرير الشامل 📊
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="p-20 text-center text-slate-300 font-bold italic">لا يوجد طلاب مفعلين للأكواد.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>