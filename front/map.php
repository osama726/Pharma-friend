<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>الخريطة الطبية</title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="css/map.css">
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
          <form class="main_form" action="map.php" method="GET">
              <input class="form-control" type="search" id="search-box-map" name="query" placeholder="ابحث عن جميع خدماتنا" aria-label="Search" dir="rtl" value="<?= htmlspecialchars($_GET['query'] ?? '') ?>">
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
<!-- End nav -->

<div class="int" style="justify-content: left;" >
    <?php if (!isset($_SESSION['user'])): ?>
        <a href="login.php" class="btn btn-success">تسجيل</a>
    <?php else: ?>
        <a href="profile.php" class="px-5 py-2 profile" dir="rtl">مرحبًا، <?= $_SESSION['user']['name'] ?>.  ملفك الشخصي</a>
    <?php endif; ?> 
    <a href="contact_us.php" class="btn btn-success">تحدث معنا</a>
</div>
<!-- نهايه حقلين التسجيل   -->

  <div class="background" dir="rtl">
    <div class="color"></div>
    <div class="paragraph">
        <p class="tiitle">طريقك لأقرب رعاية صحية: مستشفى، صيدلية، معمل أو مركز أشعة.</p>
            <input
              type="search"
              class="search"
              id="search-box"
              name="query"
              placeholder="ابحث عن أقرب صيدلية أو مستشفى أو معمل أو مركز أشعة"
            />
    </div>
  </div>

  <!--  تحديد موقعي -->
   <!-- <p class="my_location">عرض موقعك الحالي</p> -->
  <button id="locate-btn">عرض موقعك الحالي  <i class="fa-solid fa-street-view"></i></button>


<!-- Start Map -->
<div id="map"></div>


<!-- Start Ambulance -->
 <table>
    <tr>
      <td class="header" colspan="3">وحدات الإسعاف</td>
    </tr>
    <tr class="header-rowW">
      <td>الاسم</td>
      <td>العنوان</td>
      <td>الرقم</td>
    </tr>

    <!-- 20 صف فاضي -->
    <tr>
      <td> مستشفيات جامعه المنصوره</td>
      <td>شارع الجمهوريه محافظه الدقهليه </td>
      <td>0502202876</td>
    </tr>
    <tr>
      <td>مستشفي طلخا المركزي   </td>
      <td>>شارع العراقي طلخا المنصوره</td>
      <td>0502522287</td>
    </tr>
    <tr>
      <td>مستشفى الامل التخصصي    </td>
      <td>>مدينه طلخا بجوار مركز طلخا</td>
      <td>0502526121</td>
    </tr>
    <tr>
      <td>مركز الشفاء</td>
      <td>
        كليه طب الاسنان شارع 3 هجرس محافظه الدقهليه
      </td>
      <td>01096692575</td>
    </tr>
    <tr>
      <td>مستشفي التامين الصحي </td>
      <td>فارسكور اول المنصوره محافظه الدقهليه</td>
      <td>0507651460</td>
    </tr>
    <tr>
      <td>مستشفي الطواريء التابعه لجامعه المنصوره</td>
      
      <td>شاريع جيهان السادات محافظه الدقهليه</td>
      
      <td>0502265472</td>
    </tr>
    <tr>
      <td>مستشفي الطلبه جامعه المنصوره</td>
      <td>المنصوره قسم ثاني محافظه المنصوره</td>
      <td>0502234096</td>
    </tr>
    <tr>
      <td>مستشفي المنصوره الدولي</td>
      <td>مدينه مبارك المنصوره قسم ثاني محافظه الدقهليه</td>
      <td>0507651450</td>
    </tr>
    <tr>
      <td>مستشفي المنصوره التخصصي</td>
      <td>المستشفي العام للمنصوره </td>
      
      <td>0507650176</td>
    </tr>
    <tr>
      <td>مرفق اسعاف الدقهليه</td>
      <td>المنصوره قسم ثاني محافظه الدقهليه</td>
      
      <td>0507652110</td>
    </tr>
    <tr>
      <td>دار خدمه المواطنين بالهيئه العامه</td>
      <td>المنصوره قسم ثاني اول محافظه الدقهليه</td>
      <td>01142264999</td>
    </tr>
    <tr>
      <td>مؤسسه حياه كريمه</td>
      <td>شارع المخطلط المنصوره قسم 2</td>
      <td>0502330851</td>
    </tr>
    <tr>
      <td>بنك الدم</td>
      <td>المنصوره الشيخ حسنين</td>
      <td> 0502396781</td>
    </tr>
    <tr>
      <td>وحده اسعاف طلخا </td>
      <td> طلخا امام مدرسه صلاح سالم التجاريه</td>
      <td>0507660254</td>
    </tr>
    <tr>
      <td>جمعيه الاورمان </td>
      <td>المنصوره قسم ثان محافظه الدقهليه</td>
      <td>01021669342</td>
    </tr>
    <tr>
      <td>مركز الاورام جامعه المنصوره </td>
      <td>شارع جيهان السادات محافظه الدقهليه</td>
      <td>0507650030</td>
    </tr>
    <tr>
      <td>مؤسسه المامون الخيريه</td>
      <td>مركز اجا محافظه الدقهليه</td>
      <td>0504335706</td>
    </tr>

  </table>

<!-- Start Ambulance -->



<!-- Start Footer -->
<footer>
  <div class=" footer-content">
  <div class="footer-info">
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
<!-- End Footer -->

<!-- Scripts -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
<script src="js/map.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
