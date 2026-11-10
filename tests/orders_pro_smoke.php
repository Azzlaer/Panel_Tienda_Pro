<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$service=(string)file_get_contents($root.'/app/orders_pro.php');
$web=(string)file_get_contents($root.'/public/orders_pro.php');
$admin=(string)file_get_contents($root.'/admin/orders_pro.php');
$sql=(string)file_get_contents($root.'/database/migrations/2026_11_02_000009_orders_pro.sql');
$checks=[
 'two additive tables'=>substr_count($sql,'CREATE TABLE IF NOT EXISTS')===2,
 'transaction for order and transition'=>substr_count($service,'beginTransaction()')===2,
 'row lock during confirmation'=>str_contains($service,'FOR UPDATE'),
 'stock guarded'=>str_contains($service,'inventory_core_decrease('),
 'seller authorization'=>str_contains($service,'$seller&&$from'),
 'buyer authorization'=>str_contains($service,'$buyer&&$from'),
 'rollback on error'=>substr_count($service,'rollBack()')===2,
 'buyer authentication'=>str_contains($web,'require_login()'),
 'post CSRF'=>str_contains($web,'verify_csrf()'),
 'admin permissions'=>str_contains($admin,'require_admin()'),
 'admin migration CSRF'=>str_contains($admin,'verify_csrf()')
];
$fail=0;foreach($checks as $key=>$ok){echo ($ok?'PASS ':'FAIL ').$key.PHP_EOL;if(!$ok)$fail++;}
exit($fail?1:0);
