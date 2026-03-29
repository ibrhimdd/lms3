<?php 
require_once '../config/db.php';
session_start();

// 1. الحماية (للطالب المسجل فقط)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); exit();
}

$lesson_id = $_GET['id'] ?? null;
if (!$lesson_id) die("الدرس غير موجود!");

// 2. جلب بيانات الدرس بالكامل
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();

if (!$lesson) die("عذراً، هذا الدرس غير متاح حالياً.");

// 3. دالة تحويل رابط يوتيوب لرابط Embed (عشان يشتغل في iframe)
function getYoutubeEmbedUrl($url) {
    $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
    $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v=)|(?:\/))([a-zA-Z0-9_-]+)/i';

    if (preg_match($longUrlRegex, $url, $matches)) {
        $youtube_id = $matches[3];
    } elseif (preg_match($shortUrlRegex, $url, $matches)) {
        $youtube_id = $matches[1];
    } else {
        return $url; // لو مش يوتيوب يرجع زي ما هو
    }
    return 'https://www.youtube.com/embed/' . $youtube_id;
}

$embed_url = getYoutubeEmbedUrl($lesson['video_url']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($lesson['lesson_title']) ?> | إبراهيم الخولي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Almarai', sans-serif; background-color: #f8fafc; }
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 2rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1); }
        .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }
    </style>
</head>
<body class="p-4 md:p-8">

<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <a href="view_course.php?id=<?= $lesson['course_id'] ?>" class="bg-white text-indigo-600 px-6 py-3 rounded-2xl font-black text-xs shadow-sm border border-slate-100 hover:scale-105 transition-all">
            ← العودة للمادة
        </a>
        <h1 class="text-xl md:text-2xl font-black text-indigo-950"><?= htmlspecialchars($lesson['lesson_title']) ?> 🎬</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 space-y-6">
            <div class="video-container bg-black">
                <?php if(!empty($lesson['video_url'])): ?>
                    <iframe src="<?= $embed_url ?>" allowfullscreen></iframe>
                <?php else: ?>
                    <div class="absolute inset-0 flex items-center justify-center text-white font-bold">لا يوجد فيديو لهذا الدرس</div>
                <?php endif; ?>
            </div>

            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
                <h3 class="font-black text-indigo-950 text-lg mb-4">عن هذا الدرس</h3>
                <p class="text-slate-600 leading-relaxed text-sm">
                    هذا الدرس مقدم من خلال منصة إبراهيم الخولي التعليمية 2026. 
                    يرجى التركيز جيداً وتحميل الملفات المرفقة للمراجعة.
                </p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-indigo-600 p-8 rounded-[2.5rem] text-white shadow-xl shadow-indigo-100">
                <h3 class="font-black mb-6">المرفقات والملفات 📂</h3>
                
                <?php if(!empty($lesson['pdf_file'])): ?>
                    <a href="../uploads/pdfs/<?= $lesson['pdf_file'] ?>" target="_blank" 
                       class="flex items-center gap-4 bg-white/10 p-4 rounded-2xl border border-white/20 hover:bg-white/20 transition-all group">
                        <div class="bg-white text-indigo-600 w-10 h-10 rounded-xl flex items-center justify-center font-black">PDF</div>
                        <div class="flex-1">
                            <p class="text-xs font-black">تحميل الملخص</p>
                            <p class="text-[9px] opacity-60">اضغط للفتح في نافذة جديدة</p>
                        </div>
                    </a>
                <?php else: ?>
                    <p class="text-xs opacity-70 font-bold italic">لا توجد ملفات PDF مرفقة بهذا الدرس.</p>
                <?php endif; ?>

                <div class="mt-8 pt-8 border-t border-white/10">
                    <p class="text-[10px] font-black uppercase opacity-50 mb-4">تعليمات:</p>
                    <ul class="text-[11px] space-y-2 opacity-80">
                        <li>• شاهد الفيديو بالكامل لتسجيل حضورك.</li>
                        <li>• قم بحل الواجب بعد الانتهاء من الدرس.</li>
                        <li>• يمكنك طرح استفساراتك في شات المادة.</li>
                    </ul>
                </div>
            </div>

            <div class="bg-white p-6 rounded-[2rem] border border-slate-100 text-center">
                <p class="text-[10px] text-slate-400 font-black mb-2 uppercase">لديك مشكلة؟</p>
                <a href="#" class="text-indigo-600 font-black text-xs hover:underline">تواصل مع الدعم الفني 🛠️</a>
            </div>
        </div>

    </div>
</div>

</body>
</html>