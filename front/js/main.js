document.querySelector('.search-box button').addEventListener('click', function () {
  alert('جارٍ البحث عن الأطباء...');
});
document.addEventListener("DOMContentLoaded", function () {
  const buttons = document.querySelectorAll(".btn-success");
  buttons.forEach(button => {
      button.addEventListener("click", function () {
          alert("تم حجز استشارتك بنجاح!");
      });
  });
});