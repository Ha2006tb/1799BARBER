<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(false, 'Phương thức không hợp lệ', null, 405);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    sendJson(false, 'Không có file nào được tải lên hoặc có lỗi tải file', null, 400);
}

$file = $_FILES['image'];
$fileName = $file['name'];
$fileTmp = $file['tmp_name'];
$fileSize = $file['size'];

// Kiểm tra dung lượng (giới hạn 5MB)
if ($fileSize > 5 * 1024 * 1024) {
    sendJson(false, 'Dung lượng file vượt quá giới hạn 5MB', null, 400);
}

// Kiểm tra định dạng
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

if (!in_array($ext, $allowed)) {
    sendJson(false, 'Định dạng file không được hỗ trợ (chỉ hỗ trợ JPG, PNG, WEBP, GIF)', null, 400);
}

// Tạo tên file độc nhất để tránh trùng lặp
$newName = uniqid('img_') . '_' . time() . '.' . $ext;
$targetDir = __DIR__ . '/../uploads/';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$targetFile = $targetDir . $newName;

if (move_uploaded_file($fileTmp, $targetFile)) {
    // Trả về đường dẫn tương đối để lưu vào DB
    sendJson(true, 'Tải ảnh lên thành công', ['path' => 'uploads/' . $newName]);
} else {
    sendJson(false, 'Không thể lưu file vào thư mục uploads', null, 500);
}
