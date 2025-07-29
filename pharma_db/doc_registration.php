<?php
session_start();

// الاتصال بقاعدة البيانات (تم التأكد من استخدام PDO كما في باقي المشروع)
include 'db.php'; // المسار ده صحيح لو registration.php و db.php في نفس الفولدر

// استقبال البيانات
$firstname = $_POST['firstname'] ?? '';
$lastname = $_POST['lastname'] ?? '';
$birthday = $_POST['birthday'] ?? '';
$phone = $_POST['phone'] ?? '';
$specialty_id = $_POST['specialization'] ?? ''; // ده جاي بالـ ID دلوقتي
$email = $_POST['email'] ?? '';
$password = trim($_POST['password'] ?? '');
$confirmpassword = trim($_POST['confirmpassword'] ?? '');
$clinic_or_consultation = $_POST['clinic_or_consultation'] ?? [];
// هنا بنحدد قيمة الـ is_clinic و is_consultation بناءً على اختيارات المستخدم
$is_clinic = in_array('clinic', $clinic_or_consultation) ? 1 : 0;
$is_consultation = in_array('consultation', $clinic_or_consultation) ? 1 : 0;
$gender = $_POST['gender'] ?? '';
$newFileName = null; // سيتم تعيينه بعد رفع الملف بنجاح

// التحقق من تطابق كلمة المرور
if ($password !== $confirmpassword) {
    $_SESSION['error'] = "كلمة المرور وتأكيدها غير متطابقتين.";
    header("Location: ../front/doc_signup.php");
    exit();
}
// التحقق من طول كلمة المرور هنا أيضاً للتأكد
if (strlen($password) < 8) {
    $_SESSION['error'] = "يجب أن لا تقل كلمة المرور عن 8 أحرف.";
    header("Location: ../front/doc_signup.php");
    exit();
}


// التحقق من صلاحية التخصص (ID)
$stmt = $pdo->prepare("SELECT id FROM specialties WHERE id = :specialty_id");
$stmt->execute(['specialty_id' => $specialty_id]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "تخصص غير صالح.";
    header("Location: ../front/doc_signup.php");
    exit();
}

// التحقق من تكرار البريد الإلكتروني
// يجب التحقق في جدول users أيضا لتجنب تكرار الايميل بين دكتور ومريض
$stmt_email_doctors = $pdo->prepare("SELECT id FROM doctors WHERE email = :email");
$stmt_email_doctors->execute(['email' => $email]);
if ($stmt_email_doctors->rowCount() > 0) {
    $_SESSION['error'] = "البريد الإلكتروني مستخدم بالفعل كطبيب.";
    header("Location: ../front/doc_signup.php");
    exit();
}
$stmt_email_users = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt_email_users->execute(['email' => $email]);
if ($stmt_email_users->rowCount() > 0) {
    $_SESSION['error'] = "البريد الإلكتروني مستخدم بالفعل كمريض.";
    header("Location: ../front/doc_signup.php");
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "صيغة البريد الإلكتروني غير صحيحة.";
    header("Location: ../front/doc_signup.php");
    exit();
}

// التحقق من تكرار رقم الهاتف (في جدول doctors فقط لأنه خاص بالدكتور)
$checkPhone = $pdo->prepare("SELECT id FROM doctors WHERE phone = :phone");
$checkPhone->execute(['phone' => $phone]);
if ($checkPhone->rowCount() > 0) {
    $_SESSION['error'] = "رقم الهاتف موجود بالفعل لدكتور آخر.";
    header("Location: ../front/doc_signup.php");
    exit();
}

// تحقق من وجود قيمة للاستشارة أو العيادة
if (!$is_clinic && !$is_consultation) {
    $_SESSION['error'] = "يرجى اختيار نوع الخدمة (استشارة أو عيادة).";
    header("Location: ../front/doc_signup.php");
    exit();
}


