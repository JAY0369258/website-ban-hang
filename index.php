<?php
session_start();
require_once 'db_connect.php';

$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Bán Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">AT Store</h1>
            <div>
                <a href="index.php" class="px-4">Trang Chủ</a>
                <div class="relative inline-block px-4">
                    <span class="cursor-pointer">Danh Mục</span>
                    <div class="absolute hidden bg-white text-black shadow-lg rounded mt-2">
                        <?php foreach ($categories as $category): ?>
                            <a href="category.php?id=<?php echo $category['id']; ?>" class="block px-4 py-2 hover:bg-gray-100"><?php echo htmlspecialchars($category['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <a href="cart.php" class="px-4">Giỏ Hàng (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="orders.php" class="px-4">Đơn Hàng</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/products.php" class="px-4">Quản Trị</a>
                    <?php endif; ?>
                    <a href="logout.php" class="px-4">Đăng Xuất</a>
                <?php else: ?>
                    <a href="login.php" class="px-4">Đăng Nhập</a>
                    <a href="register.php" class="px-4">Đăng Ký</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <form method="GET" action="search.php" class="mb-8">
            <input type="text" name="q" class="border rounded px-4 py-2 w-full md:w-1/2" placeholder="Nhập tên sản phẩm...">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tìm Kiếm</button>
        </form>
        <h2 class="text-3xl font-bold text-center mb-8">Sản Phẩm</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="bg-white rounded-lg shadow-lg p-4">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-48 object-cover rounded">
                    <h3 class="text-xl font-semibold mt-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="text-gray-600"><?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</p>
                    <p class="text-gray-600">Tồn kho: <?php echo $product['stock']; ?> <?php echo $product['stock'] > 0 ? '(Còn hàng)' : '(Hết hàng)'; ?></p>
                    <?php if ($product['stock'] > 0): ?>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Xem chi tiết</a>
                    <?php else: ?>
                        <p class="text-red-600 mt-4">Hết hàng</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>