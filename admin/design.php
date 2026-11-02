<?php
require_once __DIR__.'/../app/bootstrap.php';
require_admin();
$adminPage='design';$adminTitle='Diseño avanzado';$adminSubtitle='Identidad básica, metadatos y CSS personalizado para ajustes finos';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    foreach(['site_name','site_tagline','seo_default_description','custom_css'] as $k) save_setting($k,trim((string)($_POST[$k]??'')));
    audit_log('design.advanced.updated','settings',null,['keys'=>['site_name','site_tagline','seo_default_description','custom_css']]);
    flash('success','Configuración avanzada de diseño actualizada.');
    redirect('../admin/design.php');
}
require __DIR__.'/partials/header.php';
?>
<div class="admin-grid cols-2">
 <section class="panel-card mt-0"><div class="panel-head"><div><h2>🏷️ Identidad y SEO base</h2><p>Valores globales usados cuando una página no define metadatos propios.</p></div></div><form method="post" class="admin-form admin-form-stack"><?=csrf_field()?>
  <label>Nombre del sitio<input name="site_name" maxlength="120" value="<?=e(setting('site_name',APP_NAME))?>"></label>
  <label>Eslogan<input name="site_tagline" maxlength="220" value="<?=e(setting('site_tagline'))?>"></label>
  <label>Descripción SEO predeterminada<textarea name="seo_default_description" rows="4" maxlength="320" placeholder="Describe ComercioSocial para buscadores y redes sociales."><?=e(setting('seo_default_description',setting('site_tagline')))?></textarea></label>
  <label>CSS personalizado<textarea class="builder-code" name="custom_css" rows="18" spellcheck="false"><?=e(setting('custom_css'))?></textarea></label>
  <div class="admin-form-actions"><button class="admin-btn">💾 Guardar cambios</button><a class="admin-btn secondary" href="site_builder.php">🎨 Abrir constructor visual</a></div>
 </form></section>
 <aside class="panel-card mt-0 design-guidance"><div class="panel-head"><div><h2>🧭 Cuándo usar esta sección</h2><p>El constructor visual sigue siendo el editor principal.</p></div></div>
  <div class="notice-box">Usa <b>Diseño y portada</b> para imágenes, colores, banners y monetización. Esta pantalla queda reservada para identidad global, SEO base y CSS de precisión.</div>
  <div class="health-row"><span>Constructor visual</span><b class="health-ok">Recomendado</b></div>
  <div class="health-row"><span>CSS personalizado</span><b>Avanzado</b></div>
  <div class="health-row"><span>Metadatos SEO</span><b>Globales</b></div>
  <p class="admin-help-text">Evita reglas CSS demasiado amplias como <code>*{...}</code> o estilos sobre <code>body</code> que puedan afectar el administrador.</p>
 </aside>
</div>
<?php require __DIR__.'/partials/footer.php';?>
