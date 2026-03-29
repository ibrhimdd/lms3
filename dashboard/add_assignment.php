<?php 
require_once '../config/db.php';
session_start();

// ضبط المنطقة الزمنية
date_default_timezone_set('Africa/Cairo');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php"); exit();
}

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) die("المادة غير محددة!");

$success = ""; $error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $due_date = $_POST['due_date'];
    $max_mark = $_POST['max_mark'];
    
    $file_name = null;

    // معالجة رفع ملف (اختياري من المعلم)
    if (!empty($_FILES['assignment_file']['name'])) {
        $target_dir = "../uploads/assignments/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . "_" . basename($_FILES["assignment_file"]["name"]);
        move_uploaded_file($_FILES["assignment_file"]["tmp_id"], $target_dir . $file_name);
    }

    if (!empty($title) && !empty($due_date)) {
        $stmt = $pdo->prepare("INSERT INTO assignments (course_id, title, description, due_date, max_mark, file_path) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$course_id, $title, $desc, $due_date, $max_mark, $file_name])) {
            $success = "تم إضافة التكليف بنجاح!";
            header("refresh:2;url=manage_content.php?id=" . $course_id);
        } else { $error = "حدث خطأ أثناء الحفظ."; }
    } else { $error = "يرجى ملء الحقول الأساسية."; }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة تكليف جديد | إبراهيم الخولي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 p-6">

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-[3rem] p-10 shadow-2xl border border-slate-100 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-600 rounded-bl-full opacity-10"></div>
        
        <h2 class="text-3xl font-black text-indigo-950 mb-2">إضافة تكليف جديد 📂</h2>
        <p class="text-slate-400 font-bold text-xs mb-10">أضف واجبات منزلية أو أبحاث للطلاب</p>

        <?php if($success): ?>
            <div class="bg-emerald-50 text-emerald-600 p-4 rounded-2xl mb-6 font-black text-center border border-emerald-100"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-sm font-black text-slate-700 mb-2">عنوان التكليف *</label>
                <input type="text" name="title" required placeholder="مثلاً: بحث عن برمجة الكائنات OOP" 
                class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-500 outline-none font-bold">
            </div>

            <div>
                <label class="block text-sm font-black text-slate-700 mb-2">وصف المطلوب بالتفصيل</label>
                <textarea name="description" rows="4" class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-500 outline-none font-bold"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-black text-slate-700 mb-2">آخر موعد للتسليم *</label>
                    <input type="datetime-local" name="due_date" required class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-500 outline-none font-bold">
                </div>

                <div>
                    <label class="block text-sm font-black text-slate-700 mb-2">الدرجة القصوى</label>
                    <input type="number" name="max_mark" value="10" class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-500 outline-none font-black text-center">
                </div>
            </div>

            <div class="border-2 border-dashed border-slate-200 p-8 rounded-[2rem] text-center hover:border-indigo-400 transition-all cursor-pointer relative">
                <input type="file" name="assignment_file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                <div class="text-slate-400">
                    <p class="font-black text-sm">اسحب وأفلت ملف التعليمات هنا (اختياري)</p>
                    <p class="text-[10px] mt-1">PDF, DOCX, JPG (Max 5MB)</p>
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white p-5 rounded-[2rem] font-black text-xl shadow-xl hover:bg-slate-900 transition-all transform hover:-translate-y-1">
                نشر التكليف للطلاب 🚀
            </button>
        </form>
    </div>
</div>

</body>
</html>