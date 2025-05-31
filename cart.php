<?php
session_start();
require_once 'db_connect.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    $quantities = $_POST['quantity'];
    foreach ($quantities as $id => $quantity) {
        $quantity = (int)$quantity;
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $stock = $stmt->fetchColumn();
            if ($stock < $quantity) {
                $errors[] = "Sản phẩm {$_SESSION['cart'][$id]['name']} chỉ còn $stock trong kho.";
            } else {
                $_SESSION['cart'][$id]['quantity'] = $quantity;
            }
        }
    }
    // Save to database if logged in
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

        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cart_id]);

        $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        foreach ($_SESSION['cart'] as $id => $item) {
            $stmt->execute([$cart_id, $id, $item['quantity']]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $id = (int)$_POST['product_id'];
    unset($_SESSION['cart'][$id]);
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = (SELECT id FROM cart WHERE user_id = ?) AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $id]);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng</title>
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
                    <a href="logout.php" class="px-4">Đăng Xuất</a>
                <?php else: ?>
                    <a href="login.php" class="px-4">Đăng Nhập</a>
                    <a href="register.php" class="px-4">Đăng Ký</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Giỏ Hàng</h2>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (empty($_SESSION['cart'])): ?>
            <p class="text-center">Giỏ hàng của bạn đang trống.</p>
        <?php else: ?>
            <form method="POST" class="bg-white rounded-lg shadow-lg p-6">
                <table class="w-full mb-4">
                    <thead>
                        <tr class="border-b">
                            <th class="p-4 text-left">Sản Phẩm</th>
                            <th class="p-4 text-left">Giá</th>
                            <th class="p-4 text-left">Số Lượng</th>
                            <th class="p-4 text-left">Tổng</th>
                            <th class="p-4 text-left">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $subtotal = 0; ?>
                        <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                            <tr class="border-b">
                                <td class="p-4"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="p-4"><?php echo number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                                <td class="p-4">
                                    <input type="number" name="quantity[<?php echo $id; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="border rounded w-16 px-2 py-1">
                                </td>
                                <td class="p-4"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> VNĐ</td>
                                <td class="p-4">
                                    <form method="POST">
                                        <input type="hidden" name="product_id" value="<?php echo $id; ?>">
                                        <button type="submit" name="remove_item" class="text-red-600 hover:underline">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                            <?php $subtotal += $item['price'] * $item['quantity']; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="text-xl font-bold">Tổng cộng: <?php echo number_format($subtotal, 0, ',', '.'); ?> VNĐ</p>
                <button type="submit" name="update_cart" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Cập Nhật Giỏ Hàng</button>
                <a href="checkout.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Thanh Toán</a>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>