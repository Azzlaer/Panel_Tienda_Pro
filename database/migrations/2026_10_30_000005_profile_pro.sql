-- Opcional: la aplicación crea esta tabla automáticamente.
CREATE TABLE IF NOT EXISTS profile_pro (
 user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 opening_hours VARCHAR(500) NULL,
 specialties VARCHAR(500) NULL,
 website VARCHAR(500) NULL,
 instagram VARCHAR(500) NULL,
 facebook VARCHAR(500) NULL,
 verified TINYINT(1) NOT NULL DEFAULT 0,
 updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_profilepro_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
