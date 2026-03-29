<?php 
require_once '../config/db.php';
session_start();

// ضبط المنطقة الزمنية لضمان دقة التقارير
date_default_timezone_set('Africa/Cairo');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php"); exit();
}

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) die("الامتحان غير محدد!");

// 1. جلب بيانات الامتحان الأساسية
$exam_stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$exam_stmt->execute([$exam_id]);
$exam = $exam_stmt->fetch();

if (!$exam) die("الامتحان غير موجود!");

// 2. تحليل الأسئلة (نسبة النجاح في كل سؤال)
$q_analysis_stmt = $pdo->prepare("
    SELECT 
        q.question_text, 
        q.question_mark,
        COUNT(sa.id) as total_attempts,
        SUM(sa.is_correct) as correct_count
    FROM questions q
    LEFT JOIN student_answers sa ON q.id = sa.question_id
    WHERE q.exam_id = ?
    GROUP BY q.id
");
$q_analysis_stmt->execute([$exam_id]);
$questions_report = $q_analysis_stmt->fetchAll();

// 3. تقرير الطلاب المحدث (تم إضافة student_id وجلب آخر محاولة فقط لكل طالب)
$students_stmt = $pdo->prepare("
    SELECT 
        u.id AS sid, 
        u.full_name, 
        er.score, 
        er.total_marks, 
        er.submitted_at,
        (SELECT COUNT(*) FROM exam_results WHERE student_id = u.id AND exam_id = ?) as attempts_made
    FROM exam_results er
    JOIN users u ON er.student_id = u.id
    WHERE er.exam_id = ?
    AND er.id IN (SELECT MAX(id) FROM exam_results WHERE exam_id = ? GROUP BY student_id)
    ORDER BY er.score DESC
");
$students_stmt->execute([$exam_id, $exam_id, $exam_id]);
$students_report = $students_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقارير الامتحان | إبراهيم الخولي</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Almarai', sans-serif; }</style>
</head>
<body class="bg-slate-50 p-6 md:p-12">

<div class="max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4">
        <div>
            <h1 class="text-3xl font-black text-indigo-950 italic">إحصائيات: <?= htmlspecialchars($exam['exam_title']) ?> 📊</h1>
            <p class="text-slate-400 font-bold text-xs mt-1 uppercase tracking-widest">لوحة تحكم المعلم | 2026</p>
        </div>
        <a href="manage_content.php?id=<?= $exam['course_id'] ?>" class="bg-white text-indigo-600 px-8 py-3 rounded-2xl font-black text-xs shadow-sm border border-slate-100">رجوع للمادة</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-indigo-600 p-8 rounded-[2.5rem] text-white shadow-xl shadow-indigo-100">
            <p class="text-[10px] font-black uppercase opacity-60">إجمالي الممتحنين</p>
            <h4 class="text-4xl font-black mt-2"><?= count($students_report) ?> طالب</h4>
        </div>
        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm text-center">
            <p class="text-[10px] text-slate-400 font-black uppercase">وقت الامتحان</p>
            <h4 class="text-4xl font-black text-indigo-950 mt-2"><?= $exam['duration_minutes'] ?> د</h4>
        </div>
        <div class="bg-rose-500 p-8 rounded-[2.5rem] text-white shadow-xl shadow-rose-100">
            <p class="text-[10px] font-black uppercase opacity-60">تاريخ البدء</p>
            <h4 class="text-xl font-black mt-2"><?= date('Y-m-d', strtotime($exam['start_time'])) ?></h4>
        </div>
    </div>

    <div class="bg-white rounded-[3rem] p-8 md:p-10 border border-slate-100 shadow-sm mb-10">
        <h3 class="text-xl font-black text-indigo-950 mb-8 border-r-4 border-indigo-600 pr-3">تحليل أداء الأسئلة 🧠</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach($questions_report as $q): 
                $success_rate = ($q['total_attempts'] > 0) ? round(($q['correct_count'] / $q['total_attempts']) * 100) : 0;
            ?>
                <div class="p-6 rounded-[2rem] bg-slate-50 border border-slate-100 group hover:border-indigo-300 transition-all">
                    <p class="text-sm font-bold text-indigo-900 mb-4"><?= htmlspecialchars($q['question_text']) ?></p>
                    <div class="flex items-center gap-4">
                        <div class="flex-1 bg-slate-200 h-3 rounded-full overflow-hidden">
                            <div class="h-full <?= $success_rate > 50 ? 'bg-emerald-500' : 'bg-rose-500' ?>" style="width: <?= $success_rate ?>%"></div>
                        </div>
                        <span class="text-xs font-black <?= $success_rate > 50 ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $success_rate ?>% صح</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white rounded-[3rem] overflow-hidden border border-slate-100 shadow-sm">
        <div class="p-8 border-b border-slate-50">
            <h3 class="text-xl font-black text-indigo-950 italic">قائمة نتائج الطلاب 👥</h3>
        </div>
        <table class="w-full text-right">
            <thead>
                <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    <th class="p-6">الطالب</th>
                    <th class="p-6 text-center">الدرجة</th>
                    <th class="p-6 text-center">المحاولات</th>
                    <th class="p-6">وقت التسليم</th>
                    <th class="p-6 text-center">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php foreach($students_report as $student): 
                    $pass = ($student['score'] / $student['total_marks'] >= 0.5);
                ?>
                <tr class="hover:bg-slate-50/50 transition-all">
                    <td class="p-6 font-black text-indigo-950">
                        <a href="student_exam_detail.php?student_id=<?= $student['sid'] ?>&exam_id=<?= $exam_id ?>" 
                           class="text-indigo-600 hover:text-rose-500 underline decoration-indigo-200 decoration-2 underline-offset-4 transition-all">
                            <?= htmlspecialchars($student['full_name']) ?>
                        </a>
                    </td>
                    
                    <td class="p-6 text-center font-bold text-slate-600"><?= $student['score'] ?> / <?= $student['total_marks'] ?></td>
                    
                    <td class="p-6 text-center">
                        <span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-lg text-[10px] font-black italic">
                            <?= $student['attempts_made'] ?> محاولات
                        </span>
                    </td>
                    
                    <td class="p-6 text-xs text-slate-400 font-bold">
                        <?= !empty($student['submitted_at']) ? date('H:i | Y-m-d', strtotime($student['submitted_at'])) : 'تم التسليم' ?>
                    </td>
                    
                    <td class="p-6 text-center">
                        <span class="<?= $pass ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' ?> px-4 py-1 rounded-full text-[10px] font-black uppercase shadow-sm">
                            <?= $pass ? 'ناجح ✓' : 'راسب ✗' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>