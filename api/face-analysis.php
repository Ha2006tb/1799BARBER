<?php
// Gọi file config để lấy hàm DB với sendJson
require_once 'config.php';
// Tài khoản kết nối bên Face++ (đổi là AI ngừng chạy)
$FACEPP_API_KEY = '8M2dcQLNPZx9MhCmHrLJWoPHjHnThlu9';
$FACEPP_API_SECRET = 'lyHabJlDmPIjVnyqO_6Pmk40PCR2Ejyz';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (empty($_FILES['image']['tmp_name'])) {
        sendJson(false, 'Vui lòng upload ảnh', null, 400);
    }

    $file = $_FILES['image'];
    if ($file['size'] > 5 * 1024 * 1024) {
        sendJson(false, 'Ảnh quá lớn (tối đa 5MB)', null, 400);
    }

    $image = new CURLFile($file['tmp_name'], $file['type'], $file['name']);

    $postData = [
        'api_key' => $FACEPP_API_KEY,
        'api_secret' => $FACEPP_API_SECRET,
        'image_file' => $image,
        'return_landmark' => '1'
    ];

    $ch = curl_init('https://api-us.faceplusplus.com/facepp/v3/detect');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
// Thực hiện lệnh gửi và nhận kết quả
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['faces']) && count($data['faces']) > 0) {
        $face = $data['faces'][0];
        $shape = analyzeFaceShape($face);
        
        sendJson(true, 'Phân tích thành công', ['face_shape' => $shape]);
    } else {
        sendJson(false, 'Không nhận diện được khuôn mặt. Vui lòng thử ảnh rõ nét, chính diện.', null, 400);
    }
} else {
    sendJson(false, 'Phương thức không hợp lệ', null, 405);
}

// HÀM TOÁN HỌC TỰ VIẾT ĐỂ TÍNH TOÁN DÁNG MẶT
function analyzeFaceShape($face) {
    // Nếu API Face++ trả về các điểm landmark (83 điểm hoặc 150 điểm)
    if (isset($face['landmark'])) {
        $lm = $face['landmark'];

        // 1. Tính toán chiều rộng khuôn mặt (Khoảng cách giữa 2 góc xương hàm trái và phải)
        // Điểm landmark góc hàm trái thường là contour_left6, phải là contour_right6 (hoặc điểm tương đương)
        $left_jaw = isset($lm['contour_left6']) ? $lm['contour_left6']['x'] : $lm['contour_left1']['x'];
        $right_jaw = isset($lm['contour_right6']) ? $lm['contour_right6']['x'] : $lm['contour_right1']['x'];
        $face_width = abs($right_jaw - $left_jaw);

        // 2. Tính toán chiều dài khuôn mặt (Từ đỉnh trán xuống cằm)
        $forehead_top = isset($lm['contour_top']) ? $lm['contour_top']['y'] : $face['face_rectangle']['top'];
        $chin_bottom = $lm['contour_chin']['y'];
        $face_length = abs($chin_bottom - $forehead_top);

        // Tránh lỗi chia cho 0 nếu dữ liệu có vấn đề
        if ($face_length > 0) {
            $ratio = $face_width / $face_length;

            // Thuật toán phân loại dáng mặt dựa trên tỉ lệ thực tế giữa xương hàm và chiều dài mặt:
            if ($ratio >= 0.85) {
                return "Tròn";      // Mặt có chiều ngang lớn, gần bằng chiều dọc
            }
            if ($ratio < 0.75) {
                return "Dài";       // Mặt thon dài rõ rệt
            }
            if ($ratio >= 0.75 && $ratio < 0.80) {
                return "Trái xoan"; // Tỉ lệ mặt Oval / Trái xoan cân đối tuyệt vời
            }
            return "Vuông";         // Các tỉ lệ góc cạnh còn lại nằm trong khoảng 0.80 - 0.85
        }
    }

    // Phương án dự phòng (Fallback) nếu không lấy được landmark, dùng lại face_rectangle nhưng tối ưu lại tỉ lệ cẩn thận hơn
    $w = $face['face_rectangle']['width'];
    $h = $face['face_rectangle']['height'];
    $ratio = $w / $h;

    if ($ratio > 0.95) return "Tròn";
    if ($ratio < 0.80) return "Dài";
    if ($ratio >= 0.80 && $ratio <= 0.88) return "Trái xoan";
    return "Vuông";
}