<?php
    require 'db.php'; // الاتصال بقاعدة البيانات

    $csv_file = __DIR__ . "/data/hospitals.csv"; // تحديد مسار ملف CSV

    // التأكد من وجود الملف
    if (!file_exists($csv_file)) {
        die("❌ لم يتم العثور على ملف CSV: $csv_file");
    }

    // فتح الملف وضبط الترميز على UTF-8
    $file = fopen($csv_file, "r");

    // تخطي BOM إذا كان موجودًا
    $firstLine = fgets($file);
    if (substr($firstLine, 0, 3) == "\xEF\xBB\xBF") {
        $firstLine = substr($firstLine, 3);
    }
    rewind($file);

    $header = fgetcsv($file); // قراءة العناوين
    $added = 0;

    
    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
        if (count($data) < 7) continue; // التأكد من وجود جميع الأعمدة

        list($name, $address, $Sections, $phone, $Working_hours, $latitude, $longitude) = array_map('trim', $data);

        // تحويل الترميز إلى UTF-8 لتجنب المشاكل
        $name = mb_convert_encoding($name, "UTF-8", "auto");
        $address = mb_convert_encoding($address, "UTF-8", "auto");
        $Sections = mb_convert_encoding($Sections, "UTF-8", "auto");
        $Working_hours = mb_convert_encoding($Working_hours, "UTF-8", "auto");

        // التحقق من عدم وجود المستشفى مسبقًا
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM Hospitals WHERE name = ?");
        $checkStmt->execute([$name]);
        $exists = $checkStmt->fetchColumn();

        if (!$exists) {
            // إدخال المستشفى الجديدة
            $stmt = $pdo->prepare("INSERT INTO Hospitals (name, address, Sections, phone, Working_hours, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $address, $Sections, $phone, $Working_hours, $latitude, $longitude]);
            $added++;
        }
    }

    fclose($file);

    if ($added > 0) {
        $backup_name = __DIR__ . "/data/hospitals_backup_" . date("Y-m-d_H-i-s") . ".csv";
        rename($csv_file, $backup_name);
        echo "📂 تم تغيير اسم الملف إلى: $backup_name<br>";
    }

    echo "✅ تمت إضافة $added مستشفى جديدة!";
?>
