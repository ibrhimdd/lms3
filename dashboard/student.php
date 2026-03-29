<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'student') {
    die("غير مصرح");
}

$student_id = $_SESSION['user']['id'];

// عدد الكورسات
$courses = $conn->query("
    SELECT COUNT(*) as total 
    FROM enrollments 
    WHERE student_id=$student_id
")->fetch_assoc()['total'];

// عدد الواجبات
$assignments = $conn->query("
    SELECT COUNT(*) as total 
    FROM submissions 
    WHERE student_id=$student_id
")->fetch_assoc()['total'];

// متوسط الدرجات
$grades = $conn->query("
    SELECT AVG(grade) as avg_grade 
    FROM submissions 
    WHERE student_id=$student_id AND grade IS NOT NULL
")->fetch_assoc()['avg_grade'];

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard الطالب</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body style="font-family: Arial; background:#f4f6f9; padding:20px;">

<h2>👋 مرحباً <?php echo $_SESSION['user']['name']; ?></h2>

<div style="display:flex; gap:20px;">

    <div style="background:#fff; padding:20px; border-radius:10px; width:200px;">
        <h3>📚 الكورسات</h3>
        <p><?php echo $courses; ?></p>
    </div>

    <div style="background:#fff; padding:20px; border-radius:10px; width:200px;">
        <h3>📝 الواجبات</h3>
        <p><?php echo $assignments; ?></p>
    </div>

    <div style="background:#fff; padding:20px; border-radius:10px; width:200px;">
        <h3>📊 المتوسط</h3>
        <p><?php echo round($grades,2); ?></p>
    </div>

</div>

<br><br>

<canvas id="myChart" width="400" height="200"></canvas>

<script>
const ctx = document.getElementById('myChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['الكورسات', 'الواجبات', 'المتوسط'],
        datasets: [{
            label: 'تحليل الأداء',
            data: [
                <?php echo $courses; ?>,
                <?php echo $assignments; ?>,
                <?php echo round($grades,2); ?>
            ],
            borderWidth: 1
        }]
    }
});
</script>

</body>
</html>