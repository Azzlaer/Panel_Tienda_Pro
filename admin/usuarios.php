<?php
require dirname(__DIR__).'/config/bootstrap.php';require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
 $action=$_POST['action']??'';$value=$_POST['value']??'';
 if($id&&$id!==(int)$_SESSION['id_usuario']){
  if($action==='rol'&&in_array($value,['admin','usuario'],true)){
   $s=$conexion->prepare('UPDATE usuarios SET rol=? WHERE id=?');$s->bind_param('si',$value,$id);$s->execute();flash('Rol actualizado.');
  }elseif($action==='estado'&&in_array($value,['activo','bloqueado'],true)){
   $s=$conexion->prepare('UPDATE usuarios SET estado=? WHERE id=?');$s->bind_param('si',$value,$id);$s->execute();flash('Estado actualizado.');
  }
 }else{flash('No puedes modificar tu propia cuenta desde aquí.');}
 redirect('admin/usuarios.php');
}
$q=trim((string)($_GET['q']??''));$like='%'.$q.'%';
$s=$conexion->prepare('SELECT id,nombre,correo,rol,estado FROM usuarios WHERE nombre LIKE ? OR correo LIKE ? ORDER BY id DESC LIMIT 200');
$s->bind_param('ss',$like,$like);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);
require __DIR__.'/_top.php';?>
<div class="panel"><h2>👥 Gestión de usuarios</h2><p class="muted">Busca cuentas y administra sus permisos.</p>
<form method="get" style="display:flex;gap:10px;flex-wrap:wrap"><input style="padding:12px;flex:1;border:1px solid #dce1ec;border-radius:9px" name="q" placeholder="Buscar nombre o correo" value="<?=e($q)?>"><button class="btn primary">Buscar</button></form>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($rows as $u):?><tr><td><?=(int)$u['id']?></td><td><?=e($u['nombre'])?></td><td><?=e($u['correo'])?></td><td><span class="pill"><?=e($u['rol'])?></span></td><td><span class="pill"><?=e($u['estado'])?></span></td><td>
<?php if((int)$u['id']!==(int)$_SESSION['id_usuario']):?>
<form class="inline-form" method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=$u['id']?>"><input type="hidden" name="action" value="rol"><input type="hidden" name="value" value="<?=$u['rol']==='admin'?'usuario':'admin'?>"><button class="small-btn" onclick="return confirm('¿Cambiar rol?')"><?=$u['rol']==='admin'?'Quitar admin':'Hacer admin'?></button></form>
<form class="inline-form" method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=$u['id']?>"><input type="hidden" name="action" value="estado"><input type="hidden" name="value" value="<?=$u['estado']==='activo'?'bloqueado':'activo'?>"><button class="small-btn danger" onclick="return confirm('¿Cambiar estado?')"><?=$u['estado']==='activo'?'Bloquear':'Activar'?></button></form>
<?php else:?>Tu cuenta<?php endif;?></td></tr><?php endforeach;?>
</tbody></table></div></div>
<?php require __DIR__.'/_bottom.php';?>
