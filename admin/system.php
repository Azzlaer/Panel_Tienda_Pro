<?php
require_once __DIR__.'/../app/bootstrap.php';
require_admin();
ensure_core_v16_schema();
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $fields=[
      'showcase_notice','showcase_price_label','showcase_contact_label'
    ];
    save_setting('platform_mode','showcase');
    save_setting('showcase_notice_enabled',isset($_POST['showcase_notice_enabled'])?'1':'0');
    save_setting('showcase_hide_stock',isset($_POST['showcase_hide_stock'])?'1':'0');
    save_setting('showcase_hide_sales_language',isset($_POST['showcase_hide_sales_language'])?'1':'0');
    foreach($fields as $field) save_setting($field,trim((string)($_POST[$field]??'')));
    audit_log('system.showcase_settings_updated','settings','showcase',[ 'notice_enabled'=>isset($_POST['showcase_notice_enabled']) ]);
    flash('success','Configuración del modo vitrina actualizada.');
    redirect('../admin/system.php');
}
$adminPage='system';$adminTitle='Modo vitrina';$adminSubtitle='Define claramente que la plataforma solo exhibe información y facilita contacto';
require __DIR__.'/partials/header.php';
?>
<section class="admin-card showcase-admin-hero">
 <div><span class="admin-eyebrow">MODELO DE PLATAFORMA</span><h2>🪟 Vitrina informativa, sin comercio electrónico</h2><p>La web exhibe artículos, productos o servicios publicados por vendedores. No procesa pagos, compras, reservas, contratos, boletas ni facturas.</p></div>
 <div class="showcase-mode-badge">MODO ACTIVO<br><b>SOLO EXHIBICIÓN</b></div>
</section>
<form method="post" class="admin-grid-2 showcase-settings-grid">
 <?=csrf_field()?>
 <section class="admin-card admin-form-stack">
  <h2>📢 Aviso público</h2>
  <label class="admin-switch"><input type="checkbox" name="showcase_notice_enabled" <?=setting('showcase_notice_enabled','1')==='1'?'checked':''?>><span></span><b>Mostrar aviso de vitrina en todas las páginas</b></label>
  <label>Texto del aviso<textarea name="showcase_notice" rows="6" maxlength="1200" required><?=e(setting('showcase_notice'))?></textarea></label>
  <div class="admin-note"><b>Vista previa</b><p>ℹ️ <?=e(setting('showcase_notice'))?></p></div>
 </section>
 <section class="admin-card admin-form-stack">
  <h2>🏷️ Etiquetas neutrales</h2>
  <label>Texto sobre el precio<input name="showcase_price_label" maxlength="80" value="<?=e(setting('showcase_price_label','Valor publicado'))?>"></label>
  <label>Texto del contacto<input name="showcase_contact_label" maxlength="100" value="<?=e(setting('showcase_contact_label','Consultar al anunciante'))?>"></label>
  <label class="admin-switch"><input type="checkbox" name="showcase_hide_stock" <?=setting('showcase_hide_stock','1')==='1'?'checked':''?>><span></span><b>Ocultar stock y lenguaje de inventario</b></label>
  <label class="admin-switch"><input type="checkbox" name="showcase_hide_sales_language" <?=setting('showcase_hide_sales_language','1')==='1'?'checked':''?>><span></span><b>Evitar textos como comprar, carrito, venta o pago</b></label>
 </section>
 <section class="admin-card showcase-boundaries">
  <h2>✅ La plataforma sí hace</h2>
  <ul><li>Exhibir publicaciones y servicios.</li><li>Mostrar datos entregados por cada anunciante.</li><li>Facilitar contacto por teléfono, WhatsApp o Maps.</li><li>Publicar noticias, promociones informativas y contenido.</li></ul>
 </section>
 <section class="admin-card showcase-boundaries danger-zone-soft">
  <h2>⛔ La plataforma no hace</h2>
  <ul><li>No cobra ni recibe pagos por publicaciones.</li><li>No confirma compras, ventas ni reservas.</li><li>No emite boletas, facturas ni comprobantes comerciales.</li><li>No garantiza precios, disponibilidad, entrega o acuerdos externos.</li></ul>
 </section>
 <div class="admin-form-actions full"><button class="admin-btn primary" type="submit">💾 Guardar modo vitrina</button><a class="admin-btn" target="_blank" href="<?=url('index.php')?>">👁️ Revisar sitio público</a></div>
</form>
<?php require __DIR__.'/partials/footer.php'; ?>
