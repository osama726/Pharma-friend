<?php
    // الاتصال بقاعدة البيانات
    require 'db.php'; 

    // التأكد من أن قاعدة البيانات تستخدم الترميز UTF-8
    $pdo->exec("SET NAMES 'utf8'");
    $pdo->exec("SET CHARACTER SET utf8");

    // البحث عن المستشفى أو العنوان
    $search = isset($_GET['search']) ? trim($_GET['search']) : "";
    $sql    = "SELECT * FROM Hospitals"; 

    // لو البحث مش فارغ، نفذ الاستعلام مع التصفية
    if (!empty($search)) {
        $sql = "SELECT * FROM Hospitals WHERE name LIKE ? OR address LIKE ?";
    }

    // تنفيذ الاستعلام
    $stmt = $pdo->prepare($sql);
    if (!empty($search)) {
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt->execute();
    }

    $hospitals = $stmt->fetchAll();
?>

<!-- -------------------- -->

<!DOCTYPE html>
<html lang="ar">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>بحث عن مستشفى</title>
        <style>
            body { font-family: Arial, sans-serif; direction: rtl; text-align: center; }
            table { width: 80%; margin: 20px auto; border-collapse: collapse; }
            th, td { border: 1px solid black; padding: 10px; }
            th { background: #f2f2f2; }
            input, button { padding: 8px; margin: 10px; }
        </style>
    </head>
    <body>

        <h2>🔍 البحث عن مستشفى</h2>
        <form method="GET" action="">
            <input type="text" name="search" placeholder="ابحث عن مستشفى أو عنوان..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">بحث</button>
        </form>

        <table>
            <tr>
                <th>رقم</th>
                <th>الاسم</th>
                <th>العنوان</th>
                <th>الاقسام</th>
                <th>الهاتف</th>
                <th>مواعيد العمل</th>
                <th>خط العرض</th>
                <th>خط الطول</th>
            </tr>
            <?php if ($hospitals): ?>
                <?php foreach ($hospitals as $hospital): ?>
                <tr>
                    <td><?= htmlspecialchars($hospital['id']) ?></td>
                    <td><?= htmlspecialchars($hospital['name']); ?></td>
                    <td><?= htmlspecialchars($hospital['address']); ?></td>
                    <td><?= htmlspecialchars($hospital['Sections']); ?></td>
                    <td><?= htmlspecialchars($hospital['phone']); ?></td>
                    <td><?= htmlspecialchars($hospital['Working_hours']); ?></td>
                    <td><?= htmlspecialchars($hospital['latitude']); ?></td>
                    <td><?= htmlspecialchars($hospital['longitude']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">❌ لا توجد نتائج مطابقة</td></tr>
            <?php endif; ?>
        </table>

    </body>
</html>
