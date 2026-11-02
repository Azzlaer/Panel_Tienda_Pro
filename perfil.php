<?php
require __DIR__.'/config/bootstrap.php';if(!logged())redirect('index.php');
$id=(int)$_SESSION['id_usuario'];$s=$conexion->prepare('SELECT id,nombre,correo,rol,creado_en FROM usuarios WHERE id=?');$s->bind_param('i',$id);$s->execute();$u=$s->get_result()->fetch_assoc();
$s=$conexion->prepare('SELECT id,titulo,estado FROM publicaciones WHERE id_usuario=? ORDER BY id DESC');$s->bind_param('i',$id);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);
require __DIR__.'/_page_top.php';?><div class="panel"><h1>👤 Mi perfil</h1><p><strong><?=e($u['nombre'])?></strong> · <?=e($u['correo'])?></p><p class="muted">Rol: <?=e($u['rol'])?> · Publicaciones: <?=count($rows)?></p><h2>Mis publicaciones</h2><?php foreach($rows as $p):?><p><a href="<?=BASE_URL?>ver.php?id=<?=$p['id']?>"><?=e($p['titulo'])?></a> · <span class="pill"><?=e($p['estado'])?></span></p><?php endforeach;?></div><?php require __DIR__.'/_page_bottom.php';?>
