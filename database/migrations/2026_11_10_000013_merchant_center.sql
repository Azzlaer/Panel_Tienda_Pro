-- Merchant Center PRO: additive tables, scoped by merchant owner.
CREATE TABLE IF NOT EXISTS merchant_staff (
 owner_id BIGINT UNSIGNED NOT NULL,
 member_id BIGINT UNSIGNED NOT NULL,
 role ENUM('manager','support','analyst') NOT NULL DEFAULT 'support',
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(owner_id,member_id),
 KEY(member_id,active),
 FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(member_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS merchant_contacts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 owner_id BIGINT UNSIGNED NOT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NULL,
 phone VARCHAR(40) NULL,
 company VARCHAR(120) NULL,
 stage ENUM('lead','contacted','customer','archived') NOT NULL DEFAULT 'lead',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 KEY(owner_id,stage,created_at),
 FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS merchant_notes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 owner_id BIGINT UNSIGNED NOT NULL,
 contact_id BIGINT UNSIGNED NOT NULL,
 author_id BIGINT UNSIGNED NOT NULL,
 note VARCHAR(2000) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY(owner_id,contact_id,created_at),
 FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(contact_id) REFERENCES merchant_contacts(id) ON DELETE CASCADE,
 FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS merchant_tasks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 owner_id BIGINT UNSIGNED NOT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 assigned_to BIGINT UNSIGNED NULL,
 contact_id BIGINT UNSIGNED NULL,
 title VARCHAR(180) NOT NULL,
 status ENUM('todo','doing','done') NOT NULL DEFAULT 'todo',
 due_date DATE NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 KEY(owner_id,status,due_date),
 FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE RESTRICT,
 FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY(contact_id) REFERENCES merchant_contacts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
