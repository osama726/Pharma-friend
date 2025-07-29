<?php
    require 'db.php'; // الاتصال بقاعدة البيانات

    $csv_file = __DIR__ . "/data/radiology.csv"; // مسار ملف CSV

    if (!file_exists($csv_file)) {
        die("❌ لم يتم العثور على ملف CSV: $csv_file");
    }

    $file = fopen($csv_file, "r");

    // التعامل مع BOM إن وجد
    $firstLine = fgets($file);
    if (substr($firstLine, 0, 3) == "\xEF\xBB\xBF") {
        $firstLine = substr($firstLine, 3);
    }
    rewind($file);

    $header = fgetcsv($file); // تخطي العنوان
    $added = 0;
    $skipped = [];

    $rowNum = 1;

    while (($data = fgetcsv($file, 1000, ",")) !== false) {
        $rowNum++;

        // التحقق من عدد الأعمدة المطلوبة (8)
        if (count($data) < 8) {
            echo "⚠️ الصف رقم $rowNum يحتوي على أعمدة ناقصة وتم تخطيه<br>";
            continue;
        }

        list($name, $address, $working_hours, $delivery_service, $phone, $website, $latitude, $longitude) = array_map(function($val) {
            return trim(mb_convert_encoding($val, "UTF-8", "auto"));
        }, $data);

        // التحقق من التكرار حسب العنوان
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM radiology_centers WHERE address = ?");
        $checkStmt->execute([$address]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            $skipped[] = $address;
            continue;
        }

        // إضافة الصف
        $stmt = $pdo->prepare("INSERT INTO radiology_centers (name, address, working_hours, delivery_service, phone, website, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $address, $working_hours, $delivery_service, $phone, $website ?: 'غير متوفر', $latitude, $longitude]);
        $added++;
    }

    fclose($file);

    // إعادة تسمية الملف احتياطياً
    if ($added > 0) {
        $backup_name = __DIR__ . "/data/radiology_backup_" . date("Y-m-d_H-i-s") . ".csv";
        rename($csv_file, $backup_name);
        echo "📂 تم حفظ نسخة احتياطية باسم: $backup_name<br>";
    }

    echo "✅ تمت إضافة $added مركز أشعة جديد<br>";

    if (!empty($skipped)) {
        echo "⚠️ تم تخطي " . count($skipped) . " صف بسبب التكرار:<br>";
        foreach ($skipped as $a) {
            echo "🔁 $a<br>";
        }
    }
?>
