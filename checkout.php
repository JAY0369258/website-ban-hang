<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $user_id = $_SESSION['user_id'];
    $name = strip_tags(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW));
    $address = strip_tags(filter_input(INPUT_POST, 'address', FILTER_UNSAFE_RAW));
    $total = 0;
    $errors = [];

    // Validate inputs
    if (empty($name) || empty($address)) {
        $errors[] = "Vui lòng nhập đầy đủ họ tên và địa chỉ.";
    }

    // Check stock availability
    foreach ($_SESSION['cart'] as $id => $item) {
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $stock = $stmt->fetchColumn();
        if ($stock === false || $stock < $item['quantity']) {
            $errors[] = "Sản phẩm {$item['name']} chỉ còn $stock trong kho, bạn yêu cầu {$item['quantity']}.";
        }
        $total += $item['price'] * $item['quantity'];
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Save order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, name, address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $total, $name, $address]);
            $order_id = $pdo->lastInsertId();

            // Save order items and update stock
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            foreach ($_SESSION['cart'] as $id => $item) {
                $stmt->execute([$order_id, $id, $item['quantity'], $item['price']]);
                $stmt_stock->execute([$item['quantity'], $id]);
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            header('Location: checkout.php?success=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Đặt hàng thất bại: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán</title>
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
                <a href="logout.php" class="px-4">Đăng Xuất</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Thanh Toán</h2>
        <?php if (isset($_GET['success'])): ?>
            <p class="text-center text-green-600 font-bold">Đặt hàng thành công! Cảm ơn bạn đã mua sắm.</p>
            <a href="index.php" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Quay về trang chủ</a>
        <?php elseif (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Thông tin đơn hàng</h3>
                <?php if (empty($_SESSION['cart'])): ?>
                    <p>Giỏ hàng của bạn đang trống.</p>
                <?php else: ?>
                    <table class="w-full mb-4">
                        <thead>
                            <tr class="border-b">
                                <th class="p-4 text-left">Sản Phẩm</th>
                                <th class="p-4 text-left">Giá</th>
                                <th class="p-4 text-left">Số Lượng</th>
                                <th class="p-4 text-left">Tổng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total = 0; ?>
                            <?php foreach ($_SESSION['cart'] as $item): ?>
                                <tr class="border-b">
                                    <td class="p-4"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td class="p-4"><?php echo number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                                    <td class="p-4"><?php echo $item['quantity']; ?></td>
                                    <td class="p-4"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> VNĐ</td>
                                </tr>
                                <?php $total += $item['price'] * $item['quantity']; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="text-xl font-bold">Tổng cộng: <?php echo number_format($total, 0, ',', '.'); ?> VNĐ</p>
                    <form method="POST" class="mt-4">
                        <h3 class="text-xl font-semibold mb-4">Thông tin khách hàng</h3>
                        <div class="mb-4">
                            <label for="name" class="block">Họ Tên</label>
                            <input type="text" id="name" name="name" class="border rounded w-full px-4 py-2" required>
                        </div>
                        <div class="mb-4">
                            <label for="address" class="block">Địa Chỉ</label>
                            <input type="text" id="address" name="address" class="border rounded w-full px-4 py-2" required>
                        </div>
                        <button type="submit" name="place_order" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Đặt Hàng</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>