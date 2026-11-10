-- POS version 1: add-only migration; requires InnoDB and existing users/products.
CREATE TABLE IF NOT EXISTS pos_sales (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seller_id BIGINT UNSIGNED NOT NULL,
 total_clp BIGINT UNSIGNED NOT NULL,
 payment_method ENUM('cash','card_external','transfer','other') NOT NULL,
 notes VARCHAR(500) NOT NULL DEFAULT '',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(seller_id,created_at),
 FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_sale_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 sale_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NULL,
 product_title VARCHAR(180) NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 unit_price_clp BIGINT UNSIGNED NOT NULL,
 subtotal_clp BIGINT UNSIGNED NOT NULL,
 INDEX(sale_id),
 FOREIGN KEY (sale_id) REFERENCES pos_sales(id) ON DELETE CASCADE,
 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS pos_stock_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seller_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 sale_id BIGINT UNSIGNED NULL,
 movement_type ENUM('sale','adjustment') NOT NULL,
 quantity_delta INT NOT NULL,
 stock_after INT UNSIGNED NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(seller_id,created_at),
 INDEX(product_id,created_at),
 FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
 FOREIGN KEY (sale_id) REFERENCES pos_sales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
