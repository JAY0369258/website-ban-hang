<?php
session_start();
require_once 'db_connect.php';

$errors = [];
$success = '';
$email = '';
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $expires_at = strtotime($row['expires_at']);
        $now = time();
        if ($expires_at > $now) {
            $email = $row['email'];
            error_log("Valid token: $token for email: $email, expires: {$row['expires_at']}");
        } else {
            $errors[] = "Link đã hết hạn.";
            error_log("Expired token: $token, expires: {$row['expires_at']}, now: " . date('Y-m-d H:i:s', $now));
        }
    } else {
        $errors[] = "Link không hợp lệ.";
        error_log("Invalid token: $token");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $email) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $token_post = $_POST['csrf_token'] ?? '';

    if ($token_post !== $_SESSION['csrf_token']) {
        $errors[] = "Lỗi bảo mật. Vui lòng thử lại.";
    }

    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $errors[] = "Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa và số.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Mật khẩu xác nhận không khớp.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $email]);
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);
            $pdo->commit();
            $success = "Mật khẩu đã được đặt lại thành công! Bạn có thể <a href='login.php' class='text-blue-600 hover:underline'>đăng nhập</a>.";
            $email = '';
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Đặt lại mật khẩu thất bại: " . htmlspecialchars($e->getMessage());
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
    <title>Đặt Lại Mật Khẩu</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Đặt Lại Mật Khẩu</h2>
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
        <?php if ($email): ?>
            <form method="POST" class="bg-white rounded-lg shadow-lg p-6 max-w-md mx-auto">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="mb-4">
                    <label for="password" class="block">Mật Khẩu Mới</label>
                    <input type="password" id="password" name="password" class="border rounded w-full px-4 py-2" required>
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="block">Xác Nhận Mật Khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="border rounded w-full px-4 py-2" required>
                </div>
                <button type="submit" name="reset_password" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">Đặt Lại Mật Khẩu</button>
            </form>
        <?php else: ?>
            <p class="text-center">Link không hợp lệ. Vui lòng <a href='forgot_password.php' class='text-blue-600 hover:underline'>thử lại</a>.</p>
        <?php endif; ?>
    </div>
</body>

</html>