<?php
require_once 'db_connect.php';

$search = isset($_GET['q']) ? filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 1000000;

$query = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category_id) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}
if ($min_price) {
    $query .= " AND p.price >= ?";
    $params[] = $min_price;
}
if ($max_price < 1000000) {
    $query .= " AND p.price <= ?";
    $params[] = $max_price;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm Kiếm</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">At Store</h1>
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
        <h2 class="text-3xl font-bold text-center mb-8">Tìm Kiếm Sản Phẩm</h2>
        <form method="GET" class="mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="border rounded px-4 py-2 flex-1" placeholder="Nhập tên sản phẩm...">
                <select name="category_id" class="border rounded px-4 py-2">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="min_price" value="<?php echo $min_price; ?>" class="border rounded px-4 py-2 w-24" placeholder="Giá min">
                <input type="number" name="max_price" value="<?php echo $max_price; ?>" class="border rounded px-4 py-2 w-24" placeholder="Giá max">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tìm Kiếm</button>
            </div>
        </form>
        <?php if (empty($products)): ?>
            <p class="text-center">Không tìm thấy sản phẩm nào.</p>
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