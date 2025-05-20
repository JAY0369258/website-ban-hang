<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$date_from = date('Y-m-01'); // Mặc định: đầu tháng hiện tại
$date_to = date('Y-m-d');   // Mặc định: hôm nay
$total_revenue = 0;
$top_products = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['filter'])) {
    $date_from = strip_tags(filter_input(INPUT_GET, 'date_from', FILTER_UNSAFE_RAW));
    $date_to = strip_tags(filter_input(INPUT_GET, 'date_to', FILTER_UNSAFE_RAW));
}

if (!empty($date_from) && !empty($date_to)) {
    // Tính tổng doanh thu
    $stmt = $pdo->prepare("SELECT SUM(total) as total_revenue 
                           FROM orders 
                           WHERE status = 'completed' 
                           AND created_at BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to . ' 23:59:59']);
    $total_revenue = $stmt->fetchColumn() ?: 0;

    // Sản phẩm bán chạy
    $stmt = $pdo->prepare("SELECT p.name, SUM(oi.quantity) as total_quantity, SUM(oi.quantity * oi.price) as total_sales 
                           FROM order_items oi 
                           JOIN products p ON oi.product_id = p.id 
                           JOIN orders o ON oi.order_id = o.id 
                           WHERE o.status = 'completed' 
                           AND o.created_at BETWEEN ? AND ? 
                           GROUP BY p.id, p.name 
                           ORDER BY total_quantity DESC 
                           LIMIT 10");
    $stmt->execute([$date_from, $date_to . ' 23:59:59']);
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Thống Kê</title>
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
                <a href="../logout.php" class="px-4">Đăng Xuất</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Báo Cáo Thống Kê</h2>
        <form method="GET" class="mb-8 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Lọc Báo Cáo</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="date_from" class="block">Từ Ngày</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="border rounded w-full px-4 py-2" required>
                </div>
                <div>
                    <label for="date_to" class="block">Đến Ngày</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="border rounded w-full px-4 py-2" required>
                </div>
            </div>
            <button type="submit" name="filter" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Lọc</button>
        </form>

        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Tổng Quan Doanh Thu</h3>
            <p class="text-gray-600">Tổng doanh thu (từ <?php echo htmlspecialchars($date_from); ?> đến <?php echo htmlspecialchars($date_to); ?>):
                <span class="font-bold"><?php echo number_format($total_revenue, 0, ',', '.'); ?> VNĐ</span>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Sản Phẩm Bán Chạy</h3>
            <?php if (empty($top_products)): ?>
                <p class="text-center">Không có dữ liệu sản phẩm bán chạy.</p>
            <?php else: ?>
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="p-4 text-left">Sản Phẩm</th>
                            <th class="p-4 text-left">Số Lượng Bán</th>
                            <th class="p-4 text-left">Doanh Thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                            <tr class="border-b">
                                <td class="p-4"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="p-4"><?php echo $product['total_quantity']; ?></td>
                                <td class="p-4"><?php echo number_format($product['total_sales'], 0, ',', '.'); ?> VNĐ</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>