<?php
    // الاتصال بقاعدة البيانات
    require 'db.php'; 

    // التأكد من أن قاعدة البيانات تستخدم الترميز UTF-8
    $pdo->exec("SET NAMES 'utf8'");
    $pdo->exec("SET CHARACTER SET utf8");

    // البحث عن معمل أو العنوان
    $search = isset($_GET['search']) ? trim($_GET['search']) : "";
    $sql    = "SELECT * FROM labs"; 

    // لو البحث مش فارغ، نفذ الاستعلام مع التصفية
    if (!empty($search)) {
        $sql = "SELECT * FROM labs WHERE name LIKE ? OR address LIKE ?";
    }

    // تنفيذ الاستعلام
    $stmt = $pdo->prepare($sql);
    if (!empty($search)) {
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt->execute();
    }

    $labs = $stmt->fetchAll();
?>

<!-- -------------------- -->

<!DOCTYPE html>
<html lang="ar">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>بحث عن معامل التحاليل</title>
        <style>
            body { font-family: Arial, sans-serif; direction: rtl; text-align: center; }
            table { width: 80%; margin: 20px auto; border-collapse: collapse; }
            th, td { border: 1px solid black; padding: 10px; }
            th { background: #f2f2f2; }
            input, button { padding: 8px; margin: 10px; }
        </style>
    </head>
    <body>

        <h2>🔍 البحث عن معمل</h2>
        <form method="GET" action="">
            <input type="text" name="search" placeholder="ابحث عن معمل أو عنوان..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">بحث</button>
        </form>

        <table>
            <tr>
                <th>الرقم</th>
                <th>الاسم</th>
                <th>العنوان</th>
                <th>مواعيد العمل</th>
                <th>رقم الهاتف</th>
                <th>الموقع الإلكتروني</th>
                <th>خط العرض</th>
                <th>خط الطول</th>
            </tr>
            <?php if ($labs): ?>
                <?php foreach ($labs as $lab): ?>
                <tr>
                    <td><?php echo htmlspecialchars($lab['id']); ?></td>
                    <td><?php echo htmlspecialchars($lab['name']); ?></td>
                    <td><?php echo htmlspecialchars($lab['address']); ?></td>
                    <td><?php echo htmlspecialchars($lab['working_hours']); ?></td>
                    <td><?php echo htmlspecialchars($lab['phone']); ?></td>
                    <td>
                        <?php if (!empty($lab['website']) && $lab['website'] !== "غير متوفر"): ?>
                            <a href="<?php echo htmlspecialchars($lab['website']); ?>" target="_blank">زيارة الموقع</a>
                        <?php else: ?>
                            غير متوفر
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($lab['latitude']); ?></td>
                    <td><?php echo htmlspecialchars($lab['longitude']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">❌ لا توجد نتائج مطابقة</td></tr>
            <?php endif; ?>
        </table>
    </body>
</html>