<?php
require_once __DIR__.'/../app/bootstrap.php';require_login();require_once __DIR__.'/../app/commerce_suite.php';
if(!suite_ready()){http_response_code(503);exit('Commerce Suite debe activarse desde el panel administrativo.');}
$uid=(int)user()['id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'');
 if($action==='create'){
  $pid=(int)($_POST['product_id']??0);$qty=filter_var($_POST['quantity']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000]]);
  $q=db()->prepare("SELECT id,user_id,stock FROM products WHERE id=? AND status='published'");$q->execute([$pid]);$p=$q->fetch();
  if(!$p||$p['user_id']==$uid||$qty===false||($p['stock']!==null&&$qty>(int)$p['stock'])){flash('error','Solicitud inválida o stock insuficiente.');redirect('suite_requests.php');}
  db()->prepare('INSERT INTO suite_requests(product_id,buyer_id,seller_id,quantity,note) VALUES(?,?,?,?,?)')->execute([$pid,$uid,(int)$p['user_id'],$qty,suite_post_string('note',1000)]);
  flash('success','Solicitud enviada. No se ha realizado ningún pago ni venta.');redirect('suite_requests.php');
 }
 if($action==='update'){
  $id=(int)($_POST['id']??0);$state=(string)($_POST['state']??'');
  $allowed=['accepted','rejected','completed'];if(!in_array($state,$allowed,true)) {http_response_code(400);exit('Estado inválido');}
  $q=db()->prepare('SELECT state FROM suite_requests WHERE id=? AND seller_id=?');$q->execute([$id,$uid]);$old=$q->fetchColumn();
  if(!$old||!in_array($old,['requested','accepted'],true)||($state==='completed'&&$old!=='accepted')){http_response_code(403);exit('Transición inválida');}
  db()->prepare('UPDATE suite_requests SET state=?,updated_at=NOW() WHERE id=? AND seller_id=? AND state=?')->execute([$state,$id,$uid,$old]);
  flash('success','Solicitud actualizada.');redirect('suite_requests.php');
 }
 if($action==='cancel'){
  db()->prepare("UPDATE suite_requests SET state='cancelled',updated_at=NOW() WHERE id=? AND buyer_id=? AND state='requested'")->execute([(int)($_POST['id']??0),$uid]);
  redirect('suite_requests.php');
 }
 http_response_code(400);exit('Acción no reconocida');
}
$q=db()->prepare('SELECT r.*,p.title,u.username buyer_name FROM suite_requests r JOIN products p ON p.id=r.product_id JOIN users u ON u.id=r.buyer_id WHERE r.seller_id=? OR r.buyer_id=? ORDER BY r.created_at DESC LIMIT 150');$q->execute([$uid,$uid]);$rows=$q->fetchAll();
$pageTitle='Solicitudes de productos';require __DIR__.'/../partials/header.php';?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-suite.css')?>"><section class="suite"><header class="suite-hero"><span>COMMERCE SUITE · SOLICITUDES</span><h1>Solicitudes y reservas</h1><p>Coordinación entre comprador y vendedor; no hay pagos online ni descuentos automáticos de stock.</p></header>
<div class="suite-grid"><?php foreach($rows as $r):?><article class="suite-card"><h3><?=e($r['title'])?></h3><p>Cantidad: <?=(int)$r['quantity']?> · Estado: <strong><?=e($r['state'])?></strong></p><p><?=e($r['note'])?></p><small><?=e($r['created_at'])?></small>
<?php if((int)$r['seller_id']===$uid&&in_array($r['state'],['requested','accepted'],true)):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><select name="state"><?php if($r['state']==='requested'):?><option value="accepted">Aceptar</option><?php endif;?><option value="rejected">Rechazar</option><?php if($r['state']==='accepted'):?><option value="completed">Marcar finalizada</option><?php endif;?></select><button>Actualizar</button></form><?php elseif((int)$r['buyer_id']===$uid&&$r['state']==='requested'):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="cancel"><button name="id" value="<?=(int)$r['id']?>">Cancelar solicitud</button></form><?php endif;?></article><?php endforeach;?></div>
<?php if(!$rows):?><p>Todavía no hay solicitudes.</p><?php endif;?></section><?php require __DIR__.'/../partials/footer.php';?>
