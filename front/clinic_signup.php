<?php

session_start();
include "../pharma_db/db.php"; // تأكد من المسار الصحيح لملف الاتصال بقاعدة البيانات

// التحقق ان الـ doctor_id موجود في الـ SESSION
// لو مش موجود، معنى كده إن المستخدم وصل للصفحة دي بالغلط أو الـ SESSION انتهت.
// هنرجعه لصفحة تسجيل الدكتور الأساسية.
if (!isset($_SESSION['new_doctor_id'])) {
    $_SESSION['error'] = "يرجى تسجيل بياناتك الأساسية أولاً كطبيب.";
    header("Location: doc_signup.php");
    exit;
}

$doctor_id = $_SESSION['new_doctor_id'];
$is_clinic_selected = $_SESSION['is_clinic_selected'] ?? false;
$is_consultation_selected = $_SESSION['is_consultation_selected'] ?? false;

$error_message = '';
$success_message = '';

// معالجة بيانات الفورم عند الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_clinic_details'])) {
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    // لاحظ تغيير اسم الـ input من 'phone' إلى 'clinic_phone' لتجنب التضارب
    $clinic_phone = filter_input(INPUT_POST, 'clinic_phone', FILTER_SANITIZE_STRING); 
    $start_time_daily = filter_input(INPUT_POST, 'start_time_daily', FILTER_SANITIZE_STRING);
    $end_time_daily = filter_input(INPUT_POST, 'end_time_daily', FILTER_SANITIZE_STRING);
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);
    $working_days = $_POST['working_days'] ?? []; // الأيام المختارة هتجيلك كـ array
    $appointment_price = filter_input(INPUT_POST, 'appointment_price', FILTER_VALIDATE_FLOAT);

    // التحقق من أن جميع الحقول المطلوبة ممتلئة
    if (!$address || !$clinic_phone || !$start_time_daily || !$end_time_daily || !$latitude || !$longitude || empty($working_days)) {
        $error_message = "يرجى ملء جميع الحقول المطلوبة، تحديد الموقع، واختيار أيام العمل.";
    } else {
        try {
            $pdo->beginTransaction(); // بدأ معاملة لضمان حفظ كل البيانات أو عدم حفظ أي منها

            // تحديد service_type بناءً على الاختيارات الأصلية
            // بما أننا وصلنا هنا، فـ is_clinic_selected لازم تكون true
            // لكن لو عايز تفرق بين "عيادة فقط" و "عيادة واستشارة" ممكن تعمل كده:
            $service_type = 'clinic'; // القيمة الافتراضية
            if ($is_clinic_selected && $is_consultation_selected) {
                $service_type = 'both';
            } elseif ($is_clinic_selected) {
                $service_type = 'clinic';
            }
            // لو كان is_consultation_selected بس هي اللي true، فالمفروض إننا مكنش جينا هنا أصلاً
            // لكن لو فرضنا إننا عايزين نضيف استشارة أونلاين هنا ليها بيانات خاصة
            // لو عايز تعمل صفحة تانية للاستشارات الأونلاين لوحدها يبقى الشرط ده صح
            // لو هتسجل كل حاجة هنا، ممكن تعدل الـ HTML ليتضمن حقول الاستشارة الأونلاين وتزود الشروط

            // 1. إدراج بيانات العيادة/الموقع في جدول clinics_locations
            $stmt_clinic_loc = $pdo->prepare("INSERT INTO clinics_locations (doctor_id, service_type, address, phone_number, latitude, longitude, start_time_daily, end_time_daily, appointment_price)
                                            VALUES (:doctor_id, :service_type, :address, :phone_number, :latitude, :longitude, :start_time_daily, :end_time_daily, :appointment_price)");
            $stmt_clinic_loc->execute([
                'doctor_id' => $doctor_id,
                'service_type' => $service_type, // القيمة هتكون 'clinic' أو 'both'
                'address' => $address,
                'phone_number' => $clinic_phone,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'start_time_daily' => $start_time_daily,
                'end_time_daily' => $end_time_daily,
                'appointment_price' => $appointment_price
            ]);
            $clinic_location_id = $pdo->lastInsertId(); // جلب الـ ID بتاع السجل الجديد في clinics_locations

            // 2. إدراج أيام العمل في جدول clinic_working_days
            if (!empty($working_days)) {
                $stmt_working_days = $pdo->prepare("INSERT INTO clinic_working_days (clinic_location_id, day_of_week) VALUES (:clinic_location_id, :day_of_week)");
                foreach ($working_days as $day) {
                    $stmt_working_days->execute([
                        'clinic_location_id' => $clinic_location_id,
                        'day_of_week' => $day
                    ]);
                }
            }

            $pdo->commit(); // حفظ كل التغييرات لو مفيش أخطاء

            $success_message = "تم حفظ بيانات العيادة/الموقع بنجاح!";
            
            // هنا الخطوة الأساسية:
            // بدل ما تمسح الـ SESSION variables وتروح لصفحة تسجيل الدخول،
            // هتفترض إن الـ SESSION['doctor'] لسه موجودة من التسجيل الأولي
            // لو انت بتعمل $_SESSION['doctor'] في doc_registration.php
            // يبقى كل اللي عليك تعمله هو التوجيه مباشرة للداشبورد
            
            // هنستخدم الـ SESSION['doctor'] اللي المفروض تكون اتسجلت في doc_registration.php
            // ونضيف رسالة النجاح إليها إذا أردت عرضها في الداشبورد
            $_SESSION['doctor']['id'] = $doctor_id; 

            // توجيه مباشرة لداشبورد الدكتور
            header("Location: doctor_dashboard.php"); 
            exit;

          }
          catch (PDOException $e) {
            $pdo->rollBack(); 
            error_log("Error saving clinic/location details: " . $e->getMessage()); 
            $error_message = "حدث خطأ أثناء حفظ بيانات العيادة/الموقع. يرجى المحاولة لاحقاً."; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" >
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل عيادة الدكتور</title>
    <link rel="stylesheet" href="css/clinic_signup.css"> 
</head>
<body>

  <form id="clinicForm" method="POST" action=""> <h2> استكمال بيانات العياده</h2> 
      
    <?php if (!empty($error_message)): ?>
        <div style="color: red; text-align: center; margin-bottom: 15px;"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
        <div style="color: green; text-align: center; margin-bottom: 15px;"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <label for="address" class="text">عنوان المكان</label>
    <input type="text" id="address" name="address" placeholder="اكتب عنوان العيادة هنا" required>

    <label for="clinic_phone" class="text">رقم هاتف العياده</label>
    <input type="tel" id="clinic_phone" name="clinic_phone" maxlength="11" pattern="[0-9]{11}" placeholder="رقم الهاتف" required>

    <label for="appointment_price" class="text">سعر الكشف / الاستشارة (اختياري)</label>
    <input type="number" id="appointment_price" name="appointment_price" placeholder="مثال: 150.00" step="0.01">

    <label class="text">أيام العمل:</label>
    <div class="checkbox-group">
        <label><input type="checkbox" name="working_days[]" value="السبت"> السبت</label>
        <label><input type="checkbox" name="working_days[]" value="الأحد"> الأحد</label>
        <label><input type="checkbox" name="working_days[]" value="الاثنين"> الاثنين</label>
        <label><input type="checkbox" name="working_days[]" value="الثلاثاء"> الثلاثاء</label>
        <label><input type="checkbox" name="working_days[]" value="الأربعاء"> الأربعاء</label>
        <label><input type="checkbox" name="working_days[]" value="الخميس"> الخميس</label>
        <label><input type="checkbox" name="working_days[]" value="الجمعة"> الجمعة</label>
    </div>

    <label class="text">مواعيد العمل (ساعة بداية - نهاية)</label>
    <div class="time-group">
        <input type="time" id="startTime" name="start_time_daily" required>
        <input type="time" id="endTime" name="end_time_daily" required>
    </div>
    
    <div class="location-output" id="locationOutput">
        لم يتم تحديد الموقع بعد.
    </div>
    <input type="hidden" id="latitude" name="latitude">
    <input type="hidden" id="longitude" name="longitude">

    <button type="button" onclick="getLocation()">احصل على موقعي</button> 
    <button style="margin-top: 20px;" type="submit" name="submit_clinic_details">حفظ البيانات</button> 
  </form>

  <script>
    function getLocation() {
      const output = document.getElementById("locationOutput");
      const latInput = document.getElementById("latitude");
      const lngInput = document.getElementById("longitude");

      if (!navigator.geolocation) {
        output.innerText = "المتصفح لا يدعم تحديد الموقع.";
          return;
        }

        navigator.geolocation.getCurrentPosition(
          (position) => {
              const lat = position.coords.latitude.toFixed(6);
              const lng = position.coords.longitude.toFixed(6);
              output.innerHTML = `✅ <strong>خط العرض:</strong> ${lat}<br><strong>خط الطول:</strong> ${lng}`;
              latInput.value = lat; // تخزين القيمة في الحقل المخفي
              lngInput.value = lng; // تخزين القيمة في الحقل المخفي
          },
            (error) => {
                output.innerText = "تعذر الحصول على الموقع.";
            }
        );
    }
  </script>
</body>
</html>