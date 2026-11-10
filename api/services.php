<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $category = $_GET['category'] ?? '';
    $id = $_GET['id'] ?? 0;
    
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        if ($service) {
            // Lấy danh sách đánh giá của dịch vụ này
            $stmtReviews = $pdo->prepare("SELECT r.*, u.fullname, s.name as stylist_name FROM reviews r JOIN bookings b ON r.booking_id = b.id JOIN users u ON r.user_id = u.id JOIN stylists s ON r.stylist_id = s.id WHERE b.service_id = ? ORDER BY r.created_at DESC");
            $stmtReviews->execute([$id]);
            $service['reviews'] = $stmtReviews->fetchAll();
            
            // Tính số lượng và điểm đánh giá trung bình
            $stmtStats = $pdo->prepare("SELECT COUNT(*) as count, AVG(r.rating) as avg_rating FROM reviews r JOIN bookings b ON r.booking_id = b.id WHERE b.service_id = ?");
            $stmtStats->execute([$id]);
            $stats = $stmtStats->fetch();
            $service['rating_count'] = intval($stats['count'] ?? 0);
            $service['rating_avg'] = $stats['avg_rating'] ? round(floatval($stats['avg_rating']), 1) : null;

            sendJson(true, 'Lấy thông tin thành công', $service);
        } else {
            sendJson(false, 'Không tìm thấy dịch vụ', null, 404);
        }
    }
    
    if ($category && $category !== 'all') {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE category = ? AND status = 'active' ORDER BY id ASC");
        $stmt->execute([$category]);
    } else {
        $stmt = $pdo->query("SELECT * FROM services WHERE status = 'active' ORDER BY category ASC, id ASC");
    }
    
    $services = $stmt->fetchAll();
    sendJson(true, 'Lấy danh sách thành công', $services);

} elseif ($method === 'POST') {
    // Thêm mới (Admin)
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = intval($data['price'] ?? 0);
    $duration = intval($data['duration'] ?? 30);
    $category = $data['category'] ?? 'haircut';
    $image = $data['image'] ?? 'uploads/default_service.png';
    
    if (empty($name) || $price <= 0) {
        sendJson(false, 'Vui lòng nhập tên và giá dịch vụ hợp lệ', null, 400);
    }
    
    $stmt = $pdo->prepare("INSERT INTO services (name, description, price, duration, category, image, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    if ($stmt->execute([$name, $description, $price, $duration, $category, $image])) {
        sendJson(true, 'Thêm dịch vụ thành công', ['id' => $pdo->lastInsertId()]);
    } else {
        sendJson(false, 'Thêm dịch vụ thất bại', null, 500);
    }

} elseif ($method === 'PUT') {
    // Cập nhật (Admin)
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = intval($data['price'] ?? 0);
    $duration = intval($data['duration'] ?? 30);
    $category = $data['category'] ?? 'haircut';
    $image = $data['image'] ?? 'uploads/default_service.png';
    $status = $data['status'] ?? 'active';
    
    if (!$id || empty($name) || $price <= 0) {
        sendJson(false, 'Thông tin không hợp lệ', null, 400);
    }
    
    $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, price = ?, duration = ?, category = ?, image = ?, status = ? WHERE id = ?");
    if ($stmt->execute([$name, $description, $price, $duration, $category, $image, $status, $id])) {
        sendJson(true, 'Cập nhật thành công');
    } else {
        sendJson(false, 'Cập nhật thất bại', null, 500);
    }

} elseif ($method === 'DELETE') {
    // Xóa (Admin) - Chuyển trạng thái sang inactive hoặc xóa
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        sendJson(false, 'Thiếu ID dịch vụ', null, 400);
    }
    
    $stmt = $pdo->prepare("UPDATE services SET status = 'inactive' WHERE id = ?");
    if ($stmt->execute([$id])) {
        sendJson(true, 'Xóa dịch vụ thành công');
    } else {
        sendJson(false, 'Xóa thất bại', null, 500);
    }

} else {
    sendJson(false, 'Phương thức không hợp lệ', null, 405);
}
