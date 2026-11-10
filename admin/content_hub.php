<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_admin();
require_once __DIR__.'/../app/content_hub.php';
$adminPage='hub';
$adminTitle='Content Hub PRO';
$adminSubtitle='Artículos, guías y categorías editoriales';
$ready=hub_ready(db());
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $action=(string)($_POST['action']??'');
 if($action==='install'){
  try{hub_install(db());flash('success','Content Hub activado.');audit_log('hub.install');}
  catch(Throwable $e){error_log('Content Hub install: '.$e->getMessage());flash('error','No se pudo activar. Revisa el registro del servidor.');}
  redirect('../admin/content_hub.php');
 }
 if(!$ready){http_response_code(503);exit('Activa Content Hub antes de editar.');}
 try {
  if($action==='category'){
   $id=(int)($_POST['id']??0);$name=trim((string)($_POST['name']??''));$description=mb_substr(trim((string)($_POST['description']??'')),0,350);
   if(mb_strlen($name)<2||mb_strlen($name)>100)throw new InvalidArgumentException('Nombre de categoría inválido.');
   $slug=hub_slug(trim((string)($_POST['slug']??''))?:$name);
   if($id>0){$q=db()->prepare('UPDATE hub_categories SET name=?,slug=?,description=? WHERE id=?');$q->execute([$name,$slug,$description,$id]);}
   else {$q=db()->prepare('INSERT INTO hub_categories(name,slug,description) VALUES(?,?,?)');$q->execute([$name,$slug,$description]);}
   flash('success','Categoría guardada.');
  } elseif($action==='delete_category'){
   $id=(int)($_POST['id']??0);
   if($id<1)throw new InvalidArgumentException('Categoría inválida.');
   $q=db()->prepare('DELETE FROM hub_categories WHERE id=?');$q->execute([$id]);
   audit_log('hub.category_delete','category',$id);
   flash('success','Categoría eliminada. Sus artículos se conservan sin categoría.');
  } elseif($action==='save_article'){
   $id=(int)($_POST['id']??0);$title=trim((string)($_POST['title']??''));$summary=mb_substr(trim((string)($_POST['summary']??'')),0,600);
   $body=trim((string)($_POST['body']??''));$slug=hub_slug(trim((string)($_POST['slug']??''))?:$title);
   $status=hub_status((string)($_POST['status']??'draft'));
   $category=(int)($_POST['category_id']??0);$category=$category>0?$category:null;
   $tags=mb_substr(trim((string)($_POST['tags']??'')),0,400);
   $seoTitle=mb_substr(trim((string)($_POST['seo_title']??'')),0,190);
   $seoDescription=mb_substr(trim((string)($_POST['seo_description']??'')),0,300);
   if(mb_strlen($title)<5||mb_strlen($title)>180||mb_strlen($body)<10||mb_strlen($body)>100000)throw new InvalidArgumentException('Título o contenido fuera de rango.');
   $cover=upload_image('cover_image','content');
   if($id>0){
    $q=db()->prepare('SELECT cover_image,published_at FROM hub_articles WHERE id=?');$q->execute([$id]);$old=$q->fetch();
    if(!$old)throw new RuntimeException('Artículo inexistente.');
    $cover=$cover?:$old['cover_image'];
    $published=$status==='published'?($old['published_at']?:date('Y-m-d H:i:s')):null;
    $q=db()->prepare('UPDATE hub_articles SET category_id=?,title=?,slug=?,summary=?,body=?,cover_image=?,tags=?,seo_title=?,seo_description=?,status=?,featured=?,published_at=?,updated_at=NOW() WHERE id=?');
    $q->execute([$category,$title,$slug,$summary,$body,$cover,$tags,$seoTitle,$seoDescription,$status,isset($_POST['featured'])?1:0,$published,$id]);
   }else{
    $q=db()->prepare('INSERT INTO hub_articles(category_id,author_id,title,slug,summary,body,cover_image,tags,seo_title,seo_description,status,featured,published_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([$category,(int)user()['id'],$title,$slug,$summary,$body,$cover,$tags,$seoTitle,$seoDescription,$status,isset($_POST['featured'])?1:0,$status==='published'?date('Y-m-d H:i:s'):null]);
   }
   audit_log('hub.article_save','article',$id?:db()->lastInsertId());flash('success','Artículo guardado.');
  }elseif($action==='delete_article'){
   $id=(int)($_POST['id']??0);
   $q=db()->prepare('DELETE FROM hub_articles WHERE id=?');$q->execute([$id]);
   audit_log('hub.article_delete','article',$id);flash('success','Artículo eliminado.');
  }else throw new InvalidArgumentException('Acción no permitida.');
 }catch(Throwable $e){
  if($e instanceof PDOException){error_log('Content Hub SQL: '.$e->getMessage());flash('error','No se pudo guardar. Comprueba que el slug sea único y la categoría exista.');}
  else flash('error',$e->getMessage());
 }
 redirect('../admin/content_hub.php');
}
$categories=[];$articles=[];$editing=null;
if($ready){
 $categories=db()->query('SELECT * FROM hub_categories ORDER BY name ASC')->fetchAll();
 $articles=db()->query('SELECT a.id,a.title,a.slug,a.status,a.views,a.featured,a.updated_at,a.created_at,c.name AS category_name FROM hub_articles a LEFT JOIN hub_categories c ON c.id=a.category_id ORDER BY a.id DESC LIMIT 250')->fetchAll();
 $edit=(int)($_GET['edit']??0);
 if($edit>0){$q=db()->prepare('SELECT * FROM hub_articles WHERE id=?');$q->execute([$edit]);$editing=$q->fetch()?:null;}
}
require __DIR__.'/partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/content-hub-admin.css')?>">
<div class="hub-admin">
<?php if(!$ready):?>
<section class="panel-card"><h2>Activar Content Hub</h2><p>La instalación crea tres tablas nuevas. No modifica noticias, productos, plantillas ni usuarios. Realiza un respaldo MySQL primero.</p><form method="post"><?=csrf_field()?><button name="action" value="install" class="admin-btn">Activar módulo</button></form></section>
<?php else:?>
<section class="panel-card"><div class="panel-head"><div><h2>Centro editorial</h2><p>Publica tutoriales, entrevistas, eventos y guías. Las noticias existentes siguen independientes.</p></div><a class="admin-btn" href="<?=url('content_hub.php')?>" target="_blank" rel="noopener">Ver contenido público</a></div></section>
<div class="hub-admin-grid">
<section class="panel-card">
 <h2><?=$editing?'Editar artículo':'Nuevo artículo'?></h2>
 <form method="post" enctype="multipart/form-data" class="admin-form hub-form"><?=csrf_field()?>
  <input type="hidden" name="action" value="save_article"><input type="hidden" name="id" value="<?=(int)($editing['id']??0)?>">
  <label>Título<input name="title" required minlength="5" maxlength="180" value="<?=e($editing['title']??'')?>"></label>
  <label>Slug URL<input name="slug" maxlength="180" value="<?=e($editing['slug']??'')?>" placeholder="Se genera desde el título"></label>
  <label>Categoría<select name="category_id"><option value="0">Sin categoría</option><?php foreach($categories as $c):?><option value="<?=(int)$c['id']?>" <?=(int)($editing['category_id']??0)===(int)$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach;?></select></label>
  <label>Resumen<textarea name="summary" rows="3" maxlength="600"><?=e($editing['summary']??'')?></textarea></label>
  <label>Contenido (texto seguro, admite párrafos)<textarea name="body" required rows="12"><?=e($editing['body']??'')?></textarea></label>
  <label>Imagen destacada<input type="file" name="cover_image" accept="image/png,image/jpeg,image/webp,image/gif"></label>
  <label>Etiquetas separadas por comas<input name="tags" maxlength="400" value="<?=e($editing['tags']??'')?>"></label>
  <label>Título SEO<input name="seo_title" maxlength="190" value="<?=e($editing['seo_title']??'')?>"></label>
  <label>Descripción SEO<textarea name="seo_description" rows="2" maxlength="300"><?=e($editing['seo_description']??'')?></textarea></label>
  <label>Estado<select name="status"><?php foreach(['draft'=>'Borrador','published'=>'Publicado','hidden'=>'Oculto'] as $key=>$label):?><option value="<?=$key?>" <?=($editing['status']??'draft')===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></label>
  <label class="hub-check"><input type="checkbox" name="featured" <?=!empty($editing['featured'])?'checked':''?>> Destacado</label>
  <button class="admin-btn">Guardar artículo</button> <?php if($editing):?><a href="content_hub.php">Nuevo</a><?php endif;?>
 </form>
