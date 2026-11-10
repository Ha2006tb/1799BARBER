<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJson(false, 'Phương thức không hợp lệ', null, 405);
}

$data = [];

// Tổng doanh thu từ Bookings (completed)
$stmt = $pdo->query("SELECT SUM(total_price) as total FROM bookings WHERE status = 'completed'");
$res = $stmt->fetch();
$data['revenue_bookings'] = intval($res['total'] ?? 0);

// Tổng doanh thu từ Orders (completed)
$stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
$res = $stmt->fetch();
$data['revenue_orders'] = intval($res['total'] ?? 0);

$data['total_revenue'] = $data['revenue_bookings'] + $data['revenue_orders'];

// Số lượng khách hàng
$stmt = $pdo->query("SELECT COUNT(id) as total FROM users WHERE role = 'customer'");
$res = $stmt->fetch();
$data['total_customers'] = intval($res['total'] ?? 0);

// Tổng số lịch đặt và số lịch đang chờ
$stmt = $pdo->query("SELECT COUNT(id) as total FROM bookings");
$res = $stmt->fetch();
$data['total_bookings'] = intval($res['total'] ?? 0);

$stmt = $pdo->query("SELECT COUNT(id) as total FROM bookings WHERE status = 'pending'");
$res = $stmt->fetch();
$data['pending_bookings'] = intval($res['total'] ?? 0);

// Tổng số đơn hàng và số đơn đang chờ
$stmt = $pdo->query("SELECT COUNT(id) as total FROM orders");
$res = $stmt->fetch();
$data['total_orders'] = intval($res['total'] ?? 0);

$stmt = $pdo->query("SELECT COUNT(id) as total FROM orders WHERE status = 'pending'");
$res = $stmt->fetch();
$data['pending_orders'] = intval($res['total'] ?? 0);

sendJson(true, 'Lấy thống kê thành công', $data);
