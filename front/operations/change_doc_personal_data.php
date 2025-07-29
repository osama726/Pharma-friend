<?php
session_start();
// المسار هنا: ../../pharma_db/db.php
// لأن الملف ده جوه operations/، يبقى لازم نطلع منه مرتين (../../) عشان نوصل للـ root،
// وبعدين ندخل على pharma_db/ عشان نوصل لـ db.php
require_once '../../pharma_db/db.php';

// 1. التحقق من تسجيل دخول الدكتور
if (!isset($_SESSION['doctor']) || !isset($_SESSION['doctor']['id'])) {
    $_SESSION['message'] = 'يجب تسجيل الدخول كطبيب أولاً لتعديل ملفك الشخصي.';
    header("Location: ../front/login.php"); // توجيه لصفحة تسجيل الدخول الموحدة
    exit;
}

// توليد CSRF Token إذا لم يكن موجود (مهم جداً للأمان)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$doctor_id = $_SESSION['doctor']['id'];

// جلب بيانات الدكتور الحالية لعرضها في الفورم
$stmt_doctor_info = $pdo->prepare("
    SELECT 
        d.id,
        d.firstname,
        d.lastname,
        d.phone,
        d.email,
        d.password, -- لغرض التحقق من كلمة المرور الحالية
        d.specialty_id,
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
$stmt_doctor_info->execute(['doctor_id' => $doctor_id]);
$doctor = $stmt_doctor_info->fetch(PDO::FETCH_ASSOC);

// لو الدكتور مش موجود (حالة نادرة بعد تسجيل الدخول)
if (!$doctor) {
    $_SESSION['message'] = 'حدث خطأ: لم يتم العثور على بيانات الطبيب.';
    header("Location: ../front/doctor_dashboard.php");
    exit;
}

// متغيرات لتخزين الأخطاء
$errors = [];

// معالجة بيانات الفورم بعد الإرسال (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_save'])) {
    // التحقق من CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("⚠️ محاولة هجوم مشبوهة (CSRF)!");
    }

    // استقبال البيانات الجديدة
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialty_id = $_POST['specialization'] ?? ''; // ID التخصص الجديد
    $is_clinic = in_array('clinic', $_POST['clinic_or_consultation'] ?? []) ? 1 : 0;
    $is_consultation = in_array('consultation', $_POST['clinic_or_consultation'] ?? []) ? 1 : 0;
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // === التحقق من صحة البيانات وتعديلها ===

    // التحقق من رقم الهاتف (نفس منطق المستخدم)
    $phone_formatted = preg_replace('/\D/', '', $phone); // أرقام فقط
    if (strlen($phone_formatted) === 11 && $phone_formatted[0] === '0') {
        $phone_formatted = substr($phone_formatted, 1); // شيل الصفر للتخزين في القاعدة لو 011 رقم
    }
    if (!preg_match('/^1[0125][0-9]{8}$/', $phone_formatted)) { // التحقق من أن يبدأ بـ 1 ويليه 0 أو 1 أو 2 أو 5 و 8 أرقام
        $errors[] = "❌ رقم الهاتف غير صالح. يجب أن يتكون من 11 رقم ويبدأ بـ 010 أو 011 أو 012 أو 015.";
    }


    // التحقق من تكرار البريد الإلكتروني (في جدول doctors و users)
    if ($email !== $doctor['email']) {
        $checkEmailDoctor = $pdo->prepare("SELECT id FROM doctors WHERE email = :email AND id != :doctor_id");
        $checkEmailDoctor->execute(['email' => $email, 'doctor_id' => $doctor_id]);
        if ($checkEmailDoctor->fetch()) {
            $errors[] = "❌ هذا البريد الإلكتروني مستخدم بالفعل كطبيب آخر.";
        }
        $checkEmailUser = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $checkEmailUser->execute(['email' => $email]);
        if ($checkEmailUser->fetch()) {
            $errors[] = "❌ هذا البريد الإلكتروني مستخدم بالفعل كمريض.";
        }
        // لو الإيميل اتغير، لازم يدخل كلمة المرور الحالية للتحقق
        if (empty($current_password)) {
            $errors[] = "❌ لتغيير البريد الإلكتروني، يجب إدخال كلمة المرور الحالية.";
        } elseif (!password_verify($current_password, $doctor['password'])) {
            $errors[] = "❌ كلمة المرور الحالية غير صحيحة.";
        }
    }
    
    // التحقق من التخصص
    $stmt_spec = $pdo->prepare("SELECT id FROM specialties WHERE id = :specialty_id");
    $stmt_spec->execute(['specialty_id' => $specialty_id]);
    if (!$stmt_spec->fetch()) {
        $errors[] = "❌ تخصص غير صالح.";
    }

    // التحقق من وجود خدمة (عيادة أو استشارة)
    if (!$is_clinic && !$is_consultation) {
        $errors[] = "❌ يرجى اختيار نوع الخدمة (استشارة أو عيادة).";
    }

    // التحقق من تغيير كلمة المرور
    $hashed_new_password = null;
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "❌ لتغيير كلمة المرور، يجب إدخال كلمة المرور الحالية.";
        } elseif (!password_verify($current_password, $doctor['password'])) {
            $errors[] = "❌ كلمة المرور الحالية غير صحيحة.";
        } elseif (empty($confirm_password)) {
            $errors[] = "❌ الرجاء تأكيد كلمة المرور الجديدة.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "❌ كلمتا المرور غير متطابقتين.";
        } elseif (strlen($new_password) < 8) { // التحقق من طول الباسورد الجديد
            $errors[] = "❌ يجب أن لا تقل كلمة المرور الجديدة عن 8 أحرف.";
        } else {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        }
    }

    // === معالجة رفع الملف الجديد للشهادة (إذا تم اختيار ملف جديد) ===
    $new_certificate_file = $doctor['certificate_file']; // افتراضياً هي القديمة
    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['certificate_file']['tmp_name'];
        $fileName = $_FILES['certificate_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

        if (!in_array($fileExtension, $allowedExts)) {
            $errors[] = "❌ نوع ملف الشهادة غير مسموح. الأنواع المسموح بها: PDF, DOC, DOCX, JPG, JPEG, PNG.";
        } else {
            $uploadFileDir = '../../uploads/'; // مسار الرفع: يخرج من operations/ ثم يدخل uploads/ في الـ root
            if (!file_exists($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $new_certificate_file_name = uniqid() . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $new_certificate_file_name;

            if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                $errors[] = "❌ حدث خطأ أثناء رفع ملف الشهادة.";
            } else {
                $new_certificate_file = $new_certificate_file_name; // حفظ الاسم الجديد للتخزين
                // لو فيه ملف قديم، ممكن تحذفه هنا (اختياري)
                // if (!empty($doctor['certificate_file']) && file_exists($uploadFileDir . $doctor['certificate_file'])) {
                //     unlink($uploadFileDir . $doctor['certificate_file']);
                // }
            }
        }
    }
    // === نهاية معالجة رفع الملف ===

    // لو مفيش أخطاء، قم بتحديث البيانات في قاعدة البيانات
    if (empty($errors)) {
        $params = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'phone' => $phone_formatted, // نستخدم الرقم بعد التنسيق
            'specialty_id' => $specialty_id,
            'is_clinic' => $is_clinic,
            'is_consultation' => $is_consultation,
            'certificate_file' => $new_certificate_file,
            'id' => $doctor_id
        ];

        $sql = "UPDATE doctors SET 
                    firstname = :firstname, 
                    lastname = :lastname, 
                    email = :email, 
                    phone = :phone,
                    specialty_id = :specialty_id,
                    is_clinic = :is_clinic,
                    is_consultation = :is_consultation,
                    certificate_file = :certificate_file";

        if ($hashed_new_password !== null) { // لو كلمة المرور اتغيرت
            $params['password'] = $hashed_new_password;
            $sql .= ", password = :password";
        }

        $sql .= " WHERE id = :id";
        $stmt_update = $pdo->prepare($sql);
        $stmt_update->execute($params);

        // تحديث بيانات الجلسة بعد الحفظ
        $_SESSION['doctor']['first_name'] = $firstname;
        $_SESSION['doctor']['last_name'] = $lastname;
        $_SESSION['doctor']['name'] = $firstname . ' ' . $lastname;
        $_SESSION['doctor']['email'] = $email;
        // لا نقوم بتحديث الـ phone أو is_clinic/is_consultation في الجلسة
        // لأننا لا نستخدمها بشكل مباشر في صفحات العرض عادةً، لكن يمكنك إضافتها إذا احتجت.

        $_SESSION['message'] = "✅ تم حفظ التعديلات بنجاح.";
        header("Location: ../doc_profile.php"); // توجيه لصفحة البروفايل بعد التعديل
        exit;
    } else {
        $_SESSION['edit_errors'] = $errors; // حفظ الأخطاء لعرضها
        // إعادة توجيه لنفس الصفحة عشان رسائل الأخطاء تظهر
        header("Location: " . $_SERVER['HTTP_REFERER']); // يرجع لنفس الصفحة اللي جه منها
        exit();
    }
}

