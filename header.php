<?php
$unread=0;
if(logged()){
 $uid=(int)$_SESSION['id_usuario'];
 $s=$conexion->prepare('SELECT COUNT(*) n FROM mensajes WHERE id_receptor=? AND leido=0');
 $s->bind_param('i',$uid);$s->execute();$unread=(int)$s->get_result()->fetch_assoc()['n'];
}
?>
<header class="site-header"><a class="brand" href="<?=BASE_URL?>index.php">🛍️ MiTienda</a>
<nav class="main-nav"><a href="<?=BASE_URL?>index.php">Inicio</a><a href="<?=BASE_URL?>index.php?tipo=articulo">Artículos</a><a href="<?=BASE_URL?>index.php?tipo=servicio">Servicios</a></nav>
<div class="nav-actions">
<?php if(!logged()): ?>
<button type="button" data-modal="modalLogin" class="btn ghost">Ingresar</button><button type="button" data-modal="modalRegistro" class="btn primary">Registrarse</button>
<?php else: ?>
<span class="greeting">👤 <?=e($_SESSION['nombre'])?></span>
<a href="<?=BASE_URL?>publicar.php">➕ Publicar</a><a href="<?=BASE_URL?>perfil.php">Perfil</a><a href="<?=BASE_URL?>favoritos.php">❤️</a><a href="<?=BASE_URL?>inbox.php">📥 <?=$unread?:''?></a>
<?php if(admin()):?><a class="btn primary" href="<?=BASE_URL?>admin/index.php">🛡️ Admin</a><?php endif;?>
<a href="<?=BASE_URL?>logout.php">Salir</a>
<?php endif;?>
</div></header>
