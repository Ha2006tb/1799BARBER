CREATE DATABASE IF NOT EXISTS datlichcattoc DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE datlichcattoc;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `branches`;
DROP TABLE IF EXISTS `lookbooks`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `stylists`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- Bảng người dùng (Lưu ý: Mật khẩu không mã hóa theo yêu cầu)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'customer', 'stylist') DEFAULT 'customer',
  `points` INT DEFAULT 0,
  `membership_tier` ENUM('Silver', 'Gold', 'Diamond') DEFAULT 'Silver',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Stylist
CREATE TABLE `stylists` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `avatar` VARCHAR(255) DEFAULT 'uploads/default_stylist.png',
  `bio` TEXT,
  `rating` DECIMAL(3,1) DEFAULT 5.0,
  `experience` VARCHAR(50),
  `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Dịch vụ & Combo
CREATE TABLE `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `price` INT NOT NULL,
  `duration` INT NOT NULL DEFAULT 30, -- phút
  `category` ENUM('combo', 'haircut', 'chemical', 'skin') DEFAULT 'haircut',
  `image` VARCHAR(255) DEFAULT 'uploads/default_service.png',
  `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Sản phẩm (30Shine Store / GentleBarber Store)
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `price` INT NOT NULL,
  `image` VARCHAR(255) DEFAULT 'uploads/default_product.png',
  `category` ENUM('wax', 'shampoo', 'skincare') DEFAULT 'wax',
  `stock` INT DEFAULT 100,
  `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Bộ sưu tập kiểu tóc (Lookbook)
CREATE TABLE `lookbooks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `image` VARCHAR(255) DEFAULT 'uploads/default_lookbook.png',
  `image2` VARCHAR(255) DEFAULT NULL,
  `image3` VARCHAR(255) DEFAULT NULL,
  `content` TEXT,
  `face_shape_suitable` VARCHAR(100),
  `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Chi nhánh (Branches)
