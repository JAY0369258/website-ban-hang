<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        $error = "Tên đăng nhập đã tồn tại.";
    }
}
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
    <!-- Navbar -->
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">AT Store</h1>
            <div>
                <a href="index.php" class="px-4">Trang Chủ</a>
                <a href="cart.php" class="px-4">Giỏ Hàng (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
                <a href="login.php" class="px-4">Đăng Nhập</a>
            </div>
        </div>
    </nav>

    <!-- Đăng ký -->
    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Đăng Ký</h2>
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
                    <label for="email" class="block">Email</label>
                    <input type="email" id="email" name="email" class="border rounded w-full px-4 py-2" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block">Mật khẩu</label>
                    <input type="password" id="password" name="password" class="border rounded w-full px-4 py-2" required>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Đăng Ký</button>
            </form>
        </div>
    </div>
</body>

</html>