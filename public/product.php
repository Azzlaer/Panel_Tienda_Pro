<?php
require_once __DIR__.'/../app/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$q = db()->prepare(
    "SELECT pr.*, c.name AS category_name, u.username, p.*,
            p.show_usd AS show_usd
     FROM products pr
     JOIN categories c ON c.id = pr.category_id
     JOIN users u ON u.id = pr.user_id
     JOIN profiles p ON p.user_id = u.id
     WHERE pr.id = ?
       AND (pr.status = 'published' OR pr.user_id = ? OR ? = 'admin')"
);
$q->execute([$id, user()['id'] ?? 0, user()['role'] ?? '']);
$p = $q->fetch();

if (!$p) {
    http_response_code(404);
    exit('Artículo no encontrado.');
}

db()->prepare('UPDATE products SET views = views + 1 WHERE id = ?')->execute([$id]);

$im = db()->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
$im->execute([$id]);
$images = $im->fetchAll();

// La portada también forma parte del visor. Se eliminan imágenes repetidas.
$galleryImages = [];
$seenImages = [];
$addGalleryImage = static function (?string $path, string $label = '') use (&$galleryImages, &$seenImages): void {
    $path = trim((string)$path);
    if ($path === '' || isset($seenImages[$path])) {
        return;
    }
    $seenImages[$path] = true;
    $galleryImages[] = ['image' => $path, 'label' => $label];
};

$addGalleryImage($p['cover_image'] ?? '', 'Imagen de portada');
foreach ($images as $index => $img) {
    $addGalleryImage($img['image'] ?? '', 'Imagen '.($index + 1));
}

$embed = youtube_embed($p['video_url']);
$rate = current_usd_clp();
$showUsd = (int)($p['show_usd'] ?? 0);
$usd = ($rate && $showUsd) ? (float)$p['price_clp'] / $rate : null;
$pageTitle=$p['title'];
$pageDescription=mb_strimwidth(strip_tags((string)$p['description']),0,300,'…');
$pageCanonical=url('product.php?id='.$p['id']);
$pageImage=$p['cover_image'];

require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/product-viewer.css')?>">

<div class="breadcrumbs">
    <a href="index.php">Inicio</a> › <?=e($p['category_name'])?> › <?=e($p['title'])?>
</div>

