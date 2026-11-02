-- ComercioSocial Enterprise 5.1 / migration (safe to re-run)
CREATE TABLE IF NOT EXISTS commerce_map_settings (
 id TINYINT UNSIGNED PRIMARY KEY,
 image VARCHAR(255) NULL,
 title VARCHAR(180) NOT NULL DEFAULT 'Plano de locales',
 description VARCHAR(500) NOT NULL DEFAULT '',
 updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO commerce_map_settings(id,title) VALUES (1,'Encuentra tu local');
CREATE TABLE IF NOT EXISTS commerce_map_pins (
 commerce_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 x_percent DECIMAL(6,3) NOT NULL,
 y_percent DECIMAL(6,3) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 CONSTRAINT fk_map_commerce FOREIGN KEY(commerce_id) REFERENCES commerce_directory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
