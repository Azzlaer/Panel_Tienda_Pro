<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';require_admin();require_once __DIR__.'/../app/pos.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 if(($_POST['action']??'')==='install'){
  try {pos_install(db());audit_log('pos.install');flash('success','POS activado; tablas creadas sin borrar datos.');}catch(Throwable $e){flash('error','Error al activar POS: '.$e->getMessage());}
  redirect('../admin/pos.php');
 }
 http_response_code(400);exit('Acción no válida.');
}
$ready=pos_ready(db());$count=0;$total=0;
if($ready){$r=db()->query('SELECT COUNT(*) n,COALESCE(SUM(total_clp),0) total FROM pos_sales')->fetch();$count=(int)$r['n'];$total=(int)$r['total'];}
$adminPage='pos';$adminTitle='Punto de Venta PRO';$adminSubtitle='Activación y resumen de registros presenciales';require __DIR__.'/partials/header.php';?>
<section class="panel-card"><div class="panel-head"><div><h2>🧾 POS Enterprise</h2><p>Control de ventas presenciales y movimientos de stock; no se realizan cobros online.</p></div></div>
<?php if(!$ready):?><p>La activación crea tres tablas nuevas. No elimina ni modifica registros existentes. Realiza primero un respaldo MySQL.</p><form method="post"><?=csrf_field()?><button class="admin-btn" name="action" value="install">Activar POS</button></form>
<?php else:?><div class="admin-grid cols-2"><div class="metric-card"><div><strong><?=$count?></strong><small>Ventas registradas</small></div></div><div class="metric-card"><div><strong>$<?=number_format($total,0,',','.')?></strong><small>Total histórico CLP</small></div></div></div><p><a class="admin-btn" href="<?=url('pos.php')?>">Abrir caja de vendedor</a></p><?php endif;?></section>
<?php require __DIR__.'/partials/footer.php';
