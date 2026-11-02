SET NAMES utf8mb4;
START TRANSACTION;

INSERT IGNORE INTO categories(parent_id,name,slug,icon,description,active,sort_order) VALUES(NULL,'Deportes','deportes','🚴','Deporte y vida al aire libre',1,50);

INSERT INTO users(username,email,password_hash,role,status) VALUES('demo_vendedor_01','demo01@comerciosocial.local','$2y$12$N5MEgb3t1EnV7z3kEhYkVe3pNno50IA32fGIQUXei.nnHVB3MX2V.','user','active');
SET @u1=LAST_INSERT_ID();
INSERT INTO profiles(user_id,display_name,bio,location,business_local,business_number,business_address,phone,whatsapp,google_maps_url,show_usd)
VALUES(@u1,'Vendedor Demo 01','Perfil demostrativo para probar la vitrina informativa.','Viña del Mar','Galería Cristal','12','Av. Valparaíso 1050, Viña del Mar','+56 9 7000 0001','56970000001','https://maps.google.com/',0);

INSERT INTO products(user_id,category_id,title,slug,description,price_clp,stock,video_url,cover_image,status,featured,delivery_options,views,created_at)
SELECT @u1,c.id,'Parlante Karaoke Bluetooth','demo-01-parlante-karaoke','Parlante portátil con micrófono inalámbrico, luces LED y batería recargable. Publicación demostrativa para revisar la galería y el diseño responsive.',53990,NULL,NULL,'uploads/products/demo/producto-01-cover.jpg','published',1,'Retiro en local, Entrega coordinada, Chilexpress, Blue Express',17,DATE_SUB(NOW(),INTERVAL 10 DAY)
FROM categories c WHERE c.slug='tecnologia' LIMIT 1;
SET @p1=LAST_INSERT_ID();
INSERT INTO product_images(product_id,image,sort_order) VALUES
(@p1,'uploads/products/demo/producto-01-01.jpg',1),
(@p1,'uploads/products/demo/producto-01-02.jpg',2),
(@p1,'uploads/products/demo/producto-01-03.jpg',3);
INSERT INTO seller_monthly_scores(user_id,score_month,published_count,sales_count,sales_rating,stars) VALUES(@u1,DATE_FORMAT(CURRENT_DATE,'%Y-%m'),1,0,0,3);

INSERT INTO users(username,email,password_hash,role,status) VALUES('demo_vendedor_02','demo02@comerciosocial.local','$2y$12$N5MEgb3t1EnV7z3kEhYkVe3pNno50IA32fGIQUXei.nnHVB3MX2V.','user','active');
SET @u2=LAST_INSERT_ID();
INSERT INTO profiles(user_id,display_name,bio,location,business_local,business_number,business_address,phone,whatsapp,google_maps_url,show_usd)
VALUES(@u2,'Vendedor Demo 02','Perfil demostrativo para probar la vitrina informativa.','Valparaíso','Portal Puerto','8','Av. Brasil 1420, Valparaíso','+56 9 7000 0002','56970000002','https://maps.google.com/',1);

INSERT INTO products(user_id,category_id,title,slug,description,price_clp,stock,video_url,cover_image,status,featured,delivery_options,views,created_at)
SELECT @u2,c.id,'Notebook Gamer Ryzen 7','demo-02-notebook-gamer','Notebook de alto rendimiento para juegos, trabajo y creación de contenido. Publicación demostrativa para revisar la galería y el diseño responsive.',749990,NULL,NULL,'uploads/products/demo/producto-02-cover.jpg','published',1,'Retiro en local, Entrega coordinada, Chilexpress, Blue Express',68,DATE_SUB(NOW(),INTERVAL 9 DAY)
FROM categories c WHERE c.slug='tecnologia' LIMIT 1;
SET @p2=LAST_INSERT_ID();
INSERT INTO product_images(product_id,image,sort_order) VALUES
(@p2,'uploads/products/demo/producto-02-01.jpg',1),
(@p2,'uploads/products/demo/producto-02-02.jpg',2),
(@p2,'uploads/products/demo/producto-02-03.jpg',3);
INSERT INTO seller_monthly_scores(user_id,score_month,published_count,sales_count,sales_rating,stars) VALUES(@u2,DATE_FORMAT(CURRENT_DATE,'%Y-%m'),1,0,0,4);

