<?php
session_start();

// ملف الاتصال بقاعدة البيانات
include "../pharma_db/db.php"; // المسار ده صحيح بناءً على هيكل المجلدات

// 1. التحقق مما إذا كان المستخدم مسجلاً للدخول
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['message'] = 'يجب تسجيل الدخول أولاً للوصول إلى الدردشة.';
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id']; // معرف المستخدم المسجل دخوله

// 2. الحصول على معرف الدكتور (id) من رابط URL والتحقق منه
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'خطأ: لم يتم تحديد الدكتور المطلوب للدردشة.';
    header("Location: specialty.php");
    exit();
}

$doctor_id = intval($_GET['id']);

// 3. جلب بيانات الدكتور واسم التخصص من جدول Specialties

try {
    $stmt = $pdo->prepare("
        SELECT
            d.firstname,
            d.lastname,
            s.name AS specialty
        FROM
            doctors d
        JOIN
            specialties s ON d.specialty_id = s.id
        WHERE
            d.id = :doctor_id
    ");
    
    $stmt->execute(['doctor_id' => $doctor_id]);
    $doctor_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor_info) {
        $_SESSION['message'] = 'خطأ: الدكتور المطلوب غير موجود.';
        header("Location: specialty.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['message'] = 'حدث خطأ في الاتصال بالبيانات. يرجى المحاولة لاحقاً.';
    header("Location: specialty.php");
    exit();
}

// 4. تخزين بيانات الدكتور والمستخدم في الجلسة لاستخدامها في الشات
$_SESSION['chat_doctor_id'] = $doctor_id;
$_SESSION['chat_doctor_name'] = $doctor_info['firstname'] . ' ' . $doctor_info['lastname'];
$_SESSION['chat_user_id'] = $user_id;

// === NEW CODE START: تحديث الرسائل كـ "مقروءة" عندما يفتح المريض المحادثة ===
try {
    $stmt_update_read_status = $pdo->prepare("
        UPDATE messages
        SET is_read = 1
        WHERE
            sender_id = :doctor_id_from_doc AND sender_type = 'doctor' AND
            receiver_id = :user_id_current AND receiver_type = 'user' AND
            is_read = 0
    ");
    $stmt_update_read_status->execute([
        'doctor_id_from_doc' => $doctor_id,
        'user_id_current' => $user_id
    ]);
    // لا يوجد رسالة نجاح أو فشل هنا، يتم التحديث في الخلفية
} catch (PDOException $e) {
    error_log("Error updating message read status for user: " . $e->getMessage());
    // يمكن إضافة رسالة خطأ للمستخدم هنا إذا أردت، لكن يفضل أن يكون التحديث صامتاً.
}
// === NEW CODE END ===
?>


<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دردشة مع الدكتور <?php echo htmlspecialchars($doctor_info['firstname'] . ' ' . $doctor_info['lastname']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/chat.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="home_page.php">
            <img src="images/Logo.png" alt="Logo" >
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto" dir="rtl">
                <li class="nav-item"><a class="nav-link" href="home_page.php">الرئيسية</a></li>
                <li class="nav-item"><a class="nav-link" href="map.php">الخريطة</a></li>
                <li class="nav-item"><a class="nav-link" href="specialty.php">الاستشاره</a></li>
                <li class="nav-item"><a class="nav-link" href="treatment.php"><pre style="font-size: 19px;font-family: 'cairo', sans-serif;">معلومات طبية</pre></a></li>
                <li class="nav-item" ><a class="nav-link" href="Vaccines.php">اللقاحات</a></li>
            </ul>

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
<div class="intre"  >
    <?php if (!isset($_SESSION['user'])): ?>
        <a href="login.php" class="btn btn-success">تسجيل</a>
    <?php else: ?>
        <a href="profile.php" class="px-5 py-2 profile" dir="rtl">مرحبًا، <?= htmlspecialchars($_SESSION['user']['name'] ?? 'مستخدم'); ?>. ملفك الشخصي</a>
        <?php endif; ?>
    <a href="contact_us.php" class="btn btn-success">تحدث معنا</a>
</div>


<div class="background">
    <div class="color"></div>
    <h1 class="photo_tiitle">استشارتك مع الطبيب</h1>
</div>

<div class="container mt-4 mb-4">
    <div class="card text-center shadow-sm">
        <div class="card-body">
            <h3 class="card-title" dir="rtl">تتحدث الآن مع الدكتور: <span style="color: #28a745;"><?= htmlspecialchars($doctor_info['firstname'] . ' ' . $doctor_info['lastname']); ?></span></h3>
            <p class="card-text" style="font-size: 1.1em;">التخصص: <strong><?= htmlspecialchars($doctor_info['specialty']); ?></strong></p>
            <p class="card-text">تأكد من توضيح حالتك للطبيب بدقة للحصول على أفضل استشارة.</p>
        </div>
    </div>
</div>


    <div id="messages" class="BOX-CHAT mt-5">
        </div>

    <Div class="SEND-ALL">
        <button onclick="sendMessage()" class="BTT">أرسل</button>
        <input type="text" id="patientMessage" placeholder="اكتب رسالتك هنا" class="SEND">
    </Div>


<script>
// دالة لإرسال الرسالة عبر AJAX إلى send_message.php
    async function sendMessage() {
        const messageInput = document.getElementById("patientMessage");
        const messageText = messageInput.value.trim();

        if (messageText === '') {
            return;
        }

        try {
            // المسار الصحيح: 'api/send_message.php'
            const response = await fetch('../api/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message_text: messageText, sender_type: 'user' }) // هنا بتتبعت الرسالة ونوع المرسل
            });

            const result = await response.json();

            if (result.status === 'success') {
                messageInput.value = '';
                getMessages();
            } else {
                alert('خطأ في إرسال الرسالة: ' + result.message);
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert('حدث خطأ غير متوقع أثناء إرسال الرسالة.');
        }
    }

    // دالة لجلب الرسائل من قاعدة البيانات وعرضها
    async function getMessages() {
        const messagesDiv = document.getElementById("messages");
        try {
            // المسار الصحيح: 'api/get_messages.php'
            const response = await fetch('../api/get_messages.php');
            const result = await response.json();

            if (result.status === 'success') {
                messagesDiv.innerHTML = '';

                if (result.messages.length === 0) {
                    messagesDiv.innerHTML = '<div style="text-align: center; color: gray; padding: 20px;">لا توجد رسائل بعد. ابدأ المحادثة!</div>';
                } else {
                    result.messages.forEach(msg => {
                        const msgDiv = document.createElement("div");
                        const isMyMessage = msg.sender_type === 'user' && msg.sender_id == <?= json_encode($user_id) ?>;

                        msgDiv.classList.add('message');
                        if (isMyMessage) {
                            msgDiv.classList.add('my-message');
                        } else {
                            msgDiv.classList.add('other-message');
                        }

                        let senderName = isMyMessage ? 'أنت' : 'الدكتور ' + (<?= json_encode($_SESSION['chat_doctor_name'] ?? 'الطبيب') ?>);

                        // هنا يوجد خطأ في استخدام القالب String Literal (علامة $ ومفتاح القوس { } )
                        // الخطأ: \$ \{senderName\} بدلاً من ${senderName}
                        // الخطأ: \$ \{new Date(msg.timestamp)...\} بدلاً من ${new Date(msg.timestamp)...}
                        msgDiv.innerHTML = `
                            <div class="sender-info">${senderName} <span class="timestamp">${new Date(msg.timestamp).toLocaleTimeString('ar-EG', {hour: '2-digit', minute:'2-digit'})}</span></div>
                            <div class="message-content">${htmlspecialchars(msg.message_text)}</div>
                        `;
                        messagesDiv.appendChild(msgDiv);
                    });
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                }
            } else {
                console.error('Error fetching messages:', result.message);
                messagesDiv.innerHTML = '<div style="text-align: center; color: red;">خطأ في جلب الرسائل: ' + htmlspecialchars(result.message) + '</div>';
            }
        } catch (error) {
            console.error('Error fetching messages (network error):', error);
            messagesDiv.innerHTML = '<div style="text-align: center; color: red;">حدث خطأ في الشبكة. يرجى التحقق من اتصالك.</div>';
        }
    }

    // دالة مساعدة لـ htmlspecialchars في JavaScript (مهمة للأمان)
    function htmlspecialchars(str) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    window.onload = function () {
        getMessages();
        setInterval(getMessages, 3000);
    };

    document.getElementById("patientMessage").addEventListener("keypress", function(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            sendMessage();
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>



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
</body>
</html>