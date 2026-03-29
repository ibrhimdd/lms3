<?php 
require_once '../config/db.php';
session_start();

// جلب رقم المادة من الرابط
$course_id = $_GET['id'] ?? null;
if (!$course_id) die("رقم المادة غير موجود");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $count = $_POST['token_count']; // عدد الأكواد المطلوب إنشاؤها
    
    for ($i = 0; $i < $count; $i++) {
        $new_token = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)); // كود من 8 رموز
        $sql = "INSERT INTO course_tokens (course_id, token_code) VALUES (?, ?)";
        $pdo->prepare($sql)->execute([$course_id, $new_token]);
    }
    $success = "تم إنشاء $count كود بنجاح!";
}

include '../includes/header.php';
?>

<main class="lg:mr-72 p-6 md:p-10 min-h-screen">
    <header class="mb-10 mt-16 lg:mt-0 animate__animated animate__fadeIn">
        <h2 class="text-3xl font-black text-indigo-950">إدارة أكواد التسجيل 🎫</h2>
        <p class="text-slate-500">مادة رقم: #<?= $course_id ?></p>
    </header>

    <section class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 mb-10">
        <form method="POST" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1">
                <label class="block font-bold mb-2 text-slate-700">كم عدد الأكواد التي تريد إنشاؤها؟</label>
                <input type="number" name="token_count" min="1" max="100" required 
                class="w-full bg-slate-50 p-4 rounded-2xl border-none outline-none focus:ring-2 focus:ring-indigo-500 font-bold">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-10 py-4 rounded-2xl font-black hover:scale-105 transition-all">
                توليد الأكواد 🚀
            </button>
        </form>
    </section>

    <section class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-indigo-50 text-indigo-950 font-black">
                    <th class="p-6">الكود السري</th>
                    <th class="p-6 text-center">الحالة</th>
                    <th class="p-6">المستخدم</th>
                    <th class="p-6">تاريخ الاستخدام</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php
                $stmt = $pdo->prepare("SELECT ct.*, u.full_name FROM course_tokens ct 
                                       LEFT JOIN users u ON ct.used_by_student_id = u.id 
                                       WHERE ct.course_id = ? ORDER BY ct.id DESC");
                $stmt->execute([$course_id]);
                $tokens = $stmt->fetchAll();

                foreach ($tokens as $t):
                ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="p-6 font-mono font-bold text-indigo-600 text-lg"><?= $t['token_code'] ?></td>
                    <td class="p-6 text-center">
                        <?php if($t['is_used']): ?>
                            <span class="bg-red-100 text-red-600 px-4 py-1 rounded-full text-xs font-bold">مستخدم</span>
                        <?php else: ?>
                            <span class="bg-emerald-100 text-emerald-600 px-4 py-1 rounded-full text-xs font-bold">جاهز</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-6 text-slate-600"><?= $t['full_name'] ?? '---' ?></td>
                    <td class="p-6 text-slate-400 text-sm"><?= $t['used_at'] ?: 'غير محدد' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>