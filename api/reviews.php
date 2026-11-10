<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stylistId = $_GET['stylist_id'] ?? 0;
    
    if ($stylistId) {
        $stmt = $pdo->prepare("
            SELECT r.*, u.fullname as user_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.stylist_id = ? 
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$stylistId]);
    } else {
        $stmt = $pdo->query("
            SELECT r.*, u.fullname as user_name, s.name as stylist_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            JOIN stylists s ON r.stylist_id = s.id 
            ORDER BY r.created_at DESC
        ");
    }
    
    $reviews = $stmt->fetchAll();
    sendJson(true, 'Lấy danh sách đánh giá thành công', $reviews);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $bookingId = intval($data['booking_id'] ?? 0);
    $userId = intval($data['user_id'] ?? 0);
    $stylistId = intval($data['stylist_id'] ?? 0);
    $rating = intval($data['rating'] ?? 5);
    $comment = trim($data['comment'] ?? '');
    
    if (!$bookingId || !$userId || !$stylistId || $rating < 1 || $rating > 5) {
        sendJson(false, 'Thông tin đánh giá không hợp lệ', null, 400);
    }
    
    // Kiểm tra đã đánh giá chưa
    $stmtCheck = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ?");
    $stmtCheck->execute([$bookingId]);
    if ($stmtCheck->fetch()) {
        sendJson(false, 'Bạn đã đánh giá cho lịch đặt này rồi', null, 400);
    }
    
    $stmt = $pdo->prepare("INSERT INTO reviews (booking_id, user_id, stylist_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$bookingId, $userId, $stylistId, $rating, $comment])) {
        // Cập nhật điểm đánh giá trung bình của stylist
        $stmtAvg = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE stylist_id = ?");
        $stmtAvg->execute([$stylistId]);
        $res = $stmtAvg->fetch();
        $newAvg = round($res['avg_rating'], 1);
        
        $stmtUpdate = $pdo->prepare("UPDATE stylists SET rating = ? WHERE id = ?");
        $stmtUpdate->execute([$newAvg, $stylistId]);
        
        sendJson(true, 'Gửi đánh giá thành công! Cảm ơn phản hồi của bạn.');
    } else {
        sendJson(false, 'Gửi đánh giá thất bại', null, 500);
    }

} else {
    sendJson(false, 'Phương thức không hợp lệ', null, 405);
}
