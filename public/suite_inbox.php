<?php
require_once __DIR__.'/../app/bootstrap.php';require_login();require_once __DIR__.'/../app/commerce_suite.php';
if(!suite_ready()){http_response_code(503);exit('Commerce Suite debe activarse desde el panel administrativo.');}
$uid=(int)user()['id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'');
 if($action==='new'){
  $id=(int)($_POST['product_id']??0);$q=db()->prepare("SELECT id,user_id FROM products WHERE id=? AND status='published'");$q->execute([$id]);$p=$q->fetch();
  $subject=suite_post_string('subject',160);$body=suite_post_string('body',4000);
  if(!$p||$p['user_id']==$uid||mb_strlen($subject)<3||mb_strlen($body)<5){flash('error','Consulta inválida.');redirect('suite_inbox.php');}
  db()->prepare('INSERT INTO suite_inquiries(product_id,buyer_id,seller_id,subject,body) VALUES(?,?,?,?,?)')->execute([$id,$uid,(int)$p['user_id'],$subject,$body]);
  flash('success','Consulta enviada.');redirect('suite_inbox.php');
 }
 if($action==='reply'){
  $id=(int)($_POST['id']??0);$reply=suite_post_string('reply',4000);
  if(mb_strlen($reply)<2){flash('error','Respuesta demasiado breve.');redirect('suite_inbox.php');}
  db()->prepare("UPDATE suite_inquiries SET reply=?,state='answered',updated_at=NOW() WHERE id=? AND seller_id=? AND state <> 'closed'")->execute([$reply,$id,$uid]);
  redirect('suite_inbox.php');
 }
 if($action==='close'){
  db()->prepare("UPDATE suite_inquiries SET state='closed',updated_at=NOW() WHERE id=? AND (buyer_id=? OR seller_id=?)")->execute([(int)($_POST['id']??0),$uid,$uid]);redirect('suite_inbox.php');
 }
 http_response_code(400);exit('Acción inválida');
}
$q=db()->prepare('SELECT i.*,p.title FROM suite_inquiries i JOIN products p ON p.id=i.product_id WHERE i.seller_id=? OR i.buyer_id=? ORDER BY i.created_at DESC LIMIT 150');$q->execute([$uid,$uid]);$rows=$q->fetchAll();
$pageTitle='Mensajes comerciales';require __DIR__.'/../partials/header.php';?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-suite.css')?>"><section class="suite"><header class="suite-hero"><span>COMMERCE SUITE · MENSAJERÍA</span><h1>Consultas comerciales</h1><p>Mensajes privados asociados a productos.</p></header><div class="suite-grid"><?php foreach($rows as $r):?><article class="suite-card"><h3><?=e($r['subject'])?></h3><small><?=e($r['title'])?> · <?=e($r['state'])?></small><p><?=nl2br(e($r['body']))?></p><?php if($r['reply']):?><div class="suite-reply"><b>Respuesta</b><p><?=nl2br(e($r['reply']))?></p></div><?php endif;?><?php if($r['state']!=='closed'&&(int)$r['seller_id']===$uid):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="reply"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><textarea name="reply" maxlength="4000" required placeholder="Escribe una respuesta"><?=e($r['reply'])?></textarea><button>Responder</button></form><?php endif;?><?php if($r['state']!=='closed'):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="close"><button name="id" value="<?=(int)$r['id']?>">Cerrar consulta</button></form><?php endif;?></article><?php endforeach;?></div><?php if(!$rows):?><p>No tienes consultas aún.</p><?php endif;?></section><?php require __DIR__.'/../partials/footer.php';?>
