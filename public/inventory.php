<?php
require_once __DIR__.'/../app/bootstrap.php';require_login();
require_once __DIR__.'/../app/commerce_suite.php';
require_once __DIR__.'/../app/core_migrations.php';
$uid=(int)user()['id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$id=(int)($_POST['id']??0);$p=suite_owned_product($id,$uid);
 if(!$p){http_response_code(403);exit('Producto no autorizado.');}
 $rawStock=trim((string)($_POST['stock']??''));
 $stock=$rawStock===''?null:filter_var($rawStock,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>10000000]]);
 $rawPrice=trim((string)($_POST['price']??''));
 $price=$rawPrice===''?false:filter_var($rawPrice,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>999999999999]]);
 if($stock===false||$price===false){flash('error','Precio o stock inválido.');redirect('inventory.php');}
 $pdo=db();
 try {
  $pdo->beginTransaction();
  $locked=$pdo->prepare('SELECT stock,price_clp FROM products WHERE id=? AND user_id=? FOR UPDATE');
  $locked->execute([$id,$uid]);$before=$locked->fetch(PDO::FETCH_ASSOC);
  if(!$before)throw new RuntimeException('Producto no disponible.');
  $pdo->prepare('UPDATE products SET stock=?,price_clp=?,updated_at=NOW() WHERE id=? AND user_id=?')->execute([$stock,$price,$id,$uid]);
  if(core_inventory_audit_ready($pdo)){
   $pdo->prepare('INSERT INTO core_inventory_audit(seller_id,product_id,action_name,previous_stock,new_stock,previous_price_clp,new_price_clp) VALUES(?,?,?,?,?,?,?)')
   ->execute([$uid,$id,'manual_adjust',$before['stock'],$stock,$before['price_clp'],$price]);
  }
  $pdo->commit();
 } catch(Throwable $e) {
  if($pdo->inTransaction())$pdo->rollBack();
  flash('error','No se pudo actualizar inventario: '.$e->getMessage());redirect('inventory.php');
 }
 flash('success','Inventario actualizado.');redirect('inventory.php');
}
$q=db()->prepare('SELECT id,title,stock,price_clp,status FROM products WHERE user_id=? ORDER BY created_at DESC LIMIT 300');$q->execute([$uid]);$items=$q->fetchAll();
$pageTitle='Inventario PRO';require __DIR__.'/../partials/header.php';?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-suite.css')?>">
<section class="suite"><header class="suite-hero"><span>COMMERCE SUITE · VENDEDOR</span><h1>Inventario PRO</h1><p>Gestiona precios y existencias de hasta tus 300 artículos más recientes. Deja el stock vacío si no está especificado.</p></header>
<div class="suite-grid"><?php foreach($items as $p):?><form class="suite-card" method="post"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$p['id']?>"><h3><?=e($p['title'])?></h3><small><?=e($p['status'])?></small><label>Precio CLP<input required type="number" min="0" max="999999999999" name="price" value="<?=(int)$p['price_clp']?>"></label><label>Unidades disponibles<input type="number" min="0" max="10000000" name="stock" placeholder="Sin especificar" value="<?=($p['stock']===null?'':(int)$p['stock'])?>"></label><button>Guardar cambios</button></form><?php endforeach;?></div>
<?php if(!$items):?><p>No tienes productos todavía. <a href="<?=url('product_create.php')?>">Publica el primero</a>.</p><?php endif;?></section>
<?php require __DIR__.'/../partials/footer.php';?>
