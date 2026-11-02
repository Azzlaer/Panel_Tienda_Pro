<?php
declare(strict_types=1);
/** POS offline register: records an already completed in-person sale; no payment processing. */
function pos_ready(PDO $pdo): bool {
 try { foreach (['pos_sales','pos_sale_items','pos_stock_movements'] as $t) {
  $q=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
  $q->execute([$t]);if(!(int)$q->fetchColumn())return false;
 } return true; } catch(Throwable){return false;}
}
function pos_install(PDO $pdo): void {
 $sql=(string)file_get_contents(__DIR__.'/../database/migrations/2026_11_02_000008_pos.sql');
 foreach(preg_split('/;\s*(?:\r?\n|$)/',$sql) as $statement){
  $statement=trim(preg_replace('/^\s*--[^\n]*\n/m','',$statement));
  if($statement!=='')$pdo->exec($statement);
 }
}
function pos_record_sale(PDO $pdo,int $seller,int $productId,int $quantity,string $method,string $notes=''): int {
 if($seller<1||$productId<1||$quantity<1||$quantity>10000)throw new InvalidArgumentException('Producto o cantidad inválida.');
 if(!in_array($method,['cash','card_external','transfer','other'],true))throw new InvalidArgumentException('Método de pago inválido.');
 $notes=mb_substr(trim($notes),0,500);
 $pdo->beginTransaction();
 try {
  $q=$pdo->prepare("SELECT id,title,price_clp,stock FROM products WHERE id=? AND user_id=? AND status='published' FOR UPDATE");
  $q->execute([$productId,$seller]);$p=$q->fetch(PDO::FETCH_ASSOC);
  if(!$p)throw new RuntimeException('No tienes acceso al producto publicado.');
  if($p['stock']===null)throw new RuntimeException('Define stock numérico antes de registrar una venta.');
  $stock=(int)$p['stock'];if($stock<$quantity)throw new RuntimeException('Stock insuficiente.');
  $price=(int)$p['price_clp'];
  if($price<0||$price>999999999999||$quantity>intdiv(PHP_INT_MAX,max(1,$price)))throw new RuntimeException('Total fuera de rango.');
  $total=$price*$quantity;
  $q=$pdo->prepare('INSERT INTO pos_sales(seller_id,total_clp,payment_method,notes) VALUES(?,?,?,?)');
  $q->execute([$seller,$total,$method,$notes]);$sale=(int)$pdo->lastInsertId();
  $pdo->prepare('INSERT INTO pos_sale_items(sale_id,product_id,product_title,quantity,unit_price_clp,subtotal_clp) VALUES(?,?,?,?,?,?)')->execute([$sale,$productId,$p['title'],$quantity,$price,$total]);
  $after=$stock-$quantity;
  $pdo->prepare('UPDATE products SET stock=?,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$after,$productId,$seller]);
  $pdo->prepare("INSERT INTO pos_stock_movements(seller_id,product_id,sale_id,movement_type,quantity_delta,stock_after) VALUES(?,?,?,'sale',?,?)")->execute([$seller,$productId,$sale,-$quantity,$after]);
  $pdo->commit();return $sale;
 } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
