<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/merchant_center.php';
$dsn=getenv('TEST_DB_DSN')?:'mysql:host=127.0.0.1;port=3306;dbname=commerce_merchant_test;charset=utf8mb4';
$pdo=new PDO($dsn,getenv('TEST_DB_USER')?:'root',getenv('TEST_DB_PASS')?:'root',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
function merchant_assert(bool $pass,string $message): void {
 echo ($pass?'PASS ':'FAIL ').$message.PHP_EOL;
 if(!$pass)throw new RuntimeException($message);
}
$pdo->exec("CREATE TABLE users (id BIGINT UNSIGNED PRIMARY KEY,username VARCHAR(40) NOT NULL,status ENUM('active','pending','blocked') NOT NULL) ENGINE=InnoDB");
$pdo->exec("INSERT INTO users VALUES(1,'merchantA','active'),(2,'employee','active'),(3,'merchantB','active'),(4,'analyst','active'),(5,'blocked','blocked')");
merchant_install($pdo);merchant_install($pdo);
merchant_assert(merchant_ready($pdo),'migration installs and is idempotent');
merchant_assert(merchant_role($pdo,2,1)===null,'uninvited user has no access');
try{merchant_require($pdo,2,1,'crm');merchant_assert(false,'uninvited denied');}catch(RuntimeException){merchant_assert(true,'uninvited forbidden');}
merchant_staff_set($pdo,1,1,2,'support',true);
merchant_assert(merchant_role($pdo,2,1)==='support','staff membership accepted');
merchant_assert(merchant_require($pdo,2,1,'crm')==='support','support can edit CRM');
try{merchant_require($pdo,2,1,'team');merchant_assert(false,'support not allowed to invite');}catch(RuntimeException){merchant_assert(true,'support team management denied');}
try{merchant_require($pdo,3,1,'view');merchant_assert(false,'cross-owner access');}catch(RuntimeException){merchant_assert(true,'cross-owner access denied');}
merchant_staff_set($pdo,3,3,4,'analyst',true);
merchant_assert(merchant_require($pdo,4,3,'view')==='analyst','analyst can read own merchant');
try{merchant_require($pdo,4,3,'tasks');merchant_assert(false,'analyst cannot change tasks');}catch(RuntimeException){merchant_assert(true,'analyst readonly');}
try{merchant_staff_set($pdo,2,1,4,'support',true);merchant_assert(false,'support cannot invite');}catch(RuntimeException){merchant_assert(true,'support cannot add staff');}
try{merchant_staff_set($pdo,1,1,5,'analyst',true);merchant_assert(false,'blocked account allowed');}catch(RuntimeException){merchant_assert(true,'blocked account not allowed');}
$cid=merchant_contact_save($pdo,2,1,0,['name'=>'Cliente A','email'=>'a@example.cl','phone'=>'56912345678','company'=>'Empresa A','stage'=>'lead']);
merchant_assert($cid>0,'staff creates merchant-specific contact');
$bid=merchant_contact_save($pdo,3,3,0,['name'=>'Cliente B','email'=>'b@example.cl']);
merchant_assert($bid>0,'separate merchant creates contact');
try{merchant_contact_save($pdo,2,1,$bid,['name'=>'Robado']);merchant_assert(false,'cross merchant edit');}catch(RuntimeException){merchant_assert(true,'foreign contact update denied');}
merchant_assert((string)$pdo->query('SELECT name FROM merchant_contacts WHERE id='.$bid)->fetchColumn()==='Cliente B','foreign contact unchanged');
merchant_note_add($pdo,2,1,$cid,'Llamar mañana');
try{merchant_note_add($pdo,2,1,$bid,'Malicious');merchant_assert(false,'foreign note allowed');}catch(RuntimeException){merchant_assert(true,'cross merchant note denied');}
merchant_assert((int)$pdo->query('SELECT COUNT(*) FROM merchant_notes')->fetchColumn()===1,'only authorized note recorded');
$tid=merchant_task_save($pdo,2,1,'Llamar cliente',2,$cid,'2026-12-01');
merchant_assert($tid>0,'staff creates own task');
try{merchant_task_save($pdo,2,1,'Assign foreign',4,$cid,null);merchant_assert(false,'foreign assignee');}catch(RuntimeException){merchant_assert(true,'foreign assignee denied');}
try{merchant_task_save($pdo,2,1,'Foreign contact',null,$bid,null);merchant_assert(false,'foreign contact');}catch(RuntimeException){merchant_assert(true,'foreign task contact denied');}
try{merchant_task_transition($pdo,3,3,$tid,'done');merchant_assert(false,'cross-owner task transition');}catch(RuntimeException){merchant_assert(true,'cross-owner task transition denied');}
merchant_task_transition($pdo,2,1,$tid,'done');
merchant_assert((string)$pdo->query('SELECT status FROM merchant_tasks WHERE id='.$tid)->fetchColumn()==='done','owner-scoped task transition');
merchant_staff_set($pdo,1,1,2,'support',false);
merchant_assert(merchant_role($pdo,2,1)===null,'revocation effective immediately');
try{merchant_require($pdo,2,1,'crm');merchant_assert(false,'revoked still authorized');}catch(RuntimeException){merchant_assert(true,'revoked employee denied');}
echo "Merchant Center MariaDB authorization checks completed\n";
