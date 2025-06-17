<?php
session_start();
require_once 'db_connect.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$errors = [];
$user_info = [];
$momo_qr = false;

// Dữ liệu thành phố và quận/huyện
$cities = [
    'Hà Nội' => ['Ba Đình', 'Hoàn Kiếm', 'Đống Đa', 'Cầu Giấy', 'Thanh Xuân'],
    'TP Hồ Chí Minh' => ['Quận 1', 'Quận 2', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8', 'Quận 9', 'Quận 10', 'Quận 11', 'Quận 12', 'Quận Phú Nhuận', 'Bình Thạnh', 'Gò Vấp'],
    'Đà Nẵng' => ['Hải Châu', 'Thanh Khê', 'Sơn Trà', 'Ngũ Hành Sơn']
];

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT username AS name, email, phone, address FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $csrf_token_post = $_POST['csrf_token'] ?? '';
    if ($csrf_token_post !== $_SESSION['csrf_token']) {
        $errors[] = "Lỗi bảo mật. Vui lòng thử lại.";
    }

    $name = strip_tags(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW) ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = strip_tags(filter_input(INPUT_POST, 'phone', FILTER_UNSAFE_RAW) ?? '');
    $specific_address = strip_tags(filter_input(INPUT_POST, 'specific_address', FILTER_UNSAFE_RAW) ?? '');
    $street = strip_tags(filter_input(INPUT_POST, 'street', FILTER_UNSAFE_RAW) ?? '');
    $district = strip_tags(filter_input(INPUT_POST, 'district', FILTER_UNSAFE_RAW) ?? '');
    $city = strip_tags(filter_input(INPUT_POST, 'city', FILTER_UNSAFE_RAW) ?? '');
    $payment_method = $_POST['payment_method'] ?? 'COD';
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $total = 0;

    if (empty($name)) $errors[] = "Vui lòng nhập họ tên.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ.";
    if (!preg_match("/^0[3|5|7|8|9][0-9]{8}$/", $phone)) $errors[] = "Số điện thoại không hợp lệ.";
    if (empty($specific_address)) $errors[] = "Vui lòng nhập địa chỉ cụ thể.";
    if (empty($street)) $errors[] = "Vui lòng nhập tên đường.";
    if (empty($district) || !in_array($district, array_merge(...array_values($cities)))) $errors[] = "Vui lòng chọn quận/huyện.";
    if (empty($city) || !array_key_exists($city, $cities)) $errors[] = "Vui lòng chọn thành phố/tỉnh.";
    if (!in_array($payment_method, ['COD', 'MOMO'])) $errors[] = "Phương thức thanh toán không hợp lệ.";

    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $id => $item) {
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $stock = $stmt->fetchColumn();
            if ($stock === false || $stock < $item['quantity']) {
                $errors[] = "Sản phẩm {$item['name']} chỉ còn $stock trong kho.";
            }
            $total += $item['price'] * $item['quantity'];
        }
    } else {
        $errors[] = "Giỏ hàng của bạn đang trống.";
    }

    // Ghép địa chỉ
    $address = "$specific_address, $street, $district, $city";

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, name, email, phone, address, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $total, $name, $email, $phone, $address, $payment_method]);
            $order_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $order_items = '';
            foreach ($_SESSION['cart'] as $id => $item) {
                $stmt->execute([$order_id, $id, $item['quantity'], $item['price']]);
                $stmt_stock->execute([$item['quantity'], $id]);
                $order_items .= "<li>{$item['name']} (x{$item['quantity']}): " . number_format($item['price'] * $item['quantity'], 0, ',', '.') . " VNĐ</li>";
            }

            $pdo->commit();

            // Send order confirmation email
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['SMTP_USERNAME'];
                $mail->Password = $_ENV['SMTP_PASSWORD'];
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                $mail->setFrom($_ENV['SMTP_USERNAME'], 'Cửa Hàng Online');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Xác nhận đơn hàng #' . $order_id;
                $momo_phone = $_ENV['MOMO_PHONE'] ?? '0938398984';
                $momo_instructions = $payment_method === 'MOMO' ? "<p><strong>Hướng dẫn thanh toán Momo:</strong><br>1. Mở app Momo và quét mã QR hoặc chuyển khoản đến số: <strong>$momo_phone</strong>.<br>2. Số tiền: <strong>" . number_format($total, 0, ',', '.') . " VNĐ</strong>.<br>3. Nội dung chuyển khoản: <strong>DonHang$order_id</strong>.<br>4. Gửi biên lai qua email: " . htmlspecialchars($_ENV['SMTP_USERNAME']) . ".</p>" : '';
                $mail->Body = "
                    <h2>Cảm ơn bạn đã đặt hàng!</h2>
                    <p>Mã đơn hàng: $order_id</p>
                    <p>Họ tên: " . htmlspecialchars($name) . "</p>
                    <p>Địa chỉ: " . htmlspecialchars($address) . "</p>
                    <p>Số điện thoại: " . htmlspecialchars($phone) . "</p>
                    <h3>Chi tiết đơn hàng:</h3>
                    <ul>$order_items</ul>
                    <p><strong>Tổng cộng: " . number_format($total, 0, ',', '.') . " VNĐ</strong></p>
                    <p>Phương thức thanh toán: " . ($payment_method === 'MOMO' ? 'Momo (Chờ thanh toán)' : 'COD') . "</p>
                    $momo_instructions
                    <p>Chúng tôi sẽ liên hệ sớm để giao hàng.</p>
                ";
                $mail->send();
            } catch (Exception $e) {
                error_log("Failed to send order confirmation email: " . $e->getMessage());
            }

            if ($payment_method === 'COD') {
                $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'completed' WHERE id = ?");
                $stmt->execute([$order_id]);
                $_SESSION['cart'] = [];
                header('Location: order_confirmation.php?order_id=' . $order_id);
                exit;
            } elseif ($payment_method === 'MOMO') {
                $momo_qr = true;
                $_SESSION['cart'] = [];
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Đặt hàng thất bại: " . htmlspecialchars($e->getMessage());
        }
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
        <h2 class="text-3xl font-bold text-center mb-8">Thanh Toán</h2>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-600 px-4 py-3 rounded-lg mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php elseif ($momo_qr): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-md mx-auto text-center">
                <h3 class="text-xl font-bold mb-4">Thanh toán qua Momo</h3>
                <p>Quét mã QR dưới đây hoặc chuyển khoản đến:</p>
                <p><strong>Số điện thoại Momo:</strong> <?php echo htmlspecialchars($_ENV['MOMO_PHONE'] ?? '0938398984'); ?></p>
                <p><strong>Số tiền:</strong> <?php echo number_format($total, 0, ',', '.'); ?> VNĐ</p>
                <p><strong>Nội dung:</strong> DonHang<?php echo $order_id; ?></p>
                <img src="images/momo_qr.jpg" alt="Momo QR Code" class="mx-auto my-4 w-75 h-75">
                <p>Gửi biên lai thanh toán qua email: <?php echo htmlspecialchars($_ENV['SMTP_USERNAME']); ?>.</p>
                <p>Chúng tôi sẽ xác nhận đơn hàng sau khi nhận thanh toán.</p>
                <a href="order_confirmation.php?order_id=<?php echo $order_id; ?>" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Xem đơn hàng</a>
            </div>
        <?php elseif (empty($_SESSION['cart'])): ?>
            <p class="text-center text-gray-600">Giỏ hàng của bạn đang trống.</p>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Thông tin đơn hàng</h3>
                <table class="w-full mb-4 border">
                    <thead>
                        <tr class="bg-gray-100 border-b">
                            <th class="p-4 text-left">Sản Phẩm</th>
                            <th class="p-4 text-left">Giá</th>
                            <th class="p-4 text-left">Số Lượng</th>
                            <th class="p-4 text-left">Tổng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $subtotal = 0; ?>
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <tr class="border-b">
                                <td class="p-4"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="p-4"><?php echo number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                                <td class="p-4"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td class="p-4"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> VNĐ</td>
                            </tr>
                            <?php $subtotal += $item['price'] * $item['quantity']; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="text-xl font-bold">Tổng cộng: <?php echo number_format($subtotal, 0, ',', '.'); ?> VNĐ</p>
                <form method="POST" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <h3 class="text-xl font-semibold mb-4">Thông tin khách hàng</h3>
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium">Họ Tên</label>
                        <input type="text" id="name" name="name" class="border rounded-md w-full px-4 py-2 mt-1" value="<?php echo isset($user_info['name']) ? htmlspecialchars($user_info['name']) : ''; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium">Email</label>
                        <input type="email" id="email" name="email" class="border rounded-md w-full px-4 py-2 mt-1" value="<?php echo isset($user_info['email']) ? htmlspecialchars($user_info['email']) : ''; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="phone" class="block text-sm font-medium">Số Điện Thoại</label>
                        <input type="text" id="phone" name="phone" class="border rounded-md w-full px-4 py-2 mt-1" value="<?php echo isset($user_info['phone']) ? htmlspecialchars($user_info['phone']) : ''; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="specific_address" class="block text-sm font-medium">Địa chỉ cụ thể (Số nhà)</label>
                        <input type="text" id="specific_address" name="specific_address" class="border rounded-md w-full px-4 py-2 mt-1" value="<?php echo isset($user_info['address']) ? '' : ''; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="street" class="block text-sm font-medium">Tên Đường</label>
                        <input type="text" id="street" name="street" class="border rounded-md w-full px-4 py-2 mt-1" value="" required>
                    </div>
                    <div class="mb-4">
                        <label for="city" class="block text-sm font-medium">Thành Phố/Tỉnh</label>
                        <select id="city" name="city" class="border rounded-md w-full px-4 py-2 mt-1" required>
                            <option value="">Chọn thành phố/tỉnh</option>
                            <?php foreach (array_keys($cities) as $city_name): ?>
                                <option value="<?php echo htmlspecialchars($city_name); ?>"><?php echo htmlspecialchars($city_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="district" class="block text-sm font-medium">Quận/Huyện</label>
                        <select id="district" name="district" class="border rounded-md w-full px-4 py-2 mt-1" required>
                            <option value="">Chọn quận/huyện</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium">Phương thức thanh toán</label>
                        <div class="flex items-center mt-2">
                            <input type="radio" id="cod" name="payment_method" value="COD" checked class="mr-2">
                            <label for="cod" class="mr-4">Thanh toán khi nhận hàng (COD)</label>
                            <input type="radio" id="momo" name="payment_method" value="MOMO" class="mr-2">
                            <label for="momo">Thanh toán qua Momo</label>
                        </div>
                    </div>
                    <button type="submit" name="place_order" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Đặt Hàng</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const cities = <?php echo json_encode($cities); ?>;
        const citySelect = document.getElementById('city');
        const districtSelect = document.getElementById('district');

        citySelect.addEventListener('change', function() {
            const selectedCity = this.value;
            districtSelect.innerHTML = '<option value="">Chọn quận/huyện</option>';
            if (selectedCity && cities[selectedCity]) {
                cities[selectedCity].forEach(district => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            }
        });
    </script>
</body>

</html>