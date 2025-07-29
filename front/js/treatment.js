const arabicLetters = "ا ب ت ث ج ح خ د ر ز س ش ص ض ط ظ ع غ ف ق ك ل م ن ه و لا ي".split(" ");
const englishLetters = "A B C D E F G H I J K L M N O P Q R S T U V W X Y Z".split(" ");

let activeFilter = ''; // حالة الفلتر الحالي سواء كان حرف أو نص

function createKeyboard(letters, containerId) {
    const container = document.getElementById(containerId);
    letters.forEach(letter => {
        let key = document.createElement("div");
        key.className = "key";
        key.innerText = letter;
        key.onclick = () => {
            document.getElementById("search-box").value = ''; // امسح أي كتابة
            activeFilter = letter;
            highlightKey(key);
            applyFilter(letter);
        };
        container.appendChild(key);
    });
}

function applyFilter(filter) {
    let foundMed = false;
    let foundDis = false;

    document.querySelectorAll("#medicinesTable td").forEach(td => {
        const text = td.textContent.trim().toLowerCase().replace(/^ال/, '');
        if (text.startsWith(filter.toLowerCase())) {
            td.style.display = "";
            foundMed = true;
        } else {
            td.style.display = "none";
        }
    });

    document.querySelectorAll("#diseasesTable td").forEach(td => {
        const text = td.textContent.trim().toLowerCase().replace(/^ال/, '');
        if (text.startsWith(filter.toLowerCase())) {
            td.style.display = "";
            foundDis = true;
        } else {
            td.style.display = "none";
        }
    });

    // عرض أو إخفاء الرسائل
    document.getElementById("noResultsMedicines").style.display = foundMed ? "none" : "block";
    document.getElementById("noResultsDiseases").style.display = foundDis ? "none" : "block";
    document.getElementById("resetFilter").style.display = "block";
}

function highlightKey(selectedKey) {
    document.querySelectorAll(".keyboard .key").forEach(key => key.classList.remove("active"));
    selectedKey.classList.add("active");
}

// البحث بالكلمة
function filterByTextInput(text) {
    activeFilter = '';
    document.querySelectorAll(".keyboard .key").forEach(key => key.classList.remove("active"));

    let foundMed = false;
    let foundDis = false;

    document.querySelectorAll("#medicinesTable td").forEach(td => {
        const tdText = td.textContent.trim().toLowerCase();
        if (tdText.includes(text)) {
            td.style.display = "";
            foundMed = true;
        } else {
            td.style.display = "none";
        }
    });

    document.querySelectorAll("#diseasesTable td").forEach(td => {
        const tdText = td.textContent.trim().toLowerCase();
        if (tdText.includes(text)) {
            td.style.display = "";
            foundDis = true;
        } else {
            td.style.display = "none";
        }
    });

    document.getElementById("noResultsMedicines").style.display = foundMed ? "none" : "block";
    document.getElementById("noResultsDiseases").style.display = foundDis ? "none" : "block";
    document.getElementById("resetFilter").style.display = "block";
}

// زر "عرض الكل"
function resetTables() {
    document.querySelectorAll("#medicinesTable td, #diseasesTable td").forEach(td => {
        td.style.display = "";
    });
    document.querySelectorAll(".keyboard .key").forEach(key => key.classList.remove("active"));
    document.getElementById("search-box").value = "";
    document.getElementById("noResultsMedicines").style.display = "none";
    document.getElementById("noResultsDiseases").style.display = "none";
    document.getElementById("resetFilter").style.display = "none";
    activeFilter = '';
}

document.addEventListener("DOMContentLoaded", () => {
    createKeyboard(arabicLetters, "arabicKeyboard");
    createKeyboard(englishLetters, "englishKeyboard");

    document.getElementById("search-box").addEventListener("input", (e) => {
        const val = e.target.value.trim().toLowerCase();
        if (val) {
            filterByTextInput(val);
        } else {
            resetTables();
        }
    });
});