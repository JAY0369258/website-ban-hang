<?php
session_start();
require_once 'db_connect.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = (int)$_GET['order_id'];
$stmt = $pdo->prepare("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Send confirmation email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'tuan0369258@gmail.com';
    $mail->Password = 'pobv wkku pbxv cbpw';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->setFrom('tuan0369258@gmail.com', 'Cửa Hàng Online');
    $mail->addAddress($order['email']);
    $mail->isHTML(true);
    $mail->Subject = 'Xác nhận đơn hàng #' . $order_id;
    $mail->Body = "
        <h2>Xác nhận đơn hàng #$order_id</h2>
        <p>Cảm ơn bạn đã đặt hàng!</p>
        <p><strong>Khách hàng:</strong> {$order['name']}</p>
        <p><strong>Địa chỉ:</strong> {$order['address']}</p>
        <p><strong>Tổng tiền:</strong> " . number_format($order['total'], 0, ',', '.') . " VNĐ</p>
        <h3>Chi tiết đơn hàng:</h3>
        <ul>
    ";
    foreach ($items as $item) {
        $mail->Body .= "<li>{$item['name']} - {$item['quantity']} x " . number_format($item['price'], 0, ',', '.') . " VNĐ</li>";
    }
    $mail->Body .= "</ul>";
    $mail->send();
} catch (Exception $e) {
    error_log("Email sending failed: {$mail->ErrorInfo}");
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Nhận Đơn Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Xác Nhận Đơn Hàng #<?php echo $order_id; ?></h2>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <p class="text-center text-green-600 font-bold">Đặt hàng thành công! Cảm ơn bạn đã mua sắm.</p>
            <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
            <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
            <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
            <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total'], 0, ',', '.'); ?> VNĐ</p>
            <h3 class="text-xl font-semibold mt-4">Chi tiết đơn hàng</h3>
            <table class="w-full mt-4">
                <thead>
                    <tr class="border-b">
                        <th class="p-4 text-left">Sản Phẩm</th>
                        <th class="p-4 text-left">Giá</th>
                        <th class="p-4 text-left">Số Lượng</th>
                        <th class="p-4 text-left">Tổng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr class="border-b">
                            <td class="p-4"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="p-4"><?php echo number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                            <td class="p-4"><?php echo $item['quantity']; ?></td>
                            <td class="p-4"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> VNĐ</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="index.php" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Quay về trang chủ</a>
        </div>
    </div>
</body>

</html>