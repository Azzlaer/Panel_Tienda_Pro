<?php
require_once __DIR__.'/../app/bootstrap.php';
require_admin();
$adminPage='templates'; $adminTitle='Gestor de plantillas'; $adminSubtitle='Inspecciona, instala y activa diseños sin tocar código';
$error=null; $inspection=null; $previewToken=null;
$cacheDir=dirname(__DIR__).'/storage/cache';
if(!is_dir($cacheDir)) @mkdir($cacheDir,0755,true);

function template_cache_file(string $token): string {
    if(!preg_match('/^[a-f0-9]{32}$/',$token)) throw new RuntimeException('Token de previsualización inválido.');
    return dirname(__DIR__).'/storage/cache/template-'.$token.'.zip';
}
function save_setting_value(string $key,string $value): void {
    db()->prepare('INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')->execute([$key,$value]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $action=$_POST['action']??'';
        if($action==='inspect'){
            if(empty($_FILES['template_zip']['tmp_name'])||$_FILES['template_zip']['error']!==UPLOAD_ERR_OK) throw new RuntimeException('Selecciona un archivo ZIP válido.');
            if((int)$_FILES['template_zip']['size']>25*1024*1024) throw new RuntimeException('El ZIP supera el máximo de 25 MB.');
            $previewToken=bin2hex(random_bytes(16)); $cache=template_cache_file($previewToken);
            if(!move_uploaded_file($_FILES['template_zip']['tmp_name'],$cache)) throw new RuntimeException('No se pudo guardar el ZIP temporalmente.');
            $inspection=inspect_template_zip($cache);
            $_SESSION['template_preview_token']=$previewToken;
        } elseif($action==='install'){
            $token=(string)($_POST['token']??''); $cache=template_cache_file($token);
            if(!is_file($cache)) throw new RuntimeException('La previsualización venció. Sube el ZIP nuevamente.');
            $inspection=inspect_template_zip($cache);
            $slug=install_template_zip($cache,$inspection); audit_log('template.installed','template',$slug,['version'=>$inspection['manifest']['version']??'']); @unlink($cache); unset($_SESSION['template_preview_token']);
            flash('success','Plantilla “'.($inspection['manifest']['name']??$slug).'” instalada correctamente.');
            redirect('../admin/templates.php');
        } elseif($action==='activate'){
            $slug=(string)($_POST['slug']??''); $m=read_template_manifest($slug); if(!$m)throw new RuntimeException('La plantilla seleccionada no existe o está dañada.');
            $compat=template_compatibility($m); if(($compat['required']??'')!=='' && !$compat['ok'] && $slug!=='urban-pro') throw new RuntimeException('La plantilla declara un layout incompatible: '.($compat['required']?:'sin declarar').'. El sistema requiere '.current_layout_version().'.');
            save_setting_value('active_template',$slug); audit_log('template.activated','template',$slug,['name'=>$m['name'],'version'=>$m['version']??'']); flash('success','Plantilla “'.$m['name'].'” activada.'); redirect('../admin/templates.php');
        } elseif($action==='delete'){
            $slug=(string)($_POST['slug']??'');
            if($slug==='urban-pro')throw new RuntimeException('Urban Pro es la plantilla base y no se puede eliminar.');
            if($slug===active_template_slug())throw new RuntimeException('No puedes eliminar la plantilla activa. Activa otra primero.');
            if(!read_template_manifest($slug))throw new RuntimeException('La plantilla no existe.');
            remove_tree(template_root().'/'.$slug); audit_log('template.deleted','template',$slug); flash('success','Plantilla eliminada.'); redirect('../admin/templates.php');
        }
    }catch(Throwable $e){$error=$e->getMessage();if(isset($cache)&&is_file($cache)&&($_POST['action']??'')==='inspect')@unlink($cache);}
}

$templates=installed_templates(); $active=active_template_slug();
require __DIR__.'/partials/header.php';
?>
<?php if($error):?><div class="admin-alert error"><?=e($error)?></div><?php endif;?>

<section class="admin-card template-intro">
 <div><span class="admin-eyebrow">SISTEMA DE TEMAS SEGURO</span><h2>🧩 Instala un diseño desde ZIP</h2><p>Primero se inspecciona el paquete. El sistema muestra autor, versión, compatibilidad y archivos antes de permitir la instalación.</p></div>
 <div class="template-security"><b>🔐 Sin PHP ejecutable</b><small>Solo CSS, JavaScript, imágenes, fuentes y documentos. Esto reduce el riesgo de instalar código malicioso.</small></div>
</section>

