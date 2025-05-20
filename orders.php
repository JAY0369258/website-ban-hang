<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = (int)$_POST['order_id'];
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order && in_array($order['status'], ['pending', 'processing'])) {
        $pdo->beginTransaction();
        try {
            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$order_id]);

            // Restore stock
            $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            foreach ($items as $item) {
                $stmt_stock->execute([$item['quantity'], $item['product_id']]);
            }

            $pdo->commit();
            header('Location: orders.php?success=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Hủy đơn hàng thất bại: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $error = "Đơn hàng không thể hủy.";
    }
}

$stmt = $pdo->prepare("SELECT o.*, oi.product_id, oi.quantity, oi.price, p.name 
                       FROM orders o 
                       JOIN order_items oi ON o.id = oi.order_id 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE o.user_id = ? 
                       ORDER BY o.created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped_orders = [];
foreach ($orders as $order) {
    $grouped_orders[$order['id']]['order'] = $order;
    $grouped_orders[$order['id']]['items'][] = $order;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Hàng Của Tôi</title>
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
        <h2 class="text-3xl font-bold text-center mb-8">Đơn Hàng Của Tôi</h2>
        <?php if (isset($_GET['success'])): ?>
            <p class="text-center text-green-600 font-bold">Hủy đơn hàng thành công!</p>
        <?php elseif (isset($error)): ?>
            <p class="text-center text-red-600 font-bold"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (empty($grouped_orders)): ?>
            <p class="text-center">Bạn chưa có đơn hàng nào.</p>
        <?php else: ?>
            <?php foreach ($grouped_orders as $order_id => $data): ?>
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h3 class="text-xl font-semibold">Đơn hàng #<?php echo $order_id; ?> - <?php echo $data['order']['created_at']; ?></h3>
                    <p class="text-gray-600">Trạng thái: <?php echo htmlspecialchars($data['order']['status']); ?></p>
                    <p class="text-gray-600">Tổng tiền: <?php echo number_format($data['order']['total'], 0, ',', '.'); ?> VNĐ</p>
                    <?php if (in_array($data['order']['status'], ['pending', 'processing'])): ?>
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <button type="submit" name="cancel_order" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700" onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">Hủy Đơn Hàng</button>
                        </form>
                    <?php endif; ?>
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="p-4 text-left">Sản Phẩm</th>
                                <th class="p-4 text-left">Giá</th>
                                <th class="p-4 text-left">Số Lượng</th>
                                <th class="p-4 text-left">Tổng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['items'] as $item): ?>
                                <tr class="border-b">
                                    <td class="p-4"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td class="p-4"><?php echo number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                                    <td class="p-4"><?php echo $item['quantity']; ?></td>
                                    <td class="p-4"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> VNĐ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>