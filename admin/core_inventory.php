<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_admin();
require_once __DIR__.'/../app/core_migrations.php';
$adminPage='core_inventory';$adminTitle='Enterprise Core';$adminSubtitle='Migración y auditoría de inventario';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 if(($_POST['action']??'')!=='install'){http_response_code(400);exit('Acción desconocida.');}
 try{core_inventory_install(db());flash('success','Auditoría de inventario activada correctamente.');}
 catch(Throwable $e){flash('error','No se pudo instalar: '.$e->getMessage());}
 redirect('../admin/core_inventory.php');
}
$ready=core_inventory_audit_ready(db());
$records=[];if($ready)$records=db()->query("SELECT a.created_at,a.action_name,a.previous_stock,a.new_stock,a.previous_price_clp,a.new_price_clp,u.username,p.title FROM core_inventory_audit a JOIN users u ON u.id=a.seller_id JOIN products p ON p.id=a.product_id ORDER BY a.id DESC LIMIT 40")->fetchAll();
require __DIR__.'/partials/header.php';?>
<section class="panel-card"><div class="panel-head"><div><h2>🛡️ Commerce Enterprise Core</h2><p>Auditoría de cambios manuales de precio y existencias.</p></div></div>
<?php if(!$ready):?><p>Instalación aditiva: una tabla nueva, sin borrar registros. Haz respaldo antes de activar.</p><form method="post"><?=csrf_field()?><button class="admin-btn" name="action" value="install">Activar auditoría</button></form>
<?php else:?><p>Auditoría activa. Últimos movimientos:</p><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Fecha</th><th>Vendedor</th><th>Producto</th><th>Stock</th><th>Precio CLP</th></tr></thead><tbody><?php foreach($records as $r):?><tr><td><?=e($r['created_at'])?></td><td><?=e($r['username'])?></td><td><?=e($r['title'])?></td><td><?=e((string)($r['previous_stock']??'sin especificar'))?> → <?=e((string)($r['new_stock']??'sin especificar'))?></td><td><?=number_format((int)$r['previous_price_clp'],0,',','.')?> → <?=number_format((int)$r['new_price_clp'],0,',','.')?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section>
<?php require __DIR__.'/partials/footer.php';
