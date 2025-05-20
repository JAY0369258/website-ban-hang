<?php
session_start(); // Đảm bảo chỉ gọi một lần
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role']; // Thêm role vào session
        header('Location: index.php');
        exit;
    } else {
        $error = "Tên đăng nhập hoặc mật khẩu không đúng.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">AT Store</h1>
            <div>
                <a href="index.php" class="px-4">Trang Chủ</a>
                <a href="cart.php" class="px-4">Giỏ Hàng (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                <a href="register.php" class="px-4">Đăng Ký</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Đăng Nhập</h2>
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-6">
            <?php if (isset($error)): ?>
                <p class="text-red-600 mb-4"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-4">
                    <label for="username" class="block">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" class="border rounded w-full px-4 py-2" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block">Mật khẩu</label>
                    <input type="password" id="password" name="password" class="border rounded w-full px-4 py-2" required>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Đăng Nhập</button>
            </form>
        </div>
    </div>
</body>

</html>