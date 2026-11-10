<?php
declare(strict_types=1);
require_once __DIR__.'/../app/inventory_core.php';
require_once __DIR__.'/../app/pos.php';
require_once __DIR__.'/../app/orders_pro.php';
require_once __DIR__.'/../app/core_migrations.php';

$dsn=getenv('TEST_DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=commerce_test;charset=utf8mb4';
$pdo=new PDO($dsn,getenv('TEST_DB_USER') ?: 'root',getenv('TEST_DB_PASS') ?: 'root',[
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
 PDO::ATTR_EMULATE_PREPARES=>false
]);
function assert_db(bool $pass,string $message): void {
 echo ($pass?'PASS ':'FAIL ').$message.PHP_EOL;
 if(!$pass)throw new RuntimeException('Integration failed: '.$message);
}
function stock(PDO $pdo,int $id): ?int {
 $q=$pdo->prepare('SELECT stock FROM products WHERE id=?');$q->execute([$id]);
 $value=$q->fetchColumn();return $value===null?null:(int)$value;
}
$pdo->exec('CREATE TABLE users (id BIGINT UNSIGNED PRIMARY KEY, username VARCHAR(40) NOT NULL) ENGINE=InnoDB');
$pdo->exec("CREATE TABLE products (
 id BIGINT UNSIGNED PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(180) NOT NULL,price_clp BIGINT UNSIGNED NOT NULL,
 stock INT UNSIGNED NULL,status ENUM('pending','published','hidden','sold') NOT NULL DEFAULT 'pending',
 updated_at DATETIME NULL,FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB");
$pdo->exec("INSERT INTO users(id,username) VALUES (1,'seller'),(2,'buyer'),(3,'other')");
$pdo->exec("INSERT INTO products(id,user_id,title,price_clp,stock,status) VALUES
(10,1,'Articulo 1',1500,8,'published'),(11,1,'No cuantificado',1500,NULL,'published'),(12,3,'Otro negocio',100,2,'published')");
pos_install($pdo);orders_pro_install($pdo);core_inventory_install($pdo);
assert_db(pos_ready($pdo),'POS migration installs and is ready');
assert_db(orders_pro_ready($pdo),'Orders migration installs and is ready');
assert_db(core_inventory_audit_ready($pdo),'Core audit migration installs and is ready');

try { inventory_core_decrease($pdo,1,10,1);assert_db(false,'out-of-transaction blocked'); }
catch(LogicException) { assert_db(true,'transaction required for stock mutation'); }
$pdo->beginTransaction();
$after=inventory_core_decrease($pdo,1,10,1);
assert_db($after===7,'stock decremented inside transaction');
$pdo->rollBack();
assert_db(stock($pdo,10)===8,'rollback restores stock');

$sale=pos_record_sale($pdo,1,10,2,'cash','Venta de prueba');
assert_db($sale>0,'POS creates sale');
assert_db(stock($pdo,10)===6,'POS stock decremented once');
assert_db((int)$pdo->query('SELECT total_clp FROM pos_sales LIMIT 1')->fetchColumn()===3000,'POS total correct');
assert_db((int)$pdo->query('SELECT COUNT(*) FROM pos_stock_movements')->fetchColumn()===1,'POS movement ledger created');

try {pos_record_sale($pdo,3,10,1,'cash');assert_db(false,'cross-seller POS denied');}
catch(RuntimeException){assert_db(stock($pdo,10)===6,'seller ownership enforced');}
try {pos_record_sale($pdo,1,11,1,'cash');assert_db(false,'unknown stock denied');}
catch(RuntimeException){assert_db(true,'unknown stock is not sellable');}
try {pos_record_sale($pdo,1,10,7,'cash');assert_db(false,'overselling denied');}
catch(RuntimeException){assert_db(stock($pdo,10)===6,'oversell rolls back');}

$order=orders_pro_create($pdo,2,10,3,'Reserva de prueba');
assert_db(stock($pdo,10)===6,'order creation does not reserve stock');
try{orders_pro_transition($pdo,2,$order,'confirmed');assert_db(false,'buyer cannot confirm');}
catch(RuntimeException){assert_db(stock($pdo,10)===6,'only seller can confirm');}
orders_pro_transition($pdo,1,$order,'confirmed');
assert_db(stock($pdo,10)===3,'order confirmation decrements stock exactly once');
try{orders_pro_transition($pdo,1,$order,'confirmed');assert_db(false,'duplicate confirmation denied');}
catch(RuntimeException){assert_db(stock($pdo,10)===3,'duplicate confirmation does not decrement again');}
orders_pro_transition($pdo,1,$order,'completed');
assert_db((string)$pdo->query('SELECT status FROM pro_orders WHERE id='.$order)->fetchColumn()==='completed','order completion recorded');
$cancel=orders_pro_create($pdo,2,10,2,'Cancelar');
orders_pro_transition($pdo,2,$cancel,'cancelled');
assert_db(stock($pdo,10)===3,'cancelled request leaves stock unchanged');
try{orders_pro_create($pdo,1,10,1,'own product');assert_db(false,'self-order denied');}
catch(RuntimeException){assert_db(true,'buyer cannot order own product');}
assert_db((int)$pdo->query("SELECT COUNT(*) FROM pro_orders WHERE status='cancelled'")->fetchColumn()===1,'cancel status persisted');
echo "MySQL/MariaDB integration complete\n";

// Content Hub and Page Builder exercise the same MariaDB service.
require_once __DIR__.'/../app/content_hub.php';
hub_install($pdo);
hub_install($pdo); // idempotent activation
assert_db(hub_ready($pdo),'Content Hub migration installs idempotently');
$pdo->prepare('INSERT INTO hub_categories(name,slug,description) VALUES(?,?,?)')->execute(['Guías','guias','Contenido comercial']);
$category=(int)$pdo->lastInsertId();
$insert=$pdo->prepare('INSERT INTO hub_articles(category_id,author_id,title,slug,summary,body,status,featured,published_at) VALUES(?,?,?,?,?,?,?,?,?)');
$insert->execute([$category,1,'Publicación visible','publicacion-visible','Resumen','Contenido de prueba','published',1,date('Y-m-d H:i:s')]);
$published=(int)$pdo->lastInsertId();
$insert->execute([$category,1,'Borrador privado','borrador-privado','Resumen privado','No mostrar','draft',0,null]);
$articleList=$pdo->query("SELECT id FROM hub_articles WHERE status='published' AND published_at<=NOW()")->fetchAll(PDO::FETCH_COLUMN);
assert_db(count($articleList)===1 && (int)$articleList[0]===$published,'only published articles queryable');
$pdo->prepare('UPDATE hub_articles SET status=? WHERE id=?')->execute(['hidden',$published]);
assert_db((int)$pdo->query("SELECT COUNT(*) FROM hub_articles WHERE status='published'")->fetchColumn()===0,'hidden article excluded');
$blocks=hub_normalize_blocks('[{"type":"heading","title":"Nuestra galería"},{"type":"text","body":"Bienvenidos"}]');
$pdo->prepare('INSERT INTO hub_pages(author_id,title,slug,blocks_json,status,show_navigation,published_at) VALUES(?,?,?,?,?,?,?)')->execute([1,'Página publicada','sobre-nosotros',$blocks,'published',1,date('Y-m-d H:i:s')]);
$pdo->prepare('INSERT INTO hub_pages(author_id,title,slug,blocks_json,status,show_navigation,published_at) VALUES(?,?,?,?,?,?,?)')->execute([1,'Página privada','privada','[]','draft',1,null]);
assert_db((int)$pdo->query("SELECT COUNT(*) FROM hub_pages WHERE status='published' AND show_navigation=1 AND published_at<=NOW()")->fetchColumn()===1,'public navigation excludes draft pages');
assert_db(str_contains(hub_render_blocks($blocks,'http://localhost/public'),'Nuestra galería'),'stored blocks render from DB');
echo "Content Hub and Page Builder MariaDB checks complete\n";
