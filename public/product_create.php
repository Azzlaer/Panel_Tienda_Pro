<?php
require_once __DIR__.'/../app/bootstrap.php';
require_login();
if (user()['status'] !== 'active') { http_response_code(403); exit('Tu cuenta aún no está autorizada.'); }

$cats = db()->query("SELECT c.id, c.name, c.icon, p.name parent_name FROM categories c LEFT JOIN categories p ON p.id=c.parent_id WHERE c.active=1 ORDER BY COALESCE(p.name,c.name), c.sort_order, c.name")->fetchAll();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $priceRaw = preg_replace('/[^0-9]/', '', $_POST['price_clp'] ?? '0');
        $price = (int)$priceRaw;
        $category = (int)($_POST['category_id'] ?? 0);
        $video = trim($_POST['video_url'] ?? '');
        $delivery = array_values(array_intersect($_POST['delivery'] ?? [], ['Retiro en local','Entrega acordada','Uber','Blue Express','Chilexpress','Starken']));

        if ($title === '' || mb_strlen($title) < 8) throw new RuntimeException('El título debe tener al menos 8 caracteres.');
        if (mb_strlen($title) > 180) throw new RuntimeException('El título no puede superar 180 caracteres.');
        if (mb_strlen($desc) < 40) throw new RuntimeException('La descripción debe tener al menos 40 caracteres.');
        if (!$category) throw new RuntimeException('Selecciona una categoría.');
        if ($price < 0) throw new RuntimeException('El precio no es válido.');
        if ($video !== '' && !youtube_embed($video)) throw new RuntimeException('El video debe ser un enlace válido de YouTube o Vimeo.');
        if (!$delivery) throw new RuntimeException('Selecciona al menos una opción de entrega.');

        $galleryFiles = $_FILES['gallery']['tmp_name'] ?? [];
        $validGallery = 0;
        if (is_array($galleryFiles)) foreach ($galleryFiles as $tmp) if ($tmp) $validGallery++;
        if ($validGallery < 3 || $validGallery > 6) throw new RuntimeException('La galería debe contener entre 3 y 6 fotografías.');

        $cover = upload_image('cover_image', 'products');
        if (!$cover) throw new RuntimeException('Debes subir una imagen de portada.');

        $status = user()['role'] === 'admin' ? 'published' : 'pending';
        db()->beginTransaction();
        $st = db()->prepare('INSERT INTO products(user_id,category_id,title,slug,description,price_clp,stock,video_url,cover_image,status,delivery_options) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([
            user()['id'], $category, $title, unique_product_slug($title), $desc, $price,
            ($_POST['stock'] ?? '') !== '' ? max(0, (int)$_POST['stock']) : null,
            $video ?: null, $cover, $status, implode(', ', $delivery)
        ]);
        $id = (int)db()->lastInsertId();

        $count = min(6, count($galleryFiles));
        for ($i=0; $i<$count; $i++) {
            if (!$galleryFiles[$i]) continue;
            $_FILES['_gallery_one'] = [
                'name'=>$_FILES['gallery']['name'][$i], 'type'=>$_FILES['gallery']['type'][$i],
                'tmp_name'=>$galleryFiles[$i], 'error'=>$_FILES['gallery']['error'][$i], 'size'=>$_FILES['gallery']['size'][$i]
            ];
            $path = upload_image('_gallery_one', 'products');
            if ($path) db()->prepare('INSERT INTO product_images(product_id,image,sort_order) VALUES(?,?,?)')->execute([$id,$path,$i]);
        }
        unset($_FILES['_gallery_one']);
        db()->commit();

        flash('success', $status === 'published' ? 'Artículo publicado correctamente.' : 'Artículo enviado para revisión del administrador.');
        redirect('product.php?id='.$id);
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        $error = $e->getMessage();
    }
}

