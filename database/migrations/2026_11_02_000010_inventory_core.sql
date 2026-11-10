-- Enterprise Core: additive audit trail, no modification of existing POS/orders tables.
CREATE TABLE IF NOT EXISTS core_inventory_audit (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seller_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 action_name VARCHAR(50) NOT NULL,
 previous_stock INT UNSIGNED NULL,
 new_stock INT UNSIGNED NULL,
 previous_price_clp BIGINT UNSIGNED NOT NULL,
 new_price_clp BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(seller_id,created_at),
 INDEX(product_id,created_at),
 FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
