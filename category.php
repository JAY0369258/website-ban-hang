<?php
require_once 'db_connect.php';

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ?");
$stmt->execute([$category_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">AT Store</h1>
            <div>
                <a href="index.php" class="px-4">Trang Chủ</a>
                <a href="cart.php" class="px-4">Giỏ Hàng (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="orders.php" class="px-4">Đơn Hàng</a>
                    <a href="logout.php" class="px-4">Đăng Xuất</a>
                <?php else: ?>
                    <a href="login.php" class="px-4">Đăng Nhập</a>
                    <a href="register.php" class="px-4">Đăng Ký</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8"><?php echo htmlspecialchars($category['name']); ?></h2>
        <?php if (empty($products)): ?>
            <p class="text-center">Không có sản phẩm trong danh mục này.</p>
        <?php else: ?>
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
        <?php endif; ?>
    </div>
</body>

</html>