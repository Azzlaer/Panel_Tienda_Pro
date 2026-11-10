<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_admin();
require_once __DIR__.'/../app/commerce_suite.php';
require_once __DIR__.'/../app/pos.php';
require_once __DIR__.'/../app/orders_pro.php';
require_once __DIR__.'/../app/core_migrations.php';
require_once __DIR__.'/../app/content_hub.php';
require_once __DIR__.'/../app/marketplace.php';
require_once __DIR__.'/../app/merchant_center.php';

$modules = [
 'suite'=>['Commerce Suite','suite_ready','suite_install'],
 'pos'=>['Punto de Venta PRO','pos_ready','pos_install'],
 'orders'=>['Pedidos PRO','orders_pro_ready','orders_pro_install'],
 'core'=>['Auditoría Enterprise Core','core_inventory_audit_ready','core_inventory_install'],
 'hub'=>['Content Hub + Page Builder','hub_ready','hub_install'],
 'marketplace'=>['Marketplace Enterprise','marketplace_ready','marketplace_install'],
 'merchant'=>['Merchant Center PRO','merchant_ready','merchant_install']
];
function integration_module_ready(string $module): bool {
 if($module==='suite') return suite_ready();
 if($module==='pos') return pos_ready(db());
 if($module==='orders') return orders_pro_ready(db());
 if($module==='core') return core_inventory_audit_ready(db());
 if($module==='hub') return hub_ready(db());
 if($module==='marketplace') return marketplace_ready(db());
 if($module==='merchant') return merchant_ready(db());
 return false;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $module=(string)($_POST['module']??'');
 if(!array_key_exists($module,$modules)){http_response_code(400);exit('Módulo inválido.');}
 if(!integration_module_ready($module)){
  try {
   if($module==='suite')suite_install();
   elseif($module==='pos')pos_install(db());
   elseif($module==='orders')orders_pro_install(db());
   elseif($module==='core')core_inventory_install(db());
   elseif($module==='hub')hub_install(db());
   elseif($module==='marketplace')marketplace_install(db());
   elseif($module==='merchant')merchant_install(db());
   if(!integration_module_ready($module))throw new RuntimeException('Las tablas no se crearon correctamente.');
   audit_log('integration.module_activated','module',$module);
   flash('success','Módulo activado correctamente.');
  } catch(Throwable $e){
   error_log('Error integración módulo '.$module.': '.$e->getMessage());
   flash('error','No se pudo activar el módulo. Revisa los registros del servidor y la configuración MySQL.');
  }
 }
 redirect('../admin/integration_center.php');
}
$adminPage='integration';
$adminTitle='Integration Center PRO';
$adminSubtitle='Activación controlada de módulos y migraciones aditivas';
require __DIR__.'/partials/header.php';
?>
<section class="panel-card">
 <div class="panel-head"><div><h2>🔗 Commerce Enterprise Integration Center</h2><p>Activa módulos de manera individual. Respalda la base de datos antes de instalar.</p></div></div>
 <p>No reinstales la aplicación para incorporar las funcionalidades. Las migraciones no deben ejecutarse desde páginas públicas y no eliminan datos existentes.</p>
 <div class="admin-grid cols-2">
 <?php foreach($modules as $key=>$info):$ready=integration_module_ready($key);?>
 <div class="panel-card" style="margin:0;padding:19px">
  <h3><?=e($info[0])?></h3>
  <p><?=$ready?'✓ Tablas verificadas':'Pendiente de activación'?></p>
  <?php if(!$ready):?>
  <form method="post"><?=csrf_field()?><input type="hidden" name="module" value="<?=e($key)?>">
    <button class="admin-btn" type="submit">Activar <?=e($info[0])?></button>
  </form><?php endif;?>
 </div>
 <?php endforeach;?>
 </div>
 <p><strong>Orden recomendado:</strong> Commerce Suite → POS → Pedidos → Auditoría Enterprise Core → Content Hub → Marketplace → Merchant Center. El panel controla la existencia de las tablas, no garantiza sus columnas ni una migración completada en caso de interrupciones.</p>
</section>
<?php require __DIR__.'/partials/footer.php';
