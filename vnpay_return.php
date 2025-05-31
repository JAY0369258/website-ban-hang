<?php
session_start();
require_once 'db_connect.php';

$vnp_HashSecret = "YOUR_VNPAY_HASH_SECRET"; // Thay bằng HashSecret từ VNPay
$vnp_SecureHash = $_GET['vnp_SecureHash'];
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}
unset($inputData['vnp_SecureHash']);
ksort($inputData);
$hashData = http_build_query($inputData);
$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

if ($secureHash === $vnp_SecureHash && $_GET['vnp_ResponseCode'] == '00') {
    $order_id = $_GET['vnp_TxnRef'];
    $total = $_GET['vnp_Amount'] / 100;
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $name = strip_tags(filter_input(INPUT_GET, 'vnp_OrderInfo', FILTER_UNSAFE_RAW));
    $email = strip_tags(filter_input(INPUT_GET, 'vnp_OrderInfo', FILTER_UNSAFE_RAW));
    $phone = strip_tags(filter_input(INPUT_GET, 'vnp_OrderInfo', FILTER_UNSAFE_RAW));
    $address = strip_tags(filter_input(INPUT_GET, 'vnp_OrderInfo', FILTER_UNSAFE_RAW));

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, name, email, phone, address, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, 'VNPay', 'completed')");
        $stmt->execute([$user_id, $total, $name, $email, $phone, $address]);
        $order_id_db = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        foreach ($_SESSION['cart'] as $id => $item) {
            $stmt->execute([$order_id_db, $id, $item['quantity'], $item['price']]);
            $stmt_stock->execute([$item['quantity'], $id]);
        }

        $pdo->commit();
        $_SESSION['cart'] = [];
        header('Location: order_confirmation.php?order_id=' . $order_id_db);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("VNPay payment failed: {$e->getMessage()}");
        $error = "Thanh toán thất bại.";
    }
} else {
    $error = "Thanh toán không thành công.";
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết Quả Thanh Toán</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Kết Quả Thanh Toán</h2>
        <p class="text-center text-red-600 font-bold"><?php echo isset($error) ? htmlspecialchars($error) : 'Xử lý thanh toán...'; ?></p>
        <p class="text-center"><a href="index.php" class="text-blue-600 hover:underline">Quay về trang chủ</a></p>
    </div>
</body>

</html>