<?php
session_start();
?>

<?php
    // استدعاء الاتصال من المسار الصحيح
    require_once '../pharma_db/db.php';

    // جلب البيانات من جدول الأدوية والأمراض
    function fetchTableData($pdo, $tableName) {
        $allowedTables = ['medicines', 'diseases'];
        if (!in_array($tableName, $allowedTables)) {
            throw new Exception('Invalid table name');
        }
        $stmt = $pdo->prepare("SELECT name, id FROM $tableName ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }


    $medicines = fetchTableData($pdo, 'medicines');
    $diseases = fetchTableData($pdo, 'diseases');
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>treatment</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/treatment.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="home_page.php"><img src="images/Logo.png" alt="Logo"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto" dir="rtl">
                <li class="nav-item"><a class="nav-link" href="home_page.php">الرئيسية</a></li>
                <li class="nav-item"><a class="nav-link" href="map.php">الخريطة</a></li>
                <li class="nav-item"><a class="nav-link" href="specialty.php">الاستشاره</a></li>
                <li class="nav-item"><a class="nav-link" href="treatment.php"><pre style="font-size: 19px;font-family: 'cairo', sans-serif;">معلومات طبية</pre></a></li>
                <li class="nav-item"><a class="nav-link" href="Vaccines.php">اللقاحات</a></li>
            </ul>
            <form class="main_form">
                <input class="form-control" type="search" placeholder="ابحث عن جميع خدماتنا" aria-label="Search" dir="rtl">
                <button class="btt" type="submit">
                    <svg width="24" height="24" fill="white" viewBox="0 0 24 24"><path d="M10.5 2C5.8 2 2 5.8 2 10.5S5.8 19 10.5 19c2 0 3.8-.7 5.3-1.8l3.6 3.6c.4.4 1 .4 1.4 0s.4-1 0-1.4l-3.6-3.6C18.3 14.3 19 12.5 19 10.5 19 5.8 15.2 2 10.5 2zM4 10.5C4 6.9 6.9 4 10.5 4S17 6.9 17 10.5 14.1 17 10.5 17 4 14.1 4 10.5z"/></svg>
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

<!-- Buttons -->
<div class="intre">
    <?php if (!isset($_SESSION['user'])): ?>
        <a href="login.php" class="btn btn-success">تسجيل</a>
    <?php else: ?>
        <a href="profile.php" class="px-5 py-2 profile" dir="rtl">مرحبًا، <?= $_SESSION['user']['name'] ?>.  ملفك الشخصي</a>
    <?php endif; ?>
    <a href="contact_us.php" class="btn btn-success">تحدث معنا</a>
</div>

<!-- Banner -->
<div class="background">
    <div class="color"></div>
    <div class="paragraph">
        <p class="photo_tiitle">موسوعة طبية بين يديك</p>
        <input type="search" class="search" id="search-box" placeholder="محتاج تعرف أكتر؟ اكتب اسم المرض أو الدواء" />
    </div>
</div>

<!-- Data Tables -->
<div class="text">

    <h1 class="mb-4 pt-3">الادوية الشائعة</h1>
    <div class="scrollable-table">
        <table id="medicinesTable" class="table2">
            <tr>
                <?php foreach ($medicines as $index => $med): ?>
                    <td><a href="medical_info.php?id=<?= urlencode($med['id']) ?>&type=medicines" style="text-decoration:none;color:inherit"><?= htmlspecialchars($med['name']) ?></a></td>
                    <?php if (($index + 1) % 3 == 0): ?></tr><tr><?php endif; ?>
                <?php endforeach; ?>
            </tr>
        </table>
        
        <div id="noResultsMedicines" class="no-results mt-5 alert alert-danger text-center shadow-sm rounded py-4" role="alert" style="font-size: 20px; display: none;">
        <i class="fas fa-exclamation-circle fa-lg me-2"></i>
            لا توجد نتائج لبحثك ضمن الأدوية.
        </div>
    </div>


    <h1 class="mb-4 mt-5 pt-3">الامراض الشائعة</h1>
    <div class="scrollable-table">
        <table id="diseasesTable" class="table2">
            <tr>
                <?php foreach ($diseases as $index => $dis): ?>
                    <td><a href="medical_info.php?id=<?= urlencode($dis['id']) ?>&type=disease" style="text-decoration:none;color:inherit"><?= htmlspecialchars($dis['name']) ?></a></td>
                    <?php if (($index + 1) % 3 == 0): ?></tr><tr><?php endif; ?>
                <?php endforeach; ?>
            </tr>
        </table>
        <div id="noResultsDiseases" class="no-results mt-5 alert alert-danger text-center shadow-sm rounded py-4" role="alert" style="font-size: 20px; display: none;">
        <i class="fas fa-exclamation-circle fa-lg me-2"></i>
            لا توجد نتائج لبحثك ضمن الأمراض.
        </div>
    </div>

</div>

<!-- الحروف -->
<h1>ابحث عن الادوية والامراض</h1>
<div id="resetFilter" style="display: none; text-align: center;">
    <button class="btn btn-outline-success mt-4 my-1 px-4 py-2" onclick="resetTables()">عرض الكل</button>
</div>
<h2 style="font-size: 20px;"><i class="fa-solid fa-circle fa-lg"></i>العربية</h2>
<div class="keyboard" id="arabicKeyboard"></div>
<h2 style="font-size: 20px;"><i class="fa-solid fa-circle fa-lg"></i>الإنجليزية</h2>
<div class="keyboard" id="englishKeyboard"></div>



<!-- Footer -->
<footer>
    <div class="footer-content">
        <div class="info">
            <h4>تواصل معنا</h4>
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

<!-- js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="js/treatment.js"></script>

</body>
</html>
