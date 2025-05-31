<?php
session_start();
require_once 'db_connect.php';
require 'vendor/autoload.php'; // PHPMailer
use PHPMailer\PHPMailer\PHPMailer;

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = strip_tags(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW));
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = strip_tags(filter_input(INPUT_POST, 'phone', FILTER_UNSAFE_RAW));
    $password = $_POST['password'];
    $csrf_token = $_POST['csrf_token'];

    // Validate CSRF
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $errors[] = "Lỗi bảo mật. Vui lòng thử lại.";
    }

    // Validate inputs
    if (empty($name)) $errors[] = "Vui lòng nhập họ tên.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ.";
    if (!preg_match("/^0[3|5|7|8|9][0-9]{8}$/", $phone)) $errors[] = "Số điện thoại không hợp lệ.";
    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $errors[] = "Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa và số.";
    }

    // Check duplicates
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$email, $phone]);
    if ($stmt->fetch()) {
        $errors[] = "Email hoặc số điện thoại đã được sử dụng.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));

        $pdo->beginTransaction();
        try {
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password, is_active) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$name, $email, $phone, $hashed_password]);

            // Insert verification token
            $stmt = $pdo->prepare("INSERT INTO email_verifications (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, date('Y-m-d H:i:s', strtotime('+24 hours'))]);

            // Send verification email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'tuan0369258@gmail.com';
            $mail->Password = 'pobv wkku pbxv cbpw';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('tuan0369258@gmail.com', 'Cửa Hàng Online');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Xác nhận tài khoản';
            $mail->Body = "Vui lòng nhấp vào link để xác nhận: <a href='http://localhost/website/verify.php?token=$token'>Xác nhận</a>";
            $mail->send();

            $pdo->commit();
            $success = "Đăng ký thành công! Vui lòng kiểm tra email để xác nhận.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Đăng ký thất bại: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Đăng Ký</h2>
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
        <form method="POST" class="bg-white rounded-lg shadow-lg p-6 max-w-md mx-auto">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="mb-4">
                <label for="name" class="block">Họ Tên</label>
                <input type="text" id="name" name="name" class="border rounded w-full px-4 py-2" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block">Email</label>
                <input type="email" id="email" name="email" class="border rounded w-full px-4 py-2" required>
            </div>
            <div class="mb-4">
                <label for="phone" class="block">Số Điện Thoại</label>
                <input type="text" id="phone" name="phone" class="border rounded w-full px-4 py-2" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block">Mật Khẩu</label>
                <input type="password" id="password" name="password" class="border rounded w-full px-4 py-2" required>
            </div>
            <button type="submit" name="register" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">Đăng Ký</button>
            <p class="mt-4 text-center">Đã có tài khoản? <a href="login.php" class="text-blue-600 hover:underline">Đăng nhập</a></p>
        </form>
    </div>
</body>

</html>