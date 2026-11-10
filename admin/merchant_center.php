<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';require_admin();require_once __DIR__.'/../app/merchant_center.php';
$adminPage='merchant_center';$adminTitle='Merchant Center PRO';$adminSubtitle='Métricas agregadas y estado del módulo para comerciantes';
$ready=merchant_ready(db());$stats=['owners'=>0,'staff'=>0,'contacts'=>0,'tasks'=>0];
if($ready){
 $stats['owners']=(int)db()->query('SELECT COUNT(DISTINCT owner_id) FROM merchant_staff')->fetchColumn();
 $stats['staff']=(int)db()->query('SELECT COUNT(*) FROM merchant_staff WHERE active=1')->fetchColumn();
 $stats['contacts']=(int)db()->query('SELECT COUNT(*) FROM merchant_contacts')->fetchColumn();
 $stats['tasks']=(int)db()->query("SELECT COUNT(*) FROM merchant_tasks WHERE status<>'done'")->fetchColumn();
}
require __DIR__.'/partials/header.php';?>
<section class="panel-card"><div class="panel-head"><div><h2>🏪 Merchant Center PRO</h2><p>Centro de colaboradores, CRM, tareas y panel de métricas para vendedores.</p></div><a class="admin-btn" href="<?=url('merchant_center.php')?>" target="_blank" rel="noopener">Abrir centro de comercio</a></div>
<?php if(!$ready):?><p>Faltan las tablas del módulo. Activa Merchant Center desde <a href="integration_center.php">Integration Center</a> después de respaldar MySQL.</p>
<?php else:?><div class="admin-grid cols-2"><div class="panel-card"><h3><?=(int)$stats['owners']?></h3>Comercios con colaboradores</div><div class="panel-card"><h3><?=(int)$stats['staff']?></h3>Empleados autorizados</div><div class="panel-card"><h3><?=(int)$stats['contacts']?></h3>Contactos CRM (total)</div><div class="panel-card"><h3><?=(int)$stats['tasks']?></h3>Tareas pendientes (total)</div></div><?php endif;?>
<p>Los contactos y notas individuales no se muestran a los administradores en este resumen. El propietario controla el acceso de sus empleados mediante roles.</p>
</section><?php require __DIR__.'/partials/footer.php';