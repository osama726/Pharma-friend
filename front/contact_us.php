<?php
session_start();
// استدعاء الاتصال بقاعدة البيانات
include "../pharma_db/db.php"; 

$form_success_message = '';
$form_error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? ''); // لو هتحفظ رقم الهاتف كمان
    $message_text = trim($_POST['message_text'] ?? '');

    // تحققات بسيطة
    if (empty($first_name) || empty($message_text)) {
        $form_error_message = "يرجى ملء الاسم الأول والرسالة.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_error_message = "صيغة البريد الإلكتروني غير صحيحة.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO user_reviews (first_name, last_name, email, message_text) VALUES (:first_name, :last_name, :email, :message_text)");
            $stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'message_text' => $message_text
            ]);
            $form_success_message = "✅ تم إرسال رسالتك بنجاح! شكراً لك.";
            // مسح قيم الفورم بعد النجاح لو عايز
            // header("Location: contact_us.php?success=1"); exit(); // ممكن تعمل redirect عشان الفورم متتبعتش تاني

        } catch (PDOException $e) {
            error_log("Contact Us - Save Review Error: " . $e->getMessage());
            $form_error_message = "❌ حدث خطأ أثناء إرسال رسالتك. يرجى المحاولة لاحقاً.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تواصل معنا</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/contact_us.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="home_page.php">
            <img src="images/Logo.png" alt="اللوجو" >
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto" dir="rtl">
                <li class="nav-item"><a class="nav-link" href="home_page.php">الرئيسية</a></li>
                <li class="nav-item"><a class="nav-link" href="map.php">الخريطة</a></li>
                <li class="nav-item"><a class="nav-link" href="specialty.php">الاستشاره</a></li>
                <li class="nav-item"><a class="nav-link" href="treatment.php"><pre style="font-size: 19px;font-family: 'cairo', sans-serif;">معلومات طبية</pre></a></li>
                <li class="nav-item" ><a class="nav-link" href="Vaccines.php">اللقاحات</a></li>
            </ul>

            <form class="main_form">
                <input class="form-control" type="search" placeholder="ابحث عن جميع خدماتنا" aria-label="Search" dir="rtl">
                <button class="btt" type="submit">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.5 2C5.80558 2 2 5.80558 2 10.5C2 15.1944 5.80558 19 10.5 19C12.4869 19 14.3146 18.3183 15.7619 17.176L19.4142 20.8283C19.8047 21.2188 20.4379 21.2188 20.8284 20.8283C21.2189 20.4378 21.2189 19.8046 20.8284 19.4141L17.1761 15.7618C18.3183 14.3145 19 12.4868 19 10.5C19 5.80558 15.1944 2 10.5 2ZM4 10.5C4 6.91015 6.91015 4 10.5 4C14.0899 4 17 6.91015 17 10.5C17 14.0899 14.0899 17 10.5 17C6.91015 17 4 14.0899 4 10.5Z" fill="white"/>
                    </svg>
                </button>
            </form>
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

<div class="int" style="justify-content: left;" >
    <?php if (!isset($_SESSION['user'])): ?>
        <a href="login.php" class="btn btn-success">تسجيل</a>
    <?php else: ?>
        <a href="profile.php" class="px-5 py-2 profile" dir="rtl">مرحبًا، <?= $_SESSION['user']['name'] ?>. ملفك الشخصي</a>
    <?php endif; ?> 
</div>

<div class="background">
    <div class="color"></div>
</div>

<div class="contact">
    <div class="contact-container">
        <div class="contact-info">
            <h2>تواصل معنا</h2>
            <p>اسئلة او تعليمات او اقتراحات ؟ما عليك سوي ملء النموذج و سنتواصل معك قريبا </p>
            <p><i class="fas fa-phone"></i>+112 25454 4851</p>
            <p><i class="fas fa-envelope"></i> Contact@pharma.com</p>
        </div>
        <div class="contact-form">
            <?php if ($form_success_message): ?>
                <div class="alert alert-success text-center"><?= htmlspecialchars($form_success_message) ?></div>
            <?php endif; ?>
            <?php if ($form_error_message): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($form_error_message) ?></div>
            <?php endif; ?>
            <form id="contactForm" method="POST" action="contact_us.php"> <input type="text" id="firstName" name="first_name" placeholder="الاسم الأول" required>
                <input type="text" id="lastName" name="last_name" placeholder="الاسم الأخير"> <input type="email" id="email" name="email" placeholder="البريد الإلكتروني">
                <input type="text" id="phone" name="phone" placeholder="رقم الهاتف" maxlength="11" pattern="[0-9]{11}" required style="padding-left: 40px;">
                <textarea id="message" name="message_text" placeholder="اكتب رسالتك هنا..." required></textarea>
                <button type="submit">إرسال</button>
            </form>
        </div>
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
            <div class="icons"><a href="#"><i class="fab fa-facebook"></i></a></div>
            <div class="icons"><a href="#"><i class="fab fa-twitter"></i></a></div>
            <div class="icons"><a href="#"><i class="fab fa-instagram"></i></a></div>
            <div class="icons"><a href="#"><i class="fab fa-linkedin"></i></a></div>
            <div class="icons"><a href="#"><i class="fas fa-envelope"></i></a></div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.getElementById("contactForm").addEventListener("submit", function(event) {
        // بما أن الفورم هتعمل submit طبيعي، مش محتاجين e.preventDefault() هنا
        // الكود اللي فات بتاع alert("تم إرسال رسالتك بنجاح!"); هيتشال لأنه الـ PHP هيطلع رسالة
        // document.getElementById("clickableIcon").addEventListener("click", function() {
        //     alert("تم النقر على الأيقونة!");
        // });
    });
</script>

</body>
</html>