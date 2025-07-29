<?php
session_start();

// ملف الاتصال بقاعدة البيانات (المسار الصحيح بناءً على هيكل المجلدات)
include "../pharma_db/db.php"; 

// 1. التحقق من تسجيل دخول الدكتور
if (!isset($_SESSION['doctor']) || !isset($_SESSION['doctor']['id'])) {
    $_SESSION['message'] = 'يجب تسجيل الدخول كطبيب أولاً لعرض ملفك الشخصي.';
    header("Location: login.php"); // توجيه لصفحة تسجيل الدخول الموحدة
    exit();
}

$current_doctor_id = $_SESSION['doctor']['id'];
$doctor_data = null;
$specialty_name = "غير محدد"; // قيمة افتراضية

// 2. جلب بيانات الدكتور من قاعدة البيانات
try {
    $stmt = $pdo->prepare("
        SELECT 
            d.id,
            d.firstname,
            d.lastname,
            d.phone,
            d.email,
            d.is_clinic,
            d.is_consultation,
            d.certificate_file,
            s.name AS specialty_name_from_db 
        FROM 
            doctors d
        JOIN 
            specialties s ON d.specialty_id = s.id
        WHERE 
            d.id = :doctor_id
    ");
    $stmt->execute(['doctor_id' => $current_doctor_id]);
    $doctor_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor_data) {
        $_SESSION['message'] = 'عذراً، لم يتم العثور على بيانات ملفك الشخصي.';
        header("Location: doctor_dashboard.php"); 
        exit();
    }
    $specialty_name = $doctor_data['specialty_name_from_db'];

} catch (PDOException $e) {
    error_log("Doctor Profile - Database error: " . $e->getMessage());
    $_SESSION['message'] = 'حدث خطأ في جلب بيانات ملفك الشخصي. يرجى المحاولة لاحقاً.';
    header("Location: doctor_dashboard.php");
    exit();
}

// رسائل من الجلسة (لو جاي من صفحة تانية)
$session_message = '';
if (isset($_SESSION['message'])) {
    $session_message = $_SESSION['message'];
    unset($_SESSION['message']);
}
// رسائل نجاح/خطأ من عمليات سابقة
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملف الدكتور الشخصي</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/profile.css"> </head>
<body>
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container" dir="ltr">
        <a class="navbar-brand" href="doctor_dashboard.php"> <img src="images/Logo.png" alt="اللوجو" >
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto" dir="rtl">
                <li class="nav-item"><a class="nav-link" href="doctor_dashboard.php">لوحة التحكم</a></li> <li class="nav-item"><a class="nav-link" href="doc_profile.php">ملفي الشخصي</a></li>
            </ul>

        </div>
    </div>
</nav>
<script>
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
</script>
<div class="profile-container">
    <div class="profile-header">
        <h1>👋 مرحبًا دكتور <?= htmlspecialchars($doctor_data['firstname']) ?>!</h1> <p>هنا تجد كل بياناتك وإعداداتك الشخصية</p>
    </div>
    
    <div class="profile-section">
        <h2>📋 معلومات الحساب</h2>


        <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>الاسم الكامل:</strong> <?= htmlspecialchars($doctor_data['firstname'] . ' ' . $doctor_data['lastname']) ?></li>
            <li class="list-group-item"><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($doctor_data['email']) ?></li>
            <li class="list-group-item"><strong>رقم الهاتف:</strong> 0<?= htmlspecialchars($doctor_data['phone']) ?></li>
            <li class="list-group-item"><strong>التخصص:</strong> <?= htmlspecialchars($specialty_name) ?></li>
            <li class="list-group-item">
                <strong>نوع الخدمة:</strong> 
                <?php 
                    $service_types = [];
                    if ($doctor_data['is_clinic']) {
                        $service_types[] = 'عيادة خارجية';
                    }
                    if ($doctor_data['is_consultation']) {
                        $service_types[] = 'استشارة أونلاين';
                    }
                    echo !empty($service_types) ? htmlspecialchars(implode(' و ', $service_types)) : 'غير محدد';
                ?>
            </li>
            <?php if (!empty($doctor_data['certificate_file'])): ?>
                <li class="list-group-item">
                    <strong>ملف الشهادة:</strong> 
                    <a href="../uploads/<?= htmlspecialchars($doctor_data['certificate_file']) ?>" target="_blank" class="btn btn-sm btn-info">عرض الشهادة</a>
                </li>
            <?php else: ?>
                <li class="list-group-item text-muted"><strong>ملف الشهادة:</strong> لم يتم رفع ملف شهادة.</li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="profile-section">
        <h2>🔧 إعدادات الحساب</h2>
        <a href="operations/change_doc_personal_data.php" class="btn btn-outline-secondary">تعديل بياناتك</a>
        </div>
    
    <div class="profile-section">
        <h2>🗑️ الإجراءات الحساسة</h2>
        <a href="operations/logout.php" onclick="return confirm('هل تريد تسجيل الخروج؟')" class="btn btn-danger">تسجيل الخروج</a>
        <a href="operations/delete_account.php" onclick="return confirm('هل أنت متأكد من حذف الحساب؟')" class="btn btn-outline-danger">حذف الحساب نهائيًا</a>
    </div>
</div>

<footer>
    <div class=" footer-content">
    <div class="info">
        <h4>تواصل معنا </h4>

        <p><i class="fas fa-phone-alt"></i>call: 112 25454 4851</p>
        <p><i class="fas fa-envelope"></i>Contact@pharma.com</p>
    </div>
    <div class="social-info">
    <p>© 2025 Pharma Friend | جميع الحقوق محفوظة</p>
</div>
    <div class="social-icons">
    <div class="icons">
        <a href="#"><i class="fab fa-facebook"></i></a>
    </div>
    <div class="icons">
        <a href="#"><i class="fab fa-twitter"></i></a>
</div>
<div class="icons">
        <a href="#"><i class="fab fa-instagram"></i></a>
    </div>
    <div class="icons">
        <a href="#"><i class="fab fa-linkedin"></i></a>
    </div>
    <div class="icons">
        <a href="#"><i class="fas fa-envelope"></i></a>
    </div>
    </div>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>