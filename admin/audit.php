<?php
require_once __DIR__.'/../app/bootstrap.php';require_admin();ensure_core_v16_schema();
$limit=max(20,min(300,(int)($_GET['limit']??100)));
$action=trim((string)($_GET['action']??''));
$sql="SELECT l.*,u.username,p.display_name FROM admin_audit_logs l LEFT JOIN users u ON u.id=l.admin_id LEFT JOIN profiles p ON p.user_id=u.id";
$params=[];if($action!==''){$sql.=' WHERE l.action LIKE ?';$params[]='%'.$action.'%';}$sql.=' ORDER BY l.created_at DESC LIMIT '.$limit;
$q=db()->prepare($sql);$q->execute($params);$logs=$q->fetchAll();
$adminPage='audit';$adminTitle='Auditoría administrativa';$adminSubtitle='Historial de cambios y acciones sensibles';require __DIR__.'/partials/header.php';
?>
<section class="admin-card"><form class="admin-filterbar"><input name="action" value="<?=e($action)?>" placeholder="Filtrar por acción, por ejemplo template"><select name="limit"><option>50</option><option <?=$limit===100?'selected':''?>>100</option><option <?=$limit===200?'selected':''?>>200</option><option <?=$limit===300?'selected':''?>>300</option></select><button class="admin-btn">🔎 Filtrar</button></form></section>
<section class="admin-card"><div class="admin-section-title"><div><span class="admin-eyebrow">SEGURIDAD Y CONTROL</span><h2>Últimas acciones registradas</h2></div><span class="admin-badge"><?=count($logs)?> eventos</span></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Fecha</th><th>Administrador</th><th>Acción</th><th>Entidad</th><th>Detalles</th></tr></thead><tbody><?php foreach($logs as $l):?><tr><td><?=date('d/m/Y H:i',strtotime($l['created_at']))?></td><td><?=e($l['display_name']?:$l['username']?:'Sistema')?></td><td><code><?=e($l['action'])?></code></td><td><?=e(($l['entity_type']?:'—').($l['entity_id']?' #'.$l['entity_id']:''))?></td><td><details><summary>Ver</summary><pre><?=e($l['details_json']?:'Sin detalles adicionales')?></pre></details></td></tr><?php endforeach;?><?php if(!$logs):?><tr><td colspan="5">Todavía no hay eventos registrados.</td></tr><?php endif;?></tbody></table></div></section>
<?php require __DIR__.'/partials/footer.php'; ?>