// معالجة رفع الملف
if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['fileUpload']['tmp_name'];
    $fileName = $_FILES['fileUpload']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    if (!in_array($fileExtension, $allowedExts)) {
        $_SESSION['error'] = "نوع الملف غير مسموح. الأنواع المسموح بها: PDF, DOC, DOCX, JPG, JPEG, PNG.";
        header("Location: ../front/doc_signup.php");
        exit();
    }

    // تحديد مسار الرفع: افترض أن مجلد 'uploads' موجود في PHARMA_FRIEND/uploads/
    $uploadFileDir = '../uploads/'; 
    
    // التأكد من وجود المجلد
    if (!file_exists($uploadFileDir)) {
        mkdir($uploadFileDir, 0755, true);
    }

    $newFileName = uniqid() . '.' . $fileExtension; // اسم فريد للملف
    $dest_path = $uploadFileDir . $newFileName;

    if (!move_uploaded_file($fileTmpPath, $dest_path)) {
        $_SESSION['error'] = "حدث خطأ أثناء رفع الملف إلى السيرفر.";
        header("Location: ../front/doc_signup.php");
        exit();
    }
} else {
    $_SESSION['error'] = "يرجى رفع الشهادة الطبية المطلوبة.";
    header("Location: ../front/doc_signup.php");
    exit();
}

// تشفير كلمة المرور
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// إدخال البيانات في قاعدة البيانات
try {
    $stmt = $pdo->prepare("INSERT INTO doctors 
        (firstname, lastname, birthday, phone, specialty_id, email, password, is_clinic, is_consultation, gender, certificate_file)
        VALUES (:firstname, :lastname, :birthday, :phone, :specialty_id, :email, :password, :is_clinic, :is_consultation, :gender, :certificate_file)");

    $stmt->execute([
        'firstname' => $firstname,
        'lastname' => $lastname,
        'birthday' => $birthday,
        'phone' => $phone,
        'specialty_id' => $specialty_id,
        'email' => $email,
        'password' => $hashed_password,
        'is_clinic' => $is_clinic,
        'is_consultation' => $is_consultation,
        'gender' => $gender,
        'certificate_file' => $newFileName
    ]);

    $new_doctor_id = $pdo->lastInsertId();

    // تخزين بيانات الدكتور في الـ SESSION لتسجيل الدخول التلقائي
    $_SESSION['doctor'] = [
        'id' => $new_doctor_id, // استخدام الـ ID الجديد
        'first_name' => $firstname,
        'last_name' => $lastname,
        'name' => $firstname . ' ' . $lastname,
        'email' => $email,
        'phone' => $phone,
        'is_clinic' => $is_clinic,
        'is_consultation' => $is_consultation,
        'role' => 'doctor' // تحديد نوع المستخدم
    ];

    // تخزين ID الدكتور واختياراته في الـ SESSION عشان نستخدمها في الصفحة التالية
    $_SESSION['new_doctor_id'] = $new_doctor_id;
    $_SESSION['is_clinic_selected'] = $is_clinic;
    $_SESSION['is_consultation_selected'] = $is_consultation;

    // *** هنا التغيير الأساسي: تعديل شرط التحويل لصفحة استكمال البيانات ***
    // الدكتور بيتحول لـ clinic_signup.php لو اختار 'clinic' (عيادة)
    // سواء اختار معاها 'consultation' أو لأ.
    if ($is_clinic) { // الشرط هنا أصبح يعتمد على 'is_clinic' فقط
        $_SESSION['success'] = "تم التسجيل بنجاح! يرجى استكمال بيانات العيادة/الاستشارة.";
        header("Location: ../front/clinic_signup.php"); 
        exit();
    } else {
        // لو اختار 'consultation' فقط أو لم يختر أي شيء (وده غير محتمل بسبب التحقق)
        $_SESSION['success'] = "تم التسجيل بنجاح!.";
        header("Location: ../front/doctor_dashboard.php"); 
        exit();
    }

} catch (PDOException $e) {
    error_log("Doctor registration database error: " . $e->getMessage()); 
    $_SESSION['error'] = "حدث خطأ أثناء التسجيل. يرجى المحاولة لاحقاً."; 
    header("Location: ../front/doc_signup.php");
    exit();
}