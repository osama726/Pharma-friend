<?php
session_start();

// الاتصال بقاعدة البيانات (يفضل استخدام PDO لتوحيد طريقة الاتصال)
// بما أن باقي مشروعك بيستخدم PDO، يفضل تحويل هذا الملف أيضاً لاستخدام PDO
// هنستخدم db.php هنا للاتصال
include "db.php"; // المسار ده صحيح لو registration.php و db.php في نفس الفولدر

// استقبال البيانات من الفورم
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// التحقق من أن الحقول غير فارغة
if (empty($email) || empty($password)) {
    $_SESSION['error'] = "يجب إدخال البريد الإلكتروني وكلمة المرور.";
    header("Location: ../front/login.php"); // توجيه للـ login.php اللي هي الـ HTML
    exit;
}

$loggedIn = false; // متغير لتتبع حالة تسجيل الدخول

try {
    // 1. محاولة تسجيل الدخول كـ "مريض" (من جدول users)
    $stmt_user = $pdo->prepare("SELECT id, password, first_name, last_name, email, phone FROM users WHERE email = :email");
    $stmt_user->execute(['email' => $email]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // تسجيل الدخول كمريض بنجاح
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'], // الاسم الكامل
            'first_name' => $user['first_name'], // الاسم الأول لوحده
            'email' => $user['email'],
            'phone' => $user['phone'] ?? null,
            'role' => 'patient' // تحديد نوع المستخدم
        ];
        $loggedIn = true;
        header("Location: ../front/home_page.php"); // توجيه المريض للصفحة الرئيسية
        exit;
    }

    // 2. محاولة تسجيل الدخول كـ "دكتور" (من جدول doctors) إذا لم يتم تسجيل الدخول كمريض
    if (!$loggedIn) {
        $stmt_doctor = $pdo->prepare("SELECT id, password, firstname, lastname, email FROM doctors WHERE email = :email");
        $stmt_doctor->execute(['email' => $email]);
        $doctor = $stmt_doctor->fetch(PDO::FETCH_ASSOC);

        if ($doctor && password_verify($password, $doctor['password'])) {
            // تسجيل الدخول كدكتور بنجاح
            $_SESSION['doctor'] = [ // تخزين بيانات الدكتور في متغير session مختلف
                'id' => $doctor['id'],
                'first_name' => $doctor['firstname'],
                'last_name' => $doctor['lastname'],
                'name' => $doctor['firstname'] . ' ' . $doctor['lastname'], // الاسم الكامل للدكتور
                'email' => $doctor['email'],
                'role' => 'doctor' // تحديد نوع المستخدم
            ];
            $loggedIn = true;
            header("Location: ../front/doctor_dashboard.php"); // توجيه الدكتور للوحة التحكم بتاعته
            exit;
        }
    }

    // إذا فشل تسجيل الدخول كمريض وكدكتور
    if (!$loggedIn) {
        $_SESSION['error'] = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
        header("Location: ../front/login.php");
        exit;
    }

} catch (PDOException $e) {
    // تسجيل الخطأ للمراجعة في لوج السيرفر
    error_log("Login processing error: " . $e->getMessage());
    $_SESSION['error'] = "حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة لاحقاً.";
    header("Location: ../front/login.php");
    exit;
}

// في حالة وجود أي مشكلة غير متوقعة (لا ينبغي أن تحدث إذا كان الكود سليم)
$_SESSION['error'] = "حدث خطأ غير متوقع.";
header("Location: ../front/login.php");
exit;
?>