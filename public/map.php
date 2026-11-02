<?php
require_once __DIR__.'/../app/bootstrap.php';ensure_commerce_map_schema();
$settings=db()->query('SELECT * FROM commerce_map_settings WHERE id=1')->fetch();
$rows=db()->query("SELECT d.id,d.name,d.local_number,d.description,d.image,u.username,p.x_percent,p.y_percent FROM commerce_map_pins p JOIN commerce_directory d ON d.id=p.commerce_id JOIN users u ON u.id=d.user_id WHERE d.active=1 AND u.status='active' ORDER BY d.name")->fetchAll();
$pageTitle='Mapa de locales';$pageDescription='Explora los locales del recinto y sus productos.';$pageCanonical=url('map.php');require __DIR__.'/../partials/header.php';?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-map.css')?>?v=5.1">
<div class="commerce-map-page"><header class="commerce-map-heading"><span>DIRECTORIO INTERACTIVO</span><h1><?=e($settings['title']?:'Mapa de locales')?></h1><p><?=e($settings['description']?:'Selecciona un marcador para conocer un comercio y sus productos.')?></p><a href="<?=url('commerces.php')?>">← Directorio de comercios</a></header>
<?php if(!empty($settings['image'])):?>
<div class="commerce-map-layout"><div class="commerce-map-surface" aria-label="Mapa interactivo de locales"><img src="<?=url($settings['image'])?>" alt="Plano del recinto" draggable="false"><?php foreach($rows as $r):?><a class="map-pin" href="#local-<?=(int)$r['id']?>" style="left:<?=e((string)$r['x_percent'])?>%;top:<?=e((string)$r['y_percent'])?>%" title="<?=e($r['name'])?>" aria-label="Ver <?=e($r['name'])?>">📍</a><?php endforeach;?></div>
<aside class="commerce-map-list"><h2>Locales del plano</h2><input id="mapSearch" type="search" placeholder="Buscar nombre o número de local" aria-label="Buscar locales"><div id="mapSearchEntries"><?php foreach($rows as $r):?><article class="map-list-item" id="local-<?=(int)$r['id']?>" data-search="<?=e(mb_strtolower($r['name'].' '.$r['local_number'],'UTF-8'))?>"><strong><?=e($r['name'])?></strong><small>Local <?=e($r['local_number']?:'sin número')?></small><p><?=e(mb_strimwidth($r['description'],0,135,'…','UTF-8'))?></p><a href="<?=url('profile.php?u='.urlencode($r['username']))?>">Ver perfil y productos →</a></article><?php endforeach;?></div></aside></div>
<?php else:?><div class="commerce-map-empty"><h2>Plano todavía no disponible</h2><p>El administrador aún no ha publicado un plano del recinto.</p><a href="<?=url('commerces.php')?>">Explorar comercios</a></div><?php endif;?></div>
<script src="<?=url('assets/js/commerce-map-public.js')?>?v=5.1" defer></script>
<?php require __DIR__.'/../partials/footer.php';