// جلب التخصصات من قاعدة البيانات لعرضها في الـ select box
$stmt_specialties = $pdo->query("SELECT id, name FROM specialties");
$specialties = $stmt_specialties->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل بيانات الطبيب</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .container { margin-top: 50px; margin-bottom: 50px; }
        .card { border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); padding: 30px; }
        h2 { color: #166357; margin-bottom: 30px; text-align: center; font-weight: bold; }
        label { font-weight: 500; margin-bottom: 5px; color: #333; }
        .form-control{
                direction: rtl;
        }
        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40,167,69,.25);
        }
        .position-relative .toggle-password {
            position: absolute;
            top: 65%;
            right: 15px;
            cursor: pointer;
            color: #888;
        }
        .btn-primary, .btn-danger {
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 1.1em;
            transition: all 0.3s ease;
        }
        .btn-primary { background-color: #28a745; border-color: #28a745; }
        .btn-primary:hover { background-color: #218838; border-color: #1e7e34; }
        .btn-secondary { border-radius: 30px; padding: 10px 25px; font-size: 1.1em; }
        .alert ul { padding-right: 20px; }
        .form-check-label { margin-left: 10px; }
        .form-check-input { margin-top: 5px; margin-left: 50px; }
        .form-check {align-items: center; margin-bottom: 10px; }
    </style>
</head>
<body dir="rtl" class="p-4">

<div class="container">
    <div class="card">
        <h2>👤 تعديل بيانات الطبيب</h2>

        <?php if (!empty($_SESSION['edit_errors'])): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($_SESSION['edit_errors'] as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; unset($_SESSION['edit_errors']); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="editForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="confirm_save" value="1">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="mb-3">
                <label class="form-label">الاسم الأول</label>
                <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($doctor['firstname']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">الاسم الأخير</label>
                <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($doctor['lastname']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($doctor['email']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">رقم الهاتف</label>
                <?php
                    $phone_input = $doctor['phone'];
                    if ($phone_input && strlen($phone_input) === 10) {
                        $phone_input = '0' . $phone_input; // نضيف الصفر لو مفقود للعرض فقط
                    }
                ?>
                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($phone_input) ?>" required maxlength="11">
            </div>

            <div class="mb-3">
                <label class="form-label">التخصص</label>
                <select name="specialization" class="form-select" required>
                    <option selected disabled>اختر التخصص</option>
                    <?php foreach ($specialties as $spec): ?>
                        <option value="<?= htmlspecialchars($spec['id']) ?>" <?= ($doctor['specialty_id'] == $spec['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($spec['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">نوع الخدمة</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="clinic_or_consultation[]" value="clinic" id="isClinic" <?= ($doctor['is_clinic']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isClinic">عيادة خارجية</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="clinic_or_consultation[]" value="consultation" id="isConsultation" <?= ($doctor['is_consultation']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isConsultation">استشارة أونلاين</label>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">ملف الشهادة الطبية</label>
                <input type="file" name="certificate_file" class="form-control" accept=".pdf,.doc ,.docx,.jpg,.jpeg,.png">
                <?php if (!empty($doctor['certificate_file'])): ?>
                    <small class="form-text text-muted">الملف الحالي: <a href="../../uploads/<?= htmlspecialchars($doctor['certificate_file']) ?>" target="_blank">عرض الشهادة الحالية</a></small>
                <?php else: ?>
                    <small class="form-text text-muted">لا يوجد ملف شهادة مرفوع حالياً.</small>
                <?php endif; ?>
            </div>


            <hr>
            <h5>🔒 تغيير كلمة المرور (اختياري)</h5>

            <div class="mb-3 position-relative">
                <label class="form-label">كلمة المرور الحالية</label>
                <input type="password" name="current_password" id="current_password" class="form-control px-5">
                <i class="fas fa-eye toggle-password" toggle="#current_password"></i>
            </div>

            <div class="mb-3 position-relative">
                <label class="form-label">كلمة المرور الجديدة</label>
                <input type="password" name="new_password" id="new_password" class="form-control px-5">
                <i class="fas fa-eye toggle-password" toggle="#new_password"></i>
            </div>

            <div class="mb-3 position-relative">
                <label class="form-label">تأكيد كلمة المرور</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control px-5">
                <i class="fas fa-eye toggle-password" toggle="#confirm_password"></i>
            </div>

            <button type="button" class="btn btn-primary" onclick="showConfirmation()">🔍 الحفظ</button>
            <a href="../doc_profile.php" class="btn btn-secondary">الرجوع للملف الشخصي</a>
        </form>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-end">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد التعديلات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body" id="summaryContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">💾 حفظ</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // عرض/إخفاء كلمة المرور
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', () => {
            const input = document.querySelector(icon.getAttribute('toggle'));
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('fa-eye-slash');
            icon.classList.toggle('fa-eye');
        });
    });

    function showConfirmation() {
        const form = document.getElementById('editForm');
        const formData = new FormData(form);
        const summary = document.getElementById('summaryContent');
        let htmlSummary = '<h5>هل أنت متأكد من حفظ التعديلات التالية؟</h5>';
        
        // جلب البيانات من الفورم وعرضها
        const fields = [
            { name: 'firstname', label: 'الاسم الأول' },
            { name: 'lastname', label: 'الاسم الأخير' },
            { name: 'email', label: 'البريد الإلكتروني' },
            { name: 'phone', label: 'رقم الهاتف' },
            // النوع
            // التخصص
            { name: 'specialization', label: 'التخصص', getValue: (id) => {
                const selectElement = form.querySelector('[name="specialization"]');
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                return selectedOption ? selectedOption.text : id;
            }},
            // نوع الخدمة
            { name: 'clinic_or_consultation[]', label: 'نوع الخدمة', getValue: (vals) => {
                const selected = [];
                if (form.querySelector('#isClinic').checked) selected.push('عيادة خارجية');
                if (form.querySelector('#isConsultation').checked) selected.push('استشارة أونلاين');
                return selected.length > 0 ? selected.join(' و ') : 'غير محدد';
            }},
            // ملف الشهادة
            { name: 'certificate_file', label: 'ملف الشهادة', getValue: (file) => {
                if (file && file.name) return 'تم اختيار ملف جديد: ' + file.name;
                return 'لم يتم اختيار ملف جديد.';
            }},
            // كلمة المرور الجديدة (لا تعرض القيمة الفعلية لأسباب أمنية)
            { name: 'new_password', label: 'كلمة المرور الجديدة', getValue: (val) => val ? 'تم تغييرها' : 'لم تتغير' }
        ];

        fields.forEach(field => {
            if (field.name === 'clinic_or_consultation[]') {
                htmlSummary += `<p><strong>${field.label}:</strong> ${field.getValue()}</p>`;
            } else if (field.name === 'certificate_file') {
                 // FormData.get() doesn't work for files in direct Stringify, need to check files object
                const fileInput = form.querySelector('[name="certificate_file"]');
                htmlSummary += `<p><strong>${field.label}:</strong> ${field.getValue(fileInput.files[0])}</p>`;
            }
            else {
                const value = formData.get(field.name);
                if (value !== null && value !== undefined) {
                    htmlSummary += `<p><strong>${field.label}:</strong> ${field.getValue ? field.getValue(value) : value}</p>`;
                }
            }
        });

        summary.innerHTML = htmlSummary;
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();
    }

    function submitForm() {
        document.getElementById('editForm').submit();
    }
</script>

</body>
</html>