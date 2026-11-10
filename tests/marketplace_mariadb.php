<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/marketplace.php';
$dsn=getenv('TEST_DB_DSN')?:'mysql:host=127.0.0.1;port=3306;dbname=commerce_test;charset=utf8mb4';
$db=new PDO($dsn,getenv('TEST_DB_USER')?:'root',getenv('TEST_DB_PASS')?:'root',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
function check_mkt(bool $ok,string $message): void {
 echo ($ok?'PASS ':'FAIL ').$message.PHP_EOL;
 if(!$ok)throw new RuntimeException($message);
}
// Isolated CI DB, never production; create minimal application schema.
$db->exec("CREATE TABLE users(id BIGINT UNSIGNED PRIMARY KEY,username VARCHAR(40) NOT NULL,status ENUM('active','pending','blocked') NOT NULL) ENGINE=InnoDB");
$db->exec("CREATE TABLE profiles(user_id BIGINT UNSIGNED PRIMARY KEY,display_name VARCHAR(100) NOT NULL,FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB");
$db->exec("CREATE TABLE categories(id BIGINT UNSIGNED PRIMARY KEY,parent_id BIGINT UNSIGNED NULL,name VARCHAR(120) NOT NULL,active TINYINT(1) NOT NULL DEFAULT 1) ENGINE=InnoDB");
$db->exec("CREATE TABLE products(id BIGINT UNSIGNED PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,category_id BIGINT UNSIGNED NOT NULL,title VARCHAR(180) NOT NULL,description TEXT NOT NULL,price_clp BIGINT UNSIGNED NOT NULL,stock INT UNSIGNED NULL,cover_image VARCHAR(255) NOT NULL,views BIGINT UNSIGNED NOT NULL DEFAULT 0,featured TINYINT NOT NULL DEFAULT 0,status ENUM('published','pending','hidden') NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id),FOREIGN KEY(category_id) REFERENCES categories(id)) ENGINE=InnoDB");
$db->exec("INSERT INTO users VALUES(1,'shop','active'),(2,'buyer','active'),(3,'other','active'),(4,'inactive','pending')");
$db->exec("INSERT INTO profiles VALUES(1,'Tienda central'),(2,'Comprador'),(3,'Tienda tercera'),(4,'Tienda pendiente')");
$db->exec("INSERT INTO categories VALUES(1,NULL,'Tecnología',1),(2,NULL,'Otros',1)");
$db->exec("INSERT INTO products(id,user_id,category_id,title,description,price_clp,stock,cover_image,views,featured,status) VALUES
(10,1,1,'Teclado','Mecánico',25000,4,'assets/cover.svg',20,1,'published'),
(11,1,1,'Mouse','Óptico',5000,0,'assets/cover.svg',10,0,'published'),
(12,3,2,'Lámpara','Decoración',15000,NULL,'assets/cover.svg',1,0,'published'),
(13,4,1,'No visible','Pendiente vendedor',100,1,'assets/cover.svg',0,0,'published'),
(14,1,1,'Borrador','No publicado',500,2,'assets/cover.svg',0,0,'pending')");
marketplace_install($db);marketplace_install($db);
check_mkt(marketplace_ready($db),'migration ready and idempotent');
$r=marketplace_query($db,marketplace_filters([]),1);
check_mkt($r['total']===3,'published active catalog hides pending seller and draft');
$r=marketplace_query($db,marketplace_filters(['q'=>'teclado','available'=>'1']),1);
check_mkt($r['total']===1 && (int)$r['items'][0]['id']===10,'search and availability');
$r=marketplace_query($db,marketplace_filters(['min'=>'10000','max'=>'30000']),1);
check_mkt($r['total']===2,'price range filters');
$r=marketplace_query($db,marketplace_filters(['category'=>'1','sort'=>'price_asc']),1);
check_mkt($r['total']===2 && (int)$r['items'][0]['id']===11,'category and sort');
$r=marketplace_query($db,marketplace_filters(['seller'=>'3']),1);
check_mkt($r['total']===1 && (int)$r['items'][0]['id']===12,'seller scope');
$compare=marketplace_comparison($db,[12,10,13,14]);
check_mkt(count($compare)===2 && (int)$compare[0]['id']===12,'comparison excludes hidden items');
marketplace_favorite($db,2,10,true);marketplace_favorite($db,2,10,true);
check_mkt((int)$db->query('SELECT COUNT(*) FROM marketplace_favorites')->fetchColumn()===1,'favorite deduplication');
marketplace_favorite($db,2,13,true);
check_mkt((int)$db->query('SELECT COUNT(*) FROM marketplace_favorites')->fetchColumn()===1,'cannot favorite pending seller');
marketplace_favorite($db,3,10,true);
check_mkt((int)$db->query('SELECT COUNT(*) FROM marketplace_favorites')->fetchColumn()===2,'favorites isolated per user');
marketplace_favorite($db,2,10,false);
check_mkt((int)$db->query('SELECT COUNT(*) FROM marketplace_favorites WHERE user_id=3')->fetchColumn()===1,'remove favorite only from owner');
$json=json_encode(marketplace_filters(['category'=>1]),JSON_THROW_ON_ERROR);
$s=$db->prepare('INSERT INTO marketplace_saved_searches(user_id,name,filters_json) VALUES(?,?,?)');
$s->execute([2,'Tecnología',$json]);$s->execute([3,'Tecnología',$json]);
check_mkt((int)$db->query('SELECT COUNT(*) FROM marketplace_saved_searches')->fetchColumn()===2,'saved searches independent per user');
$db->prepare('DELETE FROM marketplace_saved_searches WHERE name=? AND user_id=?')->execute(['Tecnología',2]);
check_mkt((int)$db->query('SELECT COUNT(*) FROM marketplace_saved_searches WHERE user_id=3')->fetchColumn()===1,'search deletion owner scoped');
echo "Marketplace MariaDB integration complete\n";
