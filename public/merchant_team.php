<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';require_login();require_once __DIR__.'/../app/merchant_center.php';
$pdo=db();if(!merchant_ready($pdo)){http_response_code(503);exit('Merchant Center no activado.');}
$owner=(int)user()['id'];merchant_require($pdo,$owner,$owner,'team');
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  $username=trim((string)($_POST['username']??''));
  $role=(string)($_POST['role']??'support');$active=($_POST['action']??'')==='enable';
  $q=$pdo->prepare("SELECT id FROM users WHERE username=? AND status='active'");$q->execute([$username]);$id=(int)$q->fetchColumn();
  if(!$id)throw new RuntimeException('Usuario activo no encontrado.');
  if($active){$q=$pdo->prepare('SELECT COUNT(*) FROM merchant_staff WHERE owner_id=? AND active=1');$q->execute([$owner]);if((int)$q->fetchColumn()>=20)throw new RuntimeException('Máximo 20 colaboradores activos.');}
  merchant_staff_set($pdo,$owner,$owner,$id,$role,$active);
  flash('success',$active?'Colaborador autorizado.':'Colaborador desactivado.');
 }catch(Throwable $e){flash('error',$e->getMessage());}
 redirect('merchant_team.php');
}
$q=$pdo->prepare('SELECT s.*,u.username,u.status FROM merchant_staff s JOIN users u ON u.id=s.member_id WHERE s.owner_id=? ORDER BY s.active DESC,u.username ASC');$q->execute([$owner]);$staff=$q->fetchAll();
$pageTitle='Equipo del comercio';require __DIR__.'/../partials/header.php';?>
<link rel="stylesheet" href="<?=url('assets/css/merchant-center.css')?>">
<section class="merchant"><header class="merchant-hero"><span>MERCHANT CENTER · EQUIPO</span><h1>Colaboradores y permisos</h1><p>Autoriza usuarios existentes. No se comparten contraseñas ni se concede acceso al POS, al inventario o a la cuenta personal del propietario.</p></header><div class="merchant-links"><a href="<?=url('merchant_center.php')?>">← Dashboard</a><a href="<?=url('merchant_crm.php')?>">CRM</a></div>
<div class="merchant-panel"><h2>Autorizar colaborador</h2><form method="post" class="merchant-form"><?=csrf_field()?><input type="hidden" name="action" value="enable"><label>Usuario existente<input name="username" required maxlength="40" placeholder="Nombre de usuario"></label><label>Rol<select name="role"><option value="manager">Gerente (CRM y tareas)</option><option value="support">Soporte (CRM y tareas)</option><option value="analyst">Analista (solo lectura)</option></select></label><button>Guardar permisos</button></form><p>El propietario conserva el control de las invitaciones. El máximo es 20 colaboradores activos.</p></div>
<div class="merchant-panel"><h2>Colaboradores</h2><div class="merchant-table"><table><thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th>Acción</th></tr></thead><tbody><?php foreach($staff as $s):?><tr><td><?=e($s['username'])?></td><td><?=e($s['role'])?></td><td><?=$s['active']?'Activo':'Desactivado'?></td><td><form method="post"><?=csrf_field()?><input type="hidden" name="username" value="<?=e($s['username'])?>"><input type="hidden" name="role" value="<?=e($s['role'])?>"><button name="action" value="<?=$s['active']?'disable':'enable'?>"><?=$s['active']?'Revocar':'Reactivar'?></button></form></td></tr><?php endforeach;?></tbody></table></div></div></section>
<?php require __DIR__.'/../partials/footer.php';
