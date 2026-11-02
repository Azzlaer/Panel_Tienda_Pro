<?php
require_once __DIR__.'/../app/bootstrap.php'; require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  $logo=upload_image('logo_image_file','design');
  $home=upload_image('home_cover_file','design');
  $promo=upload_image('promo_cover_file','design');
  $keys=['home_title','home_kicker','home_description','home_cover','promo_kicker','promo_title','promo_description','promo_cover','top_phone','top_email','logo_icon','logo_image','footer_address','newsletter_text','home_show_register','adsense_enabled','adsense_client','adsense_home_code','adsense_article_code','usd_clp_manual','primary_color','secondary_color','button_radius','custom_css'];
  $st=db()->prepare('INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
  foreach($keys as $k){
   $v=in_array($k,['home_show_register','adsense_enabled'],true)?(isset($_POST[$k])?'1':'0'):trim((string)($_POST[$k]??setting($k)));
   if($k==='logo_image'&&$logo)$v=$logo; if($k==='home_cover'&&$home)$v=$home; if($k==='promo_cover'&&$promo)$v=$promo;
   $st->execute([$k,$v]);
  }
  flash('success','Diseño, imágenes y monetización guardados correctamente.');
 }catch(Throwable $e){flash('error',$e->getMessage());}
 redirect('../admin/site_builder.php');
}
$adminPage='builder';$adminTitle='Diseño y portada';$adminSubtitle='Centro visual para identidad, portada, banners, colores y monetización';require __DIR__.'/partials/header.php';
?>
<form method="post" enctype="multipart/form-data" class="builder-pro"><?=csrf_field()?>
<section class="builder-hero admin-card"><div><span class="admin-eyebrow">CONSTRUCTOR VISUAL</span><h2>Personaliza tu comercio sin editar archivos</h2><p>Sube tu logo, cambia portadas, ajusta colores y revisa una vista previa antes de guardar.</p></div><div class="builder-actions"><a class="admin-btn secondary" target="_blank" href="<?=url('index.php')?>">↗ Vista pública</a><button class="admin-btn primary">💾 Guardar cambios</button></div></section>
<div class="builder-layout">
 <div class="builder-main">
  <section class="admin-card builder-section"><div class="builder-section-head"><span>1</span><div><h2>Identidad de marca</h2><p>Logo, nombre visual y datos de contacto.</p></div></div>
   <div class="builder-logo-grid"><label class="builder-upload"><input type="file" name="logo_image_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="#logoPreview"><div id="logoPreview" class="builder-image-preview logo-preview"><?php if(setting('logo_image')):?><img src="<?=url(setting('logo_image'))?>"><?php else:?><strong><?=e(setting('logo_icon','🛍️'))?></strong><?php endif;?></div><b>Subir imagen del logo</b><small>PNG o WEBP transparente recomendado, hasta <?=round(MAX_UPLOAD_BYTES/1048576)?> MB.</small></label>
   <div class="admin-form-stack"><label>Emoji alternativo<input name="logo_icon" value="<?=e(setting('logo_icon','🛍️'))?>"></label><label>Ruta actual del logo<input name="logo_image" value="<?=e(setting('logo_image'))?>" placeholder="uploads/design/logo.webp"></label><div class="form-grid"><label>Teléfono<input name="top_phone" value="<?=e(setting('top_phone'))?>"></label><label>Correo público<input type="email" name="top_email" value="<?=e(setting('top_email'))?>"></label></div></div></div>
  </section>
  <section class="admin-card builder-section"><div class="builder-section-head"><span>2</span><div><h2>Portada principal</h2><p>Mensaje e imagen que reciben a tus visitantes.</p></div></div>
   <div class="builder-cover-preview" id="homePreview" style="background-image:linear-gradient(90deg,rgba(14,24,40,.78),rgba(14,24,40,.20)),url('<?=url(setting('home_cover','assets/cover.svg'))?>')"><small><?=e(setting('home_kicker','TIENDAS LOCALES · GRANDES HISTORIAS'))?></small><strong><?=e(setting('home_title',setting('site_name')))?></strong><p><?=e(setting('home_description'))?></p></div>
   <div class="form-grid"><label>Título<input name="home_title" value="<?=e(setting('home_title',setting('site_name')))?>"></label><label>Texto superior<input name="home_kicker" value="<?=e(setting('home_kicker','TIENDAS LOCALES · GRANDES HISTORIAS'))?>"></label></div><label>Descripción<textarea name="home_description" rows="4"><?=e(setting('home_description'))?></textarea></label><div class="form-grid"><label>Subir nueva portada<input type="file" name="home_cover_file" accept="image/*" data-background-preview="#homePreview"></label><label>Ruta o URL actual<input name="home_cover" value="<?=e(setting('home_cover','assets/cover.svg'))?>"></label></div><label class="check-card"><input type="checkbox" name="home_show_register" <?=setting('home_show_register','1')==='1'?'checked':''?>> Mostrar botón de registro de vendedores</label>
  </section>
  <section class="admin-card builder-section"><div class="builder-section-head"><span>3</span><div><h2>Promociones</h2><p>Banner secundario para campañas y temporadas.</p></div></div><div class="form-grid"><label>Texto pequeño<input name="promo_kicker" value="<?=e(setting('promo_kicker','PROMOCIONES DE TEMPORADA'))?>"></label><label>Título<input name="promo_title" value="<?=e(setting('promo_title','Descubre ofertas de comercios locales'))?>"></label></div><label>Descripción<textarea name="promo_description" rows="3"><?=e(setting('promo_description'))?></textarea></label><div class="form-grid"><label>Subir banner<input type="file" name="promo_cover_file" accept="image/*"></label><label>Ruta o URL<input name="promo_cover" value="<?=e(setting('promo_cover'))?>"></label></div></section>
  <section class="admin-card builder-section"><div class="builder-section-head"><span>4</span><div><h2>Apariencia avanzada</h2><p>Colores globales, esquinas y CSS personalizado.</p></div></div><div class="builder-color-grid"><label>Color principal<input type="color" name="primary_color" value="<?=e(setting('primary_color','#2563eb'))?>"><b><?=e(setting('primary_color','#2563eb'))?></b></label><label>Color secundario<input type="color" name="secondary_color" value="<?=e(setting('secondary_color','#f59e0b'))?>"><b><?=e(setting('secondary_color','#f59e0b'))?></b></label><label>Radio de botones<input name="button_radius" value="<?=e(setting('button_radius','14px'))?>" placeholder="14px"></label></div><label>CSS personalizado<textarea class="builder-code" name="custom_css" rows="13" spellcheck="false"><?=e(setting('custom_css'))?></textarea></label></section>
 </div>
 <aside class="builder-sidebar">
  <section class="admin-card builder-sticky"><h2>Configuración adicional</h2><label>Dirección del pie<input name="footer_address" value="<?=e(setting('footer_address','Chile'))?>"></label><label>Texto de novedades<textarea name="newsletter_text" rows="4"><?=e(setting('newsletter_text'))?></textarea></label><hr><h3>💱 Conversión monetaria</h3><label>1 USD en CLP<input type="number" step="0.01" name="usd_clp_manual" value="<?=e(setting('usd_clp_manual','0'))?>"><small>Usa 0 para consulta automática.</small></label><hr><h3>💰 Google AdSense</h3><label class="check-card"><input type="checkbox" name="adsense_enabled" <?=setting('adsense_enabled')==='1'?'checked':''?>> Activar espacios AdSense</label><label>Publisher ID<input name="adsense_client" value="<?=e(setting('adsense_client'))?>" placeholder="ca-pub-..."></label><details><summary>Códigos de anuncios</summary><label>Portada<textarea name="adsense_home_code" rows="6"><?=e(setting('adsense_home_code'))?></textarea></label><label>Artículos<textarea name="adsense_article_code" rows="6"><?=e(setting('adsense_article_code'))?></textarea></label></details><button class="admin-btn primary wide">💾 Guardar configuración</button></section>
 </aside>
</div></form>
<script>document.querySelectorAll('[data-preview]').forEach(i=>i.addEventListener('change',()=>{const f=i.files[0],t=document.querySelector(i.dataset.preview);if(f&&t){const r=new FileReader;r.onload=e=>t.innerHTML='<img src="'+e.target.result+'">';r.readAsDataURL(f)}}));document.querySelectorAll('[data-background-preview]').forEach(i=>i.addEventListener('change',()=>{const f=i.files[0],t=document.querySelector(i.dataset.backgroundPreview);if(f&&t){const r=new FileReader;r.onload=e=>t.style.backgroundImage='linear-gradient(90deg,rgba(14,24,40,.78),rgba(14,24,40,.20)),url("'+e.target.result+'")';r.readAsDataURL(f)}}));</script>
<?php require __DIR__.'/partials/footer.php';?>
