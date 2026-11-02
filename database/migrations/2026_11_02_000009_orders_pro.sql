-- Aditiva: pedidos por comercio sin cobro online
CREATE TABLE IF NOT EXISTS pro_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 buyer_id BIGINT UNSIGNED NOT NULL, seller_id BIGINT UNSIGNED NOT NULL,
 status ENUM('requested','confirmed','completed','cancelled','rejected') NOT NULL DEFAULT 'requested',
 total_clp BIGINT UNSIGNED NOT NULL, note VARCHAR(1000) NOT NULL DEFAULT '',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL,
 INDEX(buyer_id,created_at), INDEX(seller_id,status,created_at),
 FOREIGN KEY(buyer_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pro_order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL, product_id BIGINT UNSIGNED NULL,
 title_snapshot VARCHAR(180) NOT NULL, quantity INT UNSIGNED NOT NULL,
 price_clp BIGINT UNSIGNED NOT NULL, subtotal_clp BIGINT UNSIGNED NOT NULL,
 INDEX(order_id), FOREIGN KEY(order_id) REFERENCES pro_orders(id) ON DELETE CASCADE,
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
