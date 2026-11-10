<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_admin();
require_once __DIR__.'/../app/content_hub.php';
$adminPage='page_builder';$adminTitle='Page Builder PRO';$adminSubtitle='Constructor visual por bloques sin PHP ni HTML arbitrario';
$ready=hub_ready(db());
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'');
 if(!$ready){http_response_code(503);exit('Activa Content Hub antes de editar páginas.');}
 try {
  if($action==='delete'){
   $id=(int)($_POST['id']??0);$q=db()->prepare('DELETE FROM hub_pages WHERE id=?');$q->execute([$id]);
   audit_log('hub.page_delete','page',$id);flash('success','Página eliminada.');
  }elseif($action==='save'){
   $id=(int)($_POST['id']??0);$title=trim((string)($_POST['title']??''));
   $slug=hub_slug((string)(trim((string)($_POST['slug']??''))?:$title));
   if(mb_strlen($title)<3||mb_strlen($title)>180)throw new InvalidArgumentException('Título inválido.');
   $blocks=hub_normalize_blocks((string)($_POST['blocks_json']??'[]'));
   $status=hub_status((string)($_POST['status']??'draft'));
   $summary=mb_substr(trim((string)($_POST['summary']??'')),0,600);
   $seoTitle=mb_substr(trim((string)($_POST['seo_title']??'')),0,190);
   $seoDescription=mb_substr(trim((string)($_POST['seo_description']??'')),0,300);
   $nav=isset($_POST['show_navigation'])?1:0;
   $sort=max(-1000,min(1000,(int)($_POST['sort_order']??0)));
   if($id>0){
    $q=db()->prepare('SELECT published_at FROM hub_pages WHERE id=?');$q->execute([$id]);$old=$q->fetch();
    if(!$old)throw new RuntimeException('Página no encontrada.');
    $published=$status==='published'?($old['published_at']?:date('Y-m-d H:i:s')):null;
    $q=db()->prepare('UPDATE hub_pages SET title=?,slug=?,summary=?,blocks_json=?,seo_title=?,seo_description=?,status=?,show_navigation=?,sort_order=?,published_at=?,updated_at=NOW() WHERE id=?');
    $q->execute([$title,$slug,$summary,$blocks,$seoTitle,$seoDescription,$status,$nav,$sort,$published,$id]);
   }else{
    $q=db()->prepare('INSERT INTO hub_pages(author_id,title,slug,summary,blocks_json,seo_title,seo_description,status,show_navigation,sort_order,published_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([(int)user()['id'],$title,$slug,$summary,$blocks,$seoTitle,$seoDescription,$status,$nav,$sort,$status==='published'?date('Y-m-d H:i:s'):null]);
   }
   audit_log('hub.page_save','page',$id?:db()->lastInsertId());flash('success','Página guardada.');
  }else throw new InvalidArgumentException('Acción inválida.');
 }catch(Throwable $e){
  if($e instanceof PDOException){error_log('Page Builder SQL: '.$e->getMessage());flash('error','Error al guardar. Verifica que el slug no esté duplicado.');}
  else flash('error',$e->getMessage());
 }
 redirect('../admin/page_builder.php');
}
$editing=null;$pages=[];
if($ready){
 $id=(int)($_GET['edit']??0);
 if($id){$q=db()->prepare('SELECT * FROM hub_pages WHERE id=?');$q->execute([$id]);$editing=$q->fetch()?:null;}
 $pages=db()->query('SELECT id,title,slug,status,show_navigation,sort_order,created_at FROM hub_pages ORDER BY sort_order,id DESC LIMIT 250')->fetchAll();
}
require __DIR__.'/partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/content-hub-admin.css')?>">
<div class="hub-admin">
<?php if(!$ready):?><section class="panel-card"><h2>Page Builder no activado</h2><p>Primero activa el módulo desde <a href="content_hub.php">Content Hub PRO</a>.</p></section>
<?php else:?>
<section class="panel-card"><div class="panel-head"><div><h2>Page Builder PRO</h2><p>Agrega, ordena y configura bloques. El navegador genera el JSON; PHP valida antes de publicar.</p></div><a class="admin-btn" href="page_builder.php">Nueva página</a></div></section>
<div class="hub-admin-grid">
<section class="panel-card"><h2><?=$editing?'Editar página':'Crear página'?></h2>
<form class="admin-form hub-form" method="post" id="hubPageForm"><?=csrf_field()?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=(int)($editing['id']??0)?>">
<label>Título<input name="title" required minlength="3" maxlength="180" value="<?=e($editing['title']??'')?>"></label>
<label>URL (slug)<input name="slug" maxlength="180" placeholder="Automático desde el título" value="<?=e($editing['slug']??'')?>"></label>
<label>Descripción<input name="summary" maxlength="600" value="<?=e($editing['summary']??'')?>"></label>
<div class="hub-builder-head"><h3>Bloques del contenido</h3><label>Agregar bloque<select id="hubAddType"><option value="hero">Portada</option><option value="heading">Título</option><option value="text">Texto</option><option value="image">Imagen</option><option value="callout">Destacado</option><option value="cta">Botón / CTA</option><option value="divider">Separador</option></select></label><button type="button" id="hubAddBlock" class="admin-btn">+ Añadir bloque</button></div>
<div id="hubBlocks" class="hub-blocks-editor"></div>
<textarea id="hubBlockJson" name="blocks_json" hidden><?=e($editing['blocks_json']??'[]')?></textarea>
<label>Estado<select name="status"><?php foreach(['draft'=>'Borrador','published'=>'Publicado','hidden'=>'Oculto'] as $key=>$label):?><option value="<?=$key?>" <?=($editing['status']??'draft')===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></label>
<label class="hub-check"><input type="checkbox" name="show_navigation" <?=!empty($editing['show_navigation'])?'checked':''?>> Mostrar enlace en el menú público</label>
<label>Orden de navegación<input type="number" name="sort_order" min="-1000" max="1000" value="<?=(int)($editing['sort_order']??0)?>"></label>
<label>Título SEO<input name="seo_title" maxlength="190" value="<?=e($editing['seo_title']??'')?>"></label>
<label>Descripción SEO<input name="seo_description" maxlength="300" value="<?=e($editing['seo_description']??'')?></label>
<button class="admin-btn" type="submit">Guardar página</button>
</form></section>
<section class="hub-sidebar">
<section class="panel-card"><h2>Páginas existentes</h2><div class="hub-list"><?php foreach($pages as $p):?><div class="hub-admin-item"><strong><?=e($p['title'])?></strong><small><?=e($p['status'])?> · <?=e($p['slug'])?></small><div><a href="page_builder.php?edit=<?=(int)$p['id']?>">Editar</a> · <a href="<?=url('page.php?slug='.rawurlencode($p['slug']))?>" target="_blank" rel="noopener">Ver pública</a><form method="post" onsubmit="return confirm('¿Eliminar página?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><button class="hub-delete" name="id" value="<?=(int)$p['id']?>">Eliminar</button></form></div></div><?php endforeach;?></div></section>
<section class="panel-card"><h2>Vista previa</h2><p>Esta vista muestra una aproximación textual del contenido. Guarda y abre la página publicada para verificar el diseño exacto.</p><div id="hubPreview" class="hub-preview"></div></section>
</section></div>
<script src="<?=url('assets/js/content-page-builder.js')?>" defer></script>
<?php endif;?></div>
<?php require __DIR__.'/partials/footer.php';
