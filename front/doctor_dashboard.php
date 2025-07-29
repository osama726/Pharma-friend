<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ملف الاتصال بقاعدة البيانات
include "../pharma_db/db.php"; 

// 1. التحقق من تسجيل دخول الدكتور
if (!isset($_SESSION['doctor']) || !isset($_SESSION['doctor']['id'])) {
    $_SESSION['message'] = 'يجب تسجيل الدخول كطبيب أولاً للوصول إلى لوحة التحكم.';
    header("Location: login.php"); 
    exit();
}

$current_doctor_id = $_SESSION['doctor']['id'];

// استقبال قيمة البحث من الـ GET
$search_query = $_GET['search_query'] ?? '';
$search_param_like = '%' . $search_query . '%'; // عشان نستخدمها في LIKE

// 2. جلب جميع الرسائل المتعلقة بالدكتور الحالي
// هذا الاستعلام سيجلب كل الرسائل التي يكون الدكتور الحالي طرفاً فيها
// ثم سنقوم بمعالجة تحديد آخر رسالة وعدد الرسائل غير المقروءة في PHP
try {
    $sql_all_messages_for_doctor = "
        SELECT
            m.id,
            m.sender_id,
            m.sender_type,
            m.receiver_id,
            m.receiver_type,
            m.message_text,
            m.timestamp,
            m.is_read,
            u.id AS user_id,
            u.first_name,
            u.last_name
        FROM
            messages m
        JOIN users u ON (
            (m.sender_type = 'user' AND m.sender_id = u.id) OR
            (m.receiver_type = 'user' AND m.receiver_id = u.id)
        )
        WHERE
            (m.sender_type = 'doctor' AND m.sender_id = :doctor_id_1 AND m.receiver_type = 'user') OR
            (m.receiver_type = 'doctor' AND m.receiver_id = :doctor_id_2 AND m.sender_type = 'user')
        ORDER BY
            m.timestamp DESC, m.id DESC -- ترتيب تنازلي للحصول على أحدث رسالة بسهولة في PHP
    ";
    
    $params = [
        'doctor_id_1' => $current_doctor_id,
        'doctor_id_2' => $current_doctor_id
    ];

    $stmt_all_messages = $pdo->prepare($sql_all_messages_for_doctor);
    $stmt_all_messages->execute($params);
    $all_messages = $stmt_all_messages->fetchAll(PDO::FETCH_ASSOC);

    // معالجة الرسائل في PHP لتحديد آخر رسالة لكل محادثة وعدد الرسائل غير المقروءة
    $conversations = [];
    $last_message_processed = []; // لتتبع آخر رسالة لكل محادثة (user_id)
    $unread_counts = []; // لتخزين عدد الرسائل غير المقروءة لكل user_id

    foreach ($all_messages as $msg) {
        $user_in_conversation_id = ($msg['sender_type'] == 'user') ? $msg['sender_id'] : $msg['receiver_id'];
        
        // إذا كان اسم المستخدم لا يطابق البحث، تخطى هذه الرسالة
        if (!empty($search_query) && 
            (strpos(mb_strtolower($msg['first_name']), mb_strtolower($search_query)) === false && 
             strpos(mb_strtolower($msg['last_name']), mb_strtolower($search_query)) === false)) {
            continue; // تخطي الرسالة إذا لم تتطابق مع البحث
        }

        // تحديد آخر رسالة لكل محادثة (أول رسالة نراها في الترتيب التنازلي)
        if (!isset($last_message_processed[$user_in_conversation_id])) {
            $conversations[$user_in_conversation_id] = [
                'user_id' => $user_in_conversation_id,
                'first_name' => $msg['first_name'],
                'last_name' => $msg['last_name'],
                'last_message_time' => $msg['timestamp'],
                'last_message_content' => $msg['message_text'],
                'last_message_sender_type' => $msg['sender_type'],
                'unread_count' => 0 // سيتم تحديثها لاحقا
            ];
            $last_message_processed[$user_in_conversation_id] = true;
        }

        // حساب الرسائل غير المقروءة
        if ($msg['sender_type'] == 'user' && $msg['receiver_type'] == 'doctor' && $msg['is_read'] == 0 && $msg['receiver_id'] == $current_doctor_id) {
            $unread_counts[$user_in_conversation_id] = ($unread_counts[$user_in_conversation_id] ?? 0) + 1;
        }
    }

    // تحديث عدد الرسائل غير المقروءة في مصفوفة المحادثات
    foreach ($conversations as $user_id_key => &$conv) {
        $conv['unread_count'] = $unread_counts[$user_id_key] ?? 0;
    }
    unset($conv);

    // تحويل المصفوفة إلى قائمة مرقمة (اختياري، لتنسيق HTML)
    $conversations = array_values($conversations);


} catch (PDOException $e) {
    error_log("Doctor Dashboard - Error fetching conversations: " . $e->getMessage());
    $_SESSION['message'] = 'حدث خطأ أثناء جلب المحادثات. يرجى المحاولة لاحقاً.';
    header("Location: doctor_dashboard.php");
    exit();
}

