document.addEventListener("DOMContentLoaded", function () {
  const forms = document.querySelectorAll("form");
  forms.forEach((form) => {
    form.addEventListener("submit", function () {
      if (form.querySelector('button[name="add_to_cart"]')) {
        alert("Sản phẩm đã được thêm vào giỏ hàng!");
      }
    });
  });

  // Xử lý dropdown danh mục
  const categoryMenu = document.querySelector(".relative");
  if (categoryMenu) {
    categoryMenu.addEventListener("mouseenter", function () {
      this.querySelector(".hidden").style.display = "block";
    });
    categoryMenu.addEventListener("mouseleave", function () {
      this.querySelector(".hidden").style.display = "none";
    });
  }
});
