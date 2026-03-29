<?php
session_start();
include '../config/db.php';

if ($_SESSION['user']['role'] != 'teacher') {
    die("غير مصرح");
}

$course_id = $_GET['course_id'];

function generateCode($length = 8) {
    return substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $count = $_POST['count'];

    for ($i = 0; $i < $count; $i++) {
        $code = generateCode();

        $conn->query("INSERT INTO course_codes (course_id, code)
                      VALUES ('$course_id', '$code')");
    }

    echo "تم إنشاء الأكواد";
}
?>

<h2>توليد أكواد</h2>

<form method="POST">
    <input type="number" name="count" placeholder="عدد الأكواد" required><br>
    <button type="submit">توليد</button>
</form>
<?php
$result = $conn->query("SELECT * FROM course_codes WHERE course_id=$course_id");

while ($row = $result->fetch_assoc()) {
    echo $row['code'];

    if ($row['used']) {
        echo " ❌ مستخدم";
    } else {
        echo " ✅ متاح";
    }

    echo "<br>";
}
?>