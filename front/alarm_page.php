<?php
session_start();
require_once '../pharma_db/db.php'; // ملف الاتصال بقاعدة البيانات باستخدام PDO

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// -------------------------
// عند إرسال النموذج
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $medicine_name = trim($_POST['medicine_name'] ?? '');

if (empty($medicine_name)) {
    $_SESSION['error'] = "<p style='text-align: center;'>يرجى كتابة واختيار اسم الدواء من القائمة.</p>";
    header("Location: alarm_page.php");
    exit;
}

// ابحث عن ID بناءً على الاسم
$stmt = $pdo->prepare("SELECT id FROM medicines WHERE name = ?");
$stmt->execute([$medicine_name]);
$medicine = $stmt->fetch();

if (!$medicine) {
    $_SESSION['error'] = "<p style='text-align: center;'>⚠️ الدواء غير موجود في قاعدة البيانات. الرجاء اختيار اسم صحيح.</p>";
    header("Location: alarm_page.php");
    exit;
}

$medicine_id = $medicine['id'];


    $dosage = $_POST['interval_hours'] . ' ساعات';
    $frequency = $_POST['times_per_day'];
    $start_time = $_POST['start_time'];

    try {
        $stmt = $pdo->prepare("INSERT INTO user_medications (user_id, medicine_id, dosage, frequency, start_time)
                            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $medicine_id, $dosage, $frequency, $start_time]);

        $_SESSION['success_medication'] = "✅ تم إضافة التذكير بنجاح! لتنشيط إشعارات التذكير بكفاءة، يُفضل تحميل التطبيق من هنا: <a href='link-to-your-app-download' class='btn btn-sm btn-primary' target='_blank'>⬇️ تحميل التطبيق</a>";
        header("Location: profile.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "<p style='text-align: center;'>❌ حدث خطأ أثناء حفظ التذكير: </p>" . $e->getMessage();
        header("Location: alarm_page.php");
        exit;
    }
}

// -------------------------
// جلب قائمة الأدوية لعرضها في الـ select
// -------------------------
$stmt = $pdo->query("SELECT id, name FROM medicines ORDER BY name");
$all_medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------------------------
// جلب الأدوية المرتبطة بالمستخدم لعرض الإشعارات
// -------------------------
$stmt = $pdo->prepare("
    SELECT m.name, um.start_time, um.frequency
    FROM user_medications um
    JOIN medicines m ON um.medicine_id = m.id
    WHERE um.user_id = ?
");
$stmt->execute([$user_id]);
$medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$medications_json = json_encode($medications);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منبه الدواء</title>
    <link rel="stylesheet" href="css/alarm.css">
</head>
<body>

<div class="container-alarm">
    <h1>منبه الدواء 💊</h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

<form id="reminderForm" action="alarm_page.php" method="POST">
    <label for="medicine_name">اسم الدواء:</label>
    <input type="text" id="medicine_name" name="medicine_name" list="medicines" required>
    <datalist id="medicines">
        <?php foreach ($all_medicines as $med): ?>
            <option value="<?= htmlspecialchars($med['name']) ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <label for="start_time">موعد أول جرعة:</label>
    <input type="datetime-local" id="start_time" name="start_time" required>

    <label for="interval_hours">كل كام ساعة (الفاصل الزمني):</label>
    <input type="number" id="interval_hours" name="interval_hours" min="1" required>

    <label for="times_per_day">عدد المرات في اليوم:</label>
    <input type="number" id="times_per_day" name="times_per_day" min="1" max="24" required>

    <button type="submit">💾 حفظ التذكير</button>
</form>

</div>
</body>
</html>
