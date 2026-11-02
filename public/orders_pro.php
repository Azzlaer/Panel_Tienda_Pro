<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';require_login();require_once __DIR__.'/../app/orders_pro.php';
if(!orders_pro_ready(db())){http_response_code(503);exit('Pedidos PRO aún no activado por administración.');}
$uid=(int)user()['id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'');
 try{
  if($action==='create'){$id=orders_pro_create(db(),$uid,(int)($_POST['product_id']??0),(int)($_POST['quantity']??0),(string)($_POST['note']??''));flash('success','Solicitud #'.$id.' registrada; sin pagos ni reserva de stock.');}
  elseif($action==='transition'){orders_pro_transition(db(),$uid,(int)($_POST['order_id']??0),(string)($_POST['to']??''));flash('success','Estado actualizado.');}
  else throw new RuntimeException('Acción inválida.');
 }catch(Throwable $e){flash('error',$e->getMessage());}
 redirect('orders_pro.php');
}
$q=db()->prepare("SELECT o.*,i.title_snapshot,i.quantity,i.price_clp,u.username seller_name FROM pro_orders o JOIN pro_order_items i ON i.order_id=o.id JOIN users u ON u.id=o.seller_id WHERE o.buyer_id=? OR o.seller_id=? ORDER BY o.id DESC LIMIT 150");
$q->execute([$uid,$uid]);$rows=$q->fetchAll();
$pageTitle='Pedidos PRO';require __DIR__.'/../partials/header.php';?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-orders.css')?>">
<section class="orders-wrap"><header class="orders-hero"><span>COMMERCE SUITE · PEDIDOS PRO</span><h1>Pedidos y coordinación comercial</h1><p>Pedidos entre comprador y vendedor. No se procesa dinero, boletas ni despacho automático.</p></header>
<section class="orders-box"><h2>Solicitar un producto</h2><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="create"><label>ID del producto publicado<input type="number" name="product_id" required min="1" value="<?=max(0,(int)($_GET['product_id']??0))?>"></label><label>Cantidad<input type="number" name="quantity" value="1" required min="1" max="1000"></label><label>Nota para el vendedor<textarea name="note" maxlength="1000"></textarea></label><button>Enviar solicitud sin pago</button></form></section>
<div class="orders-grid"><?php foreach($rows as $o):$seller=(int)$o['seller_id']===$uid;$buyer=(int)$o['buyer_id']===$uid;?>
<article class="orders-box"><small>Pedido #<?=(int)$o['id']?> · <?=e($o['created_at'])?></small><h2><?=e($o['title_snapshot'])?></h2><p><?=e($o['seller_name'])?> · <?= (int)$o['quantity']?> unidad(es) · <b>$<?=number_format((int)$o['total_clp'],0,',','.')?></b></p><p>Estado: <strong><?=e($o['status'])?></strong></p><p><?=nl2br(e($o['note']))?></p>
<?php if(($seller&&in_array($o['status'],['requested','confirmed'],true))||($buyer&&$o['status']==='requested')):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="transition"><input type="hidden" name="order_id" value="<?=(int)$o['id']?>"><label>Actualizar estado<select name="to">
<?php if($seller&&$o['status']==='requested'):?><option value="confirmed">Confirmar y descontar stock</option><option value="rejected">Rechazar</option><?php elseif($seller&&$o['status']==='confirmed'):?><option value="completed">Marcar finalizado</option><?php else:?><option value="cancelled">Cancelar solicitud</option><?php endif;?></select></label><button>Actualizar</button></form><?php endif;?></article>
<?php endforeach;?></div><?php if(!$rows):?><p>No hay pedidos todavía.</p><?php endif;?></section>
<?php require __DIR__.'/../partials/footer.php';
