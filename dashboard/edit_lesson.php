<?php 
require_once '../config/db.php';
session_start();

// 1. الحماية (للمعلم والأدمن فقط)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php"); exit();
}

$lesson_id = $_GET['id'] ?? null;
if (!$lesson_id) die("الدرس غير محدد!");

// 2. جلب بيانات الدرس الحالية قبل أي تعديل
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();

if (!$lesson) die("الدرس غير موجود!");

$course_id = $lesson['course_id']; // نحتاجه للرجوع
$success = "";
$error = "";

// 3. معالجة التحديث عند إرسال الفورم
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $video = $_POST['video_url'];
    $pdf_name = $lesson['pdf_file']; // الاحتفاظ بالملف القديم كافتراضي

    // إذا اختار المدرس ملف PDF جديد
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
        $target_dir = "../uploads/pdfs/";
        $file_ext = pathinfo($_FILES["pdf_file"]["name"], PATHINFO_EXTENSION);
        $new_pdf_name = time() . "_" . uniqid() . "." . $file_ext;
        
        if (move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $target_dir . $new_pdf_name)) {
            // مسح الملف القديم من السيرفر لتوفير المساحة (اختياري)
            if ($pdf_name && file_exists($target_dir . $pdf_name)) {
                unlink($target_dir . $pdf_name);
            }
            $pdf_name = $new_pdf_name;
        }
    }

    // تحديث البيانات في الجدول
    $update = $pdo->prepare("UPDATE lessons SET lesson_title = ?, video_url = ?, pdf_file = ? WHERE id = ?");
    if ($update->execute([$title, $video, $pdf_name, $lesson_id])) {
        $success = "تم تحديث الدرس بنجاح! ✨";
        // تحديث متغير الدرس لعرض البيانات الجديدة في الحقول فوراً
        $lesson['lesson_title'] = $title;
        $lesson['video_url'] = $video;
        $lesson['pdf_file'] = $pdf_name;
    } else {
        $error = "فشل التحديث، تأكد من الاتصال بقاعدة البيانات.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل درس: <?= htmlspecialchars($lesson['lesson_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen p-6">

<div class="max-w-3xl mx-auto">
    <a href="manage_content.php?id=<?= $course_id ?>" class="inline-block mb-6 text-indigo-600 font-black text-sm">← إلغاء والرجوع للمادة</a>

    <div class="bg-white rounded-[3rem] p-10 shadow-2xl shadow-indigo-100 border border-slate-100">
        <div class="mb-8">
            <span class="bg-amber-100 text-amber-700 px-4 py-1 rounded-full text-[10px] font-black uppercase">وضع التعديل</span>
            <h2 class="text-3xl font-black text-indigo-950 mt-3">تعديل بيانات الدرس ✏️</h2>
        </div>

        <?php if($success): ?>
            <div class="bg-emerald-50 text-emerald-600 p-5 rounded-2xl mb-6 font-black text-center border border-emerald-100"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="bg-rose-50 text-rose-600 p-5 rounded-2xl mb-6 font-black text-center border border-rose-100"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <div>
                <label class="block text-sm font-black text-indigo-950 mb-2">عنوان الدرس</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($lesson['lesson_title']) ?>"
                class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-600 outline-none transition-all font-bold">
            </div>

            <div>
                <label class="block text-sm font-black text-indigo-950 mb-2">رابط الفيديو (YouTube)</label>
                <input type="url" name="video_url" value="<?= htmlspecialchars($lesson['video_url']) ?>"
                class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-600 outline-none transition-all text-left font-mono text-sm" dir="ltr">
            </div>

            <div>
                <label class="block text-sm font-black text-indigo-950 mb-2">تغيير ملف الـ PDF (اختياري)</label>
                <?php if($lesson['pdf_file']): ?>
                    <p class="text-[10px] text-emerald-600 font-black mb-2 italic">📄 يوجد ملف حالي: <?= $lesson['pdf_file'] ?></p>
                <?php endif; ?>
                <div class="relative">
                    <input type="file" name="pdf_file" accept=".pdf"
                    class="w-full p-4 bg-indigo-50/50 rounded-2xl border-2 border-dashed border-indigo-200 file:hidden text-indigo-600 font-bold cursor-pointer">
                    <span class="absolute left-4 top-4 text-xs text-indigo-400 font-black">اختر ملف جديد لاستبدال القديم</span>
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white p-6 rounded-[2rem] font-black text-xl shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all">
                حفظ التغييرات الآن ✅
            </button>
        </form>
    </div>
</div>

</body>
</html>