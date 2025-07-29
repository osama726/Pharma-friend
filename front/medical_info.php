<?php
session_start();
?>

<?php
    require_once '../pharma_db/db.php'; // تأكد من المسار

    $query = $_GET['query'] ?? null;

if ($query) {
    // ابحث في قاعدة البيانات عن المرض أو الدواء
    $stmt = $pdo->prepare("SELECT *, 'medicines' AS type FROM medicines WHERE name LIKE ? LIMIT 1");
    $stmt->execute(["%$query%"]);
    $data = $stmt->fetch();

    if (!$data) {
        $stmt = $pdo->prepare("SELECT *, 'disease' AS type FROM diseases WHERE name LIKE ? LIMIT 1");
        $stmt->execute(["%$query%"]);
        $data = $stmt->fetch();
    }

    if ($data) {
        // إعادة توجيه لعرض العنصر بشكل صحيح حسب النظام الحالي
        header("Location: medical_info.php?id=" . $data['id'] . "&type=" . $data['type']);
        exit;
    } else {
        // لم يتم العثور على نتيجة
        echo "<script>alert('لم يتم العثور على نتائج'); window.location = 'medical_info.php';</script>";
        exit;
    }
}

    $id = $_GET['id'] ?? null;
    $type = $_GET['type'] ?? null;

    // if (!$id || !in_array($type, ['medicines', 'disease'])) {
    //     die("طلب غير صالح.");
    // }

    $table = $type === 'medicines' ? 'medicines' : 'diseases';

    // جلب البيانات من الجدول المناسب
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    // if (!$data) {
    //     echo '<div class="alert alert-warning" style="text-align: center;" dir="rtl">⚠️لا توجد معلومات متوفرة  .</div>';

        // die("العنصر غير موجود.");
    // }
    $related_medicines = [];

    if ($type === 'disease') {
        $disease_name = $data['name'];
        $stmtMed = $pdo->prepare("SELECT id, name, active_ingredient FROM medicines WHERE Indications_for_use LIKE ?");
        $stmtMed->execute(["%$disease_name%"]);
        $related_medicines = $stmtMed->fetchAll();
    }


?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($type === 'medicines') ? "معلومات عن دواء: " . $data['name'] : "معلومات عن مرض: " . $data['name']; ?> </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/medical_info.css">
    <!-- <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> -->
</head>
<body>

    <!-- Start Navbar -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <!-- اللوجو -->
        <a class="navbar-brand" href="home_page.php">
            <img src="images/Logo.png" alt="Logo" >
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
                <li class="nav-item" ><a class="nav-link" href="Vaccines.php">اللقاحات</a></li>
            </ul>

        <!-- حقل البحث -->
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
<!-- End Navbar -->


<!-- button -->
<div class="intre"  >
    <?php if (!isset($_SESSION['user'])): ?>
        <a href="login.php" class="btn btn-success">تسجيل</a>
    <?php else: ?>
        <a href="profile.php" class="px-5 py-2 profile" dir="rtl">مرحبًا، <?= $_SESSION['user']['name'] ?>.  ملفك الشخصي</a>
    <?php endif; ?>
    <a href="contact_us.php" class="btn btn-success">تحدث معنا</a>
</div>
<!-- button -->


<!-- Start head banner -->
<div class="background">
    <div class="color"></div>
    <div class="paragraph">
        <form action="medical_info.php" method="get" id="bannerSearchForm" class="d-flex justify-content-center">
            <p class="photo_tiitle">موسوعة طبية بين يديك</p>
            <input
                type="search"
                name="query"
                class="search"
                id="search-box"
                placeholder="محتاج تعرف أكتر؟ اكتب اسم المرض أو الدواء"
            />
            <button class="button"><i class="bi bi-search"></i></button>
        </form>
    </div>
</div>
<!-- End head banner -->
<div class="container mt-5" id="search-results">
    <!-- النتيجة هتظهر هنا -->
