<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $id => $quantity) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id]['quantity'] = (int)$quantity;
        }
    }
    header('Location: cart.php');
    exit;
}

if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header('Location: cart.php');
    exit;
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
    <!-- Navbar -->
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

    <!-- Giỏ hàng -->
    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Giỏ Hàng</h2>
        <?php if (empty($_SESSION['cart'])): ?>
            <p class="text-center">Giỏ hàng của bạn đang trống.</p>
        <?php else: ?>
            <form method="POST">
                <table class="w-full bg-white rounded-lg shadow-lg">
                    <thead>
                        <tr class="border-b">
                            <th class="p-4 text-left">Sản Phẩm</th>
                            <th class="p-4 text-left">Giá</th>
                            <th class="p-4 text-left">Số Lượng</th>
                            <th class="p-4 text-left">Tổng</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total = 0; ?>
                        <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                            <tr class="border-b">
                                <td class="p-4"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="p-4"><?php echo number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                                <td class="p-4">
                                    <input type="number" name="quantity[<?php echo $id; ?>]" value="<?php echo $item['quantity']; ?>" min="0" class="border rounded px-2 py-1 w-16">
                                </td>
                                <td class="p-4"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> VNĐ</td>
                                <td class="p-4">
                                    <a href="cart.php?remove=<?php echo $id; ?>" class="text-red-600 hover:underline">Xóa</a>
                                </td>
                            </tr>
                            <?php $total += $item['price'] * $item['quantity']; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="mt-4 flex justify-between">
                    <button type="submit" name="update_cart" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Cập nhật giỏ hàng</button>
                    <p class="text-xl font-bold">Tổng cộng: <?php echo number_format($total, 0, ',', '.'); ?> VNĐ</p>
                </div>
            </form>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="checkout.php" class="mt-4 inline-block bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Thanh Toán</a>
            <?php else: ?>
                <p class="mt-4 text-red-600">Vui lòng <a href="login.php" class="underline">đăng nhập</a> để thanh toán.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>

</html>