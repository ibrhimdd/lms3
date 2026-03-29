<?php 
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php"); exit();
}

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) die("المادة غير محددة!");

$success = ""; $error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['exam_title'];
    $duration = $_POST['duration'];
    $attempts = $_POST['attempts'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $exam_date = date('Y-m-d');

    if (!empty($title) && !empty($start) && !empty($end)) {
        $stmt = $pdo->prepare("INSERT INTO exams (course_id, exam_title, exam_date, duration_minutes, attempts_count, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$course_id, $title, $exam_date, $duration, $attempts, $start, $end])) {
            $new_exam_id = $pdo->lastInsertId();
            $success = "تم إنشاء الامتحان بنجاح! جاري تحويلك لإضافة الأسئلة...";
            header("refresh:2;url=add_questions.php?exam_id=" . $new_exam_id);
        } else { $error = "حدث خطأ أثناء الحفظ."; }
    } else { $error = "يرجى تحديد مواعيد البداية والنهاية."; }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إعداد امتحان احترافي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen p-4 md:p-10">

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-[3rem] p-8 md:p-12 shadow-2xl border border-slate-100">
        <h2 class="text-3xl font-black text-indigo-950 mb-8 border-r-8 border-rose-500 pr-4">إعدادات الامتحان المتقدمة ⏱️</h2>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-black text-slate-700 mb-2">اسم الامتحان</label>
                <input type="text" name="exam_title" required class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-rose-500 outline-none font-bold">
            </div>

            <div>
                <label class="block text-sm font-black text-slate-700 mb-2">تاريخ ووقت البدء</label>
                <input type="datetime-local" name="start_time" required class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-rose-500 outline-none font-bold">
            </div>

            <div>
                <label class="block text-sm font-black text-slate-700 mb-2">تاريخ ووقت الإغلاق</label>
                <input type="datetime-local" name="end_time" required class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-rose-500 outline-none font-bold">
            </div>

            <div>
                <label class="block text-sm font-black text-slate-700 mb-2">مدة الحل (بالدقائق)</label>
                <input type="number" name="duration" value="30" class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-rose-500 outline-none font-black text-center">
            </div>

            <div>
                <label class="block text-sm font-black text-slate-700 mb-2">عدد محاولات الدخول</label>
                <select name="attempts" class="w-full p-4 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-rose-500 outline-none font-black">
                    <option value="1">محاولة واحدة فقط</option>
                    <option value="2">محاولتان</option>
                    <option value="3">3 محاولات</option>
                    <option value="999">غير محدود</option>
                </select>
            </div>

            <button type="submit" class="md:col-span-2 bg-slate-900 text-white p-6 rounded-[2rem] font-black text-xl shadow-xl hover:bg-rose-600 transition-all mt-4">
                حفظ وإضافة الأسئلة ➔
            </button>
        </form>
    </div>
</div>

</body>
</html>