<?php
session_start();
require_once 'db_connect.php';

$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$search = strip_tags(filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW) ?? '');
$category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
$sort = in_array($_GET['sort'] ?? '', ['price_asc', 'price_desc']) ? $_GET['sort'] : '';

$query = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];
if ($search) {
    $query .= " AND p.name LIKE ?";
    $params[] = "%$search%";
}
if ($category_id) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}
if ($sort === 'price_asc') {
    $query .= " ORDER BY p.price ASC";
} elseif ($sort === 'price_desc') {
    $query .= " ORDER BY p.price DESC";
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product && $product['stock'] >= $quantity) {
        if (!isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        } else {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            if ($_SESSION['cart'][$product_id]['quantity'] > $product['stock']) {
                $_SESSION['cart'][$product_id]['quantity'] = $product['stock'];
            }
        }

        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $cart_id = $stmt->fetchColumn();

            if (!$cart_id) {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
                $stmt->execute([$user_id]);
                $cart_id = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("REPLACE INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$cart_id, $product_id, $_SESSION['cart'][$product_id]['quantity']]);
        }
    } else {
        $errors[] = "Sản phẩm không đủ tồn kho.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cửa Hàng Online</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Cửa Hàng Online</h1>
            <div>
                <a href="index.php" class="px-4">Trang Chủ</a>
                <a href="cart.php" class="px-4">Giỏ Hàng (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                <a href="orders.php" class="px-4">Đơn Hàng</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin/index.php" class="px-4">Quản Trị</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="account.php" class="px-4">Tài Khoản</a>
                    <a href="logout.php" class="px-4">Đăng Xuất</a>
                <?php else: ?>
                    <a href="login.php" class="px-4">Đăng Nhập</a>
                    <a href="register.php" class="px-4">Đăng Ký</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Sản Phẩm</h2>
        <form method="GET" class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Tìm kiếm sản phẩm" class="border rounded w-full px-4 py-2">
                </div>
                <div>
                    <select name="category_id" class="border rounded w-full px-4 py-2">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <select name="sort" class="border rounded w-full px-4 py-2">
                        <option value="">Sắp xếp</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Giá: Thấp đến Cao</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Giá: Cao đến Thấp</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tìm Kiếm</button>
        </form>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <?php if ($product['image']): ?>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-48 w-full object-cover mb-4">
                    <?php endif; ?>
                    <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($product['category_name']); ?></p>
                    <p class="text-lg font-bold"><?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</p>
                    <p class="text-gray-600"><?php echo $product['stock'] > 0 ? 'Còn hàng' : 'Hết hàng'; ?></p>
                    <form method="POST" class="mt-4">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="border rounded w-16 px-2 py-1">
                        <button type="submit" name="add_to_cart" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 <?php echo $product['stock'] <= 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>Thêm vào giỏ</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>