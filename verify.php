<?php
require_once 'db_connect.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $pdo->prepare("SELECT email FROM email_verifications WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $email = $stmt->fetchColumn();

    if ($email) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE email = ?");
            $stmt->execute([$email]);
            $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE email = ?");
            $stmt->execute([$email]);
            $pdo->commit();
            $message = "Tài khoản đã được kích hoạt! Vui lòng đăng nhập.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Kích hoạt thất bại: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $message = "Link xác nhận không hợp lệ hoặc đã hết hạn.";
    }
} else {
    $message = "Không có token xác nhận.";
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Nhận Tài Khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <div class="container mx-auto py-8 text-center">
        <h2 class="text-3xl font-bold mb-4">Xác Nhận Tài Khoản</h2>
        <p class="text-lg"><?php echo htmlspecialchars($message); ?></p>
        <a href="login.php" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Đăng Nhập</a>
    </div>
</body>

</html>