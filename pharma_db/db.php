<?php
// إعداد الاتصال بقاعدة البيانات
    $host       = 'localhost';
    $dbname     = 'PharmaFriend';
    $username   = 'root';
    $password   = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // تمكين استثناءات الأخطاء
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // جلب البيانات كمصفوفة
            PDO::ATTR_EMULATE_PREPARES => false, // تعطيل محاكاة الاستعلامات لمنع الاختراقات
        ]);

    } catch (PDOException $e) {
        throw new Exception("❌ خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
    }
?>