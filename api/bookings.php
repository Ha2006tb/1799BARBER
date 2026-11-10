<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $userId = $_GET['user_id'] ?? 0;
    $status = $_GET['status'] ?? '';
    $id = $_GET['id'] ?? 0;
    
    if ($id) {
        $stmt = $pdo->prepare("
            SELECT b.*, u.fullname as user_name, u.phone as user_phone, s.name as stylist_name, s.avatar as stylist_avatar, sv.name as service_name, sv.image as service_image 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            JOIN stylists s ON b.stylist_id = s.id 
            JOIN services sv ON b.service_id = sv.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        if ($booking) {
            sendJson(true, 'Lấy thông tin thành công', $booking);
        } else {
            sendJson(false, 'Không tìm thấy lịch đặt', null, 404);
        }
    }
    
    $where = [];
    $params = [];
    
    if ($userId) {
        $where[] = "b.user_id = ?";
        $params[] = $userId;
    }
    if ($status && $status !== 'all') {
        $where[] = "b.status = ?";
        $params[] = $status;
    }
    
    $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $stmt = $pdo->prepare("
        SELECT b.*, u.fullname as user_name, u.phone as user_phone, s.name as stylist_name, sv.name as service_name 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN stylists s ON b.stylist_id = s.id 
        JOIN services sv ON b.service_id = sv.id 
        $whereSql 
        ORDER BY b.booking_date DESC, b.booking_time DESC
    ");
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    sendJson(true, 'Lấy danh sách lịch đặt thành công', $bookings);

} elseif ($method === 'POST') {
    // Đặt lịch mới (Customer)
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $userId = intval($data['user_id'] ?? 0);
    $stylistId = intval($data['stylist_id'] ?? 0);
    $serviceId = intval($data['service_id'] ?? 0);
    $bookingDate = trim($data['booking_date'] ?? '');
    $bookingTime = trim($data['booking_time'] ?? '');
    $notes = trim($data['notes'] ?? '');
    
    if (!$userId || !$stylistId || !$serviceId || empty($bookingDate) || empty($bookingTime)) {
        sendJson(false, 'Vui lòng chọn đầy đủ thông tin đặt lịch', null, 400);
    }
    
    // Kiểm tra stylist có bận không
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE stylist_id = ? AND booking_date = ? AND booking_time = ? AND status != 'cancelled'");
    $stmt->execute([$stylistId, $bookingDate, $bookingTime]);
    if ($stmt->fetch()) {
        sendJson(false, 'Stylist đã có lịch hẹn vào khung giờ này. Vui lòng chọn giờ khác!', null, 400);
    }
    
    // Lấy giá dịch vụ
    $stmt = $pdo->prepare("SELECT price FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
    if (!$service) {
        sendJson(false, 'Dịch vụ không tồn tại', null, 404);
    }
    
    // Lấy giá stylist
    $stmt = $pdo->prepare("SELECT price FROM stylists WHERE id = ?");
    $stmt->execute([$stylistId]);
    $stylist = $stmt->fetch();
    if (!$stylist) {
        sendJson(false, 'Stylist không tồn tại', null, 404);
    }
    
    $totalPrice = $service['price'] + $stylist['price'];
    
    $stmt = $pdo->prepare("INSERT INTO bookings 
        (user_id, stylist_id, service_id, booking_date, booking_time, total_price, status, notes) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
        
    if ($stmt->execute([$userId, $stylistId, $serviceId, $bookingDate, $bookingTime, $totalPrice, $notes])) {
        sendJson(true, 'Đặt lịch thành công! Chúng tôi sẽ sớm liên hệ xác nhận.', ['id' => $pdo->lastInsertId()]);
    } else {
        sendJson(false, 'Đặt lịch thất bại', null, 500);
    }

} elseif ($method === 'PUT') {
    // Cập nhật trạng thái (Admin) hoặc Hoàn thành (tích điểm)
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);
    $status = $data['status'] ?? '';
    
    if (!$id || empty($status)) {
        sendJson(false, 'Thông tin không hợp lệ', null, 400);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        sendJson(false, 'Không tìm thấy lịch đặt', null, 404);
    }
    
    // Nếu chuyển sang completed và trước đó chưa completed thì cộng điểm cho user
    if ($status === 'completed' && $booking['status'] !== 'completed') {
        $addPoints = intval($booking['total_price'] / 1000); // Mỗi 1000đ = 1 điểm
        
        $stmtUser = $pdo->prepare("SELECT points FROM users WHERE id = ?");
        $stmtUser->execute([$booking['user_id']]);
        $user = $stmtUser->fetch();
        
        if ($user) {
            $newPoints = $user['points'] + $addPoints;
            $tier = 'Silver';
            if ($newPoints >= 1000) $tier = 'Diamond';
            elseif ($newPoints >= 300) $tier = 'Gold';
            
            $stmtUpdateUser = $pdo->prepare("UPDATE users SET points = ?, membership_tier = ? WHERE id = ?");
            $stmtUpdateUser->execute([$newPoints, $tier, $booking['user_id']]);
        }
    }
    
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        sendJson(true, 'Cập nhật trạng thái thành công');
    } else {
        sendJson(false, 'Cập nhật thất bại', null, 500);
    }

} elseif ($method === 'DELETE') {
    // Hủy lịch (Customer / Admin)
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        sendJson(false, 'Thiếu ID lịch đặt', null, 400);
    }
    
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    if ($stmt->execute([$id])) {
        sendJson(true, 'Hủy lịch đặt thành công');
    } else {
        sendJson(false, 'Hủy lịch thất bại', null, 500);
    }

} else {
    sendJson(false, 'Phương thức không hợp lệ', null, 405);
}
