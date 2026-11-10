<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';require_admin();
require_once __DIR__.'/../app/vip_product_effects.php';
$pdo=db();$ready=vip_effect_ready($pdo);
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  if(!$ready)throw new RuntimeException('Activa el módulo en Integration Center.');
  $seller=filter_var($_POST['seller_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
  if(!$seller)throw new InvalidArgumentException('Vendedor inválido.');
  $enable=($_POST['action']??'')==='enable';$until=$enable?(trim((string)($_POST['expires_at']??''))?:null):null;
  vip_grant($pdo,(int)user()['id'],(int)$seller,$enable,$until);
  audit_log('vip.seller_access_changed','user',(int)$seller,['enabled'=>$enable,'expires_at'=>$until]);
  flash('success','Permisos VIP actualizados.');
 }catch(Throwable $e){flash('error',$e->getMessage());}
 redirect('../admin/vip_product_effects.php');
}
$members=[];
if($ready){
 $members=$pdo->query("SELECT u.id,u.username,p.display_name,v.enabled,v.expires_at FROM users u LEFT JOIN profiles p ON p.user_id=u.id LEFT JOIN vip_seller_access v ON v.seller_id=u.id WHERE u.status='active' ORDER BY COALESCE(v.enabled,0) DESC,u.id DESC LIMIT 250")->fetchAll();
}
$adminPage='vip_effects';$adminTitle='Efectos VIP para publicaciones';$adminSubtitle='Accesos VIP independientes y estilos visuales predefinidos';require __DIR__.'/partials/header.php';?>
<link rel="stylesheet" href="<?=url('assets/css/vip-product-effects.css')?>">
<section class="panel-card"><h2>✨ NameEffects adaptado a Comercios</h2><p>Autoriza o revoca VIP para vendedores. Los efectos se aplican solo al título de artículos publicados, y se desactivan automáticamente cuando vence el acceso. Sin PHP/CSS personalizado de vendedores.</p>
<?php if(!$ready):?><div class="vip-effect-access"><strong>Módulo sin activar.</strong><p>Actívalo en <a href="integration_center.php">Integration Center</a>. No altera tablas existentes.</p></div>
<?php else:?><div class="vip-effect-access">Los accesos se gestionan desde administración. Si desactivas VIP, los efectos dejan de aparecer públicamente sin borrar sus selecciones guardadas.</div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Vendedor</th><th>VIP actual</th><th>Editar acceso</th></tr></thead><tbody>
<?php foreach($members as $m):?><tr><td><?=e($m['display_name']?:$m['username'])?><small>@<?=e($m['username'])?> · ID <?=(int)$m['id']?></small></td>
<td><?=!empty($m['enabled'])?'Habilitado':'No asignado / deshabilitado'?><?php if($m['expires_at']):?><small>Vence <?=e($m['expires_at'])?></small><?php endif;?></td>
<td><form method="post" class="vip-effect-admin"><?=csrf_field()?><input type="hidden" name="seller_id" value="<?=(int)$m['id']?>">
<label>Vencimiento (vacío = permanente)<input type="date" name="expires_at" value="<?=e($m['expires_at']?substr($m['expires_at'],0,10):'')?>"></label>
<div><button name="action" value="enable">Habilitar VIP</button> <button name="action" value="disable" style="background:#8b3453">Revocar VIP</button></div></form></td></tr><?php endforeach;?></tbody></table></div>
<?php endif;?></section>
<?php require __DIR__.'/partials/footer.php';
