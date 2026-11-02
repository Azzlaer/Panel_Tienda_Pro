<?php
require __DIR__.'/config/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();
 $action=$_POST['action']??'';
 if($action==='registro'){
  $nombre=trim((string)($_POST['nombre']??''));
  $correo=strtolower(trim((string)($_POST['correo']??'')));
  $pass=(string)($_POST['password']??'');
  if(mb_strlen($nombre)<2||!filter_var($correo,FILTER_VALIDATE_EMAIL)||strlen($pass)<8){flash('Revisa tus datos: contraseña de mínimo 8 caracteres.');}
  else {
   try{
    $stmt=$conexion->prepare('INSERT INTO usuarios(nombre,correo,password) VALUES(?,?,?)');
    $hash=password_hash($pass,PASSWORD_DEFAULT);
    $stmt->bind_param('sss',$nombre,$correo,$hash);$stmt->execute();
    flash('Registro exitoso. Ahora puedes iniciar sesión.');
   }catch(mysqli_sql_exception $e){flash($e->getCode()===1062?'Ese correo ya está registrado.':'No se pudo completar el registro.');}
  }
 }elseif($action==='login'){
  $correo=trim((string)($_POST['correo_login']??''));
  $pass=(string)($_POST['password_login']??'');
  $stmt=$conexion->prepare('SELECT id,nombre,correo,password,rol,estado FROM usuarios WHERE correo=? LIMIT 1');
  $stmt->bind_param('s',$correo);$stmt->execute();$u=$stmt->get_result()->fetch_assoc();
  if($u&&$u['estado']==='activo'&&password_verify($pass,$u['password'])){
   session_regenerate_id(true);
   $_SESSION['id_usuario']=(int)$u['id'];$_SESSION['nombre']=$u['nombre'];$_SESSION['correo']=$u['correo'];$_SESSION['rol']=$u['rol'];
   flash('¡Bienvenido, '.$u['nombre'].'!');
  }else{flash('Credenciales incorrectas o cuenta bloqueada.');}
 }
 redirect('index.php');
}
$filtro=$_GET['tipo']??'todos';
if(!in_array($filtro,['todos','articulo','servicio'],true))$filtro='todos';
$sql="SELECT p.*,u.nombre,ROUND(AVG(r.estrellas),1) promedio,COUNT(r.id) total_resenas
 FROM publicaciones p JOIN usuarios u ON u.id=p.id_usuario
 LEFT JOIN resenas r ON r.id_publicacion=p.id
 WHERE p.estado='activo'".($filtro==='todos'?'':" AND p.tipo=?")."
 GROUP BY p.id ORDER BY p.fecha DESC LIMIT 60";
$stmt=$conexion->prepare($sql);
if($filtro!=='todos')$stmt->bind_param('s',$filtro);
$stmt->execute();$pubs=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ui=json_decode(@file_get_contents(BASE_PATH.'/config/ui.json'),true)?:[];
require BASE_PATH.'/templates/'.TEMPLATE_ACTIVO.'/index.php';
