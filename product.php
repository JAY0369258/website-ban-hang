<?php
session_start();
require_once 'db_connect.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}

// Thêm sản phẩm vào giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity > $product['stock']) {
        $error = "Số lượng yêu cầu vượt quá tồn kho ($product[stock] sản phẩm).";
    } else {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'stock' => $product['stock']
            ];
        }
        header('Location: cart.php');
        exit;
    }
}

// Thêm đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review']) && isset($_SESSION['user_id'])) {
    $rating = (int)$_POST['rating'];
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$product_id, $user_id, $rating, $comment]);
    header('Location: product.php?id=' . $product_id);
    exit;
}

// Lấy đánh giá
$stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính trung bình đánh giá
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?");
$stmt->execute([$product_id]);
$rating_data = $stmt->fetch(PDO::FETCH_ASSOC);
$avg_rating = $rating_data['avg_rating'] !== null ? round($rating_data['avg_rating'], 1) : 0;
$review_count = $rating_data['review_count'];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
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
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/index.php" class="px-4">Quản Trị</a>
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
        <div class="bg-white rounded-lg shadow-lg p-6 flex flex-col md:flex-row">
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full md:w-1/2 h-64 object-cover rounded">
            <div class="md:ml-6 mt-4 md:mt-0">
                <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($product['name']); ?></h2>
                <p class="text-gray-600 mt-2"><?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</p>
                <p class="mt-2">Tồn kho: <?php echo $product['stock']; ?> <?php echo $product['stock'] > 0 ? '(Còn hàng)' : '(Hết hàng)'; ?></p>
                <p class="mt-4"><?php echo htmlspecialchars($product['description']); ?></p>
                <?php if (isset($error)): ?>
                    <p class="text-red-600 mt-2"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <?php if ($product['stock'] > 0): ?>
                    <form method="POST" class="mt-4">
                        <label for="quantity" class="mr-2">Số lượng:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="border rounded px-2 py-1 w-16">
                        <button type="submit" name="add_to_cart" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Thêm vào giỏ hàng</button>
                    </form>
                <?php else: ?>
                    <p class="text-red-600 mt-4">Sản phẩm đã hết hàng.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-8">
            <h3 class="text-xl font-semibold mb-4">Đánh Giá Sản Phẩm (<?php echo $review_count; ?> đánh giá, trung bình <?php echo $avg_rating; ?>/5)</h3>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" class="mb-8 bg-white rounded-lg shadow-lg p-6">
                    <h4 class="text-lg font-semibold mb-4">Viết Đánh Giá</h4>
                    <div class="mb-4">
                        <label for="rating" class="block">Điểm đánh giá</label>
                        <select id="rating" name="rating" class="border rounded px-4 py-2" required>
                            <option value="1">1 sao</option>
                            <option value="2">2 sao</option>
                            <option value="3">3 sao</option>
                            <option value="4">4 sao</option>
                            <option value="5">5 sao</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="comment" class="block">Bình luận</label>
                        <textarea id="comment" name="comment" class="border rounded w-full px-4 py-2"></textarea>
                    </div>
                    <button type="submit" name="add_review" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Gửi Đánh Giá</button>
                </form>
            <?php else: ?>
                <p class="mb-4">Vui lòng <a href="login.php" class="text-blue-600 hover:underline">đăng nhập</a> để viết đánh giá.</p>
            <?php endif; ?>

            <?php if ($reviews): ?>
                <h4 class="text-lg font-semibold mb-4">Danh Sách Đánh Giá</h4>
                <?php foreach ($reviews as $review): ?>
                    <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
                        <p class="font-semibold"><?php echo htmlspecialchars($review['username']); ?> - <?php echo $review['rating']; ?>/5 sao</p>
                        <p class="text-gray-600"><?php echo htmlspecialchars($review['comment']); ?></p>
                        <p class="text-gray-500 text-sm"><?php echo $review['created_at']; ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Chưa có đánh giá nào cho sản phẩm này.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>