<div class="admin-grid-2 template-manager-grid">
 <section class="admin-card">
  <h2>📥 Subir e inspeccionar</h2>
  <form method="post" enctype="multipart/form-data" class="admin-form-stack">
   <?=csrf_field()?><input type="hidden" name="action" value="inspect">
   <label class="admin-upload-zone"><input type="file" name="template_zip" accept=".zip,application/zip" required><span>🗜️</span><b>Seleccionar template.zip</b><small>Máximo 25 MB. Debe contener template.json.</small></label>
   <button class="admin-btn primary" type="submit">🔎 Analizar archivo ZIP</button>
  </form>
  <div class="manifest-help">
   <h3>Archivo obligatorio: <code>template.json</code></h3>
   <pre>{
  "name": "Mi plantilla",
  "slug": "mi-plantilla",
  "version": "1.0.0",
  "author": "Nombre del creador",
  "description": "Descripción del diseño",
  "min_app_version": "1.6.0",
  "layout_version": "urban-2",
  "styles": ["assets/theme.css"],
  "scripts": ["assets/theme.js"]
}</pre>
  </div>
 </section>

 <section class="admin-card">
  <h2>✅ Verificaciones automáticas</h2>
  <ul class="template-check-list">
   <li><span>✓</span> ZIP legible y menor a 25 MB</li><li><span>✓</span> Protección contra rutas <code>../</code></li>
   <li><span>✓</span> Manifiesto JSON válido</li><li><span>✓</span> Nombre, slug, autor y versión</li>
   <li><span>✓</span> Compatibilidad con ComercioSocial</li><li><span>✓</span> Archivos declarados existentes</li>
   <li><span>✓</span> Bloqueo de PHP, EXE y archivos peligrosos</li><li><span>✓</span> Límite descomprimido de 60 MB</li>
  </ul>
  <div class="admin-note"><b>💡 Estructura recomendada</b><pre>mi-plantilla/
├── template.json
├── README.txt
└── assets/
    ├── theme.css
    ├── theme.js
    └── images/</pre></div>
 </section>
</div>

<?php if($inspection): $m=$inspection['manifest']; ?>
<section class="admin-card inspection-result">
 <div class="inspection-head"><div><span class="status-dot ok"></span><div><span class="admin-eyebrow">PAQUETE VÁLIDO</span><h2><?=e($m['name'])?> <small>v<?=e($m['version'])?></small></h2></div></div><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="install"><input type="hidden" name="token" value="<?=e($previewToken)?>"><button class="admin-btn success" type="submit">⬇️ Instalar esta plantilla</button></form></div>
 <div class="manifest-grid">
  <div><small>Creador</small><b>👤 <?=e($m['author'])?></b></div><div><small>Slug</small><b><code><?=e($m['slug'])?></code></b></div>
  <div><small>Versión mínima</small><b><?=e($m['min_app_version']??'2.0.0')?></b></div><div><small>Tamaño descomprimido</small><b><?=number_format($inspection['total_size']/1024,1,',','.')?> KB</b></div>
 </div>
 <p class="manifest-description"><?=e($m['description'])?></p>
 <?php if(!empty($m['tags'])&&is_array($m['tags'])):?><div class="template-tags"><?php foreach($m['tags'] as $tag):?><span>#<?=e((string)$tag)?></span><?php endforeach;?></div><?php endif;?>
 <details class="file-inspector" open><summary>📂 Ver contenido del ZIP (<?=count($inspection['files'])?> elementos)</summary><div class="zip-file-list"><?php foreach($inspection['files'] as $f):?><div><code><?=e($f['name'])?></code><small><?=number_format($f['size']/1024,1,',','.')?> KB</small></div><?php endforeach;?></div></details>
 <details class="file-inspector"><summary>📄 Ver template.json</summary><pre><?=e(json_encode($m,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></pre></details>
</section>
<?php endif;?>

<section class="admin-card">
 <div class="admin-section-title"><div><span class="admin-eyebrow">BIBLIOTECA</span><h2>Plantillas instaladas</h2></div><span class="admin-badge"><?=count($templates)?> instaladas</span></div>
 <div class="template-library">
 <?php foreach($templates as $t): $slug=$t['slug']; $isActive=$slug===$active; ?>
  <article class="template-card <?=$isActive?'active':''?>">
   <div class="template-card-preview"><?php if(!empty($t['preview'])):?><img src="<?=e(template_public_asset($slug,$t['preview']))?>" alt=""><?php else:?><div><span>🎨</span><b><?=e($t['name'])?></b></div><?php endif;?><?php if($isActive):?><em>ACTIVA</em><?php endif;?></div>
   <div class="template-card-body"><div><h3><?=e($t['name'])?></h3><span>v<?=e($t['version'])?></span></div><p><?=e($t['description'])?></p><small>Creada por <b><?=e($t['author'])?></b></small></div>
   <div class="template-card-actions">
    <?php if(!$isActive):?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="activate"><input type="hidden" name="slug" value="<?=e($slug)?>"><button class="admin-btn primary">⚡ Activar</button></form><?php else:?><a class="admin-btn" target="_blank" href="<?=url('index.php')?>">👁️ Ver sitio</a><?php endif;?>
    <?php if($slug!=='urban-pro'&&!$isActive):?><form method="post" onsubmit="return confirm('¿Eliminar esta plantilla?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="slug" value="<?=e($slug)?>"><button class="admin-btn danger">🗑️</button></form><?php endif;?>
   </div>
  </article>
 <?php endforeach;?>
 </div>
</section>
<?php require __DIR__.'/partials/footer.php'; ?>
