<?php
require __DIR__.'/config/bootstrap.php';
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);if(!$id){http_response_code(404);exit('Publicación no encontrada');}
$s=$conexion->prepare("SELECT p.*,u.nombre vendedor,ROUND(AVG(r.estrellas),1) promedio,COUNT(r.id) total_resenas FROM publicaciones p JOIN usuarios u ON u.id=p.id_usuario LEFT JOIN resenas r ON r.id_publicacion=p.id WHERE p.id=? AND p.estado='activo' GROUP BY p.id");
$s->bind_param('i',$id);$s->execute();$p=$s->get_result()->fetch_assoc();
if(!$p){http_response_code(404);exit('Publicación no disponible');}
if($_SERVER['REQUEST_METHOD']==='POST'&&logged()){
 check_csrf();$uid=(int)$_SESSION['id_usuario'];$action=$_POST['action']??'';
 if($action==='favorito'){
  $s=$conexion->prepare('INSERT IGNORE INTO favoritos(id_usuario,id_publicacion) VALUES(?,?)');$s->bind_param('ii',$uid,$id);$s->execute();flash('Guardado en favoritos.');
 }elseif($action==='mensaje'&&$uid!==(int)$p['id_usuario']){
  $texto=trim((string)($_POST['contenido']??''));
  if(mb_strlen($texto)>=2&&mb_strlen($texto)<=2000){$dest=(int)$p['id_usuario'];$s=$conexion->prepare('INSERT INTO mensajes(id_emisor,id_receptor,contenido) VALUES(?,?,?)');$s->bind_param('iis',$uid,$dest,$texto);$s->execute();flash('Mensaje enviado.');}
 }
 redirect('ver.php?id='.$id);
}
require __DIR__.'/_page_top.php';?>
<div class="panel" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:28px"><img src="<?=e(image_url($p['imagen']))?>" alt="<?=e($p['titulo'])?>" style="width:100%;border-radius:16px;max-height:450px;object-fit:cover"><div><span class="pill"><?=e($p['tipo'])?></span><h1><?=e($p['titulo'])?></h1><h2 style="color:#4357ed">$<?=number_format((float)$p['precio'],0,',','.')?></h2><p><?=nl2br(e($p['descripcion']))?></p><p>⭐ <?=$p['total_resenas']?e($p['promedio']).' / 5':'Sin reseñas'?></p><p>👤 <a href="<?=BASE_URL?>vendedor.php?id=<?=(int)$p['id_usuario']?>"><?=e($p['vendedor'])?></a></p>
<?php if(logged()):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="favorito"><button class="btn ghost">❤️ Guardar favorito</button></form>
<?php if((int)$_SESSION['id_usuario']!==(int)$p['id_usuario']):?><form class="form-stack" method="post" style="margin-top:22px"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="mensaje"><label>Contactar al vendedor<textarea name="contenido" rows="3" required minlength="2" maxlength="2000" placeholder="Hola, ¿sigue disponible?"></textarea></label><button class="btn primary">Enviar mensaje</button></form><?php endif;?>
<?php else:?><p>Inicia sesión para contactar o guardar este anuncio.</p><?php endif;?></div></div>
<?php require __DIR__.'/_page_bottom.php';?>
