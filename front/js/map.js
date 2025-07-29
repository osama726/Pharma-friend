const map = L.map("map").setView([31.03993, 31.37953], 13);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  const icons = {
    "صيدلية": L.icon({ iconUrl: "../api/icon/pharmacy.png", iconSize: [40, 40] }),
    "مستشفى": L.icon({ iconUrl: "../api/icon/hospital.png", iconSize: [40, 40] }),
    "معمل": L.icon({ iconUrl: "../api/icon/lab.png", iconSize: [50, 50] }),
    "مركز أشعة": L.icon({ iconUrl: "../api/icon/radiology_centers.png", iconSize: [50, 50] }),
    "عيادة الطبيب": L.icon({ iconUrl: "../api/icon/doctor.png", iconSize: [50, 50] }),
    "موقعي": L.icon({ iconUrl: "../api/icon/human.png", iconSize: [40, 40] })
  };

  function isValidUrl(url) {
    try {
      if (!url || typeof url !== "string") return false;
      const parsed = new URL(url);
      return parsed.protocol === "http:" || parsed.protocol === "https:";
    } catch (_) {
      return false;
    }
  }

  const apiUrl = "http://main/pharma_friend/api/locations_api.php";

fetch(apiUrl)
  .then(response => {
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    const markers = data.map(place => {
      // الـ icon هيتم تحديده بناءً على الـ place.type اللي جاي من الـ API
      const icon = icons[place.type] || icons["صيدلية"]; // لو النوع مش موجود، يستخدم أيقونة افتراضية
      const marker = L.marker([place.lat, place.lng], { icon }).addTo(map);

      let popupContent = `<strong>${place.name}</strong><br>`;
      popupContent += `🏷: ${place.type}<br>`;
      
      // *** تعديل هنا: إضافة شروط لعرض البيانات بناءً على نوع المكان ***
      if (place.address) popupContent += `📍 العنوان: ${place.address}<br>`;
      if (place.phone) popupContent += `☎️ التليفون: ${place.phone}<br>`;

      if (place.type === 'عيادة طبيب') {
        // بيانات خاصة بعيادة الطبيب
        if (place.specialty) popupContent += `🧑‍⚕️ التخصص: ${place.specialty}<br>`;
        if (place.working_hours) popupContent += `🕒 مواعيد العمل: ${place.working_hours}<br>`;
        if (place.appointment_price) popupContent += `💰 سعر الكشف: ${place.appointment_price}<br>`;
        // ممكن تضيف هنا رابط لملف تعريف الدكتور أو صفحة حجز المواعيد لو موجودة في الـ API
        // if (place.doctor_id) popupContent += `<a href="doc_profile.php?id=${place.doctor_id}">عرض ملف الدكتور</a><br>`;

      } else {
        // بيانات خاصة بأنواع الأماكن الأخرى (صيدلية، مستشفى، معمل، مركز أشعة)
        if (place.working_hours) popupContent += `🕒 مواعيد العمل: ${place.working_hours}<br>`;
        if (place.delivery_service) popupContent += `🚚 خدمة التوصيل: ${place.delivery_service}<br>`;
        if (place.website) popupContent += `🌐 الموقع الإلكتروني: ${isValidUrl(place.website) ? `<a href="${place.website}" target="_blank">زيارة الموقع</a>` : "غير متوفر"}<br>`;
        if (place.facebook) popupContent += `📘 فيسبوك: ${isValidUrl(place.facebook) ? `<a href="${place.facebook}" target="_blank">صفحة الفيسبوك</a>` : "غير متوفر"}<br>`;
        if (place.Sections) popupContent += `🏥 الأقسام: ${place.Sections}<br>`;
      }

      marker.bindPopup(popupContent);
      return { ...place, marker };
    });

      document.getElementById("search-box").addEventListener("input", function () {
        const searchText = this.value.trim().toLowerCase();
        markers.forEach(p => {
          const match = p.name.toLowerCase().includes(searchText);
                  (p.address && p.address.toLowerCase().includes(searchText)) ||
                  (p.specialty && p.specialty.toLowerCase().includes(searchText)); // إضافة البحث عن التخصص
          if (match || searchText === "") {
            p.marker.addTo(map);
            if (match && searchText !== "") {
              p.marker.openPopup();
              map.setView([p.lat, p.lng], 15);
            }
          }
          else {
            map.removeLayer(p.marker);
          }
        });
      });
    })
    .catch(error => {
      alert("حدث خطأ أثناء تحميل البيانات. تأكد من الاتصال أو الرابط.");
      console.error("Fetch error:", error);//////////
    });

  document.getElementById("locate-btn").addEventListener("click", () => {
    if (!navigator.geolocation) {
      alert("المتصفح لا يدعم تحديد الموقع");
      return;
    }

    navigator.geolocation.getCurrentPosition(
      position => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;

        L.marker([lat, lng], { icon: icons["موقعي"] })
          .addTo(map)
          .bindPopup("📍 هذا هو موقعك الحالي")
          .openPopup();

        map.setView([lat, lng], 15);
      },
      () => {
        alert("تعذر الحصول على الموقع");
      }
    );
  });
