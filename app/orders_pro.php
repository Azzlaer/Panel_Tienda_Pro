<?php
declare(strict_types=1);
function orders_pro_ready(PDO $pdo): bool {
 try { foreach(['pro_orders','pro_order_items'] as $table){$q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');$q->execute([$table]);if(!(int)$q->fetchColumn())return false;}return true;}catch(Throwable){return false;}
}
function orders_pro_install(PDO $pdo): void {
 $sql=(string)file_get_contents(__DIR__.'/../database/migrations/2026_11_02_000009_orders_pro.sql');
 foreach(preg_split('/;\s*(?:\r?\n|$)/',$sql) as $statement){$statement=trim(preg_replace('/^\s*--[^\n]*\n/m','',$statement));if($statement!=='')$pdo->exec($statement);}
}
function orders_pro_create(PDO $pdo,int $buyer,int $productId,int $qty,string $note): int {
 if($buyer<1||$productId<1||$qty<1||$qty>1000)throw new InvalidArgumentException('Cantidad inválida.');
 $note=mb_substr(trim($note),0,1000);
 $pdo->beginTransaction();
 try{
  $q=$pdo->prepare("SELECT id,user_id,title,price_clp,stock FROM products WHERE id=? AND status='published' FOR UPDATE");$q->execute([$productId]);$p=$q->fetch(PDO::FETCH_ASSOC);
  if(!$p||(int)$p['user_id']===$buyer)throw new RuntimeException('Producto no disponible.');
  if($p['stock']===null||$qty>(int)$p['stock'])throw new RuntimeException('No hay stock verificable suficiente.');
  $price=(int)$p['price_clp'];if($qty>intdiv(PHP_INT_MAX,max(1,$price)))throw new RuntimeException('Total fuera de rango.');
  $total=$qty*$price;
  $pdo->prepare('INSERT INTO pro_orders(buyer_id,seller_id,total_clp,note) VALUES(?,?,?,?)')->execute([$buyer,(int)$p['user_id'],$total,$note]);
  $order=(int)$pdo->lastInsertId();
  $pdo->prepare('INSERT INTO pro_order_items(order_id,product_id,title_snapshot,quantity,price_clp,subtotal_clp) VALUES(?,?,?,?,?,?)')->execute([$order,$productId,$p['title'],$qty,$price,$total]);
  // Se descuenta al CONFIRMAR por el vendedor, no al solicitar.
  $pdo->commit();return $order;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function orders_pro_transition(PDO $pdo,int $actor,int $orderId,string $to): void {
 if($actor<1||$orderId<1)throw new InvalidArgumentException('Identificador inválido.');
 $pdo->beginTransaction();
 try{
  $q=$pdo->prepare('SELECT * FROM pro_orders WHERE id=? FOR UPDATE');$q->execute([$orderId]);$o=$q->fetch(PDO::FETCH_ASSOC);
  if(!$o)throw new RuntimeException('Pedido inexistente.');
  $seller=(int)$o['seller_id']===$actor;$buyer=(int)$o['buyer_id']===$actor;
  $from=$o['status'];
  $allowed=($seller&&$from==='requested'&&in_array($to,['confirmed','rejected'],true))
   ||($seller&&$from==='confirmed'&&$to==='completed')
   ||($buyer&&$from==='requested'&&$to==='cancelled');
  if(!$allowed)throw new RuntimeException('Transición no permitida.');
  if($to==='confirmed'){
   $items=$pdo->prepare('SELECT product_id,quantity FROM pro_order_items WHERE order_id=?');$items->execute([$orderId]);
   foreach($items->fetchAll(PDO::FETCH_ASSOC) as $i){
    $update=$pdo->prepare("UPDATE products SET stock=stock-?,updated_at=NOW() WHERE id=? AND user_id=? AND status='published' AND stock IS NOT NULL AND stock>=?");
    $update->execute([(int)$i['quantity'],(int)$i['product_id'],$actor,(int)$i['quantity']]);
    if($update->rowCount()!==1)throw new RuntimeException('Stock insuficiente al confirmar; pedido sin modificar.');
   }
  }
  $pdo->prepare('UPDATE pro_orders SET status=?,updated_at=NOW() WHERE id=?')->execute([$to,$orderId]);
  $pdo->commit();
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
