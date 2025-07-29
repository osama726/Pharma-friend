<?php
session_start();
session_unset(); // يمسح كل بيانات السيشن
session_destroy(); // ينهي الجلسة تمامًا

// ارجع المستخدم لصفحة تسجيل الدخول أو الهوم
header("Location: ../home_page.php");
exit;
?>
