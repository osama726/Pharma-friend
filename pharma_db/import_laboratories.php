<?php
    require 'db.php'; // الاتصال بقاعدة البيانات

    $csv_file = __DIR__ . "/data/laboratory.csv"; // تحديد مسار ملف CSV

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

    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
        // التأكد من أن عدد الأعمدة 6 فقط (بدون id)
        if (count($data) < 7) {
            echo "❌ خطأ: عدد الأعمدة غير كافٍ في السطر: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "<br>";
            continue;
        }

        // استخراج البيانات مع إزالة أي مسافات زائدة وتحويل الترميز
        list($name, $address, $working_hours, $phone, $website, $latitude, $longitude) = array_map(function($value) {
            return trim(mb_convert_encoding($value, "UTF-8", "auto"));
        }, $data);

        // التحقق من عدم وجود المعمل مسبقًا
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM labs WHERE latitude = ?");
        $checkStmt->execute([$latitude]);
        $exists = $checkStmt->fetchColumn();

        if (!$exists) {
            // إدخال المعمل الجديد
            $stmt = $pdo->prepare("INSERT INTO labs (name, address, working_hours, phone, website, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $address, $working_hours, $phone, $website, $latitude, $longitude]);
            $added++;
        }
    }

    fclose($file);

    if ($added > 0) {
        $backup_name = __DIR__ . "/data/laboratory_backup_" . date("Y-m-d_H-i-s") . ".csv";
        rename($csv_file, $backup_name);
        echo "📂 تم تغيير اسم الملف إلى: $backup_name<br>";
    }

    echo "✅ تمت إضافة $added معمل جديد!";
?>