INSERT INTO users(username,email,password_hash,role,status) VALUES('demo_vendedor_03','demo03@comerciosocial.local','$2y$12$N5MEgb3t1EnV7z3kEhYkVe3pNno50IA32fGIQUXei.nnHVB3MX2V.','user','active');
SET @u3=LAST_INSERT_ID();
INSERT INTO profiles(user_id,display_name,bio,location,business_local,business_number,business_address,phone,whatsapp,google_maps_url,show_usd)
VALUES(@u3,'Vendedor Demo 03','Perfil demostrativo para probar la vitrina informativa.','Santiago','Centro Comercial Andes','24','Av. Libertador Bernardo O''Higgins 1200, Santiago','+56 9 7000 0003','56970000003','https://maps.google.com/',0);

INSERT INTO products(user_id,category_id,title,slug,description,price_clp,stock,video_url,cover_image,status,featured,delivery_options,views,created_at)
SELECT @u3,c.id,'Mochila Urbana Impermeable','demo-03-mochila-urbana','Mochila resistente al agua con compartimento para notebook y correas acolchadas. Publicación demostrativa para revisar la galería y el diseño responsive.',44990,NULL,NULL,'uploads/products/demo/producto-03-cover.jpg','published',0,'Retiro en local, Entrega coordinada, Chilexpress, Blue Express',41,DATE_SUB(NOW(),INTERVAL 8 DAY)
FROM categories c WHERE c.slug='moda' LIMIT 1;
SET @p3=LAST_INSERT_ID();
INSERT INTO product_images(product_id,image,sort_order) VALUES
(@p3,'uploads/products/demo/producto-03-01.jpg',1),
(@p3,'uploads/products/demo/producto-03-02.jpg',2),
(@p3,'uploads/products/demo/producto-03-03.jpg',3);
INSERT INTO seller_monthly_scores(user_id,score_month,published_count,sales_count,sales_rating,stars) VALUES(@u3,DATE_FORMAT(CURRENT_DATE,'%Y-%m'),1,0,0,5);

INSERT INTO users(username,email,password_hash,role,status) VALUES('demo_vendedor_04','demo04@comerciosocial.local','$2y$12$N5MEgb3t1EnV7z3kEhYkVe3pNno50IA32fGIQUXei.nnHVB3MX2V.','user','active');
SET @u4=LAST_INSERT_ID();
INSERT INTO profiles(user_id,display_name,bio,location,business_local,business_number,business_address,phone,whatsapp,google_maps_url,show_usd)
VALUES(@u4,'Vendedor Demo 04','Perfil demostrativo para probar la vitrina informativa.','Providencia','Galería Providencia','31','Av. Providencia 1880, Providencia','+56 9 7000 0004','56970000004','https://maps.google.com/',1);

INSERT INTO products(user_id,category_id,title,slug,description,price_clp,stock,video_url,cover_image,status,featured,delivery_options,views,created_at)
SELECT @u4,c.id,'Sofá Modular Gris','demo-04-sofa-modular','Sofá modular de tres cuerpos con tapiz lavable y estructura reforzada. Publicación demostrativa para revisar la galería y el diseño responsive.',329990,NULL,NULL,'uploads/products/demo/producto-04-cover.jpg','published',0,'Retiro en local, Entrega coordinada, Chilexpress, Blue Express',94,DATE_SUB(NOW(),INTERVAL 7 DAY)
FROM categories c WHERE c.slug='hogar' LIMIT 1;
SET @p4=LAST_INSERT_ID();
INSERT INTO product_images(product_id,image,sort_order) VALUES
(@p4,'uploads/products/demo/producto-04-01.jpg',1),
(@p4,'uploads/products/demo/producto-04-02.jpg',2),
(@p4,'uploads/products/demo/producto-04-03.jpg',3);
INSERT INTO seller_monthly_scores(user_id,score_month,published_count,sales_count,sales_rating,stars) VALUES(@u4,DATE_FORMAT(CURRENT_DATE,'%Y-%m'),1,0,0,2);

