<?php
    require 'db.php'; // استدعاء ملف الاتصال بقاعدة البيانات

    // استعلام البحث إذا تم إدخال كلمة مفتاحية
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $query  = "SELECT * FROM Diseases";
    $params = [];

    if (!empty($search)) {
        $query .= " WHERE 
            name LIKE :search1 OR 
            id = :search2 OR 
            symptoms LIKE :search3 OR 
            high_risk_groups LIKE :search4 OR 
            related_medications LIKE :search5";
    }

    // تجهيز الاستعلام
    $stmt = $pdo->prepare($query);

    // ربط القيم مع المعاملات
    if (!empty($search)) {
        $stmt->bindValue(':search1', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':search2', is_numeric($search) ? $search : 0, PDO::PARAM_INT); // التأكد من أن ID رقم
        $stmt->bindValue(':search3', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':search4', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':search5', "%$search%", PDO::PARAM_STR);
    }

    // تنفيذ الاستعلام
    $stmt->execute();
    $diseases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- -------------------- -->

<!DOCTYPE html>
<html lang="ar">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>قائمة الأمراض</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; direction: rtl; }
            table { width: 90%; margin: auto; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ccc; padding: 10px; }
            th { background-color: #f8f8f8; }
            input[type="text"] { padding: 7px; width: 250px; }
            button { padding: 8px 15px; cursor: pointer; }
        </style>
    </head>
    <body>

        <h2>📋 قائمة الأمراض</h2>

        <!-- نموذج البحث -->
        <form method="GET">
            <input type="text" name="search" placeholder="🔍 ابحث عن مرض أو رقم التعريف أو الأعراض أو الأدوية..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">بحث</button>
        </form>

        <table>
            <tr>
                <th>رقم</th>
                <th>اسم المرض</th>
                <th>التعريف</th>
                <th>الأنواع</th>
                <th>الأعراض</th>
                <th>الأسباب</th>
                <th>طرق العلاج</th>
                <th>الأدوية المرتبطة</th>
                <th>طرق الوقاية</th>
                <th>الأشخاص الأكثر عرضة</th>
            </tr>
            <?php if ($diseases): ?>
                <?php foreach ($diseases as $disease): ?>
                    <tr>
                        <td><?= htmlspecialchars($disease['id']) ?></td>
                        <td><?= htmlspecialchars($disease['name']) ?></td>
                        <td><?= htmlspecialchars($disease['definition']) ?></td>
                        <td><?= htmlspecialchars($disease['types']) ?></td>
                        <td><?= htmlspecialchars($disease['symptoms']) ?></td>
                        <td><?= htmlspecialchars($disease['causes']) ?></td>
                        <td><?= htmlspecialchars($disease['treatments']) ?></td>
                        <td><?= htmlspecialchars($disease['related_medications']) ?></td>
                        <td><?= htmlspecialchars($disease['prevention']) ?></td>
                        <td><?= htmlspecialchars($disease['high_risk_groups']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="10">⚠️ لا توجد نتائج</td></tr>
            <?php endif; ?>
        </table>
    </body>
</html>
