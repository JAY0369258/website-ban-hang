<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT username, email, phone, address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $username = strip_tags(filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = strip_tags(filter_input(INPUT_POST, 'phone', FILTER_UNSAFE_RAW));
    $address = strip_tags(filter_input(INPUT_POST, 'address', FILTER_UNSAFE_RAW));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ.";
    }
    if (!preg_match("/^0[3|5|7|8|9][0-9]{8}$/", $phone)) {
        $errors[] = "Số điện thoại không hợp lệ.";
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR phone = ?) AND id != ?");
    $stmt->execute([$email, $phone, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = "Email hoặc số điện thoại đã được sử dụng.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$username, $email, $phone, $address, $user_id]);
        $success = "Cập nhật thông tin thành công.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_hash = $stmt->fetchColumn();

    if (!password_verify($current_password, $current_hash)) {
        $errors[] = "Mật khẩu hiện tại không đúng.";
    }
    if (strlen($new_password) < 8 || !preg_match("/[A-Z]/", $new_password) || !preg_match("/[0-9]/", $new_password)) {
        $errors[] = "Mật khẩu mới phải có ít nhất 8 ký tự, bao gồm chữ hoa và số.";
    }
    if ($new_password !== $confirm_password) {
        $errors[] = "Mật khẩu xác nhận không khớp.";
    }

    if (empty($errors)) {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user_id]);
        $success = "Đổi mật khẩu thành công.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài Khoản</title>
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
                <a href="logout.php" class="px-4">Đăng Xuất</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Quản Lý Tài Khoản</h2>
        <?php if ($success): ?>
            <p class="text-center text-green-600 font-bold"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Cập Nhật Thông Tin</h3>
                <form method="POST">
                    <div class="mb-4">
                        <label for="username" class="block">Tên Người Dùng</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="border rounded w-full px-4 py-2" required>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="border rounded w-full px-4 py-2" required>
                    </div>
                    <div class="mb-4">
                        <label for="phone" class="block">Số Điện Thoại</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="border rounded w-full px-4 py-2" required>
                    </div>
                    <div class="mb-4">
                        <label for="address" class="block">Địa Chỉ</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" class="border rounded w-full px-4 py-2">
                    </div>
                    <button type="submit" name="update_info" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Cập Nhật</button>
                </form>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Đổi Mật Khẩu</h3>
                <form method="POST">
                    <div class="mb-4">
                        <label for="current_password" class="block">Mật Khẩu Hiện Tại</label>
                        <input type="password" id="current_password" name="current_password" class="border rounded w-full px-4 py-2" required>
                    </div>
                    <div class="mb-4">
                        <label for="new_password" class="block">Mật Khẩu Mới</label>
                        <input type="password" id="new_password" name="new_password" class="border rounded w-full px-4 py-2" required>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="block">Xác Nhận Mật Khẩu</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="border rounded w-full px-4 py-2" required>
                    </div>
                    <button type="submit" name="change_password" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Đổi Mật Khẩu</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>