INSERT INTO users(username,email,password_hash,role,status) VALUES('demo_vendedor_05','demo05@comerciosocial.local','$2y$12$N5MEgb3t1EnV7z3kEhYkVe3pNno50IA32fGIQUXei.nnHVB3MX2V.','user','active');
SET @u5=LAST_INSERT_ID();
INSERT INTO profiles(user_id,display_name,bio,location,business_local,business_number,business_address,phone,whatsapp,google_maps_url,show_usd)
VALUES(@u5,'Vendedor Demo 05','Perfil demostrativo para probar la vitrina informativa.','Quilpué','Paseo Quilpué','5','Los Carrera 745, Quilpué','+56 9 7000 0005','56970000005','https://maps.google.com/',0);

INSERT INTO products(user_id,category_id,title,slug,description,price_clp,stock,video_url,cover_image,status,featured,delivery_options,views,created_at)
SELECT @u5,c.id,'Taladro Inalámbrico 20V','demo-05-taladro-inalambrico','Taladro percutor con dos baterías, cargador y maletín de transporte. Publicación demostrativa para revisar la galería y el diseño responsive.',89990,NULL,NULL,'uploads/products/demo/producto-05-cover.jpg','published',0,'Retiro en local, Entrega coordinada, Chilexpress, Blue Express',52,DATE_SUB(NOW(),INTERVAL 6 DAY)
FROM categories c WHERE c.slug='servicios' LIMIT 1;
SET @p5=LAST_INSERT_ID();
INSERT INTO product_images(product_id,image,sort_order) VALUES
(@p5,'uploads/products/demo/producto-05-01.jpg',1),
(@p5,'uploads/products/demo/producto-05-02.jpg',2),
(@p5,'uploads/products/demo/producto-05-03.jpg',3);
INSERT INTO seller_monthly_scores(user_id,score_month,published_count,sales_count,sales_rating,stars) VALUES(@u5,DATE_FORMAT(CURRENT_DATE,'%Y-%m'),1,0,0,3);

INSERT INTO users(username,email,password_hash,role,status) VALUES('demo_vendedor_06','demo06@comerciosocial.local','$2y$12$N5MEgb3t1EnV7z3kEhYkVe3pNno50IA32fGIQUXei.nnHVB3MX2V.','user','active');
SET @u6=LAST_INSERT_ID();
INSERT INTO profiles(user_id,display_name,bio,location,business_local,business_number,business_address,phone,whatsapp,google_maps_url,show_usd)
VALUES(@u6,'Vendedor Demo 06','Perfil demostrativo para probar la vitrina informativa.','Concón','Centro Concón','18','Av. Concón Reñaca 320, Concón','+56 9 7000 0006','56970000006','https://maps.google.com/',1);

INSERT INTO products(user_id,category_id,title,slug,description,price_clp,stock,video_url,cover_image,status,featured,delivery_options,views,created_at)
SELECT @u6,c.id,'Bicicleta Mountain Bike','demo-06-bicicleta-mountain-bike','Bicicleta aro 29 con frenos de disco y suspensión delantera. Publicación demostrativa para revisar la galería y el diseño responsive.',289990,NULL,NULL,'uploads/products/demo/producto-06-cover.jpg','published',0,'Retiro en local, Entrega coordinada, Chilexpress, Blue Express',103,DATE_SUB(NOW(),INTERVAL 5 DAY)
FROM categories c WHERE c.slug='deportes' LIMIT 1;
SET @p6=LAST_INSERT_ID();
INSERT INTO product_images(product_id,image,sort_order) VALUES
(@p6,'uploads/products/demo/producto-06-01.jpg',1),
(@p6,'uploads/products/demo/producto-06-02.jpg',2),
(@p6,'uploads/products/demo/producto-06-03.jpg',3);
INSERT INTO seller_monthly_scores(user_id,score_month,published_count,sales_count,sales_rating,stars) VALUES(@u6,DATE_FORMAT(CURRENT_DATE,'%Y-%m'),1,0,0,4);

