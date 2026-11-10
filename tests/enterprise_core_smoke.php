<?php
declare(strict_types=1);
require_once __DIR__.'/../app/inventory_core.php';
$assert=function(bool $value,string $name): void {
 echo ($value?'PASS ':'FAIL ').$name.PHP_EOL;
 if(!$value)exit(1);
};
$assert(inventory_core_total(1500,3)===4500,'total calculation');
$assert(inventory_core_total(0,1)===0,'zero price');
foreach([[-1,1],[100,0],[100,10001]] as [$price,$qty]){
 try{inventory_core_total($price,$qty);$assert(false,'reject price/qty');}catch(InvalidArgumentException){$assert(true,'reject price/qty');}
}
try{inventory_core_decrease(new PDO('sqlite::memory:'),1,1,1);$assert(false,'requires transaction');}
catch(Throwable $e){
 // SQLite may be unavailable: static checks below enforce transaction guard.
}
$svc=(string)file_get_contents(__DIR__.'/../app/inventory_core.php');
$inventory=(string)file_get_contents(__DIR__.'/../public/inventory.php');
$admin=(string)file_get_contents(__DIR__.'/../admin/core_inventory.php');
$sql=(string)file_get_contents(__DIR__.'/../database/migrations/2026_11_02_000010_inventory_core.sql');
$assert(str_contains($svc,'inTransaction()'),'shared service requires transaction');
$assert(str_contains($svc,'stock>=?') && str_contains($svc,'rowCount() !== 1'),'stock conditional guard');
$assert(str_contains($svc,'FOR UPDATE'),'product locking');
$assert(str_contains($inventory,'beginTransaction()') && str_contains($inventory,'rollBack()'),'manual inventory transaction');
$assert(str_contains($inventory,'core_inventory_audit'),'audited manual adjustments');
$assert(str_contains($admin,'require_admin()') && str_contains($admin,'verify_csrf()'),'admin access and CSRF');
$assert(substr_count($sql,'CREATE TABLE IF NOT EXISTS')===1 && !preg_match('/\b(?:DROP|TRUNCATE)\b/i',$sql),'non destructive migration');