require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-forms.css')?>">
<section class="commerce-editor product-editor">
    <div class="editor-heading">
        <div><span class="editor-kicker">PUBLICACIÓN DE PRODUCTOS</span><h1>📦 Crea una publicación irresistible</h1><p>Completa cada bloque. Una ficha clara y buenas fotos aumentan la confianza del comprador.</p></div>
        <div class="publication-status"><span>🛡️</span><div><b><?=user()['role']==='admin'?'Publicación inmediata':'Revisión administrativa'?></b><small><?=user()['role']==='admin'?'Quedará visible al guardar.':'Se publicará cuando sea aprobada.'?></small></div></div>
    </div>
    <?php if ($error): ?><div class="flash error"><?=e($error)?></div><?php endif; ?>

    <div class="editor-layout">
        <form class="editor-form" method="post" enctype="multipart/form-data" id="productEditor">
            <?=csrf_field()?>
            <article class="editor-panel">
                <div class="panel-title"><span>1</span><div><h2>Información principal</h2><p>Usa un título descriptivo, sin mayúsculas excesivas.</p></div></div>
                <label><b>Título del artículo *</b><input name="title" maxlength="180" minlength="8" value="<?=e($_POST['title'] ?? '')?>" placeholder="Ej.: Notebook Lenovo Legion 5 Ryzen 7, 16 GB RAM" required data-counter="titleCounter"><span class="char-counter" id="titleCounter"></span></label>
                <label><b>Categoría y subcategoría *</b><select name="category_id" required><option value="">Seleccionar categoría…</option><?php foreach($cats as $c):?><option value="<?=$c['id']?>" <?=((int)($_POST['category_id']??0)===(int)$c['id'])?'selected':''?>><?=e(($c['icon'] ?: '📁').' '.($c['parent_name'] ? $c['parent_name'].' › ' : '').$c['name'])?></option><?php endforeach;?></select></label>
                <label><b>Descripción completa *</b><textarea name="description" rows="9" minlength="40" maxlength="10000" required data-counter="descriptionCounter" placeholder="Describe estado, medidas, marca, modelo, garantía, accesorios incluidos y cualquier detalle importante."><?=e($_POST['description'] ?? '')?></textarea><span class="char-counter" id="descriptionCounter"></span></label>
            </article>

            <article class="editor-panel">
                <div class="panel-title"><span>2</span><div><h2>Precio y disponibilidad</h2><p>Todos los valores se publican en pesos chilenos.</p></div></div>
                <div class="field-grid two">
                    <label><b>Precio CLP *</b><div class="input-prefix price-input"><span>$</span><input name="price_clp" inputmode="numeric" value="<?=e($_POST['price_clp'] ?? '')?>" placeholder="749990" required id="priceClp"></div><small id="priceFormatted">Ingresa el valor sin puntos.</small></label>
                    <label><b>Stock disponible</b><input type="number" name="stock" min="0" max="999999" value="<?=e($_POST['stock'] ?? '')?>" placeholder="Vacío = consultar"><small>Usa 0 si está temporalmente agotado.</small></label>
                </div>
            </article>

            <article class="editor-panel">
                <div class="panel-title"><span>3</span><div><h2>Fotografías</h2><p>Una portada y entre 3 y 6 imágenes adicionales.</p></div></div>
                <label class="cover-dropzone">
                    <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif" required data-preview="productCoverPreview">
                    <img id="productCoverPreview" src="<?=url('assets/cover.svg')?>" alt="Vista previa">
                    <div><b>🖼️ Seleccionar portada</b><small>Recomendado 1200 × 900 px · fondo limpio y buena iluminación</small></div>
                </label>
                <label class="gallery-dropzone"><input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required id="galleryInput"><span>📸</span><b>Seleccionar entre 3 y 6 fotografías</b><small id="galleryStatus">Ninguna fotografía seleccionada</small></label>
                <div class="gallery-preview" id="galleryPreview"></div>
            </article>

            <article class="editor-panel">
                <div class="panel-title"><span>4</span><div><h2>Video y entrega</h2><p>Demuestra el artículo o servicio y explica cómo lo recibe el comprador.</p></div></div>
                <label><b>Video de YouTube o Vimeo</b><div class="input-prefix"><span>▶️</span><input type="url" name="video_url" id="videoUrl" value="<?=e($_POST['video_url'] ?? '')?>" placeholder="https://youtube.com/watch?v=..."></div></label>
                <div class="delivery-grid">
                    <?php foreach(['Retiro en local'=>'🏪','Entrega acordada'=>'🤝','Uber'=>'🚗','Blue Express'=>'📦','Chilexpress'=>'🚚','Starken'=>'🚛'] as $d=>$icon):?>
                    <label class="delivery-option"><input type="checkbox" name="delivery[]" value="<?=e($d)?>" <?=in_array($d,$_POST['delivery']??[],true)?'checked':''?>><span><?=$icon?></span><b><?=e($d)?></b><small><?=match($d){'Retiro en local'=>'Cliente retira en tu dirección','Entrega acordada'=>'Coordinación directa','Uber'=>'Envío urbano coordinado','Blue Express'=>'Cobertura según transportista','Chilexpress'=>'Sucursal o domicilio',default=>'Sucursal o domicilio'}?></small></label>
                    <?php endforeach;?>
                </div>
                <div class="editor-tip inline"><b>🇨🇱 Importante</b><p>Indica en la descripción quién paga el despacho, cobertura, plazos y si entregas boleta o factura.</p></div>
            </article>

            <div class="editor-actions"><a href="<?=url('index.php')?>" class="btn btn-muted">← Cancelar</a><button class="btn big" type="submit">🚀 <?=user()['role']==='admin'?'Publicar ahora':'Enviar a revisión'?></button></div>
        </form>

        <aside class="editor-side">
            <div class="product-live-preview">
                <span class="preview-label">VISTA PREVIA</span><img id="sideProductCover" src="<?=url('assets/cover.svg')?>" alt=""><div><small>Tu categoría</small><h3 id="sideProductTitle">Título de tu artículo</h3><strong id="sideProductPrice">$0</strong><p>Publicado por <?=e(user()['display_name'])?></p></div>
            </div>
            <div class="checklist-card"><h3>✅ Lista de calidad</h3><ul><li>Título claro y específico</li><li>Precio correcto en CLP</li><li>Descripción de al menos 40 caracteres</li><li>Portada de buena calidad</li><li>3 a 6 fotografías reales</li><li>Al menos una forma de entrega</li></ul></div>
        </aside>
    </div>
</section>
<script src="<?=url('assets/js/commerce-forms.js')?>"></script>
<?php require __DIR__.'/../partials/footer.php'; ?>
