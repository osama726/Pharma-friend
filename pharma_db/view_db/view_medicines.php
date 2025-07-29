<?php
    require 'db.php'; // ملف الاتصال بقاعدة البيانات

    // ضبط الترميز على UTF-8 لضمان عرض البيانات بشكل صحيح
    $pdo->exec("SET NAMES 'utf8'");
    $pdo->exec("SET CHARACTER SET 'utf8'");

    $search = isset($_GET['search']) ? trim($_GET['search']) : "";
    $sql = "SELECT * FROM medicines";

    // إذا كانت كلمة البحث غير فارغة، تنفيذ الاستعلام مع التصفية على عمودي الاسم والمادة الفعالة
    if (!empty($search)) {
        $sql = "SELECT * FROM medicines  WHERE name LIKE ? OR active_ingredient LIKE ? OR Indications_for_use LIKE ?";
    }

    $stmt = $pdo->prepare($sql);
    if (!empty($search)) {
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $stmt->execute();
    }

    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- -------------------- -->

<!DOCTYPE html>
<html lang="ar">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>قائمة الأدوية</title>
        <style>
            body { font-family: Arial, sans-serif; direction: rtl; text-align: center; }
            table { width: 80%; margin: 20px auto; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 10px; }
            th { background: #f2f2f2; }
            input, button { padding: 8px; margin: 10px; }
        </style>
    </head>
    <body>

        <h2>🔍 قائمة الأدوية</h2>
        <form method="GET" action="">
            <input type="text" name="search" placeholder="ابحث عن دواء أو مادة فعالة..." value="<?= htmlspecialchars($search); ?>">
            <button type="submit">بحث</button>
        </form>
        
        <table>
            <tr>
                <th>الرقم</th>
                <th>الاسم</th>
                <th>المادة الفعالة</th>
                <th>دواعي الاستخدام</th>
                <th>الجرعة</th>
                <th>التحذيرات</th>
            </tr>
            <?php if ($medicines): ?>
                <?php foreach ($medicines as $index => $med): ?>
                    <tr>
                        <td><?= htmlspecialchars($med['id']); ?></td>
                        <td><?= htmlspecialchars($med['name']); ?></td>
                        <td><?= htmlspecialchars($med['active_ingredient']); ?></td>
                        <td><?= htmlspecialchars($med['indications_for_use']); ?></td>
                        <td><?= htmlspecialchars($med['dosage']); ?></td>
                        <td><?= htmlspecialchars($med['warnings']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">❌ لا توجد نتائج مطابقة</td></tr>
            <?php endif; ?>
        </table>

    </body>
</html>