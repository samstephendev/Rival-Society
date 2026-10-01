-- ===================================================
-- Rival Society Seed Data
-- 4 Sample Products matching assets/images/
-- Idempotent: Only inserts if image_front is not present
-- ===================================================
INSERT INTO `products` (`name`, `price`, `image_front`, `image_back`, `stock`, `status`, `is_new`, `created_at`)
SELECT 'Naruto Sage Mode Heavyweight Tee', 1499.00, 'D1Front.jpg', 'D1Back.jpg', 25, 'active', 1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `products` WHERE `image_front` = 'D1Front.jpg'
);

INSERT INTO `products` (`name`, `price`, `image_front`, `image_back`, `stock`, `status`, `is_new`, `created_at`)
SELECT 'Sasuke Uchiha Curse Mark Oversized Tee', 1599.00, 'D2Front.jpg', 'D2Back.jpg', 20, 'active', 1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `products` WHERE `image_front` = 'D2Front.jpg'
);

INSERT INTO `products` (`name`, `price`, `image_front`, `image_back`, `stock`, `status`, `is_new`, `created_at`)
SELECT 'Itachi Crow Graphic Boxy Tee', 1699.00, 'D3Front.jpg', 'D3Back.jpg', 15, 'active', 0, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `products` WHERE `image_front` = 'D3Front.jpg'
);

INSERT INTO `products` (`name`, `price`, `image_front`, `image_back`, `stock`, `status`, `is_new`, `created_at`)
SELECT 'Kakashi Anbu Black Ops Graphic Tee', 1799.00, 'D4Front.jpg', 'D4Back.jpg', 30, 'active', 0, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `products` WHERE `image_front` = 'D4Front.jpg'
);
