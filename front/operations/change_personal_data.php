<?php
session_start();
require_once '../../pharma_db/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}

// توليد CSRF Token إذا لم يكن موجود
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_save'])) {
    // تحقق من CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("⚠️ محاولة هجوم مشبوهة (CSRF)!");
    }

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $errors = [];

    // إزالة أي رموز غير أرقام
    // $phone = preg_replace('/\D/', '', $phone);

    // لو الرقم بدأ بـ 0 وشكله 11 رقم → نحذف الصفر
    if (strlen($phone) === 11 && $phone[0] === '0') {
        $phone = substr($phone, 1);
    }

    $phone = preg_replace('/\D/', '', $phone); // أرقام فقط

    if (preg_match('/^01[0125][0-9]{8}$/', $phone)) {
        $phone = substr($phone, 1); // شيل الصفر للتخزين
    } elseif (preg_match('/^1[0125][0-9]{8}$/', $phone)) {
        // الرقم تمام بالفعل بدون صفر
    } else {
        $errors[] = "❌ رقم الهاتف غير صالح. يجب أن يتكون من 11 رقم ويبدأ بـ 010 أو 011 أو 012 أو 015.";
    }

    if ($email !== $user['email']) {
        $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkEmail->execute([$email, $user_id]);
        if ($checkEmail->fetch()) {
            $errors[] = "❌ هذا البريد الإلكتروني مستخدم بالفعل.";
        }

        if (empty($current_password)) {
            $errors[] = "❌ لتغيير البريد الإلكتروني، يجب إدخال كلمة المرور الحالية.";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "❌ كلمة المرور الحالية غير صحيحة.";
        }
    }

    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "❌ لتغيير كلمة المرور، يجب إدخال كلمة المرور الحالية.";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "❌ كلمة المرور الحالية غير صحيحة.";
        } elseif (empty($confirm_password)) {
            $errors[] = "❌ الرجاء تأكيد كلمة المرور الجديدة.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "❌ كلمتا المرور غير متطابقتين.";
        }
    }

    if (empty($errors)) {
        $params = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'id' => $user_id
        ];

        $sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone";

        if (!empty($new_password)) {
            $params['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            $sql .= ", password = :password";
        }

        $sql .= " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // تحديث بيانات الجلسة
        $_SESSION['user']['first_name'] = $first_name;
        $_SESSION['user']['last_name'] = $last_name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['phone'] = $phone;

        $_SESSION['message'] = "✅ تم حفظ التعديلات بنجاح.";
        header("Location: ../profile.php");
        exit;
    } else {
        $_SESSION['edit_errors'] = $errors;
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل بيانات الحساب</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .toggle-password {
            position: absolute;
            top: 60%;
            right: 15px;
            cursor: pointer;
            color: #888;
        }
    </style>
</head>
<body dir="rtl" class="p-4">

<div class="container">
    <h2>👤 تعديل بيانات الحساب</h2>

    <?php if (!empty($_SESSION['edit_errors'])): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($_SESSION['edit_errors'] as $err): ?>
                    <li><?= $err ?></li>
                <?php endforeach; unset($_SESSION['edit_errors']); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="editForm" method="POST">
        <input type="hidden" name="confirm_save" value="1">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="mb-3">
            <label class="form-label">الاسم الأول</label>
            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">الاسم الأخير</label>
            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">رقم الهاتف</label>
            <?php
                $phone_input = $user['phone'];
                if ($phone_input && strlen($phone_input) === 10) {
                    $phone_input = '0' . $phone_input;
                }
            ?>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone_input) ?>" required>

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
        <a href="../profile.php" class="btn btn-secondary">الرجوع</a>
    </form>
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
        const first_name = document.querySelector('[name="first_name"]').value;
        const last_name = document.querySelector('[name="last_name"]').value;
        const email = document.querySelector('[name="email"]').value;
        const phone = document.querySelector('[name="phone"]').value;

        const summary = `
            <p><strong>الاسم الأول:</strong> ${first_name}</p>
            <p><strong>الاسم الأخير:</strong> ${last_name}</p>
            <p><strong>البريد الإلكتروني:</strong> ${email}</p>
            <p><strong>رقم الهاتف:</strong> ${phone}</p>
        `;

        document.getElementById('summaryContent').innerHTML = summary;
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();
    }

    function submitForm() {
        document.getElementById('editForm').submit();
    }
</script>

</body>
</html>
