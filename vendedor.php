<?php
require __DIR__.'/config/bootstrap.php';
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);if(!$id){http_response_code(404);exit('Vendedor no encontrado');}
$s=$conexion->prepare('SELECT nombre,creado_en FROM usuarios WHERE id=?');$s->bind_param('i',$id);$s->execute();$u=$s->get_result()->fetch_assoc();if(!$u){http_response_code(404);exit('Vendedor no encontrado');}
$s=$conexion->prepare("SELECT id,titulo,precio FROM publicaciones WHERE id_usuario=? AND estado='activo' ORDER BY id DESC");$s->bind_param('i',$id);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);
require __DIR__.'/_page_top.php';?><div class="panel"><h1>👤 <?=e($u['nombre'])?></h1><p class="muted"><?=count($rows)?> publicaciones activas</p><?php foreach($rows as $p):?><p><a href="<?=BASE_URL?>ver.php?id=<?=$p['id']?>"><?=e($p['titulo'])?></a> · $<?=number_format((float)$p['precio'],0,',','.')?></p><?php endforeach;?></div><?php require __DIR__.'/_page_bottom.php';?>
