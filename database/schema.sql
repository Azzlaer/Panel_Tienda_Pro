CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(40) NOT NULL UNIQUE,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','user') NOT NULL DEFAULT 'user',
 status ENUM('pending','active','blocked') NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS profiles (
 user_id BIGINT UNSIGNED PRIMARY KEY,
 display_name VARCHAR(100) NOT NULL,
 bio TEXT NULL, location VARCHAR(120) NULL,
 avatar VARCHAR(255) NULL, cover VARCHAR(255) NULL,
 business_local VARCHAR(120) NULL, business_number VARCHAR(40) NULL, business_address VARCHAR(255) NULL,
 phone VARCHAR(40) NULL, whatsapp VARCHAR(40) NULL, google_maps_url VARCHAR(500) NULL,
 show_usd TINYINT(1) NOT NULL DEFAULT 0,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS posts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(180) NULL, body TEXT NOT NULL, image VARCHAR(255) NULL,
 status ENUM('published','hidden') NOT NULL DEFAULT 'published',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX(user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS comments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 post_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
 body VARCHAR(1500) NOT NULL, status ENUM('published','hidden') NOT NULL DEFAULT 'published',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ads (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(120) NOT NULL, image VARCHAR(255) NULL, target_url VARCHAR(500) NULL,
 position ENUM('sidebar','banner') NOT NULL DEFAULT 'sidebar', active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS settings (`key` VARCHAR(100) PRIMARY KEY, value MEDIUMTEXT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS visits (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, path VARCHAR(255) NOT NULL,
 ip_hash CHAR(64) NOT NULL, user_id BIGINT UNSIGNED NULL, user_agent VARCHAR(255) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(created_at), INDEX(ip_hash), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 identity_hash CHAR(64) NOT NULL,
 success TINYINT(1) NOT NULL DEFAULT 0,
 attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(identity_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 parent_id BIGINT UNSIGNED NULL,
 name VARCHAR(120) NOT NULL,
 slug VARCHAR(140) NOT NULL UNIQUE,
 icon VARCHAR(20) NULL,
 description VARCHAR(500) NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0,
 FOREIGN KEY(parent_id) REFERENCES categories(id) ON DELETE CASCADE,
 INDEX(parent_id,active,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 category_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(180) NOT NULL,
 slug VARCHAR(210) NOT NULL UNIQUE,
 description MEDIUMTEXT NOT NULL,
 price_clp BIGINT UNSIGNED NOT NULL DEFAULT 0,
 stock INT UNSIGNED NULL,
 video_url VARCHAR(500) NULL,
 cover_image VARCHAR(255) NOT NULL,
 status ENUM('pending','published','hidden','sold') NOT NULL DEFAULT 'pending',
 featured TINYINT(1) NOT NULL DEFAULT 0,
 delivery_options VARCHAR(500) NULL,
 views BIGINT UNSIGNED NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE RESTRICT,
 INDEX(status,created_at), INDEX(category_id,status), INDEX(user_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 image VARCHAR(255) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 INDEX(product_id,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seller_monthly_scores (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 score_month CHAR(7) NOT NULL,
 published_count INT UNSIGNED NOT NULL DEFAULT 0,
 sales_count INT UNSIGNED NOT NULL DEFAULT 0,
 sales_rating DECIMAL(3,2) NOT NULL DEFAULT 0,
 stars TINYINT UNSIGNED NOT NULL DEFAULT 0,
 calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY(user_id,score_month),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seller_sales (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seller_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NULL,
 rating TINYINT UNSIGNED NOT NULL,
 note VARCHAR(500) NULL,
 sale_date DATE NOT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL,
 FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE RESTRICT,
 INDEX(seller_id,sale_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS home_sections (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 section_key VARCHAR(80) NOT NULL UNIQUE,
 title VARCHAR(160) NOT NULL,
 enabled TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0,
 settings_json MEDIUMTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS themes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 slug VARCHAR(120) NOT NULL UNIQUE,
 css MEDIUMTEXT NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS news (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 created_by BIGINT UNSIGNED NOT NULL,
 title VARCHAR(190) NOT NULL,
 slug VARCHAR(220) NOT NULL UNIQUE,
 excerpt VARCHAR(600) NULL,
 content MEDIUMTEXT NOT NULL,
 content_format ENUM('bbcode','html','mixed') NOT NULL DEFAULT 'mixed',
 cover_image VARCHAR(255) NULL,
 status ENUM('draft','published','hidden') NOT NULL DEFAULT 'draft',
 featured TINYINT(1) NOT NULL DEFAULT 0,
 show_home TINYINT(1) NOT NULL DEFAULT 1,
 views BIGINT UNSIGNED NOT NULL DEFAULT 0,
 published_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE RESTRICT,
 INDEX(status,show_home,published_at), INDEX(featured,published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS feedback_threads (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seller_id BIGINT UNSIGNED NOT NULL,
 subject VARCHAR(180) NOT NULL,
 category ENUM('suggestion','problem','design','function','support','other') NOT NULL DEFAULT 'suggestion',
 priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
 status ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
 admin_read TINYINT(1) NOT NULL DEFAULT 0,
 seller_read TINYINT(1) NOT NULL DEFAULT 1,
 is_public TINYINT(1) NOT NULL DEFAULT 0,
 seller_rating TINYINT UNSIGNED NULL,
 admin_rating TINYINT UNSIGNED NULL,
 assigned_admin_id BIGINT UNSIGNED NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(assigned_admin_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(seller_id,status,created_at), INDEX(admin_read,status), INDEX(is_public,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS feedback_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 thread_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 body TEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(thread_id) REFERENCES feedback_threads(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX(thread_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS donation_methods (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 method_type ENUM('bank','crypto','payment') NOT NULL DEFAULT 'bank',
 title VARCHAR(140) NOT NULL, description TEXT NULL,
 bank_name VARCHAR(140) NULL, account_holder VARCHAR(160) NULL, rut VARCHAR(20) NULL,
 account_type VARCHAR(80) NULL, account_number VARCHAR(100) NULL, email VARCHAR(190) NULL,
 wallet_address VARCHAR(255) NULL, network VARCHAR(100) NULL,
 active TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL,
 INDEX(active,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS donations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seller_id BIGINT UNSIGNED NOT NULL, method_id BIGINT UNSIGNED NULL,
 amount_clp BIGINT UNSIGNED NOT NULL DEFAULT 0,
 external_amount DECIMAL(20,8) NULL, external_currency VARCHAR(20) NULL,
 reference_code VARCHAR(180) NULL, proof_file VARCHAR(255) NULL,
 seller_message VARCHAR(1000) NULL,
 status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
 admin_note VARCHAR(1000) NULL, reviewed_by BIGINT UNSIGNED NULL,
 donated_at DATETIME NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, reviewed_at DATETIME NULL,
 FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(method_id) REFERENCES donation_methods(id) ON DELETE SET NULL,
 FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(seller_id,status,donated_at), INDEX(status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Núcleo ComercioSocial 2.2: modo vitrina, migraciones y auditoría
CREATE TABLE IF NOT EXISTS schema_migrations (
 migration VARCHAR(160) PRIMARY KEY,
 applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 admin_id BIGINT UNSIGNED NULL,
 action VARCHAR(100) NOT NULL,
 entity_type VARCHAR(80) NULL,
 entity_id VARCHAR(80) NULL,
 details_json MEDIUMTEXT NULL,
 ip_hash CHAR(64) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(admin_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(created_at), INDEX(action), INDEX(entity_type,entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings(`key`,value) VALUES
('platform_mode','showcase'),
('showcase_notice_enabled','1'),
('showcase_notice','Esta plataforma funciona como vitrina informativa. No procesa compras, pagos, reservas ni contratos. Cada visitante debe contactar directamente al anunciante para solicitar información.'),
('showcase_price_label','Valor publicado'),
('showcase_contact_label','Consultar al anunciante'),
('showcase_hide_stock','1'),
('showcase_hide_sales_language','1'),
('template_layout_version','urban-2'),
('app_version','3.0.0'),
('installation_generation','clean-3');
