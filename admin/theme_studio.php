<?php
require_once __DIR__.'/../app/bootstrap.php';require_admin();
$slug=active_template_slug();$sections=studio_sections();$themes=installed_templates();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $action=(string)($_POST['action']??'save');
 if($action==='reset'){$config=studio_defaults();}
 else {
  $config=studio_normalize(['primary'=>$_POST['primary']??'', 'accent'=>$_POST['accent']??'', 'surface'=>$_POST['surface']??'', 'ink'=>$_POST['ink']??'', 'font'=>$_POST['font']??'', 'radius'=>$_POST['radius']??'', 'content_width'=>$_POST['content_width']??'', 'card_columns'=>$_POST['card_columns']??'', 'hero_height'=>$_POST['hero_height']??'', 'header_style'=>$_POST['header_style']??'', 'hide'=>$_POST['hide']??[], 'order'=>array_filter(explode(',',(string)($_POST['order']??'')))]);
 }
 save_setting('studio_theme_'.$slug,json_encode($config,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
 audit_log('theme_studio.saved','template',$slug,['action'=>$action]);
 flash('success',$action==='reset'?'Diseño restablecido.':'Cambios visuales publicados en el tema '.$slug.'.');
 redirect('../admin/theme_studio.php');
}
$c=studio_config($slug);$adminPage='studio';$adminTitle='Theme Studio PRO';$adminSubtitle='Editor visual de apariencia por plantilla, sin modificar sus archivos originales';require __DIR__.'/partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/theme-studio-admin.css')?>">
<div class="studio-header"><div><h2>🖌️ Diseña tu sitio en vivo</h2><p>Los cambios se guardan únicamente para <strong><?=e($slug)?></strong>. Puedes editar otros temas sin perder esta configuración.</p></div><div><a class="admin-btn secondary" href="templates.php">🧩 Elegir plantilla</a><a class="admin-btn secondary" href="<?=url('index.php')?>" target="_blank" rel="noopener">↗ Abrir sitio</a></div></div>
<form id="studioForm" method="post" class="studio-layout"><?=csrf_field()?><input type="hidden" name="action" value="save"><input type="hidden" name="order" id="studioOrder" value="<?=e(implode(',',$c['order']))?>">
<div class="studio-controls">
<section class="admin-card"><h3>🎨 Identidad visual</h3><div class="studio-colors"><?php foreach(['primary'=>'Principal','accent'=>'Acento','surface'=>'Fondo de tarjetas','ink'=>'Texto'] as $key=>$label):?><label><?=e($label)?><input type="color" name="<?=e($key)?>" value="<?=e($c[$key])?>"></label><?php endforeach;?></div><label>Tipografía<select name="font"><?php foreach(['system'=>'Sistema','modern'=>'Moderna','editorial'=>'Editorial / Serif','rounded'=>'Redondeada'] as $v=>$label):?><option value="<?=$v?>" <?=$c['font']===$v?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></label><label>Encabezado<select name="header_style"><?php foreach(['standard'=>'Normal','compact'=>'Compacto','centered'=>'Centrado'] as $v=>$label):?><option value="<?=$v?>" <?=$c['header_style']===$v?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></label></section>
<section class="admin-card"><h3>📐 Distribución y tamaños</h3><?php foreach(['radius'=>['Redondeo (px)',0,32,1],'content_width'=>['Ancho máximo (px)',980,1540,20],'card_columns'=>['Columnas de productos',2,5,1],'hero_height'=>['Altura de portada (px)',230,650,10]] as $key=>$info):?><label class="studio-range"><span><?=e($info[0])?> <output><?=e((string)$c[$key])?></output></span><input type="range" name="<?=e($key)?>" min="<?=$info[1]?>" max="<?=$info[2]?>" step="<?=$info[3]?>" value="<?=$c[$key]?>"></label><?php endforeach;?></section>
<section class="admin-card"><h3>🧱 Secciones de portada</h3><p>Usa las flechas para ordenar; desmarca para ocultar una sección.</p><div id="studioSections"><?php foreach($c['order'] as $id):?><div class="studio-section" data-key="<?=e($id)?>"><label><input type="checkbox" name="visible[]" value="<?=e($id)?>" <?=in_array($id,$c['hide'],true)?'':'checked'?>> <?=e($sections[$id])?></label><span><button type="button" class="studio-move" data-direction="up" aria-label="Subir">↑</button><button type="button" class="studio-move" data-direction="down" aria-label="Bajar">↓</button></span></div><?php endforeach;?></div><div id="studioHidden"></div></section>
<div class="studio-save"><button type="submit" class="admin-btn primary">💾 Publicar diseño</button><button type="button" id="studioReset" class="admin-btn secondary">↩ Restablecer</button></div>
</div>
<div class="studio-preview admin-card"><div class="studio-preview-top"><strong>Vista previa sin publicar</strong><span><button type="button" data-width="100%">Escritorio</button><button type="button" data-width="780px">Tablet</button><button type="button" data-width="390px">Móvil</button></span></div><div class="studio-frame-wrap"><iframe title="Vista previa del sitio" id="studioFrame" src="<?=url('index.php')?>" loading="lazy"></iframe></div><small>La vista previa utiliza los datos reales del sitio. Los cambios sólo son definitivos al pulsar Publicar diseño.</small></div>
</form>
<script src="<?=url('assets/js/theme-studio-admin.js')?>" defer></script>
<?php require __DIR__.'/partials/footer.php';
