-- VIP product effects v1: fully additive; existing posts, themes and VIP modules remain untouched.
CREATE TABLE IF NOT EXISTS vip_seller_access (
 seller_id BIGINT UNSIGNED PRIMARY KEY,
 expires_at DATETIME NULL,
 enabled TINYINT(1) NOT NULL DEFAULT 1,
 granted_by BIGINT UNSIGNED NULL,
 granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 CONSTRAINT vip_access_seller_fk FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT vip_access_admin_fk FOREIGN KEY(granted_by) REFERENCES users(id) ON DELETE SET NULL,
 KEY idx_vip_active(enabled,expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS vip_product_effects (
 product_id BIGINT UNSIGNED PRIMARY KEY,
 seller_id BIGINT UNSIGNED NOT NULL,
 effect_key VARCHAR(32) NOT NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT vip_effect_product_fk FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 CONSTRAINT vip_effect_seller_fk FOREIGN KEY(seller_id) REFERENCES users(id) ON DELETE CASCADE,
 KEY idx_vip_effect_seller(seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
