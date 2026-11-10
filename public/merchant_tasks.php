<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';require_login();require_once __DIR__.'/../app/merchant_center.php';
$pdo=db();if(!merchant_ready($pdo)){http_response_code(503);exit('Merchant Center no activado.');}
$actor=(int)user()['id'];
try{$ctx=merchant_context($pdo,$actor,$_POST['owner']??$_GET['owner']??$actor);$owner=$ctx['owner'];$role=$ctx['role'];}catch(Throwable){http_response_code(403);exit('Acceso denegado.');}
$canEdit=$role!=='analyst';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();if(!$canEdit){http_response_code(403);exit('Sin permisos.');}
 try {
  $action=(string)($_POST['action']??'');
  if($action==='new'){
   $assigned=filter_var($_POST['assigned_to']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:null;
   $contact=filter_var($_POST['contact_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:null;
   merchant_task_save($pdo,$actor,$owner,(string)($_POST['title']??''),$assigned,$contact,trim((string)($_POST['due_date']??''))?:null);
  }elseif($action==='status')merchant_task_transition($pdo,$actor,$owner,(int)($_POST['id']??0),(string)($_POST['status']??''));
  elseif($action==='delete'){
   $q=$pdo->prepare('DELETE FROM merchant_tasks WHERE id=? AND owner_id=?');$q->execute([(int)($_POST['id']??0),$owner]);
  }else throw new InvalidArgumentException('Acción inválida.');
  flash('success','Tarea actualizada.');
 }catch(Throwable $e){flash('error',$e->getMessage());}
 redirect('merchant_tasks.php?owner='.$owner);
}
$q=$pdo->prepare('SELECT t.*,u.username assignee_name,c.name contact_name FROM merchant_tasks t LEFT JOIN users u ON u.id=t.assigned_to LEFT JOIN merchant_contacts c ON c.id=t.contact_id AND c.owner_id=t.owner_id WHERE t.owner_id=? ORDER BY t.status ASC,t.due_date ASC,t.id DESC LIMIT 200');$q->execute([$owner]);$tasks=$q->fetchAll();
$q=$pdo->prepare('SELECT id,name FROM merchant_contacts WHERE owner_id=? ORDER BY name LIMIT 200');$q->execute([$owner]);$contacts=$q->fetchAll();
$q=$pdo->prepare("SELECT u.id,u.username FROM users u WHERE u.id=? OR (u.status='active' AND EXISTS(SELECT 1 FROM merchant_staff s WHERE s.owner_id=? AND s.member_id=u.id AND s.active=1)) ORDER BY u.username");$q->execute([$owner,$owner]);$staff=$q->fetchAll();
$pageTitle='Tareas del comercio';require __DIR__.'/../partials/header.php';?>
<link rel="stylesheet" href="<?=url('assets/css/merchant-center.css')?>">
<section class="merchant"><header class="merchant-hero"><span>MERCHANT CENTER · WORKFLOW</span><h1>Seguimiento de tareas</h1><p>Asigna actividades, registra vencimientos y organiza el trabajo del comercio.</p></header><div class="merchant-links"><a href="<?=url('merchant_center.php?owner='.$owner)?>">← Dashboard</a><a href="<?=url('merchant_crm.php?owner='.$owner)?>">Contactos</a></div>
<?php if($canEdit):?><div class="merchant-panel"><h2>Nueva tarea</h2><form class="merchant-form" method="post"><?=csrf_field()?><input type="hidden" name="action" value="new"><input type="hidden" name="owner" value="<?=$owner?>"><label>Actividad<input name="title" required minlength="3" maxlength="180"></label><label>Asignar a<select name="assigned_to"><option value="">Sin asignar</option><?php foreach($staff as $s):?><option value="<?=(int)$s['id']?>"><?=e($s['username'])?></option><?php endforeach;?></select></label><label>Contacto relacionado<select name="contact_id"><option value="">Ninguno</option><?php foreach($contacts as $c):?><option value="<?=(int)$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select></label><label>Fecha límite<input type="date" name="due_date"></label><button>Agregar tarea</button></form></div><?php endif;?>
<div class="merchant-panel"><h2>Tablero de tareas</h2><div class="merchant-task-grid"><?php foreach(['todo'=>'Pendientes','doing'=>'En curso','done'=>'Terminadas'] as $status=>$label):?><div class="merchant-task-column"><h3><?=e($label)?></h3><?php foreach($tasks as $t):if($t['status']!==$status)continue;?><article class="merchant-item"><strong><?=e($t['title'])?></strong><small><?=e($t['assignee_name']??'Sin asignar')?> · <?=e($t['due_date']??'Sin fecha')?></small><?php if($t['contact_name']):?><p><?=e($t['contact_name'])?></p><?php endif;?><?php if($canEdit):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="status"><input type="hidden" name="owner" value="<?=$owner?>"><input type="hidden" name="id" value="<?=(int)$t['id']?>"><select name="status"><?php foreach(['todo'=>'Pendiente','doing'=>'En curso','done'=>'Terminada'] as $k=>$v):?><option value="<?=$k?>" <?=$k===$t['status']?'selected':''?>><?=$v?></option><?php endforeach;?></select><button>Actualizar</button></form><form method="post" onsubmit="return confirm('¿Eliminar tarea?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="owner" value="<?=$owner?>"><button name="id" value="<?=(int)$t['id']?>">Eliminar</button></form><?php endif;?></article><?php endforeach;?></div><?php endforeach;?></div></div></section>
<?php require __DIR__.'/../partials/footer.php';
