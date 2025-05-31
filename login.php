<?php
session_start();
require_once 'db_connect.php';

$errors = [];
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = strip_tags(filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW) ?? '');
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    $token = $_POST['csrf_token'];

    if ($token !== $_SESSION['csrf_token']) {
        $errors[] = "Lỗi bảo mật. Vui lòng thử lại.";
    }

    if (empty($username) || empty($password)) {
        $errors[] = "Vui lòng nhập tên người dùng và mật khẩu.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, username, password, role, is_active FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password']) && $user['is_active']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_me', $token, time() + 30 * 24 * 3600, '/', '', true, true);
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$token, $user['id']]);
            }
            header('Location: index.php');
            exit;
        } else {
            $errors[] = $user && !$user['is_active'] ? "Tài khoản chưa được kích hoạt." : "Thông tin đăng nhập không đúng.";
        }
    }
}

// Auto-login with remember_me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE remember_token = ? AND is_active = 1");
    $stmt->execute([$_COOKIE['remember_me']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php');
        exit;
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
    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Đăng Nhập</h2>
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
                <label for="username" class="block">Tên Người Dùng hoặc Email</label>
                <input type="text" id="username" name="username" class="border rounded w-full px-4 py-2" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block">Mật Khẩu</label>
                <input type="password" id="password" name="password" class="border rounded w-full px-4 py-2" required>
            </div>
            <div class="mb-4">
                <label for="remember" class="inline-flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="mr-2">
                    Ghi nhớ đăng nhập
                </label>
            </div>
            <button type="submit" name="login" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">Đăng Nhập</button>
            <p class="mt-4 text-center">
                <a href="forgot_password.php" class="text-blue-600 hover:underline">Quên mật khẩu?</a> |
                <a href="register.php" class="text-blue-600 hover:underline">Đăng ký</a>
            </p>
        </form>
    </div>
</body>

</html>