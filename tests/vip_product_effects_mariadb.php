<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/vip_product_effects.php';
$dsn=getenv('TEST_DB_DSN')?:'mysql:host=127.0.0.1;port=3306;dbname=commerce_vip_effects_test;charset=utf8mb4';
$pdo=new PDO($dsn,getenv('TEST_DB_USER')?:'root',getenv('TEST_DB_PASS')?:'root',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
function vip_db_assert(bool $ok,string $name): void {
 echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
 if(!$ok)throw new RuntimeException($name);
}
$pdo->exec("CREATE TABLE users(id BIGINT UNSIGNED PRIMARY KEY, status ENUM('active','pending','blocked') NOT NULL) ENGINE=InnoDB");
$pdo->exec("CREATE TABLE products(id BIGINT UNSIGNED PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,status ENUM('pending','published','hidden','sold') NOT NULL,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB");
$pdo->exec("INSERT INTO users VALUES(1,'active'),(2,'active'),(3,'active')");
$pdo->exec("INSERT INTO products VALUES(10,1,'published'),(11,1,'hidden'),(12,2,'published')");
vip_effect_install($pdo);vip_effect_install($pdo);
vip_db_assert(vip_effect_ready($pdo),'idempotent install');
vip_db_assert(!vip_seller_active($pdo,1),'no grant means no VIP');
try{vip_effect_assign($pdo,1,10,'gold');vip_db_assert(false,'non VIP denied');}
catch(RuntimeException){vip_db_assert(true,'non VIP denied');}
vip_grant($pdo,3,1,true,null);
vip_db_assert(vip_seller_active($pdo,1),'VIP granted');
vip_effect_assign($pdo,1,10,'gold');
vip_effect_assign($pdo,1,11,'rainbow');
vip_db_assert(vip_effect_map($pdo,[10,11,12])===[10=>'gold'],'only published own article receives public effect');
try{vip_effect_assign($pdo,1,12,'pulse');vip_db_assert(false,'foreign edit allowed');}
catch(RuntimeException){vip_db_assert(true,'foreign edit denied');}
try{vip_effect_assign($pdo,1,10,'custom-css');vip_db_assert(false,'untrusted effect allowed');}
catch(InvalidArgumentException){vip_db_assert(true,'untrusted effect denied');}
vip_effect_assign($pdo,1,10,'pulse');
vip_db_assert(vip_effect_map($pdo,[10])===[10=>'pulse'],'owner changes per-product preset');
vip_effect_assign($pdo,1,10,'none');
vip_db_assert(vip_effect_map($pdo,[10])===[],'owner clears effect');
vip_effect_assign($pdo,1,10,'neon');
vip_grant($pdo,3,1,false,null);
vip_db_assert(vip_effect_map($pdo,[10])===[],'revocation hides effect without deleting selection');
vip_grant($pdo,3,1,true,null);
$pdo->exec("UPDATE vip_seller_access SET expires_at=DATE_SUB(NOW(),INTERVAL 1 DAY) WHERE seller_id=1");
vip_db_assert(vip_effect_map($pdo,[10])===[],'expired VIP hides effects');
$pdo->exec("UPDATE vip_seller_access SET expires_at=NULL WHERE seller_id=1");
vip_db_assert(vip_effect_map($pdo,[10])===[10=>'neon'],'renewal restores previous choice');
$pdo->exec("UPDATE products SET status='hidden' WHERE id=10");
vip_db_assert(vip_effect_map($pdo,[10])===[],'unpublished products never display effects');
echo "VIP Product NameEffects MariaDB integration checks complete\n";
