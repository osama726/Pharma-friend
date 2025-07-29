<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طبيبك معك</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/specialty.css">
</head>

<body>
    <!-- --------------- start navbar --------------- -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <!-- اللوجو -->
            <a class="navbar-brand" href="home_page.php">
                <img src="images/Logo.png" alt="اللوجو">
            </a>

            <!-- زر "تلت خطوط" -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- روابط النافبار -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto" dir="rtl">
                    <li class="nav-item"><a class="nav-link" href="home_page.php">الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="map.php">الخريطة</a></li>
                    <li class="nav-item"><a class="nav-link" href="specialty.php">الاستشاره</a></li>
                    <li class="nav-item"><a class="nav-link" href="treatment.php"><pre style="font-size: 19px;font-family: 'cairo', sans-serif;">معلومات طبية</pre></a></li>
                    <li class="nav-item"><a class="nav-link" href="Vaccines.php">اللقاحات</a></li>
                </ul>

                <!-- حقل البحث -->
                <form class="main_form">
                    <input class="form-control" type="search" placeholder="ابحث عن جميع خدماتنا" aria-label="Search"
                        dir="rtl">
                    <button class="btt" type="submit">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M10.5 2C5.80558 2 2 5.80558 2 10.5C2 15.1944 5.80558 19 10.5 19C12.4869 19 14.3146 18.3183 15.7619 17.176L19.4142 20.8283C19.8047 21.2188 20.4379 21.2188 20.8284 20.8283C21.2189 20.4378 21.2189 19.8046 20.8284 19.4141L17.1761 15.7618C18.3183 14.3145 19 12.4868 19 10.5C19 5.80558 15.1944 2 10.5 2ZM4 10.5C4 6.91015 6.91015 4 10.5 4C14.0899 4 17 6.91015 17 10.5C17 14.0899 14.0899 17 10.5 17C6.91015 17 4 14.0899 4 10.5Z"
                                fill="white" />
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
    <!-- --------------- End navbar --------------- -->

    <!-- button -->
    <div class="intre">
        <?php if (!isset($_SESSION['user'])): ?>
            <a href="login.php" class="btn btn-success">تسجيل</a>
        <?php else: ?>
        <a href="profile.php" class="px-5 py-2 profile" dir="rtl">مرحبًا، <?= $_SESSION['user']['name'] ?>.  ملفك الشخصي</a>
        <?php endif; ?>
        <a href="contact_us.php" class="btn btn-success">تحدث معنا</a>
    </div>

    <div class="background" dir="rtl">
        <div class="color"></div>
        <h1 class="tiitle">دليلك للاستشارة الطبية يبدأ من هنا.</h1>
    </div>

    <div class="container mt-5">
        <div class="row g-3">

<!-- Start conect to db -->
            <?php
            // الاتصال بقاعدة البيانات
            include "../pharma_db/db.php";

            // جلب التخصصات
            $sql = "SELECT id, name, icon FROM specialties";
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll();

            if (count($rows) > 0):
                foreach ($rows as $row):

            ?>
<!-- End conect to db -->


            <div class="col-md-4">
                <a href="doctor.php?specialty_id=<?= $row['id'] ?>" style="text-decoration: none;">
                    <div class="card text-center">
                        <div class="card-body">
                        <img src="images/<?= htmlspecialchars($row['icon']) ?>" alt="Icon" class="icondoc">
                        <h5 class="card-title" style="font-size: 25px;"><?= htmlspecialchars($row['name']) ?></h5>
                            <h6>احجز استشاره اون لاين او اذهب للطبيب</h6>
                        </div>
                    </div>
                </a>
            </div>
            <?php
                endforeach;
            else:
            ?>
            <p class="text-center">لا توجد تخصصات متاحة حالياً.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="footer-content">
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
</body>
</html>