</section>
<section class="hub-sidebar">
 <section class="panel-card"><h2>Categorías</h2><form class="admin-form hub-form" method="post"><?=csrf_field()?><input type="hidden" name="action" value="category"><label>Nombre<input name="name" required maxlength="100"></label><label>Slug<input name="slug" placeholder="Automático"></label><label>Descripción<input name="description" maxlength="350"></label><button class="admin-btn">Agregar categoría</button></form>
<div class="hub-list" style="margin-top:17px"><?php foreach($categories as $c):?><div class="hub-admin-item"><form class="admin-form hub-form" method="post"><?=csrf_field()?><input type="hidden" name="action" value="category"><input type="hidden" name="id" value="<?=(int)$c['id']?>"><label>Nombre<input name="name" required maxlength="100" value="<?=e($c['name'])?>"></label><label>Slug<input name="slug" required maxlength="130" value="<?=e($c['slug'])?>"></label><label>Descripción<input name="description" maxlength="350" value="<?=e($c['description'])?>"></label><button class="admin-btn">Guardar categoría</button></form><form method="post" onsubmit="return confirm('¿Eliminar la categoría? Los artículos se conservarán sin categoría.')"><?=csrf_field()?><input type="hidden" name="action" value="delete_category"><button class="hub-delete" name="id" value="<?=(int)$c['id']?>">Eliminar categoría</button></form></div><?php endforeach;?></div></section>
 <section class="panel-card"><h2>Artículos recientes</h2><div class="hub-list"><?php foreach($articles as $a):?><div class="hub-admin-item"><strong><?=e($a['title'])?></strong><small><?=e($a['status'])?> · <?=e($a['category_name']??'Sin categoría')?> · <?=(int)$a['views']?> vistas</small><div><a href="content_hub.php?edit=<?=(int)$a['id']?>">Editar</a> · <a href="<?=url('content_article.php?slug='.rawurlencode($a['slug']))?>" target="_blank" rel="noopener">Ver</a><form method="post" onsubmit="return confirm('¿Eliminar este artículo?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_article"><button class="hub-delete" name="id" value="<?=(int)$a['id']?>">Eliminar</button></form></div></div><?php endforeach;?></div></section>
 </section>
</div>
<?php endif;?>
</div>
<?php require __DIR__.'/partials/footer.php';
