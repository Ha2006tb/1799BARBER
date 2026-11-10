<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $userId = $_GET['user_id'] ?? 0;
    $id = $_GET['id'] ?? 0;
    
    if ($id) {
        $stmt = $pdo->prepare("SELECT o.*, u.fullname as user_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        
        if ($order) {
            $stmtItems = $pdo->prepare("
                SELECT oi.*, p.name as product_name, p.image as product_image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $stmtItems->execute([$id]);
            $order['items'] = $stmtItems->fetchAll();
            
            sendJson(true, 'Lấy thông tin đơn hàng thành công', $order);
        } else {
            sendJson(false, 'Không tìm thấy đơn hàng', null, 404);
        }
    }
    
    if ($userId) {
        $stmt = $pdo->prepare("SELECT o.*, u.fullname as user_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.user_id = ? ORDER BY o.order_date DESC");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->query("SELECT o.*, u.fullname as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC");
    }
    
    $orders = $stmt->fetchAll();
    sendJson(true, 'Lấy danh sách đơn hàng thành công', $orders);

} elseif ($method === 'POST') {
    // Đặt hàng (Customer)
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $userId = intval($data['user_id'] ?? 0);
    $recipientName = trim($data['recipient_name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $shippingAddress = trim($data['shipping_address'] ?? '');
    $items = $data['items'] ?? []; // mảng các item: ['product_id' => 1, 'quantity' => 2, 'price' => 280000]
    
    if (!$userId || empty($recipientName) || empty($phone) || empty($shippingAddress) || empty($items)) {
        sendJson(false, 'Vui lòng điền đầy đủ thông tin giao hàng và giỏ hàng', null, 400);
    }
    
    try {
        $pdo->beginTransaction();
        
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += intval($item['price']) * intval($item['quantity']);
        }
        
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, recipient_name, phone, shipping_address, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$userId, $totalAmount, $recipientName, $phone, $shippingAddress]);
        $orderId = $pdo->lastInsertId();
        
        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        
        foreach ($items as $item) {
            $stmtItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            $stmtUpdateStock->execute([$item['quantity'], $item['product_id']]);
        }
        
        $pdo->commit();
        sendJson(true, 'Đặt hàng thành công!', ['order_id' => $orderId]);
    } catch (\Exception $e) {
        $pdo->rollBack();
        sendJson(false, 'Đặt hàng thất bại: ' . $e->getMessage(), null, 500);
    }

} elseif ($method === 'PUT') {
    // Cập nhật trạng thái đơn hàng (Admin)
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);
    $status = $data['status'] ?? '';
    
    if (!$id || empty($status)) {
        sendJson(false, 'Thông tin không hợp lệ', null, 400);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        sendJson(false, 'Không tìm thấy đơn hàng', null, 404);
    }
    
    // Nếu chuyển sang completed và trước đó chưa completed thì cộng điểm cho user
    if ($status === 'completed' && $order['status'] !== 'completed') {
        $addPoints = intval($order['total_amount'] / 1000); // Mỗi 1000đ = 1 điểm
        
        $stmtUser = $pdo->prepare("SELECT points FROM users WHERE id = ?");
        $stmtUser->execute([$order['user_id']]);
        $user = $stmtUser->fetch();
        
        if ($user) {
            $newPoints = $user['points'] + $addPoints;
            $tier = 'Silver';
            if ($newPoints >= 1000) $tier = 'Diamond';
            elseif ($newPoints >= 300) $tier = 'Gold';
            
            $stmtUpdateUser = $pdo->prepare("UPDATE users SET points = ?, membership_tier = ? WHERE id = ?");
            $stmtUpdateUser->execute([$newPoints, $tier, $order['user_id']]);
        }
    }
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        sendJson(true, 'Cập nhật trạng thái đơn hàng thành công');
    } else {
        sendJson(false, 'Cập nhật thất bại', null, 500);
    }

} elseif ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        sendJson(false, 'Thiếu ID đơn hàng', null, 400);
    }
    
    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    if ($stmt->execute([$id])) {
        sendJson(true, 'Hủy đơn hàng thành công');
    } else {
        sendJson(false, 'Hủy đơn hàng thất bại', null, 500);
    }

} else {
    sendJson(false, 'Phương thức không hợp lệ', null, 405);
}
