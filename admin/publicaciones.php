<?php
require dirname(__DIR__).'/config/bootstrap.php';require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
 $action=$_POST['action']??'';$value=$_POST['value']??'';
 if($id&&$action==='estado'&&in_array($value,['activo','oculto'],true)){
  $s=$conexion->prepare('UPDATE publicaciones SET estado=? WHERE id=?');$s->bind_param('si',$value,$id);$s->execute();flash('Publicación actualizada.');
 }elseif($id&&$action==='eliminar'){
  $s=$conexion->prepare('DELETE FROM publicaciones WHERE id=?');$s->bind_param('i',$id);$s->execute();flash('Publicación eliminada de la base de datos.');
 }
 redirect('admin/publicaciones.php');
}
$rows=$conexion->query('SELECT p.id,p.titulo,p.tipo,p.estado,p.precio,u.nombre autor FROM publicaciones p JOIN usuarios u ON u.id=p.id_usuario ORDER BY p.id DESC LIMIT 200')->fetch_all(MYSQLI_ASSOC);
require __DIR__.'/_top.php';?>
<div class="panel"><h2>📦 Gestión de publicaciones</h2><p class="muted">Oculta, reactiva o elimina anuncios. La eliminación es irreversible.</p><div class="table-wrap"><table><thead><tr><th>ID</th><th>Publicación</th><th>Tipo</th><th>Vendedor</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($rows as $p):?><tr><td><?=$p['id']?></td><td><a href="<?=BASE_URL?>ver.php?id=<?=$p['id']?>" target="_blank"><?=e($p['titulo'])?></a></td><td><?=e($p['tipo'])?></td><td><?=e($p['autor'])?></td><td>$<?=number_format((float)$p['precio'],0,',','.')?></td><td><span class="pill"><?=e($p['estado'])?></span></td><td>
<form class="inline-form" method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=$p['id']?>"><input type="hidden" name="action" value="estado"><input type="hidden" name="value" value="<?=$p['estado']==='activo'?'oculto':'activo'?>"><button class="small-btn"><?=$p['estado']==='activo'?'Ocultar':'Activar'?></button></form>
<form class="inline-form" method="post" onsubmit="return confirm('¿Eliminar permanentemente esta publicación?')"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=$p['id']?>"><input type="hidden" name="action" value="eliminar"><button class="small-btn danger">Eliminar</button></form>
</td></tr><?php endforeach;?></tbody></table></div></div>
<?php require __DIR__.'/_bottom.php';?>
