<?php
session_start();
require_once '../db_connect.php';
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$search_params = [];
$search_query = "SELECT o.*, u.username, oi.product_id, oi.quantity, oi.price, p.name 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 JOIN order_items oi ON o.id = oi.order_id 
                 JOIN products p ON oi.product_id = p.id 
                 WHERE 1=1";
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['search'])) {
    $username = strip_tags(filter_input(INPUT_GET, 'username', FILTER_UNSAFE_RAW));
    $status = strip_tags(filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW));
    $date_from = strip_tags(filter_input(INPUT_GET, 'date_from', FILTER_UNSAFE_RAW));
    $date_to = strip_tags(filter_input(INPUT_GET, 'date_to', FILTER_UNSAFE_RAW));

    if (!empty($username)) {
        $search_query .= " AND u.username LIKE ?";
        $search_params[] = "%$username%";
    }
    if (!empty($status) && in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
        $search_query .= " AND o.status = ?";
        $search_params[] = $status;
    }
    if (!empty($date_from)) {
        $search_query .= " AND o.created_at >= ?";
        $search_params[] = $date_from;
    }
    if (!empty($date_to)) {
        $search_query .= " AND o.created_at <= ?";
        $search_params[] = $date_to . ' 23:59:59';
    }
}
$search_query .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($search_query);
$stmt->execute($search_params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped_orders = [];
foreach ($orders as $order) {
    $grouped_orders[$order['id']]['order'] = $order;
    $grouped_orders[$order['id']]['items'][] = $order;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = strip_tags(filter_input(INPUT_POST, 'status', FILTER_UNSAFE_RAW));
    if (in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);

            // Send email notification
            $stmt = $pdo->prepare("SELECT o.email, o.name FROM orders o WHERE o.id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'tuan0369258@gmail.com';
            $mail->Password = 'pobv wkku pbxv cbpw';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('your_email@gmail.com', 'Cửa Hàng Online');
            $mail->addAddress($order['email']);
            $mail->isHTML(true);
            $mail->Subject = 'Cập nhật trạng thái đơn hàng #' . $order_id;

            $template = file_get_contents('../email_template.html');
            $status_vn = ['pending' => 'Đang chờ', 'processing' => 'Đang xử lý', 'completed' => 'Hoàn thành', 'cancelled' => 'Hủy'];
            $content = "<p>Kính gửi {$order['name']},</p><p>Trạng thái đơn hàng #$order_id đã được cập nhật thành: <strong>{$status_vn[$status]}</strong>.</p>";
            $mail->Body = str_replace(['{{title}}', '{{content}}'], ['Cập nhật trạng thái đơn hàng', $content], $template);
            $mail->send();

            $pdo->commit();
            header('Location: orders.php' . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Update status failed: {$e->getMessage()}");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng</title>
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
                <a href="categories.php" class="px-4">Quản Lý Danh Mục</a>
                <a href="reports.php" class="px-4">Báo Cáo</a>
                <a href="../logout.php" class="px-4">Đăng Xuất</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto py-8">
        <h2 class="text-3xl font-bold text-center mb-8">Quản Lý Đơn Hàng</h2>
        <form method="GET" class="mb-8 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Tìm Kiếm Đơn Hàng</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="username" class="block">Tên Khách Hàng</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>" class="border rounded w-full px-4 py-2">
                </div>
                <div>
                    <label for="status" class="block">Trạng Thái</label>
                    <select id="status" name="status" class="border rounded w-full px-4 py-2">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>Đang chờ</option>
                        <option value="processing" <?php echo isset($_GET['status']) && $_GET['status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                        <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] === 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                        <option value="cancelled" <?php echo isset($_GET['status']) && $_GET['status'] === 'cancelled' ? 'selected' : ''; ?>>Hủy</option>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block">Từ Ngày</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>" class="border rounded w-full px-4 py-2">
                </div>
                <div>
                    <label for="date_to" class="block">Đến Ngày</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>" class="border rounded w-full px-4 py-2">
                </div>
            </div>
            <button type="submit" name="search" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tìm Kiếm</button>
        </form>

        <?php if (empty($grouped_orders)): ?>
            <p class="text-center">Không có đơn hàng nào.</p>
        <?php else: ?>
            <?php foreach ($grouped_orders as $order_id => $data): ?>
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h3 class="text-xl font-semibold">Đơn hàng #<?php echo $order_id; ?> - <?php echo $data['order']['created_at']; ?></h3>
                    <p class="text-gray-600">Khách hàng: <?php echo htmlspecialchars($data['order']['username']); ?></p>
                    <p class="text-gray-600">Tổng tiền: <?php echo number_format($data['order']['total'], 0, ',', '.'); ?> VNĐ</p>
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <label for="status_<?php echo $order_id; ?>" class="mr-2">Trạng thái:</label>
                        <select name="status" id="status_<?php echo $order_id; ?>" class="border rounded px-2 py-1">
                            <option value="pending" <?php echo $data['order']['status'] === 'pending' ? 'selected' : ''; ?>>Đang chờ</option>
                            <option value="processing" <?php echo $data['order']['status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                            <option value="completed" <?php echo $data['order']['status'] === 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                            <option value="cancelled" <?php echo $data['order']['status'] === 'cancelled' ? 'selected' : ''; ?>>Hủy</option>
                        </select>
                        <button type="submit" name="update_status" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Cập nhật</button>
                    </form>
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="p-4 text-left">Sản Phẩm</th>
                                <th class="p-4 text-left">Giá</th>
                                <th class="p-4 text-left">Số Lượng</th>
                                <th class="p-4 text-left">Tổng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['items'] as $item): ?>
                                <tr class="border-b">
                                    <td class="p-4"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td class="p-4"><?php echo number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                                    <td class="p-4"><?php echo $item['quantity']; ?></td>
                                    <td class="p-4"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> VNĐ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>