INSERT INTO users(username,email,password_hash,role,status) VALUES('demo_vendedor_07','demo07@comerciosocial.local','$2y$12$N5MEgb3t1EnV7z3kEhYkVe3pNno50IA32fGIQUXei.nnHVB3MX2V.','user','active');
SET @u7=LAST_INSERT_ID();
INSERT INTO profiles(user_id,display_name,bio,location,business_local,business_number,business_address,phone,whatsapp,google_maps_url,show_usd)
VALUES(@u7,'Vendedor Demo 07','Perfil demostrativo para probar la vitrina informativa.','Maipú','Paseo Maipú','42','Av. Pajaritos 2100, Maipú','+56 9 7000 0007','56970000007','https://maps.google.com/',0);

INSERT INTO products(user_id,category_id,title,slug,description,price_clp,stock,video_url,cover_image,status,featured,delivery_options,views,created_at)
SELECT @u7,c.id,'Cafetera Espresso Automática','demo-07-cafetera-espresso','Cafetera con vaporizador de leche, depósito removible y panel digital. Publicación demostrativa para revisar la galería y el diseño responsive.',159990,NULL,NULL,'uploads/products/demo/producto-07-cover.jpg','published',0,'Retiro en local, Entrega coordinada, Chilexpress, Blue Express',76,DATE_SUB(NOW(),INTERVAL 4 DAY)
FROM categories c WHERE c.slug='hogar' LIMIT 1;
SET @p7=LAST_INSERT_ID();
INSERT INTO product_images(product_id,image,sort_order) VALUES
(@p7,'uploads/products/demo/producto-07-01.jpg',1),
(@p7,'uploads/products/demo/producto-07-02.jpg',2),
(@p7,'uploads/products/demo/producto-07-03.jpg',3);
INSERT INTO seller_monthly_scores(user_id,score_month,published_count,sales_count,sales_rating,stars) VALUES(@u7,DATE_FORMAT(CURRENT_DATE,'%Y-%m'),1,0,0,5);

INSERT INTO users(username,email,password_hash,role,status) VALUES('demo_vendedor_08','demo08@comerciosocial.local','$2y$12$N5MEgb3t1EnV7z3kEhYkVe3pNno50IA32fGIQUXei.nnHVB3MX2V.','user','active');
SET @u8=LAST_INSERT_ID();
INSERT INTO profiles(user_id,display_name,bio,location,business_local,business_number,business_address,phone,whatsapp,google_maps_url,show_usd)
VALUES(@u8,'Vendedor Demo 08','Perfil demostrativo para probar la vitrina informativa.','Ñuñoa','Plaza Ñuñoa','16','Irarrázaval 3550, Ñuñoa','+56 9 7000 0008','56970000008','https://maps.google.com/',1);

INSERT INTO products(user_id,category_id,title,slug,description,price_clp,stock,video_url,cover_image,status,featured,delivery_options,views,created_at)
SELECT @u8,c.id,'Smart TV 55 pulgadas 4K','demo-08-smart-tv-55','Televisor UHD con aplicaciones, Wi-Fi, Bluetooth y control por voz. Publicación demostrativa para revisar la galería y el diseño responsive.',399990,NULL,NULL,'uploads/products/demo/producto-08-cover.jpg','published',1,'Retiro en local, Entrega coordinada, Chilexpress, Blue Express',145,DATE_SUB(NOW(),INTERVAL 3 DAY)
FROM categories c WHERE c.slug='tecnologia' LIMIT 1;
SET @p8=LAST_INSERT_ID();
INSERT INTO product_images(product_id,image,sort_order) VALUES
(@p8,'uploads/products/demo/producto-08-01.jpg',1),
(@p8,'uploads/products/demo/producto-08-02.jpg',2),
(@p8,'uploads/products/demo/producto-08-03.jpg',3);
INSERT INTO seller_monthly_scores(user_id,score_month,published_count,sales_count,sales_rating,stars) VALUES(@u8,DATE_FORMAT(CURRENT_DATE,'%Y-%m'),1,0,0,2);