// رسائل من الجلسة (لو جاي من صفحة Login مثلاً)
$session_message = '';
if (isset($_SESSION['message'])) {
    $session_message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الدكتور - <?php echo htmlspecialchars($_SESSION['doctor']['name'] ?? 'الطبيب'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/doctor.css">
    <link rel="stylesheet" href="css/doctor_dashboard.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="doctor_dashboard.php">
            <img src="images/Logo.png" alt="Logo" >
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto"  dir="rtl">
                <li class="nav-item"><a class="nav-link" href="doctor_dashboard.php">الرئيسية (لوحة التحكم)</a></li>
                <li class="nav-item"><a class="nav-link" href="doc_profile.php">ملفي الشخصي</a></li>
            </ul>

            <form class="main_form" action="doctor_dashboard.php" method="GET">
                <input class="form-control" type="search" name="search_query" placeholder="ابحث عن اسم المريض" aria-label="Search" dir="rtl" value="<?= htmlspecialchars($_GET['search_query'] ?? '') ?>">
                <button class="btt" type="submit">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M10.5 2C5.80558 2 2 5.80558 2 10.5C2 15.1944 5.80558 19 10.5 19C12.4869 19 14.3146 18.3183 15.7619 17.176L19.4142 20.8283C19.8047 21.2188 20.4379 21.2188 20.8284 20.8283C21.2189 20.4378 21.2189 19.8046 20.8284 19.4141L17.1761 15.7618C18.3183 14.3145 19 12.4868 19 10.5C19 5.80558 15.1944 2 10.5 2ZM4 10.5C4 6.91015 6.91015 4 10.5 4C14.0899 4 17 6.91015 17 10.5C17 14.0899 14.0899 17 10.5 17C6.91015 17 4 14.0899 4 10.5Z" fill="white"/>
                    </svg>
                </button>
            </form>
        </div>

    </div>
</nav>

<div class="container_messages mt-5"  dir="rtl">
    <h2 class="mb-4">مرحبًا بك، دكتور <?= htmlspecialchars($_SESSION['doctor']['first_name'] ?? 'الطبيب') ?>!</h2>

    <?php if ($session_message): ?>
        <div class="alert alert-warning alert-dismissible fade show text-center" role="alert">
            <?= htmlspecialchars($session_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <h3 class="mb-4">المحادثات مع المرضى</h3>

    <div class="row">
        <?php if (!empty($conversations)): ?>
            <?php foreach ($conversations as $conv): ?>
                <div class="col-md-6 col-lg-6">
                    <div class="patient-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><?= htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']) ?></h5>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <span class="unread-badge">
                                    <?= htmlspecialchars($conv['unread_count']) ?> رسالة جديدة
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="text-muted small mb-1">
                            <?php 
                                $message_prefix = ($conv['last_message_sender_type'] == 'user') ? 'المريض: ' : 'أنت: ';
                                // التأكد من وجود last_message_content قبل استخدامها
                                $display_message = isset($conv['last_message_content']) ? $conv['last_message_content'] : '';
                                echo $message_prefix . htmlspecialchars(mb_substr($display_message, 0, 50)) . (mb_strlen($display_message) > 50 ? '...' : ''); 
                            ?>
                        </p>
                        <p class="text-muted small">آخر رسالة: <?= date('Y-m-d H:i', strtotime($conv['last_message_time'])) ?></p>
                        <a href="doc_message_area.php?user_id=<?= htmlspecialchars($conv['user_id']) ?>" class="btn btn-success btn-chat w-100">ابدأ المحادثة</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center" role="alert">
                    لا توجد محادثات مع المرضى حتى الآن.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>