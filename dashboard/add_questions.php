<?php 
require_once '../config/db.php';
session_start();

// الحماية
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php"); exit();
}

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) die("الامتحان غير محدد!");

// جلب بيانات الامتحان للتأكد من وجوده وعرض اسمه
$exam_stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$exam_stmt->execute([$exam_id]);
$exam = $exam_stmt->fetch();

$success = "";
$error = "";

// معالجة إضافة السؤال
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = $_POST['question_text'];
    $a = $_POST['option_a'];
    $b = $_POST['option_b'];
    $c = $_POST['option_c'];
    $d = $_POST['option_d'];
    $correct = $_POST['correct_option'];
    $mark = $_POST['question_mark']; // الدرجة اللي طلبتها

    if (!empty($question_text)) {
        $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, question_mark) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$exam_id, $question_text, $a, $b, $c, $d, $correct, $mark])) {
            $success = "تم إضافة السؤال بنجاح! يمكنك إضافة سؤال آخر.";
        } else {
            $error = "حدث خطأ أثناء حفظ السؤال.";
        }
    }
}

// جلب الأسئلة المضافة حالياً لعرضها في الأسفل
$current_questions = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id DESC");
$current_questions->execute([$exam_id]);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة أسئلة: <?= htmlspecialchars($exam['exam_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 p-6">

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-black text-indigo-950 italic">بناء أسئلة امتحان: <?= htmlspecialchars($exam['exam_title']) ?></h2>
        <a href="manage_content.php?id=<?= $exam['course_id'] ?>" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-xs font-black shadow-lg shadow-indigo-100">إنهاء وحفظ</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 h-fit">
            <h3 class="font-black text-rose-500 mb-6 border-r-4 border-rose-500 pr-3">إضافة سؤال جديد</h3>
            
            <?php if($success): ?>
                <div class="bg-emerald-50 text-emerald-600 p-4 rounded-2xl mb-4 text-xs font-bold text-center border border-emerald-100"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 mb-2">نص السؤال</label>
                    <textarea name="question_text" required rows="2" class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-600 outline-none transition-all text-sm font-bold"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <input type="text" name="option_a" required placeholder="اختيار A" class="p-3 bg-slate-50 rounded-xl border border-slate-100 outline-none text-xs">
                    <input type="text" name="option_b" required placeholder="اختيار B" class="p-3 bg-slate-50 rounded-xl border border-slate-100 outline-none text-xs">
                    <input type="text" name="option_c" required placeholder="اختيار C" class="p-3 bg-slate-50 rounded-xl border border-slate-100 outline-none text-xs">
                    <input type="text" name="option_d" required placeholder="اختيار D" class="p-3 bg-slate-50 rounded-xl border border-slate-100 outline-none text-xs">
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 mb-2">الإجابة الصحيحة</label>
                        <select name="correct_option" class="w-full p-3 bg-indigo-50 rounded-xl font-black text-indigo-600 outline-none border-none">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 mb-2">درجة السؤال 🎯</label>
                        <input type="number" name="question_mark" value="1" min="1" class="w-full p-3 bg-rose-50 rounded-xl font-black text-rose-600 outline-none text-center">
                    </div>
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white p-4 rounded-2xl font-black text-sm mt-4 hover:bg-indigo-600 transition-all">إضافة السؤال للبنك</button>
            </form>
        </div>

        <div class="space-y-4">
            <h3 class="font-black text-indigo-950 mb-2">الأسئلة الحالية (<?= $current_questions->rowCount() ?>)</h3>
            <?php while($q = $current_questions->fetch()): ?>
                <div class="bg-white p-5 rounded-3xl border border-slate-100 relative">
                    <span class="absolute left-4 top-4 bg-rose-50 text-rose-600 px-2 py-1 rounded-lg text-[10px] font-black"><?= $q['question_mark'] ?> درجة</span>
                    <p class="font-bold text-sm text-indigo-900 ml-12"><?= htmlspecialchars($q['question_text']) ?></p>
                    <div class="mt-3 flex gap-2 flex-wrap">
                        <span class="text-[10px] font-bold text-slate-400">صح: (<?= $q['correct_option'] ?>)</span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

    </div>
</div>

</body>
</html>