<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
ensure_commerce_directory_schema();
$q=trim((string)($_GET['q']??''));$q=mb_substr($q,0,80);
$params=[];$where="d.active=1 AND u.status='active'";
if($q!==''){$where.=" AND (d.name LIKE ? OR d.local_number LIKE ? OR d.description LIKE ?)";$params=array_fill(0,3,'%'.$q.'%');}
$st=db()->prepare("SELECT d.id,d.name,d.local_number,d.description,d.image,u.username,p.avatar FROM commerce_directory d JOIN users u ON u.id=d.user_id LEFT JOIN profiles p ON p.user_id=u.id WHERE $where ORDER BY d.featured DESC,d.sort_order,d.id DESC LIMIT 150");
$st->execute($params);$rows=$st->fetchAll();
$pageTitle='Kiosco Digital';$pageDescription='Encuentra locales, explora el directorio y accede a sus códigos QR.';
require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-kiosk.css')?>?v=1">
<section class="kiosk-app">
<header class="kiosk-hero"><span>COMMERCE NEXUS · KIOSCO DIGITAL</span><h1>Encuentra tu próximo local</h1><p>Explora nuestros comercios, encuentra su número y escanea el QR para abrir su perfil desde tu teléfono.</p><form method="get" class="kiosk-search"><label for="kioskQuery">Buscar comercio o número de local</label><div><input id="kioskQuery" type="search" name="q" value="<?=e($q)?>" placeholder="Nombre, especialidad o local" maxlength="80"><button type="submit">Buscar</button><a href="<?=url('kiosk.php')?>">Mostrar todos</a></div></form><div class="kiosk-actions"><a href="<?=url('map.php')?>">🗺️ Ver plano de locales</a><a href="<?=url('commerces.php')?>">🏪 Directorio completo</a></div></header>
<div class="kiosk-grid"><?php foreach($rows as $r):$link=url('profile.php?u='.rawurlencode($r['username']));?>
<article class="kiosk-card"><img loading="lazy" src="<?=e(url($r['image']?:$r['avatar']?:'assets/cover.svg'))?>" alt=""><div class="kiosk-card-body"><small>LOCAL <?=e($r['local_number']?:'Sin número')?></small><h2><?=e($r['name'])?></h2><p><?=e(mb_strimwidth((string)$r['description'],0,155,'…','UTF-8'))?></p><a href="<?=e($link)?>">Ver perfil y productos</a><div class="kiosk-qr" data-qr="<?=e($link)?>" data-title="<?=e($r['name'])?>" role="img" aria-label="Código QR para <?=e($r['name'])?>"></div><button class="kiosk-print" type="button">Imprimir QR</button></div></article>
<?php endforeach;?></div>
<?php if(!$rows):?><p class="kiosk-empty">No hay comercios disponibles con esos filtros.</p><?php endif;?>
</section>
<script src="<?=url('assets/js/vendor/qrcode.min.js')?>"></script><script src="<?=url('assets/js/commerce-kiosk.js')?>?v=1" defer></script>
<?php require __DIR__.'/../partials/footer.php';
