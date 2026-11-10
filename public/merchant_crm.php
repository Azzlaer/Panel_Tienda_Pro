<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';require_login();require_once __DIR__.'/../app/merchant_center.php';
$pdo=db();if(!merchant_ready($pdo)){http_response_code(503);exit('Merchant Center no activado.');}
$actor=(int)user()['id'];
try{$ctx=merchant_context($pdo,$actor,$_POST['owner']??$_GET['owner']??$actor);$owner=$ctx['owner'];$role=$ctx['role'];}
catch(Throwable){http_response_code(403);exit('Acceso denegado.');}
$canEdit=$role!=='analyst';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 if(!$canEdit){http_response_code(403);exit('Permisos insuficientes.');}
 try {
  $action=(string)($_POST['action']??'');
  if($action==='save')merchant_contact_save($pdo,$actor,$owner,(int)($_POST['id']??0),$_POST);
  elseif($action==='note')merchant_note_add($pdo,$actor,$owner,(int)($_POST['contact_id']??0),(string)($_POST['note']??''));
  elseif($action==='delete'){
   $q=$pdo->prepare('DELETE FROM merchant_contacts WHERE id=? AND owner_id=?');$q->execute([(int)($_POST['id']??0),$owner]);
  }else throw new InvalidArgumentException('Acción inválida.');
  flash('success','CRM actualizado.');
 }catch(Throwable $e){flash('error',$e->getMessage());}
 redirect('merchant_crm.php?owner='.$owner);
}
$q=$pdo->prepare('SELECT * FROM merchant_contacts WHERE owner_id=? ORDER BY id DESC LIMIT 200');$q->execute([$owner]);$contacts=$q->fetchAll();
$edit=(int)($_GET['edit']??0);$editing=null;
if($edit){$q=$pdo->prepare('SELECT * FROM merchant_contacts WHERE owner_id=? AND id=?');$q->execute([$owner,$edit]);$editing=$q->fetch()?:null;}
$q=$pdo->prepare('SELECT n.*,c.name contact_name,u.username author FROM merchant_notes n JOIN merchant_contacts c ON c.id=n.contact_id AND c.owner_id=n.owner_id JOIN users u ON u.id=n.author_id WHERE n.owner_id=? ORDER BY n.id DESC LIMIT 50');$q->execute([$owner]);$notes=$q->fetchAll();
$pageTitle='CRM de comerciantes';require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/merchant-center.css')?>">
<section class="merchant"><header class="merchant-hero"><span>MERCHANT CENTER · CRM</span><h1>Relaciones comerciales</h1><p>Contactos internos y notas. Solo los miembros autorizados de este comercio pueden consultarlos.</p></header>
<div class="merchant-links"><a href="<?=url('merchant_center.php?owner='.$owner)?>">← Dashboard</a><a href="<?=url('merchant_tasks.php?owner='.$owner)?>">Tareas</a></div>
<?php if($canEdit):?><div class="merchant-panel"><h2><?=$editing?'Editar contacto':'Nuevo contacto'?></h2><form class="merchant-form" method="post"><?=csrf_field()?><input type="hidden" name="owner" value="<?=$owner?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=(int)($editing['id']??0)?>">
<label>Nombre<input name="name" required maxlength="120" value="<?=e($editing['name']??'')?>"></label><label>Correo<input name="email" type="email" maxlength="190" value="<?=e($editing['email']??'')?>"></label><label>Teléfono<input name="phone" maxlength="40" value="<?=e($editing['phone']??'')?>"></label><label>Organización<input name="company" maxlength="120" value="<?=e($editing['company']??'')?>"></label><label>Estado<select name="stage"><?php foreach(['lead'=>'Prospecto','contacted'=>'Contactado','customer'=>'Cliente','archived'=>'Archivado'] as $k=>$v):?><option value="<?=$k?>" <?=($editing['stage']??'lead')===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></label><button>Guardar contacto</button></form></div><?php endif;?>
<div class="merchant-panel"><h2>Contactos (<?=count($contacts)?>)</h2><div class="merchant-table"><table><thead><tr><th>Nombre</th><th>Empresa</th><th>Correo</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach($contacts as $c):?><tr><td><?=e($c['name'])?></td><td><?=e($c['company']??'')?></td><td><?=e($c['email']??'')?></td><td><?=e($c['phone']??'')?></td><td><?=e($c['stage'])?></td><td><?php if($canEdit):?><a href="<?=url('merchant_crm.php?owner='.$owner.'&edit='.(int)$c['id'])?>">Editar</a><form method="post" onsubmit="return confirm('¿Eliminar contacto y notas?')"><?=csrf_field()?><input type="hidden" name="owner" value="<?=$owner?>"><input type="hidden" name="action" value="delete"><button name="id" value="<?=(int)$c['id']?>">Eliminar</button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></div></div>
<?php if($canEdit):?><div class="merchant-panel"><h2>Nota sobre contacto</h2><form class="merchant-form" method="post"><?=csrf_field()?><input type="hidden" name="owner" value="<?=$owner?>"><input type="hidden" name="action" value="note"><label>Contacto<select name="contact_id" required><?php foreach($contacts as $c):?><option value="<?=(int)$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select></label><label>Nota interna<textarea name="note" maxlength="2000" required rows="3"></textarea></label><button>Agregar nota</button></form></div><?php endif;?>
<div class="merchant-panel"><h2>Notas recientes</h2><?php foreach($notes as $n):?><div class="merchant-item"><strong><?=e($n['contact_name'])?> · <?=e($n['author'])?></strong><small><?=e($n['created_at'])?></small><p><?=nl2br(e($n['note']))?></p></div><?php endforeach;?></div>
</section>
<?php require __DIR__.'/../partials/footer.php';
