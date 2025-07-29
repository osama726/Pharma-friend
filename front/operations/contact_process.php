<?php
try {
    // إعداد الاتصال بقاعدة البيانات باستخدام PDO
    $dsn = "mysql:host=localhost;dbname=pharmafriend;charset=utf8mb4";
    $username = "root";
    $password = "";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // استلام وتنظيف البيانات من النموذج
        $first_name   = trim($_POST["first_name"]);
        $last_name    = trim($_POST["last_name"]);
        $email        = trim($_POST["email"]);
        $phone        = trim($_POST["phone"]);
        $message_text = trim($_POST["message"]);
        $feedback     = isset($_POST["feedback"]) ? trim($_POST["feedback"]) : null;

        // إعداد بيانات جدول messages
        $sender_id     = 0;  // أو 1 إذا كنت تريد ربطه بحساب مستخدم معين
        $receiver_id   = 1;  // رقم الطبيب المستقبل (تقدر تغيره)
        $sender_type   = "user";
        $receiver_type = "doctor";
        $is_read       = 0;

        // 1. حفظ الرسالة في جدول messages
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, message_text, is_read, timestamp)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $sender_id,
            $sender_type,
            $receiver_id,
            $receiver_type,
            $message_text,
            $is_read
        ]);

        // 2. إذا تم إرسال رأي، نحفظه في جدول user_feedback
        if (!empty($feedback)) {
            $full_name = $first_name . " " . $last_name;

            $stmt2 = $pdo->prepare("INSERT INTO user_feedback (name, feedback, created_at) VALUES (?, ?, NOW())");
            $stmt2->execute([$full_name, $feedback]);
        }

        // إعادة التوجيه مع رسالة نجاح
        header("Location: contact_us.php?success=true");
        exit();
    }
} catch (PDOException $e) {
    echo "حدث خطأ أثناء معالجة طلبك: " . $e->getMessage();
    exit();
}
