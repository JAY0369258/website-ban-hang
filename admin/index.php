<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Điều Khiển Quản Trị</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>

<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Bảng Điều Khiển Quản Trị</h1>
            <div>
                <a href="index.php" class="px-4">Trang Chủ</a>
                <a href="products.php" class="px-4">Quản Lý Sản Phẩm</a>
                <a href="categories.php" class="px-4">Quản Lý Danh Mục</a>
                <a href="orders.php" class="px-4">Quản Lý Đơn Hàng</a>
                <a href="reports.php" class="px-4">Báo Cáo</a>
                <a href="../logout.php" class="px-4">Đăng Xuất</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Bảng Điều Khiển Quản Trị</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="products.php" class="bg-white rounded-lg shadow-lg p-6 text-center hover:bg-gray-50">
                <h3 class="text-xl font-semibold">Quản Lý Sản Phẩm</h3>
                <p class="text-gray-600">Thêm, sửa, xóa sản phẩm</p>
            </a>
            <a href="categories.php" class="bg-white rounded-lg shadow-lg p-6 text-center hover:bg-gray-50">
                <h3 class="text-xl font-semibold">Quản Lý Danh Mục</h3>
                <p class="text-gray-600">Quản lý danh mục sản phẩm</p>
            </a>
            <a href="orders.php" class="bg-white rounded-lg shadow-lg p-6 text-center hover:bg-gray-50">
                <h3 class="text-xl font-semibold">Quản Lý Đơn Hàng</h3>
                <p class="text-gray-600">Xem và cập nhật trạng thái đơn hàng</p>
            </a>
            <a href="reports.php" class="bg-white rounded-lg shadow-lg p-6 text-center hover:bg-gray-50">
                <h3 class="text-xl font-semibold">Báo Cáo Thống Kê</h3>
                <p class="text-gray-600">Xem doanh thu và sản phẩm bán chạy</p>
            </a>
        </div>
    </div>
</body>

</html>