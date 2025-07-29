<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <link rel="stylesheet" href="css/doc_signup.css">
    <title>sign up</title>
</head>
<body>

<!-- --------------- start navbar --------------- -->
<nav class="navbar navbar-expand-lg navbar-light">
  <div class="container">
      <!-- اللوجو -->
      <a class="navbar-brand" href="home_page.php">
        <img src="images/Logo.png" alt="اللوجو" >
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

<!-- --------------- End navbar --------------- -->


<!-- --------------- Start form&logo --------------- -->
<div class="sign-container ">
    <div class="img">
      <img src="images/Logo.png">
    </div> 
    <div class="ccu">
      
          <?php
          session_start();
          if (isset($_SESSION['error'])) {
              echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
              unset($_SESSION['error']);
          }
          if (isset($_SESSION['success'])) {
              echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
              unset($_SESSION['success']);
          }

          // الاتصال لجلب التخصصات
          require '../pharma_db/db.php';
          $stmt = $pdo->query("SELECT id, name FROM specialties");
          $specialties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
      <!-- <div class="form"> -->
      <form class="form" id="registerform" action="../pharma_db/doc_registration.php" method="post" enctype="multipart/form-data">
        <label for="firstname">الإسم الأول</label>
        <div class="input-container">
          <input class="sin" type="text" id="firstname" name="firstname" placeholder="الإسم الأول" required>
          <i class="pass_icon fa fa-user"></i>
        </div>
        
        <label for="lastname">الإسم الأخير</label>
        <div class="input-container">
          <input class="sin" type="text" id="lastname" name="lastname" placeholder="الإسم الأخير" required>
          <i class="pass_icon fa fa-user"></i>
        </div>

        <label for="birthday">تاريخ الميلاد</label>
        <div class="input-container">
          <input class="sin" type="date" id="birthday" name="birthday" required style="padding-left: 40px;">
          <i class="pass_icon fa fa-calendar-alt"></i>
        </div>
        
        <label for="phone">رقم الهاتف</label>
        <div class="input-container">
          <input class="sin" type="tel" name="phone" placeholder="رقم الهاتف" maxlength="11" pattern="[0-9]{11}" required style="padding-left: 40px;">
          <i class="pass_icon fa fa-phone"></i>
        </div>

        <label for="email">البريد الإلكتروني</label>
        <div class="input-container">
          <input class="sin" type="email" id="email" name="email" placeholder="البريد الإلكتروني" required>
          <i class="pass_icon fa fa-envelope"></i>
        </div>
        
        <label for="password">كلمة المرور</label>
        <div class="input-container">
          <input class="sin" type="password" id="password" name="password" placeholder="كلمة المرور" required minlength="8">
          <i class="fas fa-eye toggle-password" toggle="#password" title="عرض / إخفاء كلمة المرور"></i>
          <i class="pass_icon fa fa-lock"></i>
        </div>
        
        <label for="confirmpassword">تأكيد كلمة المرور</label>
        <div class="input-container">
          <input class="sin" type="password" id="confirmpassword" name="confirmpassword" placeholder="تأكيد كلمة المرور" required minlength="8">
          <i class="fas fa-eye toggle-password" toggle="#confirmpassword" title="عرض / إخفاء كلمة المرور"></i>
          <i class="pass_icon fa fa-lock"></i>
        </div>

        <label for="Specialization">التخصص</label>
        <div class="input-container">
        <select name="specialization" id="specialization" class="sin" required style="padding-left: 40px;">
          <option selected disabled>اختر التخصص</option>
          <?php foreach ($specialties as $spec): ?>
            <option value="<?= htmlspecialchars($spec['id']) ?>"><?= htmlspecialchars($spec['name']) ?></option>
          <?php endforeach; ?>
        </select>
          <i class="pass_icon fa fa-user-doctor"></i>
        </div>
        
        <label for="">استشاره ام عياده خارجيه</label>
        <div class="Consultation-box">
          <label for="استشاره">للاستشاره</label>
            <input id="استشاره" class="sin" type="checkbox" name="clinic_or_consultation[]" value="consultation" />
          <label for="عياده">عيادة</label>
            <input id="عياده" class="sin" type="checkbox" name="clinic_or_consultation[]" value="clinic" />
          <div class="trrt"> <i class="fa fa-stethoscope"></i></div>
        </div>

        <label for="">النوع</label>
        <div class="gender-box">
          <label for="ذكر">ذكر</label>
            <input id="ذكر" class="sin" type="radio" name="gender" value="male" required/>
          <label for="انثي">أنثى</label>
            <input id="انثي" class="sin" type="radio" name="gender" value="female" required/>
          <div class="trrt"><i class="fa fa-user"></i></div>
        </div>

        <label for="fileUpload" class="sendfile">ارسال ملف الشهاده الطبيه</label>
        <input class="sin" type="file" id="fileUpload" name="fileUpload" accept=".pdf,.doc ,.docx,.jpg,.jpeg,.png" required>
        <button type="submit" class="buttonn">تسجيل</button>
      </form>

      <p>لديك حساب بالفعل؟<a href="login.php"> تسجيل الدخول</a></p>
    </div>
</div>

<!-- --------------- End form&logo --------------- -->

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
<script>
    document.getElementById("registerform").addEventListener("submit", function (e) {
      console.log("فورم التسجيل بيشتغل");
    });


    document.querySelectorAll('.toggle-password').forEach(function(icon) {
        icon.addEventListener('click', function () {
            const target = document.querySelector(icon.getAttribute('toggle'));
            if (target.type === "password") {
                target.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                target.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
  </script>
</body>
</html>
