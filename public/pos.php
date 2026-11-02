<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';require_login();require_once __DIR__.'/../app/pos.php';
$uid=(int)user()['id'];
if(!pos_ready(db())){http_response_code(503);exit('El módulo POS requiere activación desde admin/pos.php.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try {
  $id=pos_record_sale(db(),$uid,(int)($_POST['product_id']??0),(int)($_POST['quantity']??0),(string)($_POST['payment_method']??''),(string)($_POST['notes']??''));
  flash('success','Venta presencial #'.$id.' registrada. Stock actualizado.');
 }catch(Throwable $e){flash('error',$e->getMessage());}
 redirect('pos.php');
}
$q=db()->prepare("SELECT id,title,price_clp,stock FROM products WHERE user_id=? AND status='published' ORDER BY title LIMIT 300");$q->execute([$uid]);$products=$q->fetchAll();
$q=db()->prepare("SELECT s.id,s.total_clp,s.payment_method,s.created_at,i.product_title,i.quantity FROM pos_sales s JOIN pos_sale_items i ON i.sale_id=s.id WHERE s.seller_id=? ORDER BY s.id DESC LIMIT 30");$q->execute([$uid]);$sales=$q->fetchAll();
$q=db()->prepare("SELECT COUNT(*) sales,COALESCE(SUM(total_clp),0) total FROM pos_sales WHERE seller_id=? AND created_at>=CURDATE()");$q->execute([$uid]);$today=$q->fetch();
$pageTitle='Punto de Venta PRO';require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-pos.css')?>">
<section class="pos-wrap"><header class="pos-hero"><span>COMMERCE SUITE · POS</span><h1>Punto de Venta</h1><p>Registro de ventas presenciales finalizadas. Este módulo no recibe pagos ni emite documentos tributarios.</p></header>
<div class="pos-metrics"><div><b><?=(int)$today['sales']?></b><small>Ventas de hoy</small></div><div><b>$<?=number_format((int)$today['total'],0,',','.')?></b><small>Total registrado hoy (CLP)</small></div><a href="<?=url('inventory.php')?>">📦 Gestionar inventario</a></div>
<div class="pos-grid"><section class="pos-card"><h2>Registrar venta presencial</h2><form method="post"><?=csrf_field()?><label>Producto publicado<select name="product_id" required><option value="">Selecciona un artículo</option><?php foreach($products as $p):?><option value="<?=(int)$p['id']?>" <?=($p['stock']===null||(int)$p['stock']<1)?'disabled':''?>><?=e($p['title'])?> · $<?=number_format((int)$p['price_clp'],0,',','.')?> · Stock: <?=($p['stock']===null?'sin especificar':(int)$p['stock'])?></option><?php endforeach;?></select></label><label>Cantidad<input name="quantity" type="number" required min="1" max="10000" value="1"></label><label>Medio recibido fuera de plataforma<select name="payment_method"><option value="cash">Efectivo</option><option value="card_external">Tarjeta (terminal externa)</option><option value="transfer">Transferencia</option><option value="other">Otro</option></select></label><label>Nota interna<textarea name="notes" maxlength="500" rows="2"></textarea></label><button type="submit">Registrar venta y descontar stock</button></form></section>
<section class="pos-card"><h2>Últimas ventas</h2><?php if(!$sales):?><p>No hay ventas presenciales registradas.</p><?php endif;?><div class="pos-sales"><?php foreach($sales as $s):?><article><b>#<?=(int)$s['id']?> · <?=e($s['product_title'])?></b><small><?=(int)$s['quantity']?> unidad(es) · <?=e($s['payment_method'])?> · <?=e($s['created_at'])?></small><strong>$<?=number_format((int)$s['total_clp'],0,',','.')?></strong></article><?php endforeach;?></div></section></div></section>
<?php require __DIR__.'/../partials/footer.php';
