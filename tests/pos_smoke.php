<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$logic=(string)file_get_contents($root.'/app/pos.php');
$page=(string)file_get_contents($root.'/public/pos.php');
$admin=(string)file_get_contents($root.'/admin/pos.php');
$sql=(string)file_get_contents($root.'/database/migrations/2026_11_02_000008_pos.sql');
$nav=(string)file_get_contents($root.'/admin/partials/header.php');
$checks=[
 'record sale transaction'=>str_contains($logic,'beginTransaction()') && str_contains($logic,'rollBack()') && str_contains($logic,'commit()'),
 'ownership enforced'=>str_contains($logic,'user_id=?') && str_contains($logic,'FOR UPDATE'),
 'no negative stock'=>str_contains($logic,'$stock<$quantity'),
 'unknown stock not sellable'=>str_contains($logic,'$p[\'stock\']===null'),
 'ledger recorded'=>str_contains($logic,'pos_stock_movements'),
 'three additive tables'=>substr_count($sql,'CREATE TABLE IF NOT EXISTS')===3,
 'seller auth'=>str_contains($page,'require_login()'),
 'seller POST CSRF'=>str_contains($page,'verify_csrf()'),
 'admin auth'=>str_contains($admin,'require_admin()'),
 'admin POST CSRF'=>str_contains($admin,'verify_csrf()'),
 'module in navigation'=>str_contains($nav,"'pos' =>"),
];
$failed=0;foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)$failed++;}
exit($failed?1:0);
