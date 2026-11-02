<?php
require_once __DIR__.'/../app/bootstrap.php';require_admin();require_once __DIR__.'/../app/commerce_suite.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'');
 if($action==='install') {suite_install();audit_log('suite.install');flash('success','Commerce Suite activada.');redirect('../admin/commerce_suite.php');}
 if(!suite_ready()){http_response_code(409);exit('Instala la migración antes de continuar');}
 if($action==='add'){
  $title=suite_post_string('title',160);$subtitle=suite_post_string('subtitle',500);
  if(mb_strlen($title)<3){flash('error','Título inválido');redirect('../admin/commerce_suite.php');}
  $link=suite_post_string('link_url',600);if($link!==''&&!filter_var($link,FILTER_VALIDATE_URL)){flash('error','URL inválida');redirect('../admin/commerce_suite.php');}
  if($link!==''&&!in_array(strtolower((string)parse_url($link,PHP_URL_SCHEME)),['https','http'],true)){http_response_code(400);exit('Protocolo no permitido');}
  $start=suite_post_string('starts_at',16);$end=suite_post_string('ends_at',16);
  if($start&&$end&&$end<$start){flash('error','Fechas inconsistentes');redirect('../admin/commerce_suite.php');}
  $image=upload_image('image','suite_campaigns');
  db()->prepare('INSERT INTO suite_campaigns(title,subtitle,image,link_url,starts_at,ends_at,active) VALUES(?,?,?,?,?,?,?)')->execute([$title,$subtitle,$image,$link?:null,$start?str_replace('T',' ',$start).':00':null,$end?str_replace('T',' ',$end).':00':null,1]);
  audit_log('suite.campaign.create');flash('success','Campaña publicada');redirect('../admin/commerce_suite.php');
 }
 if($action==='toggle'){
  db()->prepare('UPDATE suite_campaigns SET active=IF(active=1,0,1) WHERE id=?')->execute([(int)($_POST['id']??0)]);redirect('../admin/commerce_suite.php');
 }
 if($action==='delete'){
  db()->prepare('DELETE FROM suite_campaigns WHERE id=?')->execute([(int)($_POST['id']??0)]);redirect('../admin/commerce_suite.php');
 }
 http_response_code(400);exit('Acción inválida');
}
$ready=suite_ready();$campaigns=$ready?db()->query('SELECT * FROM suite_campaigns ORDER BY id DESC LIMIT 100')->fetchAll():[];
$adminPage='suite';$adminTitle='Commerce Suite';$adminSubtitle='Activación, campañas y resumen comercial';require __DIR__.'/partials/header.php';?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-suite.css')?>"><section class="suite"><header class="suite-hero"><span>COMERCIO SOCIAL · ENTERPRISE</span><h1>Commerce Suite 6.0</h1><p>Inventario, consultas privadas, solicitudes de reserva y campañas de marketing.</p></header>
<?php if(!$ready):?><div class="suite-card"><h2>Activar módulos nuevos</h2><p>La operación crea tres tablas adicionales. No borra datos existentes. Haz antes un respaldo MySQL.</p><form method="post"><?=csrf_field()?><button name="action" value="install">Ejecutar migración</button></form></div>
<?php else:?>
<div class="suite-grid"><div class="suite-card"><h2>Consultas</h2><strong><?=(int)db()->query('SELECT COUNT(*) FROM suite_inquiries')->fetchColumn()?></strong></div><div class="suite-card"><h2>Solicitudes</h2><strong><?=(int)db()->query('SELECT COUNT(*) FROM suite_requests')->fetchColumn()?></strong></div><div class="suite-card"><h2>Campañas</h2><strong><?=count($campaigns)?></strong></div></div>
<div class="suite-grid"><div class="suite-card"><h2>Nueva campaña</h2><form method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="action" value="add"><label>Título<input required maxlength="160" name="title"></label><label>Descripción<input maxlength="500" name="subtitle"></label><label>Imagen<input type="file" accept="image/jpeg,image/png,image/webp,image/gif" name="image"></label><label>Enlace HTTPS/HTTP<input type="url" name="link_url"></label><label>Desde<input type="datetime-local" name="starts_at"></label><label>Hasta<input type="datetime-local" name="ends_at"></label><button>Publicar campaña</button></form></div><div class="suite-card"><h2>Campañas publicadas</h2><?php foreach($campaigns as $c):?><div class="suite-campaign-row"><b><?=e($c['title'])?></b><small><?=((int)$c['active']===1)?'Activa':'Oculta'?></small><form method="post"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$c['id']?>"><button name="action" value="toggle">Alternar</button><button name="action" value="delete">Eliminar</button></form></div><?php endforeach;?></div></div>
<?php endif;?></section><?php require __DIR__.'/partials/footer.php';?>
