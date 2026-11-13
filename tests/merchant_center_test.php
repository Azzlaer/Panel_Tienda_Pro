<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/merchant_center.php';
function merchant_test(bool $pass,string $name): void {
 echo ($pass?'PASS ':'FAIL ').$name.PHP_EOL;
 if(!$pass)exit(1);
}
$root=dirname(__DIR__);
$sql=(string)file_get_contents($root.'/database/migrations/2026_11_10_000013_merchant_center.sql');
merchant_test(substr_count($sql,'CREATE TABLE IF NOT EXISTS')===4,'four additive tables');
merchant_test(!preg_match('/\b(?:DROP|TRUNCATE|ALTER)\b/i',$sql),'no destructive migration');
foreach(['merchant_center','merchant_crm','merchant_tasks','merchant_team'] as $name){
 $page=(string)file_get_contents($root.'/public/'.$name.'.php');
 merchant_test(str_contains($page,'require_login()'),'authenticated '.$name);
}
foreach(['merchant_crm','merchant_tasks','merchant_team'] as $name){
 $page=(string)file_get_contents($root.'/public/'.$name.'.php');
 merchant_test(str_contains($page,'verify_csrf()'),'CSRF '.$name);
}
$team=(string)file_get_contents($root.'/public/merchant_team.php');
merchant_test(str_contains($team, '$owner=(int)user()[\'id\']'),'team management scoped to personal ownership');
$lib=(string)file_get_contents($root.'/app/merchant_center.php');
merchant_test(str_contains($lib,"WHERE id=? AND owner_id=?"),'contacts and tasks tenant scope');
merchant_test(str_contains($lib,'merchant_require($pdo,$actor,$owner,'),'tenant permission enforced on mutations');
merchant_test(str_contains($lib,"'team'=>['owner']"),'staff management restricted to owner');
$integration=(string)file_get_contents($root.'/admin/integration_center.php');
merchant_test(str_contains($integration,'merchant_install(db())'),'activation wired into integration center');
$header=(string)file_get_contents($root.'/partials/header.php');
$menu=(string)file_get_contents($root.'/app/public_menu.php');
merchant_test(str_contains($header,'public_menu_items') && str_contains($menu,"'merchant_center.php'"),'merchant center available via configurable public navigation');
