<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/Vaccines.css">
    <title>اللقاحات</title>
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
    
    <div class="intre">
        <?php if (!isset($_SESSION['user'])): ?>
            <a href="login.php" class="btn btn-success">تسجيل</a>
        <?php else: ?>
            <a href="profile.php" class="px-5 py-2 profile" dir="rtl">مرحبًا، <?= $_SESSION['user']['name'] ?>.  ملفك الشخصي</a>
        <?php endif; ?>
        <a href="contact_us.php" class="btn btn-success">تحدث معنا</a>
    </div>
    <div class="background">
        <div class="color"></div>
    </div>
    <p class="p1 mt-3">اللقاحات الموصى بها للسفر الدولي</p>
    <div class="start">
        <table>
            <thead>
                <tr>
                    <th>العدوي</th>
                    <th>الأماكن التي ينصح بأخذ اللقاح بها</th>
                    <th>الملاحظات</th>
                </tr>
            </thead>
            <tbody id="vaccines-list"></tbody>
        </table>
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
    <script>
        const vaccines = [
            { disease: "كوفيد-19", location: "في جميع أنحاء العالم" , notes: "يُنصَحُ بالجرعات المعززة استنادًا إلى العمر، والأمراض المصاحبة التي يعاني منها المسافر، والتوصيات الحالية لوكالات الصحة " },
            { disease: " التهاب الكبد A", location: "جميع البلدان ذات الدخل المنخفض والمتوسّط", notes: "يَجرِي إِعطاءُ جرعتين على الأقلّ بفاصل 6 أشهر."},
            { disease: "التهاب الكبد B", location: "في جميع أنحاء العالم النامي (التهاب الكبد B شائعٌ بشكلٍ خاص في الصين)", notes: " يُنصَح بهذا اللقاح للمسافرين بإقامة طويلة، وجميع العاملين في مجال الرعاية الصحّية."},
            { disease: " الأنفلونزا", location: " في جميع أنحاء العالم", notes: "   الموسمية في نصف الكرة الشمالي: من شهر سبتمبر إلى مايو          الموسمية في نصف الكرة الجنوبي: من شهر أبريل إلى سبتمبر         في المناطق المدارية، تنتقل الأنفلونزا على مدار السنة.       في بعض الأحيان تكون لقاحات الأنفلونزا في نصف الكرة الشمالي مختلفة قليلًا عن لقاحات الأنفلونزا في نصف الكرة الجنوبي. " },
            { disease: " حمَّى التيفويد", location: "في جميع أنحاء العالم النامي، وخاصَّة في جنوبي آسيا (بما في ذلك الهند)", notes: " ويتوفَّر شكلان من اللقاح.شكل بحقنة واحدة: يحمي لمدة سنتين، ويُعتقد أنَّه أكثر أمانًا للنساء الحوامل من شكل الحبوب من اللقاح.شكل الحبوب أو الأقراص: تُؤخَذ حبة واحدة كلّ يوم حتى 4 حبات إجمالًا.وهذا الشكل يحمي لمدة 5 سنوات، ولكنَّه غير آمن للنساء الحوامل." },
            { disease: " الحُمى الصفراء", location: "أمريكا الجنوبية الاستوائية، وأفريقيا الاستوائية", notes: " هذا المرضُ نادر الحدوث، ولكنّ العديد من البلدان تحتاج إلى إثبات التطعيم للدخول.هذا اللقاح غيرُ آمن للنساء الحوامل.ينطوي هذا اللقاح على خطر مرتفع من الآثار الجانبية لدى كبار السن." },
            { disease: "داء الكَلَب ", location: " جميع البلدان، بما فيها الولاياتُ المتَّحدة", notes: " يُنصَح بهذا اللقاح للمسافرين المعرَّضين لخطر لدغات الحيوانات، بما في ذلك المخيِّمون في المناطق الريفية، والأطباء البيطريّون، والناس الذين يعيشون في المناطق النائية، والعمال الميدانيون.وهو لا يلغي الحاجةَ إلى اللقاحات الإضافية بعد لدغة الحيوان" },
            { disease: " عدوى المكوَّرات السحائية", location: " شمالي جنوب الصحراء الكبرى الأفريقية، من مالي إلى إثيوبيا (حزام التهاب السَّحايا)في جميع أنحاء العالم، وخاصَّة في ظروف المعيشة المزدحمة (مثل المَهاجِع)", notes: " تعدّ جرعة واحدة من اللقاح رباعي التكافؤ فعّالة.يكون خطرُ العدوى في حزام التهاب السحايا أعلى خلال موسم الجفاف (ديسمبر حتى يونيو).وهذا اللقاحُ مطلوب لدخول المملكة العربية السعودية في أثناء الحج أو العمرة." },
            { disease: "التهاب الدماغ الياباني ", location: "المناطق الريفية في معظم أنحاء آسيا وجنوبي آسيا، لاسيَّما في المناطق التي تستزرع الأرز والخنازير", notes: "يَجرِي إِعطاءُ جرعتين على الأقل بفاصل 28 يومًا.يمكن للبالغين الذين تتراوح أعمارهم بين 18 إلى 65 عامًا الحصول على الجرعة الثانية في وقت قصير نسبيًا كأن يكون 7 أيام بعد الجرعة الأولى.ينبغي إعطاء الجرعة الأخيرة قبل أسبوع على الأقل من السفر.لا يُنصَح بهذا اللقاح للنساء الحوامل.لا ينصح بهذا اللقاح عادةً للأشخاص الذين سيمضون أقل من شهر في المناطق الموبوءة. " },
            
        ];

        const vaccinesList = document.getElementById("vaccines-list");
        vaccines.forEach(vaccine => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <th>${vaccine.disease}</th>
                <td>${vaccine.location}</td>
                <td>${vaccine.notes}</td>
            `;
            vaccinesList.appendChild(row);
        });
    </script>
   
</body>
</html>