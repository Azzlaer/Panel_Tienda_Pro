-- Content Hub PRO and Page Builder; additive schema, no changes to existing news/pages/themes.
CREATE TABLE IF NOT EXISTS hub_categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 slug VARCHAR(130) NOT NULL UNIQUE,
 description VARCHAR(350) NOT NULL DEFAULT '',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS hub_articles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id BIGINT UNSIGNED NULL,
 author_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(180) NOT NULL,
 slug VARCHAR(190) NOT NULL UNIQUE,
 summary VARCHAR(600) NOT NULL DEFAULT '',
 body MEDIUMTEXT NOT NULL,
 cover_image VARCHAR(255) NULL,
 tags VARCHAR(400) NOT NULL DEFAULT '',
 seo_title VARCHAR(190) NOT NULL DEFAULT '',
 seo_description VARCHAR(300) NOT NULL DEFAULT '',
 status ENUM('draft','published','hidden') NOT NULL DEFAULT 'draft',
 featured TINYINT(1) NOT NULL DEFAULT 0,
 views BIGINT UNSIGNED NOT NULL DEFAULT 0,
 published_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 INDEX(status,published_at),
 INDEX(category_id,status,published_at),
 INDEX(featured,published_at),
 FOREIGN KEY(category_id) REFERENCES hub_categories(id) ON DELETE SET NULL,
 FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS hub_pages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 author_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(180) NOT NULL,
 slug VARCHAR(190) NOT NULL UNIQUE,
 summary VARCHAR(600) NOT NULL DEFAULT '',
 blocks_json MEDIUMTEXT NOT NULL,
 seo_title VARCHAR(190) NOT NULL DEFAULT '',
 seo_description VARCHAR(300) NOT NULL DEFAULT '',
 status ENUM('draft','published','hidden') NOT NULL DEFAULT 'draft',
 show_navigation TINYINT(1) NOT NULL DEFAULT 0,
 sort_order INT NOT NULL DEFAULT 0,
 published_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 INDEX(status,show_navigation,sort_order),
 FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
