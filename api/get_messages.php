<?php
session_start();
header('Content-Type: application/json');

include "../pharma_db/db.php"; 

$current_user_id = $_SESSION['user']['id'] ?? null; // ID المريض لو موجود
$current_doctor_id = $_SESSION['doctor']['id'] ?? null; // ID الدكتور لو موجود

// تحديد الطرفين بناءً على من قام بتسجيل الدخول ومن هو الطرف الآخر في الجلسة
$user_id_for_query = null;
$doctor_id_for_query = null;

if ($current_user_id) { // لو اللي فاتح الصفحة مريض (يبقى هو الـ user_id والـ doctor_id جاي من chat_doctor_id)
    $user_id_for_query = $current_user_id;
    $doctor_id_for_query = $_SESSION['chat_doctor_id'] ?? null;
} elseif ($current_doctor_id) { // لو اللي فاتح الصفحة دكتور (يبقى هو الـ doctor_id والـ user_id جاي من chat_user_id_for_doctor)
    $doctor_id_for_query = $current_doctor_id;
    $user_id_for_query = $_SESSION['chat_user_id_for_doctor'] ?? null;
}

// التحقق من وجود الطرفين الأساسيين للمحادثة
if (empty($user_id_for_query) || empty($doctor_id_for_query)) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح لك أو لم يتم تحديد أطراف المحادثة بشكل كامل.']);
    exit();
}

try {
    // جلب الرسائل بين المريض والدكتور المحدد (بغض النظر عن مين اللي فاتح الشات)
    $stmt = $pdo->prepare("
        (SELECT
            id,
            sender_id,
            sender_type,
            receiver_id,
            receiver_type,
            message_text,
            timestamp
        FROM
            messages
        WHERE
            sender_id = :user_id_1 AND sender_type = 'user' AND receiver_id = :doctor_id_1 AND receiver_type = 'doctor')
        
        UNION ALL
        
        (SELECT
            id,
            sender_id,
            sender_type,
            receiver_id,
            receiver_type,
            message_text,
            timestamp
        FROM
            messages
        WHERE
            sender_id = :doctor_id_2 AND sender_type = 'doctor' AND receiver_id = :user_id_2 AND receiver_type = 'user')
        
        ORDER BY
            timestamp ASC
    ");

    $stmt->execute([
        'user_id_1' => $user_id_for_query,
        'doctor_id_1' => $doctor_id_for_query,
        'doctor_id_2' => $doctor_id_for_query,
        'user_id_2' => $user_id_for_query
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'messages' => $messages]);

} catch (PDOException $e) {
    error_log("Error fetching messages (API): " . $e->getMessage()); // سجل الخطأ للمراجعة
    echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء جلب الرسائل. يرجى المحاولة لاحقاً.']);
}
?>