<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';require_admin();require_once __DIR__.'/../app/orders_pro.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();if(($_POST['action']??'')!=='install'){http_response_code(400);exit('Acción inválida');}
 try{orders_pro_install(db());audit_log('orders_pro.install');flash('success','Pedidos PRO activado; no se borraron datos.');}
 catch(Throwable $e){flash('error','No fue posible activar Pedidos PRO: '.$e->getMessage());}
 redirect('../admin/orders_pro.php');
}
$ready=orders_pro_ready(db());$count=0;if($ready)$count=(int)db()->query('SELECT COUNT(*) FROM pro_orders')->fetchColumn();
$adminPage='orders_pro';$adminTitle='Pedidos PRO';$adminSubtitle='Activación y control de pedidos';require __DIR__.'/partials/header.php';?>
<section class="panel-card"><div class="panel-head"><div><h2>Pedidos PRO</h2><p>Pedidos por comercio sin pagos online y sin modificaciones destructivas de datos.</p></div></div>
<?php if(!$ready):?><p>Haz respaldo antes de activar. Se crearán las tablas pro_orders y pro_order_items.</p><form method="post"><?=csrf_field()?><button class="admin-btn" name="action" value="install">Activar módulo</button></form>
<?php else:?><p>Pedidos registrados: <strong><?=$count?></strong></p><p><a class="admin-btn" href="<?=url('orders_pro.php')?>">Abrir pedidos</a></p><?php endif;?></section>
<?php require __DIR__.'/partials/footer.php';
