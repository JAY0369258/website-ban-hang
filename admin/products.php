<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = strip_tags(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW));
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $description = strip_tags(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW));
    $category_id = (int)$_POST['category_id'];
    $stock = (int)$_POST['stock'];

    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024;
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        $file_size = $_FILES['image']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Chỉ cho phép tải lên file JPG, JPEG hoặc PNG.";
        } elseif ($file_size > $max_size) {
            $errors[] = "Kích thước file tối đa là 2MB.";
        } else {
            $upload_dir = '../uploads/';
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = 'product_' . time() . '.' . $file_ext;
            $image_path = 'uploads/' . $file_name;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $file_name)) {
                $errors[] = "Không thể tải lên hình ảnh.";
            }
        }
    } else {
        $errors[] = "Vui lòng chọn một hình ảnh.";
    }

    if ($stock < 0) {
        $errors[] = "Số lượng tồn kho không được âm.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (name, price, image, description, category_id, stock) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $image_path, $description, $category_id, $stock]);
        header('Location: products.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id = (int)$_POST['id'];
    $name = strip_tags(filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW));
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $description = strip_tags(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW));
    $category_id = (int)$_POST['category_id'];
    $stock = (int)$_POST['stock'];

    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $current_image = $stmt->fetchColumn();

    $image_path = $current_image;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024;
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        $file_size = $_FILES['image']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Chỉ cho phép tải lên file JPG, JPEG hoặc PNG.";
        } elseif ($file_size > $max_size) {
            $errors[] = "Kích thước file tối đa là 2MB.";
        } else {
            $upload_dir = '../Uploads/';
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = 'product_' . time() . '.' . $file_ext;
            $image_path = 'Uploads/' . $file_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $file_name)) {
                if ($current_image && file_exists('../' . $current_image)) {
                    unlink('../' . $current_image);
                }
            } else {
                $errors[] = "Không thể tải lên hình ảnh.";
            }
        }
    }

    if ($stock < 0) {
        $errors[] = "Số lượng tồn kho không được âm.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, image = ?, description = ?, category_id = ?, stock = ? WHERE id = ?");
        $stmt->execute([$name, $price, $image_path, $description, $category_id, $stock, $id]);
        header('Location: products.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetchColumn();
    if ($image && file_exists('../' . $image)) {
        unlink('../' . $image);
    }
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: products.php');
    exit;
}

$edit_product = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>

<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Bảng Điều Khiển Quản Trị</h1>
            <div>
                <a href="index.php" class="px-4">Trang Chủ</a>
                <a href="categories.php" class="px-4">Quản Lý Danh Mục</a>
                <a href="orders.php" class="px-4">Quản Lý Đơn Hàng</a>
                <a href="reports.php" class="px-4">Báo Cáo</a>
                <a href="../logout.php" class="px-4">Đăng Xuất</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Quản Lý Sản Phẩm</h2>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="mb-8 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4"><?php echo $edit_product ? 'Chỉnh Sửa Sản Phẩm' : 'Thêm Sản Phẩm'; ?></h3>
            <?php if ($edit_product): ?>
                <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
            <?php endif; ?>
            <div class="mb-4">
                <label for="name" class="block">Tên Sản Phẩm</label>
                <input type="text" id="name" name="name" class="border rounded w-full px-4 py-2" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required>
            </div>
            <div class="mb-4">
                <label for="price" class="block">Giá</label>
                <input type="number" id="price" name="price" step="0.01" class="border rounded w-full px-4 py-2" value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required>
            </div>
            <div class="mb-4">
                <label for="image" class="block">Hình Ảnh</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/jpg" class="border rounded w-full px-4 py-2" <?php echo $edit_product ? '' : 'required'; ?>>
                <?php if ($edit_product && $edit_product['image']): ?>
                    <p class="text-sm text-gray-600 mt-2">Hình ảnh hiện tại: <img src="../<?php echo htmlspecialchars($edit_product['image']); ?>" alt="Current Image" class="h-20 mt-2"></p>
                <?php endif; ?>
            </div>
            <div class="mb-4">
                <label for="description" class="block">Mô Tả</label>
                <textarea id="description" name="description" class="border rounded w-full px-4 py-2" required><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
            </div>
            <div class="mb-4">
                <label for="category_id" class="block">Danh Mục</label>
                <select id="category_id" name="category_id" class="border rounded w-full px-4 py-2" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $edit_product && $edit_product['category_id'] == $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="stock" class="block">Số Lượng Tồn Kho</label>
                <input type="number" id="stock" name="stock" min="0" class="border rounded w-full px-4 py-2" value="<?php echo $edit_product ? $edit_product['stock'] : '0'; ?>" required>
            </div>
            <button type="submit" name="<?php echo $edit_product ? 'update_product' : 'add_product'; ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"><?php echo $edit_product ? 'Cập Nhật' : 'Thêm'; ?> Sản Phẩm</button>
        </form>

        <h3 class="text-xl font-semibold mb-4">Danh Sách Sản Phẩm</h3>
        <table class="w-full bg-white rounded-lg shadow-lg">
            <thead>
                <tr class="border-b">
                    <th class="p-4 text-left">Tên</th>
                    <th class="p-4 text-left">Danh Mục</th>
                    <th class="p-4 text-left">Giá</th>
                    <th class="p-4 text-left">Hình Ảnh</th>
                    <th class="p-4 text-left">Tồn Kho</th>
                    <th class="p-4 text-left">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr class="border-b">
                        <td class="p-4"><?php echo htmlspecialchars($product['name']); ?></td>
                        <td class="p-4"><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td class="p-4"><?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</td>
                        <td class="p-4">
                            <?php if ($product['image']): ?>
                                <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-16">
                            <?php else: ?>
                                Không có ảnh
                            <?php endif; ?>
                        </td>
                        <td class="p-4"><?php echo $product['stock']; ?> <?php echo $product['stock'] > 0 ? '(Còn hàng)' : '(Hết hàng)'; ?></td>
                        <td class="p-4">
                            <a href="products.php?edit=<?php echo $product['id']; ?>" class="text-blue-600 hover:underline">Sửa</a>
                            <a href="products.php?delete=<?php echo $product['id']; ?>" class="text-red-600 hover:underline" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>