<?php require_once __DIR__.'/../app/commerce_suite.php'; ?>
<?php if(suite_ready() && user() && (int)(user()['id']) !== (int)$p['user_id'] && $p['status']==='published'):?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-suite.css')?>"><div class="suite-grid suite" style="padding-top:0;padding-bottom:8px"><div class="suite-card"><h3>Consultar al comercio</h3><form method="post" action="<?=url('suite_inbox.php')?>"><?=csrf_field()?><input type="hidden" name="action" value="new"><input type="hidden" name="product_id" value="<?=(int)$p['id']?>"><label>Asunto<input name="subject" maxlength="160" required></label><label>Mensaje<textarea name="body" required maxlength="4000"></textarea></label><button>Enviar consulta</button></form></div><div class="suite-card"><h3>Solicitar reserva</h3><p>Solicitud sin pago ni confirmación automática de disponibilidad.</p><form method="post" action="<?=url('suite_requests.php')?>"><?=csrf_field()?><input type="hidden" name="action" value="create"><input type="hidden" name="product_id" value="<?=(int)$p['id']?>"><label>Cantidad<input name="quantity" type="number" min="1" max="1000" value="1" required></label><label>Nota<input name="note" maxlength="1000"></label><button>Solicitar reserva</button></form></div></div>
<?php endif;?>
<article class="product-detail product-detail-enhanced">
    <section class="product-media-column">
        <?php if ($galleryImages): ?>
            <div class="product-viewer" data-product-viewer>
                <div class="viewer-toolbar" aria-label="Controles de visualización">
                    <div class="viewer-fit-options" role="group" aria-label="Ajuste de imagen">
                        <button type="button" class="viewer-fit active" data-fit="contain" title="Ver la imagen completa">▣ Ajustar</button>
                        <button type="button" class="viewer-fit" data-fit="cover" title="Rellenar todo el marco">▦ Rellenar</button>
                        <button type="button" class="viewer-fit" data-fit="fill" title="Estirar al tamaño del marco">↔ Estirar</button>
                        <button type="button" class="viewer-fit" data-fit="width" title="Priorizar el ancho">↔ Ancho</button>
                        <button type="button" class="viewer-fit" data-fit="height" title="Priorizar el alto">↕ Alto</button>
                    </div>
                    <button type="button" class="viewer-fullscreen" data-viewer-fullscreen title="Abrir en pantalla completa">⛶ Ampliar</button>
                </div>

                <div class="viewer-stage fit-contain" data-viewer-stage>
                    <button type="button" class="viewer-arrow previous" data-viewer-prev aria-label="Imagen anterior">‹</button>
                    <img
                        class="viewer-main-image"
                        data-viewer-main
                        src="<?=url($galleryImages[0]['image'])?>"
                        alt="<?=e($p['title'])?>"
                        loading="eager"
                    >
                    <button type="button" class="viewer-arrow next" data-viewer-next aria-label="Imagen siguiente">›</button>
                    <div class="viewer-counter" data-viewer-counter>1 / <?=count($galleryImages)?></div>
                </div>

                <div class="viewer-caption">
                    <strong data-viewer-label><?=e($galleryImages[0]['label'])?></strong>
                    <span>Selecciona una miniatura o usa las flechas para recorrer las imágenes.</span>
                </div>

                <div class="viewer-thumbnails" data-viewer-thumbnails>
                    <?php foreach ($galleryImages as $index => $img): ?>
                        <button
                            type="button"
                            class="viewer-thumbnail <?=$index === 0 ? 'active' : ''?>"
                            data-viewer-index="<?=$index?>"
                            data-viewer-src="<?=e(url($img['image']))?>"
                            data-viewer-label="<?=e($img['label'])?>"
                            aria-label="Mostrar <?=e($img['label'])?>"
                        >
                            <img src="<?=url($img['image'])?>" alt="<?=e($img['label'])?>" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="viewer-empty">🖼️ Esta publicación no tiene imágenes disponibles.</div>
        <?php endif; ?>

        <?php if ($embed): ?>
            <section class="product-video-card">
                <div class="media-section-heading">
                    <span>▶️</span>
                    <div>
                        <strong>Video del artículo o servicio</strong>
                        <small>Contenido audiovisual publicado por el anunciante.</small>
                    </div>
                </div>
                <div class="video-wrap">
                    <iframe src="<?=e($embed)?>" allowfullscreen loading="lazy"></iframe>
                </div>
            </section>
        <?php endif; ?>
    </section>

    <aside class="purchase-card">
        <span class="category-label"><?=e($p['category_name'])?></span>
        <h1><?=e($p['title'])?></h1>
        <div class="price huge">
            <small><?=e(setting('showcase_price_label', 'Valor publicado'))?></small><br>
            <?=money_clp($p['price_clp'])?> CLP
        </div>
        <?php if ($usd): ?>
            <p class="usd">≈ USD <?=number_format($usd, 2, ',', '.')?> <small>valor referencial</small></p>
        <?php endif; ?>
        <div class="product-description"><?=nl2br(e($p['description']))?></div>

        <div class="seller-box" style="background-image:linear-gradient(rgba(8,15,25,.8),rgba(8,15,25,.8)),url('<?=url($p['cover'] ?: 'assets/cover.svg')?>')">
            <img src="<?=url($p['avatar'] ?: 'assets/img-avatar.svg')?>" alt="<?=e($p['display_name'])?>">
            <div>
                <b><?=e($p['display_name'])?></b>
                <?=stars_html(seller_stars((int)$p['user_id']))?>
                <span>🏬 LOCAL <?=e($p['business_local'] ?: 'sin informar')?> · Nº <?=e($p['business_number'] ?: '-')?></span>
                <span>📍 <?=e($p['business_address'] ?: 'Dirección no informada')?></span>
            </div>
        </div>

        <div class="contact-actions">
            <?php if ($p['whatsapp']): ?>
                <a class="btn whatsapp" target="_blank" rel="noopener" href="https://wa.me/<?=e(preg_replace('/\D+/', '', $p['whatsapp']))?>?text=<?=urlencode('Hola, quisiera solicitar información sobre: '.$p['title'])?>">💬 Consultar por WhatsApp</a>
            <?php endif; ?>
            <?php if ($p['phone']): ?>
                <a class="btn" href="tel:<?=e($p['phone'])?>">📞 Consultar por teléfono</a>
            <?php endif; ?>
            <?php if ($p['google_maps_url']): ?>
                <a class="btn maps" target="_blank" rel="noopener" href="<?=e($p['google_maps_url'])?>">🗺️ Ver Google Maps</a>
            <?php endif; ?>
        </div>

        <?php if ($p['delivery_options']): ?>
            <div class="delivery">
                <b>ℹ️ Información adicional publicada por el anunciante</b>
                <p><?=e($p['delivery_options'])?></p>
            </div>
        <?php endif; ?>
        <?=showcase_notice_html('showcase-product-notice')?>
    </aside>
</article>

<div class="viewer-lightbox" data-viewer-lightbox hidden>
    <button type="button" class="lightbox-close" data-lightbox-close aria-label="Cerrar">×</button>
    <button type="button" class="lightbox-arrow previous" data-lightbox-prev aria-label="Imagen anterior">‹</button>
    <img data-lightbox-image src="" alt="Imagen ampliada">
    <button type="button" class="lightbox-arrow next" data-lightbox-next aria-label="Imagen siguiente">›</button>
    <div class="lightbox-info" data-lightbox-info></div>
</div>

<?=adsense('article')?>
<script src="<?=url('assets/js/product-viewer.js')?>" defer></script>
<?php require __DIR__.'/../partials/footer.php'; ?>
