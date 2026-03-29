<?php 
require_once '../config/db.php';
session_start();

// حماية الصفحة (للمعلم والادمن فقط)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php"); exit();
}

$assign_id = $_GET['id'] ?? null;
if (!$assign_id) die("الواجب غير محدد!");

// 1. جلب بيانات الواجب الأساسية
$assign_stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
$assign_stmt->execute([$assign_id]);
$assignment = $assign_stmt->fetch();

// 2. جلب قائمة الطلاب اللي سلموا الواجب
$stmt = $pdo->prepare("
    SELECT 
        u.full_name, 
        ans.id AS sub_id,
        ans.file_path, 
        ans.submitted_at, 
        ans.grade,
        ans.student_notes
    FROM assignment_submissions ans
    JOIN users u ON ans.student_id = u.id
    WHERE ans.assignment_id = ?
    ORDER BY ans.submitted_at DESC
");
$stmt->execute([$assign_id]);
$submissions = $stmt->fetchAll();

// 3. معالجة رصد الدرجة (لو المعلم بعت درجة)
if (isset($_POST['update_grade'])) {
    $sub_id = $_POST['submission_id'];
    $new_grade = $_POST['grade'];
    $up_stmt = $pdo->prepare("UPDATE assignment_submissions SET grade = ? WHERE id = ?");
    $up_stmt->execute([$new_grade, $sub_id]);
    header("Location: view_submissions.php?id=" . $assign_id); exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسليمات الطلاب | إبراهيم الخولي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 p-6 md:p-12">

<div class="max-w-5xl mx-auto">
    <div class="mb-10 flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-black text-indigo-950">تسليمات: <?= htmlspecialchars($assignment['title']) ?></h1>
            <p class="text-slate-400 font-bold text-xs mt-2 uppercase tracking-widest">إجمالي التسليمات: <?= count($submissions) ?> طالب</p>
        </div>
        <a href="manage_content.php?id=<?= $assignment['course_id'] ?>" class="text-indigo-600 font-black text-xs underline">رجوع للمادة</a>
    </div>

    <?php if(count($submissions) > 0): ?>
    <div class="grid grid-cols-1 gap-6">
        <?php foreach($submissions as $sub): ?>
            <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm flex flex-col md:flex-row justify-between items-center gap-6">
                
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 font-black">
                        <?= mb_substr($sub['full_name'], 0, 1) ?>
                    </div>
                    <div>
                        <h3 class="font-black text-indigo-950"><?= htmlspecialchars($sub['full_name']) ?></h3>
                        <p class="text-[10px] text-slate-400 font-bold italic"><?= date('Y-m-d H:i', strtotime($sub['submitted_at'])) ?></p>
                    </div>
                </div>

                <?php if(!empty($sub['student_notes'])): ?>
                <div class="flex-1 px-4 border-r border-slate-50">
                    <p class="text-[10px] font-black text-slate-400 uppercase">ملاحظة الطالب:</p>
                    <p class="text-xs text-slate-600 italic">"<?= htmlspecialchars($sub['student_notes']) ?>"</p>
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-3">
                    <a href="../uploads/assignments/<?= $sub['file_path'] ?>" download class="bg-slate-900 text-white px-5 py-3 rounded-2xl text-[10px] font-black shadow-lg">تحميل الحل 📥</a>
                    
                    <form method="POST" class="flex items-center gap-2 bg-slate-50 p-2 rounded-2xl border border-slate-100">
                        <input type="hidden" name="submission_id" value="<?= $sub['sub_id'] ?>">
                        <input type="number" name="grade" value="<?= $sub['grade'] ?>" max="<?= $assignment['max_mark'] ?>" placeholder="الدرجة" 
                               class="w-16 bg-white border-0 rounded-xl text-center font-black text-xs p-2 outline-none focus:ring-2 focus:ring-indigo-500">
                        <button type="submit" name="update_grade" class="bg-emerald-500 text-white p-2 rounded-xl text-[10px] font-black hover:bg-emerald-600 transition-all">حفظ</button>
                    </form>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="bg-white p-20 rounded-[3rem] text-center border-2 border-dashed border-slate-200">
            <p class="text-slate-400 font-black italic">لم يقم أي طالب بتسليم الواجب حتى الآن 😴</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>