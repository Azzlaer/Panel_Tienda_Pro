<?php
require_once __DIR__.'/../app/bootstrap.php';require_admin();ensure_core_v16_schema();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=$_POST['action']??'';
 if($action==='repair'){
   ensure_news_schema();ensure_feedback_schema();ensure_donations_schema();ensure_core_v16_schema();
   save_setting('app_version',app_version());audit_log('system.schema_repair','system','database',['version'=>app_version()]);
   flash('success','Estructura verificada y migraciones internas aplicadas.');redirect('../admin/updates.php');
 }
}
$migrations=db()->query('SELECT * FROM schema_migrations ORDER BY applied_at DESC')->fetchAll();
$tables=['users','profiles','categories','products','product_images','news','feedback_threads','donation_methods','donations','admin_audit_logs','schema_migrations'];$checks=[];
foreach($tables as $t){$q=db()->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$q->execute([$t]);$checks[$t]=(bool)$q->fetchColumn();}
$adminPage='updates';$adminTitle='Actualizaciones y migraciones';$adminSubtitle='Verifica la estructura antes de instalar nuevas funciones';require __DIR__.'/partials/header.php';
?>
<section class="admin-card update-status-card"><div><span class="admin-eyebrow">VERSIÓN INSTALADA</span><h2>ComercioSocial <?=e(app_version())?></h2><p>Edición vitrina informativa. No incluye procesos de compra, venta, pago o facturación.</p></div><div class="update-version-badge">v<?=e(setting('app_version',app_version()))?></div></section>
<div class="admin-grid-2"><section class="admin-card"><h2>🧱 Estado de tablas esenciales</h2><div class="health-list"><?php foreach($checks as $table=>$ok):?><div><span class="status-dot <?=$ok?'ok':'bad'?>"></span><b><?=e($table)?></b><em><?=$ok?'Disponible':'Faltante'?></em></div><?php endforeach;?></div><form method="post" onsubmit="return confirm('¿Verificar y reparar la estructura ahora?')"><?=csrf_field()?><input type="hidden" name="action" value="repair"><button class="admin-btn primary">🛠️ Verificar y aplicar migraciones</button></form></section><section class="admin-card"><h2>📋 Migraciones aplicadas</h2><div class="migration-list"><?php foreach($migrations as $m):?><div><code><?=e($m['migration'])?></code><small><?=date('d/m/Y H:i',strtotime($m['applied_at']))?></small></div><?php endforeach;?></div><div class="admin-note"><b>Próxima etapa</b><p>Los futuros paquetes de actualización utilizarán manifiesto, copia previa, migraciones SQL controladas y reversión.</p></div></section></div>
<?php require __DIR__.'/partials/footer.php'; ?>
