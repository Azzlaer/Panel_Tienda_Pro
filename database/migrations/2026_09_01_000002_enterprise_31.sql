-- ComercioSocial 3.1 Enterprise
INSERT INTO settings (`key`, value) VALUES
('seo_default_description','Explora artículos, servicios y comercios locales publicados en ComercioSocial.'),
('app_version','3.1.0')
ON DUPLICATE KEY UPDATE value=VALUES(value);

INSERT IGNORE INTO schema_migrations (migration)
VALUES ('2026_09_01_000002_enterprise_31');
