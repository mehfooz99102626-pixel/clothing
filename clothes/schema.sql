-- MySQL Database Schema for Clothing E-Commerce Website
-- Database Name: clothing_store

CREATE DATABASE IF NOT EXISTS `clothing_store` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `clothing_store`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `role` ENUM('user', 'admin') DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Products Table
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `discount_price` DECIMAL(10, 2) DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `sizes` VARCHAR(100) DEFAULT 'S,M,L,XL', -- Comma separated values
    `colors` VARCHAR(100) DEFAULT 'Black,White,Navy,Gray', -- Comma separated values
    `stock` INT NOT NULL DEFAULT 0,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_new_arrival` TINYINT(1) DEFAULT 0,
    `is_best_seller` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL, -- Nullable for guest checkouts if needed, but we require login
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `discount_amount` DECIMAL(10, 2) DEFAULT 0.00,
    `coupon_code` VARCHAR(50) DEFAULT NULL,
    `shipping_name` VARCHAR(100) NOT NULL,
    `shipping_email` VARCHAR(150) NOT NULL,
    `shipping_phone` VARCHAR(20) NOT NULL,
    `shipping_address` TEXT NOT NULL,
    `payment_method` ENUM('cod', 'card', 'upi', 'netbanking', 'razorpay', 'paypal') NOT NULL,
    `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    `order_status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `size` VARCHAR(10) NOT NULL,
    `color` VARCHAR(50) NOT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `transaction_id` VARCHAR(100) DEFAULT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `response_payload` TEXT DEFAULT NULL, -- Storing raw gateway logs for debug
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Reviews Table
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `review_text` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Coupons Table
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    `value` DECIMAL(10, 2) NOT NULL,
    `min_cart_amount` DECIMAL(10, 2) DEFAULT 0.00,
    `expiry_date` DATE NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Wishlist Table
CREATE TABLE IF NOT EXISTS `wishlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_product` (`user_id`, `product_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- SEED DATA
-- ==========================================

-- Seed Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Men', 'men', 'Premium collection for Men'),
(2, 'Women', 'women', 'Elegant collection for Women'),
(3, 'Kids', 'kids', 'Comfortable collection for Kids');

-- Seed Products
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `discount_price`, `image`, `sizes`, `colors`, `stock`, `is_featured`, `is_new_arrival`, `is_best_seller`) VALUES
(1, 'Classic Denim Jacket', 'classic-denim-jacket', 'This premium classic denim jacket is made from heavyweight cotton denim. Features silver button closures, two button-flap chest pockets, and two welt hand pockets. Perfect layering piece for casual outings.', 79.99, 69.99, 'assets/images/denim_jacket.jpg', 'S,M,L,XL', 'Blue,Black', 45, 1, 0, 1),
(1, 'Tailored Slim Fit Suit', 'tailored-slim-suit', 'A modern, slim-fit suit jacket and trousers set crafted from lightweight wool blend. Fully lined interior, two-button front, notch lapel, and elegant breast pockets. An absolute must-have for official and premium evening gatherings.', 249.99, 199.99, 'assets/images/slim_suit.jpg', 'M,L,XL', 'Navy,Charcoal', 15, 1, 1, 0),
(1, 'Premium Cotton Crewneck Tee', 'cotton-crewneck-tee', 'Super soft cotton tee with a comfortable modern fit. Breathable fabric, tagless comfort back neck line, double-stitched sleeves and hem. Available in primary colors.', 24.99, NULL, 'assets/images/cotton_tee.jpg', 'S,M,L,XL', 'White,Black,Gray,Olive', 120, 0, 1, 1),
(2, 'Elegant Silk Evening Gown', 'elegant-evening-gown', 'A luxurious silk evening gown flowing down gracefully. It features a halter neckline, pleated detail at the waist, and a dramatic leg slit. Designed to impress at formal events and cocktail parties.', 189.99, 159.99, 'assets/images/evening_gown.jpg', 'S,M,L', 'Emerald,Black,Burgundy', 20, 1, 0, 1),
(2, 'Floral Print Summer Dress', 'floral-summer-dress', 'Lightweight and flowy floral sundress featuring adjustable spaghetti straps, sweetheart neckline, and tiered ruffled skirt. Ideal for picnics, garden parties, or beach walks.', 59.99, 49.99, 'assets/images/summer_dress.jpg', 'S,M,L,XL', 'Yellow,Pink', 60, 0, 1, 1),
(2, 'Cozy Oversized Cable-Knit Sweater', 'oversized-knit-sweater', 'Stay warm and fashionable in this plush cable-knit sweater. Drop shoulder design, ribbed mock neck, cuffs, and hem. Knitted with extra soft yarn.', 69.99, NULL, 'assets/images/cable_sweater.jpg', 'S,M,L', 'Beige,Cream,Gray', 35, 1, 1, 0),
(3, 'Kids Playful Graphic Hoodie', 'kids-graphic-hoodie', 'Comfortable and warm kid cotton hoodie with colorful graphic patterns. Front kangaroo pouch pocket and elastic rib cuffs. Soft fleece inside lining.', 39.99, 34.99, 'assets/images/kids_hoodie.jpg', 'S,M,L', 'Red,Yellow,Blue', 80, 0, 1, 0),
(3, 'Kids Denim Overall Set', 'kids-denim-overalls', 'Cute and sturdy denim overalls paired with a striped cotton long sleeve tee. Metal hardware snap buttons for easy wear. Durable stitching for endless playground adventures.', 49.99, NULL, 'assets/images/kids_overalls.jpg', 'S,M,L', 'Blue-Striped', 40, 1, 0, 1);

-- Seed Coupons
INSERT INTO `coupons` (`code`, `type`, `value`, `min_cart_amount`, `expiry_date`, `is_active`) VALUES
('SAVE10', 'percentage', 10.00, 50.00, '2027-12-31', 1),
('FLAT50', 'fixed', 50.00, 200.00, '2027-12-31', 1),
('WELCOME15', 'percentage', 15.00, 0.00, '2027-12-31', 1);

-- Seed Users (Default admin and default user)
-- Admin Password: 'AdminPassword123' -> hashed with BCRYPT
-- User Password: 'UserPassword123' -> hashed with BCRYPT
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`) VALUES
(1, 'Admin Fashion Store', 'admin@fashionstore.com', '$2y$10$w3U/dTLuEa3811lJ2i1Gbu0Z95656.Vn4/8W1j2a567676767', '9876543210', 'Admin HQ, Fashion Avenue, NY', 'admin'),
(2, 'John Doe', 'john@gmail.com', '$2y$10$w3U/dTLuEa3811lJ2i1Gbu0Z95656.Vn4/8W1j2a567676767', '9988776655', '123 Main Street, Suite 4B, Springfield, IL', 'user');

-- Seed Reviews
INSERT INTO `reviews` (`product_id`, `user_id`, `rating`, `review_text`) VALUES
(1, 2, 5, 'Absolutely love this denim jacket! Fits perfectly and the material feels incredibly premium.'),
(4, 2, 4, 'Wore this gown to a wedding and received so many compliments. Beautiful color and fabric, just slightly long.');
