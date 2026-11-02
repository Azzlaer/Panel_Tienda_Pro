<?php
declare(strict_types=1);
// Migración inicial explícita en AdminCP; los visitantes nunca ejecutan DDL.
function suite_install(): void {
 $sql=file_get_contents(__DIR__.'/../database/migrations/2026_10_30_000007_commerce_suite.sql');
 foreach(array_filter(array_map('trim',explode(';',(string)$sql))) as $statement){
  $statement=preg_replace('/^--[^\n]*\n/m','',$statement);
  if(trim($statement)!=='')db()->exec($statement);
 }
 db()->prepare('INSERT IGNORE INTO schema_migrations(migration) VALUES(?)')->execute(['2026_10_30_000007_commerce_suite']);
}
function suite_ready(): bool {
 try {return (bool) db()->query("SHOW TABLES LIKE 'suite_inquiries'")->fetchColumn() && (bool) db()->query("SHOW TABLES LIKE 'suite_requests'")->fetchColumn() && (bool) db()->query("SHOW TABLES LIKE 'suite_campaigns'")->fetchColumn();}
 catch(Throwable){return false;}
}
function suite_post_string(string $key,int $limit): string {return mb_substr(trim((string)($_POST[$key]??'')),0,$limit);}
function suite_owned_product(int $id,int $seller): ?array {
 $q=db()->prepare('SELECT * FROM products WHERE id=? AND user_id=? LIMIT 1');$q->execute([$id,$seller]);return $q->fetch()?:null;
}
