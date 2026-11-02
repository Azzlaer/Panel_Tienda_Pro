<?php
require __DIR__.'/config/bootstrap.php';
if(!logged())redirect('index.php');
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();$titulo=trim((string)($_POST['titulo']??''));$desc=trim((string)($_POST['descripcion']??''));$tipo=(string)($_POST['tipo']??'');$precio=filter_var($_POST['precio']??null,FILTER_VALIDATE_FLOAT);
 if(mb_strlen($titulo)<4||mb_strlen($desc)<10||!in_array($tipo,['articulo','servicio'],true)||$precio===false||$precio<0){flash('Revisa el título, descripción, tipo y precio.');}
 else {
  $image=null;
  if(isset($_FILES['imagen'])&&$_FILES['imagen']['error']!==UPLOAD_ERR_NO_FILE){
   $f=$_FILES['imagen'];
   if($f['error']!==UPLOAD_ERR_OK||$f['size']>4*1024*1024){flash('La imagen no se pudo subir o supera 4 MB.');redirect('publicar.php');}
   $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
   $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
   if(!$ext){flash('Solo JPG, PNG o WEBP.');redirect('publicar.php');}
   $image='uploads/'.bin2hex(random_bytes(16)).'.'.$ext;
   if(!move_uploaded_file($f['tmp_name'],BASE_PATH.'/'.$image)){flash('Error guardando imagen.');redirect('publicar.php');}
  }
  $uid=(int)$_SESSION['id_usuario'];
  $s=$conexion->prepare('INSERT INTO publicaciones(id_usuario,titulo,descripcion,precio,tipo,imagen) VALUES(?,?,?,?,?,?)');
  $s->bind_param('issdss',$uid,$titulo,$desc,$precio,$tipo,$image);$s->execute();
  flash('¡Publicación creada!');redirect('ver.php?id='.$conexion->insert_id);
 }
 redirect('publicar.php');
}
require __DIR__.'/_page_top.php';?>
<div class="panel"><h1>➕ Crear publicación</h1><form class="form-stack" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><label>Título<input name="titulo" maxlength="200" required></label><label>Tipo<select name="tipo"><option value="articulo">Artículo</option><option value="servicio">Servicio</option></select></label><label>Precio (CLP)<input type="number" name="precio" min="0" step="1" required></label><label>Descripción<textarea name="descripcion" rows="7" required minlength="10"></textarea></label><label>Fotografía (JPG/PNG/WEBP, hasta 4 MB)<input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"></label><button class="btn primary">Publicar anuncio</button></form></div>
<?php require __DIR__.'/_page_bottom.php';?>
