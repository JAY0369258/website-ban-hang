<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$vnp_TmnCode = "YOUR_VNPAY_TMN_CODE"; // Thay bằng mã TmnCode từ VNPay
$vnp_HashSecret = "YOUR_VNPAY_HASH_SECRET"; // Thay bằng HashSecret từ VNPay
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_Returnurl = "http://localhost/website/vnpay_return.php";

$order_id = time();
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
$total = $total * 100; // VNPay yêu cầu đơn vị VND x 100

$vnp_TxnRef = $order_id;
$vnp_OrderInfo = "Thanh toán đơn hàng #$order_id";
$vnp_OrderType = "billpayment";
$vnp_Amount = $total;
$vnp_Locale = "vn";
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

$inputData = [
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_Amount" => $vnp_Amount,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => date('YmdHis'),
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => $vnp_IpAddr,
    "vnp_Locale" => $vnp_Locale,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_OrderType" => $vnp_OrderType,
    "vnp_ReturnUrl" => $vnp_Returnurl,
    "vnp_TxnRef" => $vnp_TxnRef,
];

ksort($inputData);
$query = http_build_query($inputData);
$hashdata = $query . "&vnp_SecureHash=" . hash_hmac('sha512', $query, $vnp_HashSecret);
$vnp_Url .= "?" . $hashdata;

header('Location: ' . $vnp_Url);
exit;