CREATE TABLE `branches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `city` VARCHAR(50) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `working_hours` VARCHAR(100) NOT NULL DEFAULT '08:30 - 21:00',
  `image` VARCHAR(255) NOT NULL,
  `map_url` TEXT,
  `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Đặt lịch (Bookings)
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `stylist_id` INT NOT NULL,
  `service_id` INT NOT NULL,
  `branch_id` INT DEFAULT NULL,
  `booking_date` DATE NOT NULL,
  `booking_time` VARCHAR(20) NOT NULL,
  `total_price` INT NOT NULL,
  `status` ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`stylist_id`) REFERENCES `stylists`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Đơn hàng (Orders)
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `total_amount` INT NOT NULL,
  `order_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending', 'confirmed', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
  `recipient_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Chi tiết Đơn hàng (Order Items)
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` INT NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Đánh giá (Reviews)
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `stylist_id` INT NOT NULL,
  `rating` INT NOT NULL DEFAULT 5,
  `comment` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`stylist_id`) REFERENCES `stylists`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DỮ LIỆU MẪU (SEED DATA)

-- 1. Users (Mật khẩu không mã hóa: 123456)
INSERT INTO `users` (`id`, `fullname`, `phone`, `password`, `role`, `points`, `membership_tier`) VALUES
(1, 'Quản trị viên GentleBarber', '0999999999', '123456', 'admin', 500, 'Diamond'),
(2, 'Nguyễn Văn Khách Hàng', '0987654321', '123456', 'customer', 150, 'Gold'),
(3, 'Trần Khách Hàng Hai', '0912345678', '123456', 'customer', 20, 'Silver'),
(4, 'Master Barber Tuấn', '0901111111', '123456', 'stylist', 0, 'Silver'),
(5, 'Senior Barber Long', '0902222222', '123456', 'stylist', 0, 'Silver');

-- 2. Stylists
INSERT INTO `stylists` (`id`, `name`, `avatar`, `bio`, `rating`, `experience`) VALUES
(1, 'Master Barber Tuấn', 'uploads/stylist_tuan.jpg', 'Chuyên gia tạo kiểu tóc nam hàng đầu với hơn 10 năm kinh nghiệm. Bậc thầy của các đường cắt Fade sắc nét và tạo kiểu Quiff lịch lãm.', 4.9, '10 năm kinh nghiệm'),
(2, 'Senior Barber Long', 'uploads/stylist_long.jpg', 'Phong cách trẻ trung, năng động, luôn cập nhật các xu hướng tóc mới nhất từ Hàn Quốc và châu Âu như Mullet, Mohican.', 4.8, '6 năm kinh nghiệm'),
(3, 'Stylist Minh', 'uploads/stylist_minh.jpg', 'Chuyên gia tư vấn và tạo kiểu tóc phù hợp với từng khuôn mặt. Đam mê với nghệ thuật uốn và nhuộm tóc thời trang.', 4.7, '5 năm kinh nghiệm'),
(4, 'Stylist Hoàng', 'uploads/stylist_hoang.jpg', 'Tỉ mỉ, chu đáo và thân thiện. Chuyên các dòng tóc Classic cổ điển như Pompadour, Side Part chuẩn quý ông.', 4.9, '7 năm kinh nghiệm'),
(5, 'Senior Barber Nam', 'uploads/stylist_nam.jpg', 'Phong cách phóng khoáng, chuyên gia tóc lính thủy đánh bộ và các đường cắt Undercut sắc lẹm.', 4.8, '8 năm kinh nghiệm'),
(6, 'Barber Sơn', 'uploads/stylist_son.jpg', 'Trẻ trung, nhiệt huyết và cực kỳ chu đáo. Luôn tư vấn kiểu tóc phù hợp nhất với từng khách hàng.', 4.6, '4 năm kinh nghiệm'),
(7, 'Stylist Khánh', 'uploads/stylist_khanh.jpg', 'Nghệ sĩ tạo hình tóc độc đáo với khả năng Hair Tattoo đỉnh cao. Luôn biến mái tóc của bạn thành một tác phẩm nghệ thuật.', 4.9, '6 năm kinh nghiệm');

-- 3. Services & Combos
INSERT INTO `services` (`id`, `name`, `description`, `price`, `duration`, `category`, `image`) VALUES
(1, 'Gentle Shine Combo 10 Bước', 'Quy trình chăm sóc tóc và da mặt đỉnh cao: Rửa mặt sáng da - Massage bấm huyệt - Hút mụn cám - Gội đầu thư giãn - Tư vấn kiểu tóc - Cắt tóc tạo kiểu - Cạo mặt xả stress - Gội xả kỹ càng - Sấy tạo kiểu - Vuốt sáp cao cấp.', 150000, 45, 'combo', 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=600&q=80'),
(2, 'Cắt tóc nam tiêu chuẩn', 'Tư vấn kiểu tóc phù hợp khuôn mặt, cắt tóc tỉ mỉ và sấy tạo kiểu cơ bản với sáp vuốt tóc chính hãng.', 80000, 30, 'haircut', 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?w=600&q=80'),
(3, 'Uốn tóc Hàn Quốc cao cấp', 'Uốn phồng, uốn cụp hoặc uốn con sâu theo phong cách Idol Hàn Quốc, sử dụng thuốc uốn organic không hại tóc.', 350000, 90, 'chemical', 'https://images.unsplash.com/photo-1605497746444-ac9dbd39f69b?w=600&q=80'),
(4, 'Nhuộm tóc thời trang', 'Nhuộm các tông màu hot trend (Nâu lạnh, Khói, Xanh đen...), bền màu, bóng mượt và dưỡng sâu.', 300000, 60, 'chemical', 'https://images.unsplash.com/photo-1517832606299-7ae9b720a186?w=600&q=80'),
(5, 'Tẩy tóc & Nhuộm màu sáng', 'Quy trình tẩy tóc an toàn kết hợp phục hồi Olaplex, lên màu chuẩn xác cho các tông màu rực rỡ.', 600000, 120, 'chemical', 'https://images.unsplash.com/photo-1622286342621-4bd786c2447c?w=600&q=80'),
(6, 'Chăm sóc da mặt chuyên sâu', 'Tẩy tế bào chết, xông hơi, hút mụn, đắp mặt nạ than hoạt tính và massage da mặt trẻ hóa.', 120000, 30, 'skin', 'https://images.unsplash.com/photo-1599351431247-f9fa298aa7d1?w=600&q=80'),
(7, 'Cạo mặt, lấy ráy tai hoàng gia', 'Quy trình cạo mặt tỉ mỉ bằng dao cạo dùng một lần kết hợp lấy ráy tai bằng dụng cụ chuyên dụng đã khử trùng, mang lại cảm giác dễ chịu và thư giãn tuyệt đối.', 70000, 20, 'skin', 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=600&q=80'),
(8, 'Phục hồi tóc hư tổn Keratin', 'Liệu trình bổ sung Keratin giúp phục hồi cấu trúc tóc xơ yếu, hư tổn do hóa chất, trả lại mái tóc mềm mượt, chắc khỏe tự nhiên.', 250000, 45, 'chemical', 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=600&q=80');

-- 4. Products (GentleBarber Store)
INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `category`, `stock`) VALUES
(1, 'Sáp vuốt tóc Volcanic Clay V5', 'Sáp vuốt tóc quốc dân dành cho chất tóc người Việt. Độ giữ nếp Extreme Hold, hoàn thiện mờ tự nhiên không bóng, dễ dàng gội rửa.', 280000, 'uploads/product_volcanic.jpg', 'wax', 50),
(2, 'Pomade gốc nước Reuzel Blue', 'Dòng Pomade danh tiếng từ Hà Lan. Độ giữ nếp cao, độ bóng sang trọng, hương vani gỗ nam tính cực kỳ cuốn hút.', 420000, 'uploads/product_reuzel.jpg', 'wax', 30),
(3, 'Dầu gội nam cao cấp Gentle Cleanse', 'Dầu gội chiết xuất bạc hà và nhân sâm, làm sạch gàu, ngăn rụng tóc và mang lại cảm giác mát lạnh sảng khoái.', 190000, 'uploads/product_shampoo.jpg', 'shampoo', 80),
(4, 'Sữa rửa mặt nam ngừa mụn Gentle Care', 'Công thức dịu nhẹ chứa tinh chất tràm trà giúp làm sạch bã nhờn, kháng viêm và hỗ trợ giảm mụn hiệu quả.', 160000, 'uploads/product_cleanser.jpg', 'skincare', 65),
(5, 'Gôm xịt tóc Butterfly Shadow', 'Gôm xịt tóc định hình siêu cứng, giữ nếp suốt cả ngày dài và dễ dàng gội sạch.', 120000, 'uploads/product_hairspray.jpg', 'wax', 100),
(6, 'Dầu xả phục hồi tóc Gentle Smooth', 'Cung cấp dưỡng chất thiết yếu nuôi dưỡng sâu giúp tóc mềm mượt óng ả sau mỗi lần gội.', 180000, 'uploads/product_conditioner.jpg', 'shampoo', 75),
(7, 'Nước hoa nam Gentle Elite 50ml', 'Hương thơm gỗ trầm ấm nam tính mang phong cách cổ điển, quý phái của các quý ông Anh Quốc.', 650000, 'uploads/product_perfume.jpg', 'skincare', 40);

-- 5. Lookbooks
INSERT INTO `lookbooks` (`id`, `title`, `description`, `image`, `image2`, `image3`, `content`, `face_shape_suitable`) VALUES
(1, 'Kiểu tóc Mohican chéo cá tính', 'Kiểu tóc ngắn gọn gàng hai bên, phần mái được vuốt chéo tạo điểm nhấn góc cạnh và nam tính.', 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?w=500&q=80', 'https://images.unsplash.com/photo-1605497746444-ac9dbd39f69b?w=500&q=80', 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=500&q=80', 'Mohican chéo (hay còn gọi là Mohican Quiff) là kiểu tóc phá cách được cắt ngắn gọn gàng hai bên mai và sau gáy, trong khi phần mái được tỉa xéo cá tính. Kiểu tóc này cực kỳ hợp với những chàng trai sở hữu khuôn mặt tròn hoặc vuông vì nó tạo cảm giác thon gọn và tăng thêm nét góc cạnh cho khuôn mặt.\r\n\r\nCách vuốt kiểu Mohican chéo khá đơn giản: Sau khi gội đầu, bạn sấy tóc hướng về phía trước và nghiêng xéo theo chiều mong muốn. Sử dụng sáp vuốt tóc có độ giữ nếp cao và độ bóng mờ tự nhiên (như Volcanic Clay) xoa đều lên lòng bàn tay và vuốt đều từ gáy lên đỉnh đầu, sau đó tạo nếp xéo cho phần mái để hoàn thiện.', 'Khuôn mặt tròn, mặt vuông'),
(2, 'Kiểu tóc Undercut Quiff lịch lãm', 'Phong cách không bao giờ lỗi thời. Hai bên cắt Fade gọn, phần đỉnh để dài vừa phải vuốt dựng Quiff sang trọng.', 'https://images.unsplash.com/photo-1622286342621-4bd786c2447c?w=500&q=80', 'https://images.unsplash.com/photo-1517832606299-7ae9b720a186?w=500&q=80', 'https://images.unsplash.com/photo-1599351431247-f9fa298aa7d1?w=500&q=80', 'Undercut Quiff lịch lãm là sự kết hợp hoàn hảo giữa phong cách Undercut mát mẻ và phần mái Quiff vuốt dựng quyến rũ. Đây là kiểu tóc phù hợp với hầu hết mọi dáng khuôn mặt, đặc biệt tôn lên đường nét nam tính của cằm và trán.\r\n\r\nĐể giữ cho kiểu tóc Undercut Quiff luôn chuẩn form suốt cả ngày, bạn cần sự trợ giúp của máy sấy công suất lớn và lược bán nguyệt. Sấy ngược phần mái lên từ dưới lên để tạo độ phồng tối đa (volume), sau đó dùng Pomade gốc nước vuốt ngược ra sau để tạo vẻ ngoài sang trọng, lịch lãm phù hợp cho các buổi tiệc hoặc môi trường công sở.', 'Mọi khuôn mặt'),
(3, 'Kiểu tóc Mullet hiện đại (Modern Mullet)', 'Xu hướng hot nhất hiện nay với phần gáy để dài đặc trưng kết hợp tỉa layer nhẹ nhàng phía trên.', 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=500&q=80', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&q=80', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=500&q=80', 'Modern Mullet (tóc cá hố hiện đại) là xu hướng tóc nam phá cách nổi tiếng toàn cầu. Đặc điểm của Mullet là phần tóc gáy được để dài lãng tử, hai bên mai được cắt ngắn gọn gàng (có thể kết hợp fade nhẹ) và phần đỉnh được tỉa layer bồng bềnh.\r\n\r\nKiểu tóc này cực kỳ tôn dáng cho khuôn mặt dài, oval hoặc trái xoan. Để duy trì mái tóc Mullet đẹp tự nhiên, bạn nên sử dụng dầu dưỡng tóc (hair tonic) sau khi gội để giữ độ ẩm cho phần gáy, sau đó sấy tạo độ phồng tự nhiên cho phần đỉnh mà không cần dùng quá nhiều sáp vuốt cứng.', 'Khuôn mặt dài, mặt trái xoan'),
(4, 'Kiểu tóc Side Part Rủ 7/3 phong cách lãng tử', 'Kiểu tóc chia ngôi 7/3 uốn rủ nhẹ nhàng trước trán, mang lại vẻ ngoài lãng tử và thu hút phái đẹp.', 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=500&q=80', 'https://images.unsplash.com/photo-1512485694743-9c9538b4e6e0?w=500&q=80', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=500&q=80', 'Side Part Rủ 7/3 phong cách lãng tử là lựa chọn hàng đầu của các ngôi sao và nam thần Châu Á. Tóc được chia ngôi theo tỷ lệ 7/3 truyền thống, nhưng phần mái được uốn phồng nhẹ nhàng và rủ xuống tự nhiên trước trán.\r\n\r\nKiểu tóc này giúp che đi khuyết điểm trán cao hoặc vầng trán không cân đối, phù hợp nhất với khuôn mặt hình trái tim và kim cương. Bạn nên sấy tóc theo hướng rủ tự nhiên và dùng một chút gôm xịt giữ nếp nhẹ để mái tóc giữ được độ bồng bềnh lãng tử mà không bị bết hay cứng đơ.', 'Khuôn mặt trái tim, mặt kim cương'),
(5, 'Kiểu tóc Buzz Cut mạnh mẽ', 'Kiểu tóc húi cua cực kỳ mát mẻ, nam tính và dễ chăm sóc. Hoàn hảo cho mùa hè.', 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=500&q=80', 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?w=500&q=80', 'https://images.unsplash.com/photo-1605497746444-ac9dbd39f69b?w=500&q=80', 'Buzz Cut (tóc húi cua) là kiểu tóc ngắn tối giản, mang phong cách quân đội mạnh mẽ và năng động. Kiểu tóc này tôn lên trọn vẹn những đường nét góc cạnh, nam tính trên khuôn mặt oval hoặc mặt vuông góc cạnh.\r\n\r\nƯu điểm lớn nhất của Buzz Cut là bạn hoàn toàn không cần tốn thời gian sấy hay vuốt sáp mỗi sáng. Mái tóc luôn gọn gàng, mát mẻ, đặc biệt thích hợp cho những người thường xuyên vận động thể thao hoặc bận rộn.', 'Khuôn mặt oval, mặt góc cạnh'),
(6, 'Kiểu tóc Layer Hàn Quốc thư sinh', 'Mái tóc tỉa layer nhẹ nhàng, tạo độ bồng bềnh tự nhiên mang lại vẻ ngoài lãng tử và thư sinh chuẩn Hàn.', 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=500&q=80', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=500&q=80', 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?w=500&q=80', 'Tóc tỉa Layer Hàn Quốc mang lại vẻ ngoài thư sinh, trẻ trung và ấm áp. Mái tóc được cắt tỉa thành nhiều tầng (layer) xếp chồng lên nhau tạo cảm giác bồng bềnh và dày dặn tự nhiên.\r\n\r\nKiểu tóc này cực kỳ thích hợp cho những người có khuôn mặt dài hoặc trái xoan vì phần mái layer che bớt trán giúp khuôn mặt cân đối hơn. Bạn chỉ cần sấy khô tự nhiên sau khi gội và dùng một chút sáp vuốt tóc dạng bột (styling powder) để tạo phồng và định hình các lọn tóc tỉa xếp lớp.', 'Khuôn mặt dài, mặt trái xoan'),
(7, 'Kiểu tóc Pompadour cổ điển', 'Kiểu tóc vuốt ngược kinh điển thể hiện sự lịch lãm, quyền lực và trưởng thành của quý ông.', 'https://images.unsplash.com/photo-1488161628813-04466f872be2?w=500&q=80', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&q=80', 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?w=500&q=80', 'Pompadour cổ điển là biểu tượng trường tồn của phong cách quý ông lịch lãm. Phần mái được vuốt ngược hoàn toàn ra sau với độ phồng lớn ở phần trán, tạo cảm giác quyền lực và tôn thêm chiều cao cho khuôn mặt tròn hoặc mặt vuông.\r\n\r\nĐể tạo kiểu Pompadour đúng chuẩn, bạn cần dùng lược tròn kết hợp máy sấy sấy cuộn ngược mái tóc từ trước ra sau để tạo form phồng. Sau đó, vuốt một lượng Pomade gốc dầu hoặc gốc nước chất lượng cao (như Reuzel) để tạo độ bóng bẩy, giữ nếp chắc chắn cho mái tóc suốt cả ngày.', 'Khuôn mặt vuông, mặt tròn');

-- 6. Bookings
INSERT INTO `bookings` (`id`, `user_id`, `stylist_id`, `service_id`, `booking_date`, `booking_time`, `total_price`, `status`, `notes`) VALUES
(1, 2, 1, 1, CURDATE(), '09:00', 150000, 'completed', 'Khách muốn cắt Fade sát, vuốt sáp tự nhiên.'),
(2, 2, 2, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:30', 350000, 'confirmed', 'Uốn phồng tự nhiên, không quá xoăn.'),
(3, 3, 3, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00', 80000, 'pending', 'Khách cắt lần đầu.');

-- 7. Orders
INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `recipient_name`, `phone`, `shipping_address`) VALUES
(1, 2, 280000, 'completed', 'Nguyễn Văn Khách Hàng', '0987654321', '123 Đường ABC, Quận 1, TP. HCM'),
(2, 3, 610000, 'pending', 'Trần Khách Hàng Hai', '0912345678', '456 Đường XYZ, Quận Hoàn Kiếm, Hà Nội');

-- 8. Order Items
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 280000),
(2, 2, 2, 1, 420000),
(3, 2, 3, 1, 190000);

-- 9. Reviews
INSERT INTO `reviews` (`id`, `booking_id`, `user_id`, `stylist_id`, `rating`, `comment`) VALUES
(1, 1, 2, 1, 5, 'Anh Tuấn cắt cực kỳ ưng ý, tư vấn nhiệt tình. Sẽ tiếp tục ủng hộ GentleBarber!');

-- 10. Branches
INSERT INTO `branches` (`id`, `name`, `address`, `city`, `phone`, `working_hours`, `image`, `map_url`) VALUES
(1, 'GentleBarber Nguyễn Trãi', '123 Nguyễn Trãi, Phường Bến Thành, Quận 1', 'hcm', '0999.000.001', '08:30 - 21:00', 'https://images.unsplash.com/photo-1585747860715-2ba37e788b70?w=600&q=80', 'https://maps.google.com/maps?q=123%20Nguyễn%20Trãi,%20Quận%201,%20TP.%20HCM&t=&z=13&ie=UTF8&iwloc=&output=embed'),
(2, 'GentleBarber Lê Văn Sỹ', '456 Lê Văn Sỹ, Phường 14, Quận 3', 'hcm', '0999.000.002', '08:30 - 21:00', 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?w=600&q=80', 'https://maps.google.com/maps?q=456%20Lê%20Văn%20Sỹ,%20Quận%203,%20TP.%20HCM&t=&z=13&ie=UTF8&iwloc=&output=embed'),
(3, 'GentleBarber Điện Biên Phủ', '789 Điện Biên Phủ, Phường 25, Quận Bình Thạnh', 'hcm', '0999.000.003', '08:30 - 21:00', 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=600&q=80', 'https://maps.google.com/maps?q=789%20Điện%20Biên%20Phủ,%20Bình%20Thạnh,%20TP.%20HCM&t=&z=13&ie=UTF8&iwloc=&output=embed'),
(4, 'GentleBarber Lý Quốc Sư', '12 Lý Quốc Sư, Phường Hàng Trống, Quận Hoàn Kiếm', 'hanoi', '0999.000.004', '08:30 - 21:00', 'https://images.unsplash.com/photo-1605497746444-ac9dbd39f69b?w=600&q=80', 'https://maps.google.com/maps?q=12%20Lý%20Quốc%20Sự,%20Hoàn%20Kiếm,%20Hà%20Nội&t=&z=13&ie=UTF8&iwloc=&output=embed'),
(5, 'GentleBarber Xuân Thủy', '88 Xuân Thủy, Phường Dịch Vọng Hậu, Quận Cầu Giấy', 'hanoi', '0999.000.005', '08:30 - 21:00', 'https://images.unsplash.com/photo-1512485694743-9c9538b4e6e0?w=600&q=80', 'https://maps.google.com/maps?q=88%20Xuân%20Thủy,%20Cầu%20Giấy,%20Hà%20Nội&t=&z=13&ie=UTF8&iwloc=&output=embed'),
(6, 'GentleBarber Xã Đàn', '142 Xã Đàn, Phường Phương Liên, Quận Đống Đa', 'hanoi', '0999.000.006', '08:30 - 21:00', 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=600&q=80', 'https://maps.google.com/maps?q=142%20Xã%20Đàn,%20Đống%20Đa,%20Hà%20Nội&t=&z=13&ie=UTF8&iwloc=&output=embed'),
(7, 'GentleBarber Nguyễn Văn Linh', '99 Nguyễn Văn Linh, Phường Bình Hiên, Quận Hải Châu', 'danang', '0999.000.007', '08:30 - 21:00', 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?w=600&q=80', 'https://maps.google.com/maps?q=99%20Nguyễn%20Văn%20Linh,%20Hải%20Châu,%20Đà%20Nẵng&t=&z=13&ie=UTF8&iwloc=&output=embed');
