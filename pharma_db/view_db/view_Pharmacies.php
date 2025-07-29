<?php
    // الاتصال بقاعدة البيانات
    require 'db.php'; 

    // التأكد من أن قاعدة البيانات تستخدم الترميز UTF-8
    $pdo->exec("SET NAMES 'utf8'");
    $pdo->exec("SET CHARACTER SET utf8");

    // البحث عن صيدلية أو العنوان
    $search = isset($_GET['search']) ? trim($_GET['search']) : "";
    $sql    = "SELECT * FROM pharmacies"; 

    // لو البحث مش فارغ، نفذ الاستعلام مع التصفية
    if (!empty($search)) {
        $sql = "SELECT * FROM pharmacies WHERE name LIKE ? OR address LIKE ?";
    }

    // تنفيذ الاستعلام
    $stmt = $pdo->prepare($sql);
    if (!empty($search)) {
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt->execute();
    }

    $pharmacies = $stmt->fetchAll();
?>

<!-- -------------------- -->

<!DOCTYPE html>
<html lang="ar">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>بحث عن الصيدليات</title>
        <style>
            body { font-family: Arial, sans-serif; direction: rtl; text-align: center; }
            table { width: 80%; margin: 20px auto; border-collapse: collapse; }
            th, td { border: 1px solid black; padding: 10px; }
            th { background: #f2f2f2; }
            input, button { padding: 8px; margin: 10px; }
        </style>
    </head>
    <body>

        <h2>🔍 البحث عن صيدلية</h2>
        <form method="GET" action="">
            <input type="text" name="search" placeholder="ابحث عن صيدلية أو عنوان..." value="<?= htmlspecialchars($search) ?>">
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
                <th>الفيس بوك</th>
                <th>خط العرض</th>
                <th>خط الطول</th>
            </tr>
            <?php if ($pharmacies): ?>
                <?php foreach ($pharmacies as $pharmacy): ?>
                <tr>
                    <td><?php echo htmlspecialchars($pharmacy['id']); ?></td>
                    <td><?php echo htmlspecialchars($pharmacy['name']); ?></td>
                    <td><?php echo htmlspecialchars($pharmacy['address']); ?></td>
                    <td><?php echo htmlspecialchars($pharmacy['working_hours']); ?></td>
                    <td><?php echo htmlspecialchars($pharmacy['delivery_service']); ?></td>
                    <td><?php echo htmlspecialchars($pharmacy['phone_numbers']); ?></td>
                    <td>
                        <?php if (!empty($pharmacy['website']) && $pharmacy['website'] !== "غير متوفر"): ?>
                            <a href="<?php echo htmlspecialchars($pharmacy['website']); ?>" target="_blank">زيارة الموقع</a>
                        <?php else: ?>
                            غير متوفر
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($pharmacy['facebook']) && $pharmacy['facebook'] !== "غير متوفر"): ?>
                            <a href="<?php echo htmlspecialchars($pharmacy['facebook']); ?>" target="_blank">زيارة الموقع</a>
                        <?php else: ?>
                            غير متوفر
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($pharmacy['latitude']); ?></td>
                    <td><?php echo htmlspecialchars($pharmacy['longitude']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="10">❌ لا توجد نتائج مطابقة</td></tr>
            <?php endif; ?>
        </table>
    </body>
</html>