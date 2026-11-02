<?php
require_once __DIR__.'/../app/bootstrap.php';
require_admin(); ensure_home_slides_schema();
$adminPage='slides'; $adminTitle='Slider de portada'; $adminSubtitle='Gestiona imágenes, enlaces y tiempos de rotación';
$editing=null; $editId=max(0,(int)($_GET['edit']??0));
if($editId){$st=db()->prepare('SELECT * FROM home_slides WHERE id=?');$st->execute([$editId]);$editing=$st->fetch()?:null;}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $action=(string)($_POST['action']??'save'); $id=max(0,(int)($_POST['id']??0));
 try {
  if($action==='settings'){
   $seconds=max(3,min(60,(int)($_POST['seconds']??6)));
   db()->prepare("INSERT INTO settings (`key`,value) VALUES ('home_slider_seconds',?) ON DUPLICATE KEY UPDATE value=VALUES(value)")->execute([(string)$seconds]);
   flash('success','Intervalo actualizado.'); redirect('../admin/slides.php');
  }
  if($action==='delete'){
   db()->prepare('DELETE FROM home_slides WHERE id=?')->execute([$id]);
   flash('success','Banner eliminado.');redirect('../admin/slides.php');
  }
  if($action==='toggle'){
   db()->prepare('UPDATE home_slides SET active=1-active WHERE id=?')->execute([$id]);
   flash('success','Visibilidad actualizada.');redirect('../admin/slides.php');
  }
  $title=mb_substr(trim((string)($_POST['title']??'')),0,190);
  $subtitle=mb_substr(trim((string)($_POST['subtitle']??'')),0,500);
  $buttonText=mb_substr(trim((string)($_POST['button_text']??'')),0,90);
  $buttonUrl=trim((string)($_POST['button_url']??''));
  if($buttonUrl!=='')slide_destination($buttonUrl);
  $image=upload_image('image','slides');
  $active=isset($_POST['active'])?1:0; $order=max(0,min(99999,(int)($_POST['sort_order']??0)));
  if($id){
   $q=db()->prepare('SELECT image FROM home_slides WHERE id=?');$q->execute([$id]);$old=$q->fetchColumn();
   if(!$old)throw new RuntimeException('Banner inexistente.');
   $image=$image?:$old;
   db()->prepare('UPDATE home_slides SET image=?,title=?,subtitle=?,button_text=?,button_url=?,active=?,sort_order=? WHERE id=?')->execute([$image,$title,$subtitle,$buttonText,$buttonUrl,$active,$order,$id]);
  } else {
   if(!$image)throw new RuntimeException('Selecciona una imagen para el banner.');
   db()->prepare('INSERT INTO home_slides (image,title,subtitle,button_text,button_url,active,sort_order) VALUES (?,?,?,?,?,?,?)')->execute([$image,$title,$subtitle,$buttonText,$buttonUrl,$active,$order]);
  }
  flash('success','Banner guardado correctamente.');redirect('../admin/slides.php');
 }catch(Throwable $ex){flash('error',$ex->getMessage());redirect('../admin/slides.php'.($id?'?edit='.$id:''));}
}
$slides=db()->query('SELECT * FROM home_slides ORDER BY sort_order ASC,id DESC')->fetchAll();
require __DIR__.'/partials/header.php';
?>
<div class="panel-card"><div class="panel-head"><div><h2>⏱️ Rotación automática</h2><p>Con una sola imagen, el slider permanece estático. Con dos o más, rota automáticamente.</p></div></div>
<form method="post" class="admin-form"><?=csrf_field()?><input type="hidden" name="action" value="settings"><label>Segundos entre imágenes (3 a 60)<input type="number" name="seconds" min="3" max="60" value="<?= (int)setting('home_slider_seconds','6') ?>" required></label><button class="admin-btn mt-20">Guardar intervalo</button></form></div>
<section class="panel-card"><div class="panel-head"><div><h2><?= $editing?'✏️ Editar banner':'➕ Nuevo banner' ?></h2><p>Imagen recomendada 1600 × 600 px (JPG, PNG o WebP).</p></div><?php if($editing):?><a class="admin-btn secondary small" href="slides.php">Nuevo banner</a><?php endif;?></div>
<form method="post" enctype="multipart/form-data" class="admin-form"><?=csrf_field()?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($editing['id']??0) ?>">
<label>Imagen <?= $editing?'(opcional para conservar la actual)':'(obligatoria)' ?><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" <?= $editing?'':'required' ?>></label>
<?php if($editing):?><img src="<?=e(url($editing['image']))?>" alt="Imagen actual" style="width:100%;max-width:520px;max-height:210px;object-fit:cover;border-radius:12px;margin-top:12px"><?php endif;?>
<label class="mt-20">Título<input name="title" maxlength="190" value="<?=e($editing['title']??'')?>" placeholder="Conoce los comercios de nuestra comunidad"></label>
<label class="mt-20">Descripción<textarea name="subtitle" maxlength="500" rows="3"><?=e($editing['subtitle']??'')?></textarea></label>
<div class="admin-grid cols-2 mt-20"><label>Texto del botón<input name="button_text" maxlength="90" value="<?=e($editing['button_text']??'')?>" placeholder="Ver comercios"></label><label>Enlace del botón<input name="button_url" maxlength="500" value="<?=e($editing['button_url']??'')?>" placeholder="commerces.php o https://..."></label></div>
<div class="admin-grid cols-2 mt-20"><label>Posición<input type="number" min="0" max="99999" name="sort_order" value="<?= (int)($editing['sort_order']??0) ?>"></label><label class="check-card"><input type="checkbox" name="active" value="1" <?=!isset($editing)||(int)$editing['active']===1?'checked':''?>> Mostrar en portada</label></div>
<button class="admin-btn mt-20">💾 Guardar banner</button></form></section>
<section class="panel-card"><div class="panel-head"><div><h2>Imágenes del slider</h2><p><?=count($slides)?> banners registrados</p></div></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Imagen</th><th>Título</th><th>Orden</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($slides as $sl):?><tr><td><img src="<?=e(url($sl['image']))?>" alt="" style="width:140px;max-width:none;aspect-ratio:16/6;object-fit:cover;border-radius:8px"></td><td><b><?=e($sl['title']?:'Sin título')?></b></td><td><?= (int)$sl['sort_order'] ?></td><td><?= $sl['active']?'Visible':'Oculto' ?></td><td><div class="table-actions"><a class="admin-btn small secondary" href="?edit=<?=$sl['id']?>">Editar</a><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="toggle"><button class="admin-btn small secondary" name="id" value="<?=$sl['id']?>"><?= $sl['active']?'Ocultar':'Activar' ?></button></form><form method="post" onsubmit="return confirm('¿Eliminar este banner?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><button class="admin-btn small danger" name="id" value="<?=$sl['id']?>">Eliminar</button></form></div></td></tr><?php endforeach;?>
<?php if(!$slides):?><tr><td colspan="5">Todavía no has cargado imágenes.</td></tr><?php endif;?></tbody></table></div></section>
<?php require __DIR__.'/partials/footer.php';
