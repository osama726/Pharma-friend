<?php
session_start();

// الاتصال بقاعدة البيانات باستخدام PDO
// المسار هنا: ../../pharma_db/db.php
include '../../pharma_db/db.php'; 

$is_user = isset($_SESSION['user']) && isset($_SESSION['user']['id']);
$is_doctor = isset($_SESSION['doctor']) && isset($_SESSION['doctor']['id']);

// التحقق من أن هناك مستخدم أو دكتور مسجل دخول
if (!$is_user && !$is_doctor) {
    $_SESSION['message'] = 'يجب تسجيل الدخول لحذف حسابك.';
    header("Location: ../login.php");
    exit;
}

try {
    // === جزء حذف حساب المريض ===
    if ($is_user) {
        $user_id = $_SESSION['user']['id'];

        // 1. حذف التذكيرات المرتبطة بالمستخدم (user_medications) أولًا
        $stmt_delete_medications = $pdo->prepare("DELETE FROM user_medications WHERE user_id = :user_id");
        $stmt_delete_medications->execute(['user_id' => $user_id]);

        // 2. حذف الرسائل المرتبطة بالمستخدم (من جدول messages)
        $stmt_delete_messages = $pdo->prepare("
            DELETE FROM messages 
            WHERE (sender_id = :user_id_sender AND sender_type = 'user') 
            OR (receiver_id = :user_id_receiver AND receiver_type = 'user')
        ");
        $stmt_delete_messages->execute([
            'user_id_sender' => $user_id,
            'user_id_receiver' => $user_id
        ]);

        // 3. حذف المستخدم من جدول المستخدمين
        $stmt_delete_user = $pdo->prepare("DELETE FROM users WHERE id = :user_id");
        $stmt_delete_user->execute(['user_id' => $user_id]);

        $_SESSION['message'] = "✅ تم حذف حسابك بنجاح.";
        $redirect_page = '../login.php'; // بعد الحذف يرجع لصفحة الدخول

    } 
    // === نهاية جزء حذف حساب المريض ===

    // === جزء حذف حساب الدكتور ===
    elseif ($is_doctor) {
        $doctor_id = $_SESSION['doctor']['id'];

        // 1. حذف الرسائل المرتبطة بالدكتور (من جدول messages) أولاً
        $stmt_delete_messages = $pdo->prepare("
            DELETE FROM messages 
            WHERE (sender_id = :doctor_id_sender AND sender_type = 'doctor') 
            OR (receiver_id = :doctor_id_receiver AND receiver_type = 'doctor')
        ");
        $stmt_delete_messages->execute([
            'doctor_id_sender' => $doctor_id,
            'doctor_id_receiver' => $doctor_id
        ]);

        // 2. حذف الدكتور من جدول الأطباء
        $stmt_delete_doctor = $pdo->prepare("DELETE FROM doctors WHERE id = :doctor_id");
        $stmt_delete_doctor->execute(['doctor_id' => $doctor_id]);

        $_SESSION['message'] = "✅ تم حذف حسابك كطبيب بنجاح.";
        $redirect_page = '../login.php'; // بعد الحذف يرجع لصفحة الدخول
    } 
    // === نهاية جزء حذف حساب الدكتور ===

    // حذف بيانات السيشن وتسجيل الخروج بعد عملية الحذف بنجاح (سواء مريض أو دكتور)
    session_unset();
    session_destroy();

    header("Location: " . $redirect_page);
    exit;

} catch (PDOException $e) {
    error_log("Error deleting account: " . $e->getMessage());
    $_SESSION['error'] = "❌ حدث خطأ أثناء حذف الحساب. يرجى المحاولة لاحقاً.";
    
    // توجيه المستخدم لصفحته الشخصية حسب نوعه إذا حدث خطأ
    if ($is_user) {
        header("Location: ../profile.php");
    } elseif ($is_doctor) {
        header("Location: ../doc_profile.php");
    } else {
        header("Location: ../login.php"); // لو مفيش بيانات Session خالص
    }
    exit;
}