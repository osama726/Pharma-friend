<?php
header('Content-Type: application/json; charset=utf-8');

// تضمين ملف الاتصال بقاعدة البيانات (db.php)
// لأن locations_api.php جوه فولدر api/، فمحتاجين نخرج منه (..) عشان نوصل لـ pharma_db/
include "../pharma_db/db.php"; 

// الآن، كائن الاتصال PDO متاح باسم $pdo من ملف db.php

// الجداول وأعمدتها الحالية
$tables = [
    "pharmacies" => [
        "type" => "صيدلية",
        "fields" => ["name", "address", "phone", "working_hours", "delivery_service", "website", "facebook", "latitude", "longitude"]
    ],
    "hospitals" => [
        "type" => "مستشفى",
        "fields" => ["name", "address", "phone", "Sections", "Working_hours", "latitude", "longitude"]
    ],
    "labs" => [
        "type" => "معمل",
        "fields" => ["name", "address", "phone", "working_hours", "website", "latitude", "longitude"]
    ],
    "radiology_centers" => [
        "type" => "مركز أشعة",
        "fields" => ["name", "phone", "address", "working_hours", "delivery_service", "website", "latitude", "longitude"]
    ]
];

$results = [];

try {
    // جلب البيانات الحالية (صيدليات، مستشفيات، معامل، مراكز أشعة)
    foreach ($tables as $table => $config) {
        // نغير أسماء الأعمدة عند الجلب لتكون متطابقة مع الأسماء اللي بنستخدمها في الـ frontend
        $selectFields = [];
        foreach ($config["fields"] as $field) {
            // بعض الأعمدة ليها أسماء مختلفة في الـ popupContent في الـ JS
            if ($field === 'Working_hours' && $table === 'hospitals') {
                $selectFields[] = 'Working_hours AS working_hours'; // توحيد الاسم
            } else {
                $selectFields[] = $field;
            }
        }
        $fieldList = implode(", ", $selectFields);
        
        $sql = "SELECT $fieldList FROM $table";
        $stmt = $pdo->query($sql);

        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entry = [
                    "type" => $config["type"],
                    "name" => $row["name"],
                    "lat" => floatval($row["latitude"]),
                    "lng" => floatval($row["longitude"])
                ];

                // إضافة باقي الحقول مع معالجة خاصة لـ Sections و Delivery_service (لو موجودين)
                foreach ($row as $key => $value) {
                    if (!in_array($key, ["name", "latitude", "longitude"])) {
                        // لو كان اسم العمود "Sections" أو "delivery_service"، نستخدمه كما هو
                        // وإلا نستخدم الـ key كما هو
                        $entry[$key] = $value;
                    }
                }
                $results[] = $entry;
            }
        }
    }

    // *** إضافة جلب بيانات عيادات الأطباء من جدول clinics_locations ***
    $stmt_clinics = $pdo->prepare("
        SELECT 
            d.firstname, 
            d.lastname, 
            s.name AS specialty_name,
            cl.address, 
            cl.phone_number, 
            cl.latitude, 
            cl.longitude,
            cl.start_time_daily,
            cl.end_time_daily,
            cl.appointment_price,
            cl.service_type, -- عشان لو عايز تعرض أنواع خدمات مختلفة للدكتور (عيادة، استشارة مادية)
            GROUP_CONCAT(cwd.day_of_week ORDER BY 
                FIELD(cwd.day_of_week, 'السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة')
            ) AS working_days_list
        FROM 
            clinics_locations cl
        JOIN 
            doctors d ON cl.doctor_id = d.id
        LEFT JOIN
            specialties s ON d.specialty_id = s.id
        LEFT JOIN
            clinic_working_days cwd ON cl.id = cwd.clinic_location_id
        WHERE 
            cl.service_type IN ('clinic', 'both') -- بنجيب العيادات أو اللي بتقدم الاتنين
        GROUP BY cl.id
    ");
    $stmt_clinics->execute();

    while ($row = $stmt_clinics->fetch(PDO::FETCH_ASSOC)) {
        $doctor_name = "د. " . htmlspecialchars($row['firstname']) . " " . htmlspecialchars($row['lastname']);
        $full_address = htmlspecialchars($row['address']);
        $phone = htmlspecialchars($row['phone_number']);
        $specialty = htmlspecialchars($row['specialty_name'] ?? 'غير محدد');

        // تنسيق مواعيد العمل وأيام العمل
        $working_hours_formatted = "من " . date('h:i A', strtotime($row['start_time_daily'])) . " إلى " . date('h:i A', strtotime($row['end_time_daily']));
        $days_formatted = $row['working_days_list'] ? str_replace(',', ', ', htmlspecialchars($row['working_days_list'])) : 'غير محددة';
        $full_working_schedule = $working_hours_formatted . " - أيام: " . $days_formatted;

        $price = $row['appointment_price'] ? htmlspecialchars($row['appointment_price']) . ' ج.م' : 'غير محدد';

        $results[] = [
            'name' => $doctor_name,
            'type' => 'عيادة طبيب', // ده اللي الـ JavaScript هيستخدمه عشان يعرف نوع المكان
            'address' => $full_address,
            'phone' => $phone,
            'lat' => floatval($row['latitude']),
            'lng' => floatval($row['longitude']),
            'specialty' => $specialty,
            'working_hours' => $full_working_schedule,
            'appointment_price' => $price,
            // لو عايز تبعت الـ doctor_id لأي استخدام مستقبلي في الـ popup أو غيره
            // 'doctor_id' => $row['doctor_id'] 
        ];
    }

    echo json_encode($results, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    // من الأفضل عدم عرض $e->getMessage() مباشرة في الإنتاج لأسباب أمنية
    error_log("API Error: " . $e->getMessage()); 
    echo json_encode(["error" => "حدث خطأ أثناء جلب البيانات."], JSON_UNESCAPED_UNICODE);
    exit;
}
?>