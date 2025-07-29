<?php
    require 'db.php'; // الاتصال بقاعدة البيانات

    $csv_file = __DIR__ . "/data/pharmacies.csv"; // تحديد مسار ملف CSV

    // التأكد من وجود الملف
    if (!file_exists($csv_file)) {
        die("❌ لم يتم العثور على ملف CSV: $csv_file");
    }

    // فتح الملف وضبط الترميز إلى UTF-8
    $file = fopen($csv_file, "r");

    // إذا كان الملف يحتوي على BOM، يتم تخطيه
    $firstLine = fgets($file);
    if (substr($firstLine, 0, 3) == "\xEF\xBB\xBF") {
        $firstLine = substr($firstLine, 3);
    }
    rewind($file);

    // قراءة العناوين وتخطيها
    $header = fgetcsv($file);
    $added = 0;
    $rowNum = 1; // للعد من أول صف بعد العناوين

    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
        $rowNum++;

        // التأكد من أن عدد الأعمدة 8 على الأقل
        if (count($data) < 9) {
            echo "❌ خطأ في الصف رقم $rowNum: عدد الأعمدة غير كافٍ - " . json_encode($data, JSON_UNESCAPED_UNICODE) . "<br>";
            continue;
        }

        // استخراج البيانات وتنظيفها
        list($name, $address, $working_hours, $delivery_service, $phone_numbers, $website, $facebook, $latitude, $longitude) = array_map(function($value) {
            return trim(mb_convert_encoding($value, "UTF-8", "auto"));
        }, $data);

        try {
            // التحقق من التكرار
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM pharmacies WHERE address = ?");
            $checkStmt->execute([$name]);
            $exists = $checkStmt->fetchColumn();

            if (!$exists) {
                $stmt = $pdo->prepare("INSERT INTO pharmacies (name, address, working_hours, delivery_service, phone_numbers, website, facebook, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $address, $working_hours, $delivery_service, $phone_numbers, $website, $facebook, $latitude, $longitude]);
                $added++;
            }
        } catch (PDOException $e) {
            echo "❌ خطأ في الصف رقم $rowNum: " . $e->getMessage() . "<br>";
            continue;
        }
    }

    fclose($file);

    if ($added > 0) {
        $backup_name = __DIR__ . "/data/pharmacies_backup_" . date("Y-m-d_H-i-s") . ".csv";
        rename($csv_file, $backup_name);
        echo "📂 تم تغيير اسم الملف إلى: $backup_name<br>";
    }

    echo "✅ تمت إضافة $added صيدلية جديدة!";
?>