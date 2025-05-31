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
$success = '';
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $token = $_POST['csrf_token'] ?? '';

    if ($token !== $_SESSION['csrf_token']) {
        $errors[] = "Lỗi bảo mật. Vui lòng thử lại.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $reset_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours')); // Extended for testing

            try {
                $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                if (!$stmt->execute([$email, $reset_token, $expires_at])) {
                    $errors[] = "Không thể lưu token. Vui lòng thử lại.";
                    error_log("Failed to insert token for $email: " . print_r($pdo->errorInfo(), true));
                } else {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $_ENV['SMTP_USERNAME'];
                    $mail->Password = $_ENV['SMTP_PASSWORD'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom($_ENV['SMTP_USERNAME'], 'Cửa Hàng Online');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Đặt lại mật khẩu';
                    $mail->Body = "Nhấp vào link để đặt lại mật khẩu: <a href='http://localhost/website/reset_password.php?token=$reset_token'>Đặt lại</a><br>Link sẽ hết hạn sau 24 giờ.";
                    $mail->send();
                    $success = "Link đặt lại mật khẩu đã được gửi đến email của bạn!";
                    error_log("Reset token for $email: $reset_token");
                }
            } catch (Exception $e) {
                $errors[] = "Không thể gửi email: " . htmlspecialchars($e->getMessage());
                error_log("PHPMailer error for $email: " . $e->getMessage());
            }
        } else {
            $errors[] = "Email không tồn tại.";
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
    <title>Quên Mật Khẩu</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Quên Mật Khẩu</h2>
        <?php if ($success): ?>
            <p class="text-center text-green-600 font-bold"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="bg-white rounded-lg shadow-lg p-6 max-w-md mx-auto">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="mb-4">
                <label for="email" class="block">Email</label>
                <input type="email" id="email" name="email" class="border rounded w-full px-4 py-2" required>
            </div>
            <button type="submit" name="request_reset" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">Gửi Link Đặt Lại</button>
            <p class="mt-4 text-center">
                <a href="login.php" class="text-blue-600 hover:underline">Quay lại đăng nhập</a> |
                <a href="register.php" class="text-blue-600 hover:underline">Đăng ký</a>
            </p>
        </form>
    </div>
</body>

</html>