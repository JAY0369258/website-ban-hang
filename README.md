Website Bán Hàng
Đây là dự án website bán hàng được xây dựng bằng PHP, MySQL và Tailwind CSS, chạy trên Laragon. Website hỗ trợ các chức năng:

Tìm kiếm/lọc sản phẩm, danh mục, đánh giá sản phẩm.
Giỏ hàng, thanh toán, quản lý kho.
Admin panel: Quản lý sản phẩm, danh mục, đơn hàng, khuyến mãi, báo cáo.
Tìm kiếm đơn hàng, hủy đơn hàng (người dùng).
Hệ thống mã giảm giá.

Cài đặt

Yêu cầu:

PHP >= 7.4
MySQL
Laragon hoặc XAMPP
Composer (nếu sử dụng thư viện)

Hướng dẫn:

Clone repository: git clone https://github.com/username/website-ban-hang.git
Sao chép db_connect.example.php thành db_connect.php và cập nhật thông tin cơ sở dữ liệu.
Nhập cơ sở dữ liệu từ database.sql vào MySQL.
Cấu hình web server để trỏ vào thư mục website.
Truy cập http://localhost/website.

Cấu trúc thư mục:

/admin: Quản lý admin (sản phẩm, danh mục, đơn hàng, khuyến mãi, báo cáo).
/Uploads: Thư mục lưu hình ảnh sản phẩm.
index.php, cart.php, checkout.php, orders.php: Giao diện người dùng.

Góp ý
Liên hệ qua email: email@example.com hoặc tạo issue trên GitHub.
