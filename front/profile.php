    <?php
    session_start();

    // ملف الاتصال بقاعدة البيانات
    // المسار هنا سيكون صحيحًا إذا كان profile.php في 'front/'
    include "../pharma_db/db.php"; 

    // 1. التحقق من تسجيل دخول المريض
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        header("Location: login.php");
        exit;
    }

    $user = $_SESSION['user'];
    $user_id = $_SESSION['user']['id'];

    // === جلب معلومات الحساب للمريض (من db.php) ===
    // لا حاجة لإعادة جلب بيانات المستخدم هنا إذا كانت موجودة وكاملة في $_SESSION['user']
    // ولكن لضمان التحديث لو البيانات اتغيرت في مكان تاني
    try {
        $stmt_user_info = $pdo->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE id = :user_id");
        $stmt_user_info->execute(['user_id' => $user_id]);
        $current_user_data = $stmt_user_info->fetch(PDO::FETCH_ASSOC);

        if ($current_user_data) {
            // تحديث بيانات الجلسة إذا كانت هناك أحدث
            $_SESSION['user']['first_name'] = $current_user_data['first_name'];
            $_SESSION['user']['last_name'] = $current_user_data['last_name'];
            $_SESSION['user']['email'] = $current_user_data['email'];
            $_SESSION['user']['phone'] = $current_user_data['phone'];
            $user = $_SESSION['user']; // تحديث المتغير $user
        }

    } catch (PDOException $e) {
        error_log("User Profile - Error fetching user info: " . $e->getMessage());
        // لا يجب أن يوقف التنفيذ هنا، فقط سجل الخطأ
    }


    // === جلب الأدوية المحفوظة ===
    // تم إزالة الاتصال المباشر بـ PDO هنا والاعتماد على $pdo من db.php
    try {
        $stmt = $pdo->prepare("
            SELECT 
                um.id as user_medication_id,
                m.name, 
                m.active_ingredient,
                um.dosage, 
                um.frequency, 
                um.start_time 
            FROM user_medications um
            JOIN medicines m ON um.medicine_id = m.id
            WHERE um.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $user_id]);
        $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("User Profile - Error fetching medications: " . $e->getMessage());
        $_SESSION['error'] = "حدث خطأ أثناء جلب الأدوية المحفوظة. يرجى المحاولة لاحقاً.";
        $medications = []; 
    }
    // === نهاية جلب الأدوية المحفوظة ===


    // === جلب المحادثات ===
    try {
        $stmt_conversations = $pdo->prepare("
            SELECT
                d.id AS doctor_id,
                d.firstname,
                d.lastname,
                last_messages.timestamp AS last_message_time,
                last_messages.message_text AS last_message_content,
                last_messages.sender_type AS last_message_sender_type
            FROM
                doctors d
            JOIN (
                SELECT
                    m.id, m.sender_id, m.sender_type, m.receiver_id, m.receiver_type, m.message_text, m.timestamp,
                    ROW_NUMBER() OVER (PARTITION BY
                        CASE
                            WHEN m.sender_type = 'user' AND m.receiver_type = 'doctor' THEN m.receiver_id
                            WHEN m.sender_type = 'doctor' AND m.receiver_type = 'user' THEN m.sender_id
                        END
                    ORDER BY m.timestamp DESC, m.id DESC) as rn
                FROM messages m
                WHERE
                    (m.sender_type = 'user' AND m.sender_id = :user_id_filter_1 AND m.receiver_type = 'doctor') OR
                    (m.receiver_type = 'user' AND m.receiver_id = :user_id_filter_2 AND m.sender_type = 'doctor')
            ) AS last_messages ON (
                (d.id = last_messages.sender_id AND last_messages.sender_type = 'doctor') OR
                (d.id = last_messages.receiver_id AND last_messages.receiver_type = 'doctor')
            )
            WHERE
                last_messages.rn = 1
            ORDER BY
                last_message_time DESC
        ");
        $stmt_conversations->execute([
            'user_id_filter_1' => $user_id, 
            'user_id_filter_2' => $user_id
        ]);
        $conversations = $stmt_conversations->fetchAll(PDO::FETCH_ASSOC);

        // جلب عدد الرسائل غير المقروءة لكل دكتور
        foreach ($conversations as &$conv) {
            $stmt_unread_count = $pdo->prepare("
                SELECT COUNT(id) AS unread_count
                FROM messages
                WHERE
                    sender_id = :doctor_id AND sender_type = 'doctor' AND
                    receiver_id = :user_id AND receiver_type = 'user' AND
                    is_read = 0
            ");
            $stmt_unread_count->execute([
                'doctor_id' => $conv['doctor_id'],
                'user_id' => $user_id
            ]);
            $conv['unread_count'] = $stmt_unread_count->fetchColumn();
        }
        unset($conv);
    } catch (PDOException $e) {
        error_log("User Profile - Error fetching conversations: " . $e->getMessage());
        $_SESSION['error'] = "حدث خطأ أثناء جلب المحادثات. يرجى المحاولة لاحقاً.";
        $conversations = []; 
    }
    // === نهاية جلب المحادثات ===

    // رسائل من الجلسة (نجاح/خطأ من عمليات سابقة)
    $session_message = '';
    if (isset($_SESSION['message'])) {
        $session_message = $_SESSION['message'];
        unset($_SESSION['message']);
    }
    $error_message = ''; 
    if (isset($_SESSION['error'])) {
        $error_message = $_SESSION['error'];
        unset($_SESSION['error']);
    }
    $success_message = ''; 
    if (isset($_SESSION['success'])) {
        $success_message = $_SESSION['success'];
        unset($_SESSION['success']);
    }

    $success_message_medication = '';
    if (isset($_SESSION['success_medication'])) {
        $success_message_medication = $_SESSION['success_medication'];
        unset($_SESSION['success_medication']);
    }

    ?>


    <!DOCTYPE html>
    <html lang="ar">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>الملف الشخصي</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="css/profile.css">
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



    <div class="profile-container">
        <div class="profile-header">
            <h1>👋 مرحبًا <?= htmlspecialchars($user['first_name']) ?>!</h1>
            <p>هنا تجد كل بياناتك وإعداداتك الشخصية</p>
        </div>
        
        <div class="profile-section">
            <h2>📋 معلومات الحساب</h2>
            <!-- start message -->
            <?php
                if (!empty($_SESSION['message'])) {
                    echo '<div class="alert alert-success text-center">' . $_SESSION['message'] . '</div>';
                    unset($_SESSION['message']);
                }
                elseif (!empty($_SESSION['error'])) {
                    echo '<div class="alert alert-danger text-center">' . $_SESSION['error'] . '</div>';
                    unset($_SESSION['error']);
                }
            ?>

            <!-- end message -->
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>الاسم:</strong> <?= htmlspecialchars($user['first_name']) ?></li>
                <li class="list-group-item"><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($user['email']) ?></li>
                <?php
                $phone = $user['phone'] ?? null;
                if ($phone) {
                    $phone = (string)$phone; // حول الرقم لسلسلة نصية
                    if (strlen($phone) == 10) {
                        $phone = '0' . $phone;  // ضيف صفر في الأول لو مفقود
                    }
                } else {
                    $phone = 'غير مُسجل';
                }
                ?>
                <li class="list-group-item"><strong>رقم الهاتف:</strong> <?= htmlspecialchars($phone) ?></li>
            </ul>
        </div>
        

            <div class="profile-section box_messages">
                <h2>💬 محادثاتي مع الأطباء</h2>
                <div class="row">
                    <?php if (!empty($conversations)): ?>
                        <?php foreach ($conversations as $conv): ?>
                            <div class="col-md-6 col-lg-12 mb-4 message-card">
                                <div class="doctor-card"> <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">دكتور <?= htmlspecialchars($conv['firstname'] . ' ' . $conv['lastname']) ?></h5>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="unread-badge">
                                                <?= htmlspecialchars($conv['unread_count']) ?> رسالة جديدة
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="text-muted small mb-1">
                                        <?php 
                                            $message_prefix = ($conv['last_message_sender_type'] == 'user') ? 'أنت: ' : 'الدكتور: ';
                                            $display_message = isset($conv['last_message_content']) ? $conv['last_message_content'] : '';
                                            echo $message_prefix . htmlspecialchars(mb_substr($display_message, 0, 50)) . (mb_strlen($display_message) > 50 ? '...' : ''); 
                                        ?>
                                    </p>
                                    <p class="text-muted small">آخر رسالة: <?= date('Y-m-d H:i', strtotime($conv['last_message_time'])) ?></p>
                                    <a href="user_chat.php?id=<?= htmlspecialchars($conv['doctor_id']) ?>" class="btn btn-success btn-chat w-100">ابدأ المحادثة</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center" role="alert">
                                لا توجد محادثات مع الأطباء حتى الآن. يمكنك بدء محادثة من صفحة <a href="specialty.php">الاستشارات</a>.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>



        <div class="profile-section">
            <h2>الأدوية المحفوظة</h2>
            <?php if (!empty($success_message_medication)): ?>
                <div class="alert alert-success">
                    <?= $success_message_medication; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($medications)): ?>
                <table class="table table-striped table-bordered" dir="rtl">
                    <thead class="table-success">
                        <tr>
                            <th>الاسم</th>
                            <th>الجرعة</th>
                            <th>عدد المرات</th>
                            <th>تاريخ البداية</th>
                            <th>حذف</th>  <!-- عمود جديد -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medications as $med): ?>
                            <tr>
                                <td><?= htmlspecialchars($med['name']) ?></td>
                                <td>كل <?= htmlspecialchars($med['dosage']) ?></td>
                                <td><?= htmlspecialchars($med['frequency']) ?> مرات في اليوم</td>
                                <td><?= date('Y-m-d H:i', strtotime($med['start_time'])) ?></td>
                                <td>
                                    <form method="POST" action="operations/delete_medication.php" onsubmit="return confirm('هل أنت متأكد من حذف هذا التذكير؟');">
                                        <input type="hidden" name="user_medication_id" value="<?= $med['user_medication_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">لا يوجد أدوية محفوظة لك حاليًا.</p>
            <?php endif; ?>

            <a href="alarm_page.php" class="btn btn-success mt-3">➕ إضافة تذكير جديد</a>
        </div>

        
        <div class="profile-section">
            <h2>🔧 إعدادات الحساب</h2>
            <a href="operations/change_personal_data.php" class="btn btn-outline-secondary">تعديل بياناتك</a>
            <a href="operations/notifications_settings.php" class="btn btn-outline-secondary">إعدادات التنبيهات</a>
        </div>
        
        <div class="profile-section">
            <h2>🗑️ الإجراءات الحساسة</h2>
            <a href="operations/logout.php" onclick="return confirm('هل تريد تسجيل الخروج؟')" class="btn btn-danger">تسجيل الخروج</a>
            <a href="operations/delete_account.php" onclick="return confirm('هل أنت متأكد من حذف الحساب؟')" class="btn btn-outline-danger">حذف الحساب نهائيًا</a>
        </div>
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

    </body>
    </html>
