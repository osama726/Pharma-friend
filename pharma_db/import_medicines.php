<?php
    require 'db.php'; // الاتصال بقاعدة البيانات

    $csv_file = __DIR__ . "/data/medicines.csv"; // مسار ملف CSV

    if (!file_exists($csv_file)) {
        die("❌ لم يتم العثور على ملف CSV: $csv_file");
    }

    $file = fopen($csv_file, "r");

    // تخطي BOM لو موجود
    $firstLine = fgets($file);
    if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF") {
        $firstLine = substr($firstLine, 3);
    }
    rewind($file);

    $header = fgetcsv($file);
    $added = 0;
    $skipped = [];

    $rowNumber = 1; // لعد الصفوف
    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
        $rowNumber++;

        // التأكد من الأعمدة الستة المطلوبة
        if (count($data) < 5) {
            echo "❌ خطأ في الصف $rowNumber: عدد الأعمدة غير كافٍ<br>";
            continue;
        }

        list($name, $active_ingredient, $indications, $dosage, $warnings) = array_map(function($value) {
            return trim(mb_convert_encoding($value, "UTF-8", "auto"));
        }, $data);

        // التحقق من التكرار بناءً على name + active_ingredient + dosage
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM medicines WHERE name = ? AND active_ingredient = ? AND Indications_for_use = ?");
        $checkStmt->execute([$name, $active_ingredient, $indications]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            $skipped[] = "🚫 صف $rowNumber مكرر: ($name - $active_ingredient - $indications)";
            continue;
        }

        // إدخال البيانات
        $stmt = $pdo->prepare("INSERT INTO medicines (name, active_ingredient, Indications_for_use, dosage, warnings) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $active_ingredient, $indications, $dosage, $warnings]);
        $added++;
    }

    fclose($file);

    if (!empty($skipped)) {
        echo "<hr><b>❗ الصفوف التي لم تتم إضافتها بسبب التكرار:</b><br>";
        foreach ($skipped as $msg) {
            echo "$msg<br>";
        }
    }

    if ($added > 0) {
        $backup_name = __DIR__ . "/data/medicines_backup_" . date("Y-m-d_H-i-s") . ".csv";
        rename($csv_file, $backup_name);
        echo "<br>📂 تم تسمية الملف إلى: $backup_name<br>";
    }

    echo "✅ تمت إضافة $added دواء جديد!<br>";

?>