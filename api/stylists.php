<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? 0;
    
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM stylists WHERE id = ?");
        $stmt->execute([$id]);
        $stylist = $stmt->fetch();
        if ($stylist) {
            sendJson(true, 'Lấy thông tin thành công', $stylist);
        } else {
            sendJson(false, 'Không tìm thấy stylist', null, 404);
        }
    }
    
    $stmt = $pdo->query("SELECT id, name, avatar, bio, rating, experience, price 
                         FROM stylists 
                         WHERE status = 'active' 
                         ORDER BY rating DESC, id ASC");
    $stylists = $stmt->fetchAll();
    sendJson(true, 'Lấy danh sách thành công', $stylists);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $name = trim($data['name'] ?? '');
    $bio = trim($data['bio'] ?? '');
    $experience = trim($data['experience'] ?? '');
    $avatar = $data['avatar'] ?? 'uploads/default_stylist.png';
    $price = intval($data['price'] ?? 80000);
    
    if (empty($name)) {
        sendJson(false, 'Vui lòng nhập tên stylist', null, 400);
    }
    
    $stmt = $pdo->prepare("INSERT INTO stylists (name, avatar, bio, rating, experience, price, status) 
                           VALUES (?, ?, ?, 5.0, ?, ?, 'active')");
    if ($stmt->execute([$name, $avatar, $bio, $experience, $price])) {
        sendJson(true, 'Thêm stylist thành công', ['id' => $pdo->lastInsertId()]);
    } else {
        sendJson(false, 'Thêm stylist thất bại', null, 500);
    }

} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $bio = trim($data['bio'] ?? '');
    $experience = trim($data['experience'] ?? '');
    $avatar = $data['avatar'] ?? 'uploads/default_stylist.png';
    $price = intval($data['price'] ?? 80000);
    $status = $data['status'] ?? 'active';
    
    if (!$id || empty($name)) {
        sendJson(false, 'Thông tin không hợp lệ', null, 400);
    }
    
    $stmt = $pdo->prepare("UPDATE stylists SET name = ?, avatar = ?, bio = ?, experience = ?, price = ?, status = ? WHERE id = ?");
    if ($stmt->execute([$name, $avatar, $bio, $experience, $price, $status, $id])) {
        sendJson(true, 'Cập nhật thành công');
    } else {
        sendJson(false, 'Cập nhật thất bại', null, 500);
    }

} elseif ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) sendJson(false, 'Thiếu ID', null, 400);
    
    $stmt = $pdo->prepare("UPDATE stylists SET status = 'inactive' WHERE id = ?");
    if ($stmt->execute([$id])) {
        sendJson(true, 'Xóa stylist thành công');
    } else {
        sendJson(false, 'Xóa thất bại', null, 500);
    }
} else {
    sendJson(false, 'Phương thức không hợp lệ', null, 405);
}