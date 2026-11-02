<?php
require_once __DIR__.'/../app/bootstrap.php';
require_login();

$me = user();
$pro = profile_pro((int)$me['id']);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $displayName = trim($_POST['display_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $maps = trim($_POST['google_maps_url'] ?? '');
        $whatsapp = preg_replace('/[^0-9]/', '', $_POST['whatsapp'] ?? '');

        if ($displayName === '') throw new RuntimeException('El nombre visible es obligatorio.');
        if (mb_strlen($displayName) > 100) throw new RuntimeException('El nombre visible no puede superar 100 caracteres.');
        if (mb_strlen($bio) > 2000) throw new RuntimeException('La descripción no puede superar 2.000 caracteres.');
        if ($maps !== '' && !filter_var($maps, FILTER_VALIDATE_URL)) throw new RuntimeException('El enlace de Google Maps no es válido.');
        if ($whatsapp !== '' && (strlen($whatsapp) < 8 || strlen($whatsapp) > 15)) throw new RuntimeException('Ingresa WhatsApp con código de país, por ejemplo 56912345678.');

        $avatar = upload_image('avatar', 'avatars');
        $cover = upload_image('cover', 'covers');

        $sql = 'UPDATE profiles SET display_name=?,bio=?,location=?,business_local=?,business_number=?,business_address=?,phone=?,whatsapp=?,google_maps_url=?,show_usd=?';
        $args = [
            $displayName,
            $bio,
            trim($_POST['location'] ?? ''),
            trim($_POST['business_local'] ?? ''),
            trim($_POST['business_number'] ?? ''),
            trim($_POST['business_address'] ?? ''),
            trim($_POST['phone'] ?? ''),
            $whatsapp,
            $maps,
            isset($_POST['show_usd']) ? 1 : 0,
        ];
        if ($avatar) { $sql .= ',avatar=?'; $args[] = $avatar; }
        if ($cover) { $sql .= ',cover=?'; $args[] = $cover; }
        $sql .= ' WHERE user_id=?';
        $args[] = $me['id'];
        db()->prepare($sql)->execute($args);
        $fields=['opening_hours','specialties','website','instagram','facebook'];
        $values=[];
        foreach($fields as $key){$value=trim((string)($_POST[$key]??''));if(mb_strlen($value)>500)throw new RuntimeException('El campo '.$key.' supera 500 caracteres.');if(in_array($key,['website','instagram','facebook'],true) && $value!=='' && safe_business_link($value)==='')throw new RuntimeException('Los enlaces deben usar HTTPS.');$values[]=$value;}
        db()->prepare('INSERT INTO profile_pro (user_id,opening_hours,specialties,website,instagram,facebook) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE opening_hours=VALUES(opening_hours),specialties=VALUES(specialties),website=VALUES(website),instagram=VALUES(instagram),facebook=VALUES(facebook)')->execute(array_merge([(int)$me['id']],$values));

        flash('success', 'Perfil comercial actualizado correctamente.');
        redirect('profile_edit.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$me = user();
require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-forms.css')?>">
<section class="commerce-editor">
    <div class="editor-heading">
        <div>
            <span class="editor-kicker">CENTRO DEL VENDEDOR</span>
            <h1>🏪 Configura tu perfil comercial</h1>
            <p>Tu vitrina pública debe transmitir confianza, ubicación clara y formas rápidas de contacto.</p>
        </div>
        <a class="btn editor-preview-btn" target="_blank" href="<?=url('profile.php?u='.urlencode($me['username']))?>">👁️ Ver perfil público</a>
    </div>

    <?php if ($error): ?><div class="flash error"><?=e($error)?></div><?php endif; ?>

    <div class="editor-layout">
        <form class="editor-form" method="post" enctype="multipart/form-data" id="profileEditor">
            <?=csrf_field()?>

            <article class="editor-panel">
                <div class="panel-title"><span>1</span><div><h2>Identidad visual</h2><p>Avatar y portada que reconocerán tus clientes.</p></div></div>
                <div class="upload-grid">
                    <label class="image-uploader avatar-uploader">
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="avatarPreview">
                        <img id="avatarPreview" src="<?=url($me['avatar'] ?: 'assets/img-avatar.svg')?>" alt="Avatar">
                        <strong>📷 Cambiar avatar</strong><small>Cuadrada, JPG/PNG/WebP · máximo <?=round(MAX_UPLOAD_BYTES/1048576)?> MB</small>
                    </label>
                    <label class="image-uploader cover-uploader">
                        <input type="file" name="cover" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="coverPreview">
                        <img id="coverPreview" src="<?=url($me['cover'] ?: 'assets/cover.svg')?>" alt="Portada">
                        <strong>🖼️ Cambiar portada</strong><small>Recomendado 1600 × 500 px</small>
                    </label>
                </div>
            </article>

            <article class="editor-panel">
                <div class="panel-title"><span>2</span><div><h2>Presentación del negocio</h2><p>Explica qué vendes y por qué deberían elegirte.</p></div></div>
                <div class="field-grid two">
                    <label><b>Nombre visible *</b><input name="display_name" maxlength="100" value="<?=e($_POST['display_name'] ?? $me['display_name'])?>" required><small>Nombre comercial o nombre del vendedor.</small></label>
                    <label><b>Ciudad o comuna</b><input name="location" maxlength="120" value="<?=e($_POST['location'] ?? ($me['location'] ?? ''))?>" placeholder="Ej.: Viña del Mar, Región de Valparaíso"></label>
                </div>
                <label><b>Descripción del negocio</b><textarea name="bio" rows="6" maxlength="2000" data-counter="bioCounter" placeholder="Cuenta tu historia, especialidad, horarios y qué hace diferente a tu comercio."><?=e($_POST['bio'] ?? ($me['bio'] ?? ''))?></textarea><span class="char-counter" id="bioCounter"></span></label>
            </article>

            <article class="editor-panel">
                <div class="panel-title"><span>3</span><div><h2>Local y dirección</h2><p>Datos que aparecerán junto a tus artículos.</p></div></div>
                <div class="field-grid three">
                    <label><b>Nombre del local</b><input name="business_local" maxlength="120" value="<?=e($_POST['business_local'] ?? ($me['business_local'] ?? ''))?>" placeholder="Ej.: Galería Cristal"></label>
                    <label><b>Número</b><input name="business_number" maxlength="40" value="<?=e($_POST['business_number'] ?? ($me['business_number'] ?? ''))?>" placeholder="Ej.: Local 28"></label>
                    <label><b>Teléfono</b><input name="phone" maxlength="40" value="<?=e($_POST['phone'] ?? ($me['phone'] ?? ''))?>" placeholder="Ej.: +56 32 123 4567"></label>
                </div>
                <label><b>Dirección completa</b><input name="business_address" maxlength="255" value="<?=e($_POST['business_address'] ?? ($me['business_address'] ?? ''))?>" placeholder="Calle, número, galería, comuna y región"></label>
            </article>

            <article class="editor-panel">
                <div class="panel-title"><span>4</span><div><h2>Contacto y confianza</h2><p>Facilita que el comprador te llame, escriba o llegue al local.</p></div></div>
                <div class="field-grid two">
                    <label><b>WhatsApp con código de país</b><div class="input-prefix"><span>💬</span><input name="whatsapp" inputmode="numeric" maxlength="15" value="<?=e($_POST['whatsapp'] ?? ($me['whatsapp'] ?? ''))?>" placeholder="56912345678"></div><small>Solo números. Chile utiliza +56.</small></label>
                    <label><b>Google Maps del negocio</b><div class="input-prefix"><span>📍</span><input type="url" name="google_maps_url" value="<?=e($_POST['google_maps_url'] ?? ($me['google_maps_url'] ?? ''))?>" placeholder="https://maps.app.goo.gl/..."></div><small>Pega el enlace público de tu ficha o ubicación.</small></label>
                </div>
                <label class="switch-card"><input type="checkbox" name="show_usd" <?=isset($_POST['show_usd']) || (!$_POST && !empty($me['show_usd'])) ? 'checked' : ''?>><span class="switch-ui"></span><span><b>Mostrar equivalencia en dólares</b><small>Se mostrará como referencia junto al precio en pesos chilenos.</small></span></label>
            </article>

            <article class="editor-panel">
                <div class="panel-title"><span>5</span><div><h2>✨ Perfil PRO</h2><p>Horarios, especialidades y enlaces del comercio.</p></div></div>
                <div class="field-grid two">
                    <label><b>Horario de atención</b><input maxlength="500" name="opening_hours" value="<?=e($_POST['opening_hours']??$pro['opening_hours'])?>" placeholder="Lun–Vie 10:00–19:00"></label>
                    <label><b>Especialidades</b><input maxlength="500" name="specialties" value="<?=e($_POST['specialties']??$pro['specialties'])?>" placeholder="Tecnología, accesorios, reparación"></label>
                    <label><b>Sitio web HTTPS</b><input type="url" maxlength="500" name="website" value="<?=e($_POST['website']??$pro['website'])?>" placeholder="https://mi-tienda.cl"></label>
                    <label><b>Instagram HTTPS</b><input type="url" maxlength="500" name="instagram" value="<?=e($_POST['instagram']??$pro['instagram'])?>" placeholder="https://instagram.com/mi-tienda"></label>
                    <label><b>Facebook HTTPS</b><input type="url" maxlength="500" name="facebook" value="<?=e($_POST['facebook']??$pro['facebook'])?>" placeholder="https://facebook.com/mi-tienda"></label>
                </div>
            </article>
            <div class="editor-actions"><a href="<?=url('index.php')?>" class="btn btn-muted">← Volver</a><button class="btn big" type="submit">💾 Guardar cambios</button></div>
        </form>

        <aside class="editor-side">
            <div class="preview-profile-card">
                <div class="preview-cover" style="background-image:url('<?=url($me['cover'] ?: 'assets/cover.svg')?>')" id="sideCover"></div>
                <img class="preview-avatar" id="sideAvatar" src="<?=url($me['avatar'] ?: 'assets/img-avatar.svg')?>" alt="">
                <h3><?=e($me['display_name'])?></h3>
                <p>⭐ Perfil comercial público</p>
                <div><span>🏪</span><b><?=e($me['business_local'] ?: 'Tu local')?></b></div>
                <div><span>📍</span><b><?=e($me['business_address'] ?: 'Agrega tu dirección')?></b></div>
                <div><span>💬</span><b><?=e($me['whatsapp'] ?: 'Agrega WhatsApp')?></b></div>
            </div>
            <div class="editor-tip"><b>💡 Consejo para Chile</b><p>Incluye comuna, región, horario de atención y si emites boleta o factura dentro de tu descripción.</p></div>
        </aside>
    </div>
</section>
<script src="<?=url('assets/js/commerce-forms.js')?>"></script>
<?php require __DIR__.'/../partials/footer.php'; ?>
