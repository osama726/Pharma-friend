<?php
    require 'db.php'; // الاتصال بقاعدة البيانات

    $csv_file = __DIR__ . "/data/diseases.csv"; // تحديد مسار ملف CSV

    // التحقق من وجود الملف
    if (!file_exists($csv_file)) {
        die("❌ لم يتم العثور على ملف CSV: $csv_file");
    }

    // التأكد من الترميز UTF-8
    $content = file_get_contents($csv_file);
    if (!mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1256');
        file_put_contents($csv_file, $content); // إعادة حفظ الملف بالترميز الصحيح
    }

    $file = fopen($csv_file, "r");

    $header = fgetcsv($file); // قراءة أول سطر (عناوين الأعمدة)
    $expected_columns = 9; // عدد الأعمدة المتوقع

    if (count($header) !== $expected_columns) {
        die("❌ خطأ: عدد الأعمدة في ملف CSV غير صحيح!");
    }

    // تأمين الاتصال باستخدام UTF-8
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");

    $added = 0;
    $duplicates = 0;
    $errors = 0;

    $insertQuery = "INSERT INTO Diseases 
        (name, definition, types, symptoms, causes, treatments, related_medications, prevention, high_risk_groups) 
        VALUES ";
    $insertValues = [];
    $params = [];

    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
        // التحقق من أن الصف يحتوي على العدد المطلوب من الأعمدة
        if (count($data) !== $expected_columns) {
            error_log("⚠️ تخطي صف غير مكتمل: " . implode(", ", $data));
            $errors++;
            continue;
        }
        
        // تنظيف البيانات وإزالة الفراغات
        $data = array_map(fn($v) => empty(trim($v)) ? NULL : trim($v), $data);
        
        // استخراج القيم
        list($name, $definition, $types, $symptoms, $causes, $treatments, $related_medications, $prevention, $high_risk_groups) = $data;

        // التحقق من أن اسم المرض صالح
        if (empty($name) || mb_strlen($name) < 3) {
            error_log("⚠️ تم تجاهل اسم غير صالح: " . $name);
            continue;
        }

        // التحقق من عدم التكرار (الاسم + الأعراض معًا)
        $checkStmt = $pdo->prepare("SELECT 1 FROM Diseases WHERE name = ? AND symptoms = ? LIMIT 1");
        $checkStmt->execute([$name, $symptoms]);
        $exists = $checkStmt->fetchColumn();

        if (!$exists) {
            $insertValues[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
            array_push($params, ...$data);
        } else {
            $duplicates++;
        }
    }

    fclose($file);

    // تنفيذ الإدخال دفعة واحدة فقط في حالة وجود بيانات جديدة
    if (!empty($insertValues)) {
        try {
            $stmt = $pdo->prepare($insertQuery . implode(", ", $insertValues));
            $stmt->execute($params);
            $added = $stmt->rowCount();
        } catch (Exception $e) {
            error_log("❌ خطأ أثناء إدخال البيانات: " . $e->getMessage());
        }
    }

    // تغيير اسم ملف CSV بدلاً من حذفه
    if ($added > 0 && file_exists($csv_file)) {
        rename($csv_file, __DIR__ . "/data/diseases_backup_" . date("Y-m-d_H-i-s") . ".csv");
        echo "📂 تم تغيير اسم الملف والاحتفاظ به كنسخة احتياطية<br>";
    }

    // عرض الإحصائيات النهائية
    echo "<p>✅ تمت إضافة <strong>$added</strong> أمراض جديدة!</p>";
    echo "<p>⚠️ تم تجاهل <strong>$duplicates</strong> أمراض مكررة.</p>";
    echo "<p>⚠️ تم تجاوز <strong>$errors</strong> صفوف تالفة.</p>";
?>
