<?php 
require_once '../config/db.php';
session_start();

// الحماية
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php"); exit();
}

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) die("المادة غير محددة!");

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $video = $_POST['video_url'];
    $pdf_name = null;

    // معالجة رفع ملف الـ PDF
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
        $target_dir = "../uploads/pdfs/";
        // التأكد من وجود المجلد
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_ext = pathinfo($_FILES["pdf_file"]["name"], PATHINFO_EXTENSION);
        $pdf_name = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $pdf_name;

        if (!move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $target_file)) {
            $error = "فشل في رفع ملف الـ PDF.";
        }
    }

    if (empty($error)) {
        // الاستعلام باستخدام أسماء أعمدتك: id, course_id, lesson_title, video_url, pdf_file
        $stmt = $pdo->prepare("INSERT INTO lessons (course_id, lesson_title, video_url, pdf_file) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$course_id, $title, $video, $pdf_name])) {
            $success = "تمت إضافة الدرس بنجاح! 🚀";
        } else {
            $error = "حدث خطأ في قاعدة البيانات.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة درس جديد | إبراهيم الخولي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen p-6">

<div class="max-w-3xl mx-auto">
    <a href="manage_content.php?id=<?= $course_id ?>" class="inline-block mb-6 text-indigo-600 font-black text-sm">← الرجوع للمادة</a>

    <div class="bg-white rounded-[3rem] p-10 shadow-2xl shadow-indigo-100 border border-slate-100">
        <div class="mb-8">
            <h2 class="text-3xl font-black text-indigo-950">إضافة درس جديد 📚</h2>
            <p class="text-slate-400 font-bold text-xs mt-2 uppercase tracking-widest">نظام إبراهيم الخولي 2026</p>
        </div>

        <?php if($success): ?>
            <div class="bg-emerald-50 text-emerald-600 p-5 rounded-2xl mb-6 font-black text-center border border-emerald-100"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="bg-rose-50 text-rose-600 p-5 rounded-2xl mb-6 font-black text-center border border-rose-100"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <div>
                <label class="block text-sm font-black text-indigo-950 mb-2">عنوان الدرس *</label>
                <input type="text" name="title" required placeholder="مثلاً: المحاضرة الأولى - أساسيات البرمجة" 
                class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-600 outline-none transition-all font-bold">
            </div>

            <div>
                <label class="block text-sm font-black text-indigo-950 mb-2">رابط فيديو الشرح (يوتيوب)</label>
                <input type="url" name="video_url" placeholder="https://www.youtube.com/watch?v=..." 
                class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-600 outline-none transition-all text-left font-mono text-sm" dir="ltr">
            </div>

            <div>
                <label class="block text-sm font-black text-indigo-950 mb-2">ملف الـ PDF (ملخص الدرس)</label>
                <div class="relative">
                    <input type="file" name="pdf_file" accept=".pdf"
                    class="w-full p-4 bg-indigo-50/50 rounded-2xl border-2 border-dashed border-indigo-200 file:hidden text-indigo-600 font-bold cursor-pointer">
                    <span class="absolute left-4 top-4 text-xs text-indigo-400 font-black">اضغط لاختيار ملف</span>
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white p-6 rounded-[2rem] font-black text-xl shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all">
                نشر الدرس الآن 🚀
            </button>
        </form>
    </div>
</div>

</body>
</html>