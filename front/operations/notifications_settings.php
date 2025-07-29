<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات التنبيهات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body dir="rtl" class="p-4">

<div class="container">
    <h2>🔔 إعدادات التنبيهات</h2>

    <div class="alert alert-info mt-4">
        <p>للحصول على إشعارات دقيقة للتذكير بالأدوية:</p>
        <ul>
            <li>📱 يُفضل تحميل التطبيق من الرابط التالي:</li>
        </ul>
        <a href="link-to-your-app-download" class="btn btn-primary mt-2" target="_blank">⬇️ تحميل التطبيق</a>
    </div>

    <a href="../profile.php" class="btn btn-secondary">الرجوع</a>
</div>

</body>
</html>
