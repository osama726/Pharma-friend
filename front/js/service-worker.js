// اسم الكاش الخاص بك
const CACHE_NAME = 'pharma-friend-cache-v1';
// الملفات الأساسية التي سيتم تخزينها مؤقتاً
const urlsToCache = [
  '/pharma_friend/front/alarm_page.php',
  '/pharma_friend/front/css/alarm.css',
  // '/pharma_friend/front/js/treatment.js', // لو فيه ملف JS تاني لصفحة الـ alarm
  '/pharma_friend/front/images/Logo.png', // أيقونات ممكن تستخدم في الإشعارات
  // ممكن تضيف ملفات CSS/JS/صور أخرى أساسية لموقعك هنا
];

// حدث التثبيت (Install Event)
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

// حدث التفعيل (Activate Event)
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.filter(function(cacheName) {
          return cacheName.startsWith('pharma-friend-cache-') && cacheName !== CACHE_NAME;
        }).map(function(cacheName) {
          return caches.delete(cacheName);
        })
      );
    })
  );
});

// حدث الجلب (Fetch Event) - لخدمة الملفات من الكاش أولاً
self.addEventListener('fetch', function(event) {
  event.respondWith(
    caches.match(event.request)
      .then(function(response) {
        // Cache hit - return response
        if (response) {
          return response;
        }
        // No cache hit - fetch from network
        return fetch(event.request);
      })
  );
});

// حدث استقبال الإشعار (Push Event) - لو بتستخدم Push notifications (يتطلب سيرفر خارجي)
self.addEventListener('push', function(event) {
  const options = {
    body: event.data ? event.data.text() : 'موعد الدواء قادم!',
    icon: '/pharma_friend/front/images/Logo.png',
    badge: '/pharma_friend/front/images/Logo.png'
  };
  event.waitUntil(
    self.registration.showNotification('تذكير بالدواء', options)
  );
});

// حدث النقر على الإشعار (Notification Click Event)
self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  event.waitUntil(
    // افتح صفحة معينة عند الضغط على الإشعار
    clients.openWindow('/pharma_friend/front/alarm_page.php')
  );
});

// حدث showNotification (يتم استدعاؤه من الصفحة الرئيسية لإنشاء إشعار محلي)
// هذا ليس حدثاً، ولكنه يعالج الطلبات من الصفحة
// هذا الجزء من الكود غير ضروري داخل service-worker.js، لأنه showNotification يتم استدعاؤه على reg
// ولكن من المهم التأكد من أن الـ reg.showNotification يعمل