INSERT INTO users(username,email,password_hash,role,status) VALUES('demo_vendedor_09','demo09@comerciosocial.local','$2y$12$N5MEgb3t1EnV7z3kEhYkVe3pNno50IA32fGIQUXei.nnHVB3MX2V.','user','active');
SET @u9=LAST_INSERT_ID();
INSERT INTO profiles(user_id,display_name,bio,location,business_local,business_number,business_address,phone,whatsapp,google_maps_url,show_usd)
VALUES(@u9,'Vendedor Demo 09','Perfil demostrativo para probar la vitrina informativa.','La Serena','Galería Serena','10','Cordovez 620, La Serena','+56 9 7000 0009','56970000009','https://maps.google.com/',0);

INSERT INTO products(user_id,category_id,title,slug,description,price_clp,stock,video_url,cover_image,status,featured,delivery_options,views,created_at)
SELECT @u9,c.id,'Servicio Técnico Computadores','demo-09-servicio-tecnico-computadores','Diagnóstico, limpieza, optimización y reparación de computadores. Publicación demostrativa para revisar la galería y el diseño responsive.',35000,NULL,NULL,'uploads/products/demo/producto-09-cover.jpg','published',0,'Retiro en local, Entrega coordinada, Chilexpress, Blue Express',88,DATE_SUB(NOW(),INTERVAL 2 DAY)
FROM categories c WHERE c.slug='servicios' LIMIT 1;
SET @p9=LAST_INSERT_ID();
INSERT INTO product_images(product_id,image,sort_order) VALUES
(@p9,'uploads/products/demo/producto-09-01.jpg',1),
(@p9,'uploads/products/demo/producto-09-02.jpg',2),
(@p9,'uploads/products/demo/producto-09-03.jpg',3);
INSERT INTO seller_monthly_scores(user_id,score_month,published_count,sales_count,sales_rating,stars) VALUES(@u9,DATE_FORMAT(CURRENT_DATE,'%Y-%m'),1,0,0,3);

INSERT INTO users(username,email,password_hash,role,status) VALUES('demo_vendedor_10','demo10@comerciosocial.local','$2y$12$N5MEgb3t1EnV7z3kEhYkVe3pNno50IA32fGIQUXei.nnHVB3MX2V.','user','active');
SET @u10=LAST_INSERT_ID();
INSERT INTO profiles(user_id,display_name,bio,location,business_local,business_number,business_address,phone,whatsapp,google_maps_url,show_usd)
VALUES(@u10,'Vendedor Demo 10','Perfil demostrativo para probar la vitrina informativa.','Puerto Montt','Paseo del Sur','27','Antonio Varas 880, Puerto Montt','+56 9 7000 0010','56970000010','https://maps.google.com/',1);

INSERT INTO products(user_id,category_id,title,slug,description,price_clp,stock,video_url,cover_image,status,featured,delivery_options,views,created_at)
SELECT @u10,c.id,'Set de Herramientas Profesional','demo-10-set-herramientas','Set de herramientas de 108 piezas con maleta rígida. Publicación demostrativa para revisar la galería y el diseño responsive.',119990,NULL,NULL,'uploads/products/demo/producto-10-cover.jpg','published',0,'Retiro en local, Entrega coordinada, Chilexpress, Blue Express',61,DATE_SUB(NOW(),INTERVAL 1 DAY)
FROM categories c WHERE c.slug='servicios' LIMIT 1;
SET @p10=LAST_INSERT_ID();
INSERT INTO product_images(product_id,image,sort_order) VALUES
(@p10,'uploads/products/demo/producto-10-01.jpg',1),
(@p10,'uploads/products/demo/producto-10-02.jpg',2),
(@p10,'uploads/products/demo/producto-10-03.jpg',3);
INSERT INTO seller_monthly_scores(user_id,score_month,published_count,sales_count,sales_rating,stars) VALUES(@u10,DATE_FORMAT(CURRENT_DATE,'%Y-%m'),1,0,0,4);

COMMIT;