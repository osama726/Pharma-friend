<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $user_medication_id = $_POST['user_medication_id'] ?? null;

    if (!$user_medication_id) {
        $_SESSION['error'] = "لم يتم تحديد التذكير للحذف.";
        header("Location: ../../profile.php");
        exit;
    }

    require_once '../../pharma_db/db.php'; // الاتصال بقاعدة البيانات

    try {
        // تأكد أن التذكير ينتمي للمستخدم الحالي
        $stmt = $pdo->prepare("SELECT id FROM user_medications WHERE id = ? AND user_id = ?");
        $stmt->execute([$user_medication_id, $user_id]);
        $exists = $stmt->fetch();

        if (!$exists) {
            $_SESSION['error'] = "التذكير غير موجود أو ليس لديك صلاحية حذفه.";
            header("Location: ../profile.php");
            exit;
        }

        // حذف التذكير
        $stmt = $pdo->prepare("DELETE FROM user_medications WHERE id = ?");
        $stmt->execute([$user_medication_id]);

        $_SESSION['success'] = "✅ تم حذف التذكير بنجاح.";
        header("Location: ../profile.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "حدث خطأ أثناء حذف التذكير: " . $e->getMessage();
        header("Location: ../profile.php");
        exit;
    }
} else {
    header("Location: ../profile.php");
    exit;
}
