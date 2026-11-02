<?php require __DIR__.'/_top.php';
function count_rows(mysqli $db,string $table):int{return (int)$db->query("SELECT COUNT(*) n FROM `$table`")->fetch_assoc()['n'];}
$blocked=(int)$conexion->query("SELECT COUNT(*) n FROM usuarios WHERE estado='bloqueado'")->fetch_assoc()['n'];
?>
<div class="stats">
<?php foreach([['👥 Usuarios',count_rows($conexion,'usuarios')],['📦 Publicaciones',count_rows($conexion,'publicaciones')],['💬 Mensajes',count_rows($conexion,'mensajes')],['⭐ Reseñas',count_rows($conexion,'resenas')],['🚫 Bloqueados',$blocked]] as $m):?>
<div class="stat"><span><?=e($m[0])?></span><strong><?=$m[1]?></strong></div><?php endforeach;?></div>
<h2>Centro de control</h2><div class="admin-links"><a href="usuarios.php"><strong>👥 Usuarios</strong><span class="muted">Roles, bloqueos y cuentas</span></a><a href="publicaciones.php"><strong>📦 Publicaciones</strong><span class="muted">Moderación y visibilidad</span></a><a href="configuracion.php"><strong>🎨 Templates</strong><span class="muted">Cambiar el aspecto público</span></a></div>
<?php require __DIR__.'/_bottom.php';?>