</div>




    <!-- ✅ سؤال يتغير حسب المرض -->
    <div class="text2 mt-5">
            <?php echo ($type === 'medicines') ? "معلومات عن الدواء" : "معلومات عن المرض"; ?>
    </div>
    <div class="text3">إليكم هذه القائمة بالمعلومات عنه:</div>

    <?php
    // تحقق من وجود بيانات
        if (!$id || !in_array($type, ['medicines', 'disease'])) {
            echo '<div class="alert alert-danger py-5 mt-5" style="text-align: center;" dir="rtl">⚠️لا توجد معلومات متوفرة  .</div>';
        }
        else if (!$data) {
            echo '<div class="alert alert-danger py-5 mt-5" style="text-align: center;" dir="rtl">⚠️لا توجد معلومات متوفرة  .</div>';

            // die("العنصر غير موجود.");
        }
    ?>


    <!-- ✅ جدول الأدوية -->
<div class="alltable">
    <div class="all-table">
        <table class="custom-table mb-5 mt-3">
            <tbody>
                <?php if ($type === 'medicines'): ?>
                    <tr>
                        <th>اسم الدواء</th>
                        <td><?php echo htmlspecialchars($data['name']); ?></td>
                    </tr>
                    <tr>
                        <th>المادة الفعالة</th>
                        <td><?php echo htmlspecialchars($data['active_ingredient']); ?></td>
                    </tr>
                    <tr>
                        <th>الاستخدامات</th>
                        <td><?php echo nl2br(htmlspecialchars($data['indications_for_use'])); ?></td>
                    </tr>
                    <tr>
                        <th>الجرعة</th>
                        <td><?php echo nl2br(htmlspecialchars($data['dosage'])); ?></td>
                    </tr>
                    <tr>
                        <th>التحذيرات</th>
                        <td><?php echo nl2br(htmlspecialchars($data['warnings'])); ?></td>
                    </tr>
                <?php elseif ($type === 'disease'): ?>
                    <tr>
                        <th>اسم المرض</th>
                        <td><?php echo htmlspecialchars($data['name']); ?></td>
                    </tr>
                    <tr>
                        <th>التعريف</th>
                        <td><?php echo nl2br(htmlspecialchars($data['definition'])); ?></td>
                    </tr>
                    <tr>
                        <th>الأنواع</th>
                        <td><?php echo nl2br(htmlspecialchars($data['types'])); ?></td>
                    </tr>
                    <tr>
                        <th>الأعراض</th>
                        <td><?php echo nl2br(htmlspecialchars($data['symptoms'])); ?></td>
                    </tr>
                        <th>الأسباب</th>
                        <td><?php echo nl2br(htmlspecialchars($data['causes'])); ?></td>
                    </tr>
                    <tr>
                        <th>العلاج</th>
                        <td><?php echo nl2br(htmlspecialchars($data['treatments'])); ?></td>
                    </tr>
                    <tr>
                        <th>بعض لأدوية ذات الصلة</th>
                        <td><?php echo nl2br(htmlspecialchars($data['related_medications'])); ?></td>
                    </tr>
                    <tr>
                        <th>الوقاية</th>
                        <td><?php echo nl2br(htmlspecialchars($data['prevention'])); ?></td>
                    </tr>
                    <tr>
                        <th>الفئات عالية الخطورة</th>
                        <td><?php echo nl2br(htmlspecialchars($data['high_risk_groups'])); ?></td>
                    </tr>
                <?php endif; ?>

                
            </tbody>
        </table>
    </div>
</div>

<?php if ($type === 'disease'): ?>
    <div class="container mb-5" style="max-width: 900px; padding: 20px;">
        <h4 class="mb-3" dir="rtl"> معلومات عن ألادوية المتعلقة بهذا المرض:</h4>
        <?php if (!empty($related_medicines)): ?>
            <table class="table table-hover table-bordered" dir="rtl">
                <thead class="table-light">
                    <tr style="text-align: center;">
                        <th>اسم الدواء</th>
                        <th>المادة الفعالة</th>
                        <th>معلومات اكثر</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($related_medicines as $med): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($med['name']); ?></td>
                            <td><?php echo htmlspecialchars($med['active_ingredient']); ?></td>
                            <td style="text-align: center;"><a href="medical_info.php?type=medicines&id=<?php echo $med['id']; ?>" class="btn btn-sm btn-outline-success px-4">عرض</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="alert alert-warning" style="text-align: center;" dir="rtl">⚠️ لا توجد معلومات متوفرة بهذا الدواء.</div>
        <?php endif; ?>
    </div>
<?php endif; ?>


    <!-- ده اول الفووترعشان لو هيتعدل تمام -->
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
