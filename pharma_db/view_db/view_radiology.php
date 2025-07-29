<?php
    require 'db.php';

    // ضبط الترميز
    $pdo->exec("SET NAMES 'utf8'");
    $pdo->exec("SET CHARACTER SET utf8");

    // استلام كلمة البحث إن وجدت
    $search = isset($_GET['search']) ? trim($_GET['search']) : "";
    $sql = "SELECT * FROM radiology_centers";

    // لو في بحث، نعدّل الاستعلام
    if (!empty($search)) {
        $sql .= " WHERE name LIKE ? OR address LIKE ?";
    }

    $stmt = $pdo->prepare($sql);

    if (!empty($search)) {
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt->execute();
    }

    $results = $stmt->fetchAll();
?>

<!-- -------------------- -->

<!DOCTYPE html>
<html lang="ar">
    <head>
        <meta charset="UTF-8">
        <title>مراكز الأشعة</title>
        <style>
            body { font-family: Arial; direction: rtl; text-align: center; background-color: #f9f9f9; }
            table { width: 90%; margin: 20px auto; border-collapse: collapse; background: white; }
            th, td { border: 1px solid #ccc; padding: 10px; }
            th { background-color: #eee; }
            input, button { padding: 8px 12px; margin: 10px; }
            a { color: #007BFF; text-decoration: none; }
        </style>
    </head>
    <body>

        <h2>📋 قائمة مراكز الأشعة</h2>

        <form method="GET">
            <input type="text" name="search" placeholder="ابحث بالاسم أو العنوان..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">بحث</button>
        </form>

        <table>
            <tr>
                <th>الرقم</th>
                <th>الاسم</th>
                <th>العنوان</th>
                <th>مواعيد العمل</th>
                <th>خدمة التوصيل</th>
                <th>رقم الهاتف</th>
                <th>الموقع الإلكتروني</th>
                <th>خط العرض</th>
                <th>خط الطول</th>
            </tr>

            <?php if ($results): ?>
                <?php foreach ($results as $center): ?>
                <tr>
                    <td><?= htmlspecialchars($center['id']) ?></td>
                    <td><?= htmlspecialchars($center['name']) ?></td>
                    <td><?= htmlspecialchars($center['address']) ?></td>
                    <td><?= htmlspecialchars($center['working_hours']) ?></td>
                    <td><?= htmlspecialchars($center['delivery_service']) ?></td>
                    <td><?= htmlspecialchars($center['phone']) ?></td>
                    <td>
                        <?php if (!empty($center['website']) && $center['website'] !== 'غير متوفر'): ?>
                            <a href="<?= htmlspecialchars($center['website']) ?>" target="_blank">زيارة الموقع</a>
                        <?php else: ?>
                            غير متوفر
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($center['latitude']) ?></td>
                    <td><?= htmlspecialchars($center['longitude']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9">❌ لا توجد نتائج</td></tr>
            <?php endif; ?>
        </table>

    </body>
</html>
