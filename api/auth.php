<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(false, 'Phương thức không hợp lệ', null, 405);
    }
    
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $fullname = trim($data['fullname'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $password = trim($data['password'] ?? ''); // Không mã hóa
    
    if (empty($fullname) || empty($phone) || empty($password)) {
        sendJson(false, 'Vui lòng điền đầy đủ thông tin', null, 400);
    }
    
    // Kiểm tra số điện thoại đã tồn tại
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        sendJson(false, 'Số điện thoại đã được đăng ký', null, 400);
    }
    
    $stmt = $pdo->prepare("INSERT INTO users (fullname, phone, password, role, points, membership_tier) VALUES (?, ?, ?, 'customer', 0, 'Silver')");
    if ($stmt->execute([$fullname, $phone, $password])) {
        $userId = $pdo->lastInsertId();
        sendJson(true, 'Đăng ký thành công', [
            'id' => $userId,
            'fullname' => $fullname,
            'phone' => $phone,
            'role' => 'customer',
            'points' => 0,
            'membership_tier' => 'Silver'
        ]);
    } else {
        sendJson(false, 'Đăng ký thất bại', null, 500);
    }
} elseif ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(false, 'Phương thức không hợp lệ', null, 405);
    }
    
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $phone = trim($data['phone'] ?? '');
    $password = trim($data['password'] ?? '');
    
    if (empty($phone) || empty($password)) {
        sendJson(false, 'Vui lòng nhập số điện thoại và mật khẩu', null, 400);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    // So sánh mật khẩu không mã hóa trực tiếp
    if ($user && $user['password'] === $password) {
        unset($user['password']); // Không trả về mật khẩu
        sendJson(true, 'Đăng nhập thành công', $user);
    } else {
        sendJson(false, 'Số điện thoại hoặc mật khẩu không chính xác', null, 401);
    }
} elseif ($action === 'get_profile') {
    $userId = $_GET['user_id'] ?? 0;
    if (!$userId) {
        sendJson(false, 'Thiếu user_id', null, 400);
    }
    
    $stmt = $pdo->prepare("SELECT id, fullname, phone, role, points, membership_tier, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        sendJson(true, 'Lấy thông tin thành công', $user);
    } else {
        sendJson(false, 'Không tìm thấy người dùng', null, 404);
    }
} elseif ($action === 'update_profile') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(false, 'Phương thức không hợp lệ', null, 405);
    }
    
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $userId = $data['user_id'] ?? 0;
    $fullname = trim($data['fullname'] ?? '');
    $password = trim($data['password'] ?? '');
    
    if (!$userId || empty($fullname)) {
        sendJson(false, 'Thông tin không hợp lệ', null, 400);
    }
    
    if (!empty($password)) {
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, password = ? WHERE id = ?");
        $res = $stmt->execute([$fullname, $password, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $res = $stmt->execute([$fullname, $userId]);
    }
    
    if ($res) {
        $stmt = $pdo->prepare("SELECT id, fullname, phone, role, points, membership_tier FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        sendJson(true, 'Cập nhật thông tin thành công', $stmt->fetch());
    } else {
        sendJson(false, 'Cập nhật thất bại', null, 500);
    }
} else {
    sendJson(false, 'Hành động không hợp lệ', null, 400);
}
