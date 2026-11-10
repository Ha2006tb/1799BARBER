<?php
require_once 'config.php';

// Hàm gửi JSON an toàn không lo thiếu file bổ sung
function reply($success, $message) {
    echo json_encode(["success" => $success, "message" => $message]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// 1. LẤY DANH SÁCH (GET)
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM branches ORDER BY id DESC");
    echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
    exit;
}

// 2. THÊM HOẶC SỬA (Đều dùng POST cho đơn giản)
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) reply(false, 'Không nhận được dữ liệu');

    $id = $input['id'] ?? null;

    if ($id) {
        // Nếu có ID -> Chạy lệnh SỬA (UPDATE)
        $sql = "UPDATE branches SET name=?, city=?, address=?, map_url=?, phone=?, status=?, open_time=?, close_time=?, image=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$input['name'], $input['area'], $input['address'], $input['map_url'], $input['phone'], $input['status'], $input['open_time'], $input['close_time'], $input['image'], $id]);
        reply(true, 'Cập nhật chi nhánh thành công!');
    } else {
        // Nếu không có ID -> Chạy lệnh THÊM MỚI (INSERT)
        $sql = "INSERT INTO branches (name, city, address, map_url, phone, status, open_time, close_time, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$input['name'], $input['area'], $input['address'], $input['map_url'], $input['phone'], $input['status'], $input['open_time'], $input['close_time'], $input['image']]);
        reply(true, 'Thêm chi nhánh mới thành công!');
    }
}

// 3. XÓA (GET kết hợp tham số delete)
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM branches WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    reply(true, 'Xóa chi nhánh thành công!');
}