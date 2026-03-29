<?php 
require_once '../config/db.php';
session_start();

// ضبط المنطقة الزمنية
date_default_timezone_set('Africa/Cairo');

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];
$assign_id = $_GET['id'] ?? null;

if (!$assign_id) die("الواجب غير محدد!");

// 1. جلب بيانات الواجب الأساسية
$stmt = $pdo->prepare("SELECT a.*, c.course_name FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = ?");
$stmt->execute([$assign_id]);
$assignment = $stmt->fetch();

if (!$assignment) die("الواجب غير موجود!");

// 2. التحقق هل الطالب سلم الواجب ده قبل كدة؟
$check_stmt = $pdo->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
$check_stmt->execute([$assign_id, $user_id]);
$already_submitted = $check_stmt->fetch();

$success = ""; $error = "";

// 3. معالجة الرفع
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$already_submitted) {
    $notes = $_POST['student_notes'];
    
    if (!empty($_FILES['submission_file']['name'])) {
        $target_dir = "../uploads/assignments/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES["submission_file"]["name"], PATHINFO_EXTENSION);
        $file_name = "SUB_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["submission_file"]["tmp_name"], $target_file)) {
            $ins = $pdo->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, file_path, student_notes) VALUES (?, ?, ?, ?)");
            if ($ins->execute([$assign_id, $user_id, $file_name, $notes])) {
                $success = "تم رفع الواجب بنجاح! بالتوفيق يا بطل.";
                header("refresh:2;url=view_course.php?id=" . $assignment['course_id']);
            }
        } else {
            $error = "حدث خطأ أثناء رفع الملف، تأكد من حجم الملف.";
        }
    } else {
        $error = "يرجى اختيار ملف لرفعه.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسليم الواجب | <?= htmlspecialchars($assignment['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 p-6 md:p-12">

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-[3rem] p-10 shadow-2xl border border-slate-100 relative overflow-hidden">
        
        <div class="mb-8">
            <span class="text-[10px] bg-indigo-50 text-indigo-600 px-4 py-1 rounded-full font-black uppercase"><?= htmlspecialchars($assignment['course_name']) ?></span>
            <h1 class="text-3xl font-black text-indigo-950 mt-3"><?= htmlspecialchars($assignment['title']) ?></h1>
            <p class="text-slate-400 font-bold text-xs mt-2 italic">الموعد النهائي: <?= $assignment['due_date'] ?></p>
        </div>

        <?php if($already_submitted): ?>
            <div class="bg-emerald-50 border-2 border-emerald-100 p-8 rounded-[2rem] text-center">
                <div class="text-4xl mb-4 text-emerald-500">✅</div>
                <h3 class="text-emerald-700 font-black text-lg">لقد قمت بتسليم هذا الواجب بالفعل!</h3>
                <p class="text-emerald-600 text-xs font-bold mt-2">تاريخ التسليم: <?= $already_submitted['submitted_at'] ?></p>
                <?php if($already_submitted['grade'] !== null): ?>
                    <div class="mt-6 p-4 bg-white rounded-2xl shadow-sm inline-block">
                        <p class="text-[10px] text-slate-400 font-black">درجتك النهائية</p>
                        <p class="text-2xl font-black text-indigo-950"><?= $already_submitted['grade'] ?> / <?= $assignment['max_mark'] ?></p>
                    </div>
                <?php endif; ?>
                <a href="view_course.php?id=<?= $assignment['course_id'] ?>" class="block mt-8 text-indigo-600 font-black text-sm underline">العودة للمادة</a>
            </div>
        <?php else: ?>
            
            <?php if($success): ?> <div class="bg-emerald-500 text-white p-4 rounded-2xl mb-6 font-black text-center"><?= $success ?></div> <?php endif; ?>
            <?php if($error): ?> <div class="bg-rose-500 text-white p-4 rounded-2xl mb-6 font-black text-center"><?= $error ?></div> <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                <div class="bg-slate-50 p-6 rounded-[2rem] border-2 border-dashed border-slate-200 text-center relative group hover:border-indigo-400 transition-all">
                    <input type="file" name="submission_file" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="py-4">
                        <div class="text-4xl mb-2">📁</div>
                        <p class="text-slate-600 font-black text-sm">اضغط هنا أو اسحب الملف لرفعه</p>
                        <p class="text-[10px] text-slate-400 mt-1 uppercase">PDF, Word, Images (Max 10MB)</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-black text-slate-700 mb-2 mr-2">ملاحظات إضافية للمدرس (اختياري)</label>
                    <textarea name="student_notes" rows="3" class="w-full p-5 bg-slate-50 rounded-[2rem] border-0 focus:ring-2 focus:ring-indigo-500 outline-none font-bold text-sm" placeholder="اكتب أي ملاحظة تريد إخبار المدرس بها..."></textarea>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white p-6 rounded-[2rem] font-black text-xl shadow-xl shadow-indigo-100 hover:bg-slate-900 transition-all transform hover:-translate-y-1">
                    إرسال الواجب الآن 🚀
                </button>
            </form>
        <?php endif; ?>

    </div>
</div>

</body>
</html>