<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? 0;
    
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM lookbooks WHERE id = ?");
        $stmt->execute([$id]);
        $lookbook = $stmt->fetch();
        if ($lookbook) {
            sendJson(true, 'Lấy thông tin thành công', $lookbook);
        } else {
            sendJson(false, 'Không tìm thấy lookbook', null, 404);
        }
    }
    
    $stmt = $pdo->query("SELECT * FROM lookbooks WHERE status = 'active' ORDER BY id DESC");
    $lookbooks = $stmt->fetchAll();
    sendJson(true, 'Lấy danh sách thành công', $lookbooks);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $content = trim($data['content'] ?? '');
    $face_shape_suitable = trim($data['face_shape_suitable'] ?? '');
    $image = $data['image'] ?? 'uploads/default_lookbook.png';
    $image2 = $data['image2'] ?? null;
    $image3 = $data['image3'] ?? null;
    
    if (empty($title)) {
        sendJson(false, 'Vui lòng nhập tên kiểu tóc', null, 400);
    }
    
    $stmt = $pdo->prepare("INSERT INTO lookbooks (title, description, content, image, image2, image3, face_shape_suitable, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    if ($stmt->execute([$title, $description, $content, $image, $image2, $image3, $face_shape_suitable])) {
        sendJson(true, 'Thêm lookbook thành công', ['id' => $pdo->lastInsertId()]);
    } else {
        sendJson(false, 'Thêm lookbook thất bại', null, 500);
    }

} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $content = trim($data['content'] ?? '');
    $face_shape_suitable = trim($data['face_shape_suitable'] ?? '');
    $image = $data['image'] ?? 'uploads/default_lookbook.png';
    $image2 = $data['image2'] ?? null;
    $image3 = $data['image3'] ?? null;
    $status = $data['status'] ?? 'active';
    
    if (!$id || empty($title)) {
        sendJson(false, 'Thông tin không hợp lệ', null, 400);
    }
    
    $stmt = $pdo->prepare("UPDATE lookbooks SET title = ?, description = ?, content = ?, image = ?, image2 = ?, image3 = ?, face_shape_suitable = ?, status = ? WHERE id = ?");
    if ($stmt->execute([$title, $description, $content, $image, $image2, $image3, $face_shape_suitable, $status, $id])) {
        sendJson(true, 'Cập nhật thành công');
    } else {
        sendJson(false, 'Cập nhật thất bại', null, 500);
    }

} elseif ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        sendJson(false, 'Thiếu ID lookbook', null, 400);
    }
    
    $stmt = $pdo->prepare("UPDATE lookbooks SET status = 'inactive' WHERE id = ?");
    if ($stmt->execute([$id])) {
        sendJson(true, 'Xóa lookbook thành công');
    } else {
        sendJson(false, 'Xóa thất bại', null, 500);
    }

} else {
    sendJson(false, 'Phương thức không hợp lệ', null, 405);
}
