<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = strip_tags(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW));
    $description = strip_tags(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW));

    if (empty($name)) {
        $errors[] = "Tên danh mục không được để trống.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        header('Location: categories.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = (int)$_POST['id'];
    $name = strip_tags(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW));
    $description = strip_tags(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW));

    if (empty($name)) {
        $errors[] = "Tên danh mục không được để trống.";
    } else {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $id]);
        header('Location: categories.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: categories.php');
    exit;
}

$edit_category = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Danh Mục</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>

<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Bảng Điều Khiển Quản Trị</h1>
            <div>
                <a href="index.php" class="px-4">Trang Chủ</a>
                <a href="products.php" class="px-4">Quản Lý Sản Phẩm</a>
                <a href="orders.php" class="px-4">Quản Lý Đơn Hàng</a>
                <a href="reports.php" class="px-4">Báo Cáo</a>
                <a href="../logout.php" class="px-4">Đăng Xuất</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Quản Lý Danh Mục</h2>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="mb-8 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4"><?php echo $edit_category ? 'Chỉnh Sửa Danh Mục' : 'Thêm Danh Mục'; ?></h3>
            <?php if ($edit_category): ?>
                <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
            <?php endif; ?>
            <div class="mb-4">
                <label for="name" class="block">Tên Danh Mục</label>
                <input type="text" id="name" name="name" class="border rounded w-full px-4 py-2" value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>" required>
            </div>
            <div class="mb-4">
                <label for="description" class="block">Mô Tả</label>
                <textarea id="description" name="description" class="border rounded w-full px-4 py-2"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
            </div>
            <button type="submit" name="<?php echo $edit_category ? 'update_category' : 'add_category'; ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"><?php echo $edit_category ? 'Cập Nhật' : 'Thêm'; ?> Danh Mục</button>
        </form>

        <h3 class="text-xl font-semibold mb-4">Danh Sách Danh Mục</h3>
        <table class="w-full bg-white rounded-lg shadow-lg">
            <thead>
                <tr class="border-b">
                    <th class="p-4 text-left">Tên</th>
                    <th class="p-4 text-left">Mô Tả</th>
                    <th class="p-4 text-left">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr class="border-b">
                        <td class="p-4"><?php echo htmlspecialchars($category['name']); ?></td>
                        <td class="p-4"><?php echo htmlspecialchars($category['description']); ?></td>
                        <td class="p-4">
                            <a href="categories.php?edit=<?php echo $category['id']; ?>" class="text-blue-600 hover:underline">Sửa</a>
                            <a href="categories.php?delete=<?php echo $category['id']; ?>" class="text-red-600 hover:underline" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>