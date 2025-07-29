<?php
session_start();
header('Content-Type: application/json');

include "../pharma_db/db.php"; 

if (!isset($_SESSION['user']) && !isset($_SESSION['doctor'])) { // ممكن يكون مريض أو دكتور
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح لك. يجب تسجيل الدخول.']);
    exit();
}

$user_id = $_SESSION['user']['id'] ?? null; // ID المريض لو موجود
$doctor_id_from_session = $_SESSION['doctor']['id'] ?? null; // ID الدكتور لو موجود

// التحقق من وجود doctor_id أو user_id للمحادثة
$target_doctor_id = $_SESSION['chat_doctor_id'] ?? null; // لو المريض بيكلم دكتور
$target_user_id = $_SESSION['chat_user_id_for_doctor'] ?? null; // لو الدكتور بيكلم مريض

if (empty($target_doctor_id) && empty($target_user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'لم يتم تحديد الطرف الآخر للمحادثة.']);
    exit();
}

// التحقق من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$message_text = $data['message_text'] ?? '';
$sender_type_from_js = $data['sender_type'] ?? ''; // ده اللي هييجي من JavaScript (user أو doctor)

if (empty($message_text) || trim($message_text) === '') {
    echo json_encode(['status' => 'error', 'message' => 'لا يمكن إرسال رسالة فارغة.']);
    exit();
}

try {
    $sender_id = null;
    $receiver_id = null;
    $sender_type = '';
    $receiver_type = '';

    // تحديد مين الـ sender ومين الـ receiver بناءً على مين اللي باعت ومين اللي فاتح الشات
    if ($sender_type_from_js === 'user' && $user_id) { // لو اللي بيبعت مريض
        $sender_id = $user_id;
        $sender_type = 'user';
        $receiver_id = $target_doctor_id; // المريض بيبعت للدكتور ده
        $receiver_type = 'doctor';
    } elseif ($sender_type_from_js === 'doctor' && $doctor_id_from_session) { // لو اللي بيبعت دكتور
        $sender_id = $doctor_id_from_session;
        $sender_type = 'doctor';
        $receiver_id = $target_user_id; // الدكتور بيبعت للمريض ده
        $receiver_type = 'user';
    } else {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في تحديد نوع المرسل أو المستقبل.']);
        exit();
    }

    if (empty($sender_id) || empty($receiver_id)) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في تحديد هوية المرسل أو المستقبل.']);
        exit();
    }

    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, message_text)
        VALUES (:sender_id, :sender_type, :receiver_id, :receiver_type, :message_text)
    ");

    $stmt->execute([
        'sender_id' => $sender_id,
        'sender_type' => $sender_type,
        'receiver_id' => $receiver_id,
        'receiver_type' => $receiver_type,
        'message_text' => $message_text
    ]);

    echo json_encode(['status' => 'success', 'message' => 'الرسالة أرسلت بنجاح!']);

} catch (PDOException $e) {
    error_log("Error saving message: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء إرسال الرسالة. يرجى المحاولة لاحقاً.']);
}
?>