<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $category = $_GET['category'] ?? '';
    $id = $_GET['id'] ?? 0;
    
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if ($product) {
            sendJson(true, 'Lấy thông tin thành công', $product);
        } else {
            sendJson(false, 'Không tìm thấy sản phẩm', null, 404);
        }
    }
    
    if ($category && $category !== 'all') {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND status = 'active' ORDER BY id DESC");
        $stmt->execute([$category]);
    } else {
        $stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY id DESC");
    }
    
    $products = $stmt->fetchAll();
    sendJson(true, 'Lấy danh sách thành công', $products);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = intval($data['price'] ?? 0);
    $image = $data['image'] ?? 'uploads/default_product.png';
    $category = $data['category'] ?? 'wax';
    $stock = intval($data['stock'] ?? 100);
    
    if (empty($name) || $price <= 0) {
        sendJson(false, 'Vui lòng nhập tên và giá sản phẩm hợp lệ', null, 400);
    }
    
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, category, stock, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    if ($stmt->execute([$name, $description, $price, $image, $category, $stock])) {
        sendJson(true, 'Thêm sản phẩm thành công', ['id' => $pdo->lastInsertId()]);
    } else {
        sendJson(false, 'Thêm sản phẩm thất bại', null, 500);
    }

} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = intval($data['price'] ?? 0);
    $image = $data['image'] ?? 'uploads/default_product.png';
    $category = $data['category'] ?? 'wax';
    $stock = intval($data['stock'] ?? 100);
    $status = $data['status'] ?? 'active';
    
    if (!$id || empty($name) || $price <= 0) {
        sendJson(false, 'Thông tin không hợp lệ', null, 400);
    }
    
    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image = ?, category = ?, stock = ?, status = ? WHERE id = ?");
    if ($stmt->execute([$name, $description, $price, $image, $category, $stock, $status, $id])) {
        sendJson(true, 'Cập nhật thành công');
    } else {
        sendJson(false, 'Cập nhật thất bại', null, 500);
    }

} elseif ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        sendJson(false, 'Thiếu ID sản phẩm', null, 400);
    }
    
    $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
    if ($stmt->execute([$id])) {
        sendJson(true, 'Xóa sản phẩm thành công');
    } else {
        sendJson(false, 'Xóa thất bại', null, 500);
    }

} else {
    sendJson(false, 'Phương thức không hợp lệ', null, 405);
}
