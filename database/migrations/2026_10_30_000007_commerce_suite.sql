-- Commerce Suite 6.0: migración aditiva, idempotente para MySQL 8/MariaDB (XAMPP)
CREATE TABLE IF NOT EXISTS suite_inquiries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL, buyer_id BIGINT UNSIGNED NOT NULL, seller_id BIGINT UNSIGNED NOT NULL,
 subject VARCHAR(160) NOT NULL, body TEXT NOT NULL, reply TEXT NULL,
 state ENUM('new','answered','closed') NOT NULL DEFAULT 'new',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL,
 INDEX(buyer_id,created_at), INDEX(seller_id,state,created_at),
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 FOREIGN KEY(buyer_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS suite_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL, buyer_id BIGINT UNSIGNED NOT NULL, seller_id BIGINT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL DEFAULT 1, note VARCHAR(1000) NOT NULL DEFAULT '',
 state ENUM('requested','accepted','rejected','cancelled','completed') NOT NULL DEFAULT 'requested',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL,
 INDEX(buyer_id,created_at), INDEX(seller_id,state,created_at),
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 FOREIGN KEY(buyer_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS suite_campaigns (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(160) NOT NULL, subtitle VARCHAR(500) NOT NULL DEFAULT '',
 image VARCHAR(255) NULL, link_url VARCHAR(600) NULL,
 starts_at DATETIME NULL, ends_at DATETIME NULL, active TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(active,starts_at,ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
