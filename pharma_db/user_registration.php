<?php
session_start(); // نحتاجها لتخزين رسائل الخطأ أو النجاح

// الاتصال بقاعدة البيانات باستخدام PDO
// المسار هنا: db.php
// لأن الملف ده (user_registration.php) هيكون في نفس الفولدر بتاع db.php
include 'db.php'; // هذا يضمن أن كائن PDO $pdo متاح


// استقبال البيانات
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$birthday = $_POST['birthday'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$password = trim($_POST['password'] ?? '');
$confirmpassword = trim($_POST['confirmpassword'] ?? '');
$gender = $_POST['gender'] ?? '';

// === التحقق من صحة البيانات ===

// التحقق من البريد الإلكتروني
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['signup_error'] = "البريد الإلكتروني غير صالح.";
    header("Location: ../front/user_signup.php");
    exit;
}

// التحقق من رقم الهاتف
$phone_formatted = preg_replace('/\D/', '', $phone); // إزالة أي رموز غير أرقام
if (strlen($phone_formatted) === 11 && $phone_formatted[0] === '0') {
    $phone_formatted = substr($phone_formatted, 1); // إزالة الصفر الأول للتخزين لو الرقم 011 رقم
}
if (!preg_match('/^1[0125][0-9]{8}$/', $phone_formatted)) { // التحقق من أن يبدأ بـ 1 ويليه 0 أو 1 أو 2 أو 5 و 8 أرقام
    $_SESSION['signup_error'] = "رقم الهاتف غير صالح. يجب أن يتكون من 11 رقم ويبدأ بـ 010 أو 011 أو 012 أو 015.";
    header("Location: ../front/user_signup.php");
    exit;
}


// التحقق من تطابق كلمة المرور
if ($password !== $confirmpassword) {
    $_SESSION['signup_error'] = "كلمة المرور وتأكيدها غير متطابقتين.";
    header("Location: ../front/user_signup.php");
    exit;
}
// التحقق من طول كلمة المرور (أضفت هذا)
if (strlen($password) < 8) {
    $_SESSION['signup_error'] = "يجب أن لا تقل كلمة المرور عن 8 أحرف.";
    header("Location: ../front/user_signup.php");
    exit;
}


// التحقق من تكرار البريد الإلكتروني (في جدول users وجدول doctors)
try {
    $check_email_user_stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $check_email_user_stmt->execute(['email' => $email]);
    if ($check_email_user_stmt->rowCount() > 0) {
        $_SESSION['signup_error'] = "البريد الإلكتروني هذا مسجل بالفعل كمريض.";
        header("Location: ../front/user_signup.php");
        exit;
    }

    $check_email_doctor_stmt = $pdo->prepare("SELECT id FROM doctors WHERE email = :email");
    $check_email_doctor_stmt->execute(['email' => $email]);
    if ($check_email_doctor_stmt->rowCount() > 0) {
        $_SESSION['signup_error'] = "البريد الإلكتروني هذا مسجل بالفعل كطبيب.";
        header("Location: ../front/user_signup.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Signup email check error: " . $e->getMessage());
    $_SESSION['signup_error'] = "حدث خطأ أثناء التحقق من البريد الإلكتروني. يرجى المحاولة لاحقًا.";
    header("Location: ../front/user_signup.php");
    exit;
}


// التحقق من تكرار رقم الهاتف (في جدول users فقط)
try {
    $check_phone_user_stmt = $pdo->prepare("SELECT id FROM users WHERE phone = :phone");
    $check_phone_user_stmt->execute(['phone' => $phone_formatted]);
    if ($check_phone_user_stmt->rowCount() > 0) {
        $_SESSION['signup_error'] = "رقم الهاتف هذا مسجل بالفعل.";
        header("Location: ../front/user_signup.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Signup phone check error: " . $e->getMessage());
    $_SESSION['signup_error'] = "حدث خطأ أثناء التحقق من رقم الهاتف. يرجى المحاولة لاحقًا.";
    header("Location: ../front/user_signup.php");
    exit;
}


// تشفير كلمة المرور
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// إدخال البيانات
try {
    $sql_insert = "INSERT INTO users (first_name, last_name, birthday, email, phone, password, gender) VALUES (:first_name, :last_name, :birthday, :email, :phone, :password, :gender)";
    $stmt_insert = $pdo->prepare($sql_insert);

    $stmt_insert->execute([
        'first_name' => $first_name,
        'last_name' => $last_name,
        'birthday' => $birthday,
        'email' => $email,
        'phone' => $phone_formatted, // استخدم الرقم المنسق
        'password' => $hashed_password,
        'gender' => $gender
    ]);

    // تسجيل الدخول التلقائي للمستخدم بعد التسجيل
    $_SESSION['user'] = [
        'id' => $pdo->lastInsertId(), // جلب الـ ID اللي تم إدخاله للتو
        'name' => $first_name . ' ' . $last_name, // الاسم الكامل
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone_formatted, // الرقم المنسق
        'role' => 'patient' // تحديد نوع المستخدم
    ];

    $_SESSION['signup_success'] = "تم التسجيل بنجاح. تم تسجيل دخولك تلقائياً.";
    header("Location: ../front/home_page.php"); // توجيه لصفحة الرئيسية
    exit;

} catch (PDOException $e) {
    error_log("User registration database error: " . $e->getMessage());
    $_SESSION['signup_error'] = "حدث خطأ أثناء التسجيل. برجاء المحاولة لاحقًا.";
    header("Location: ../front/user_signup.php");
    exit;
}
?>