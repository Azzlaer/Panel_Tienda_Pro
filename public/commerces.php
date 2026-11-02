<?php
require_once __DIR__.'/../app/bootstrap.php';ensure_commerce_directory_schema();
$q=trim((string)($_GET['q']??''));$params=[];$where="d.active=1 AND u.status='active'";
if($q!==''){$where.=" AND (d.name LIKE ? OR d.local_number LIKE ? OR d.description LIKE ?)";$params=array_fill(0,3,'%'.$q.'%');}
$stmt=db()->prepare("SELECT d.*,u.username,p.avatar FROM commerce_directory d JOIN users u ON u.id=d.user_id JOIN profiles p ON p.user_id=u.id WHERE $where ORDER BY d.featured DESC,d.sort_order,d.id DESC LIMIT 150");$stmt->execute($params);$rows=$stmt->fetchAll();
$pageTitle='Directorio de comercios';$pageDescription='Conoce nuestros locales, sus especialidades y artículos disponibles.';$pageCanonical=url('commerces.php');require __DIR__.'/../partials/header.php';?>
<section class="directory-heading"><span class="directory-kicker">DIRECTORIO COMERCIAL</span><h1>Conoce nuestros comercios</h1><p>Encuentra los locales de nuestra comunidad y explora sus productos y servicios.</p><form method="get" class="directory-search"><input name="q" value="<?=e($q)?>" placeholder="Buscar nombre, número de local o especialidad"><button class="btn">Buscar</button></form></section>
<p style="margin:14px 0 22px"><a class="btn" href="<?=url('map.php')?>">🗺️ Ver mapa de locales</a></p>
<section class="directory-grid" aria-label="Comercios disponibles"><?php foreach($rows as $r):?><a class="directory-card" href="<?=url('profile.php?u='.urlencode($r['username']))?>"><div class="directory-image"><img loading="lazy" src="<?=url($r['image']?:$r['avatar']?:'assets/cover.svg')?>" alt="<?=e($r['name'])?>"><?php if($r['featured']):?><span>★ Destacado</span><?php endif;?></div><div class="directory-info"><small>LOCAL <?=e($r['local_number']?:'—')?></small><h2><?=e($r['name'])?></h2><p><?=e(mb_strimwidth($r['description'],0,220,'…','UTF-8'))?></p><strong>Ver perfil y productos →</strong></div></a><?php endforeach;?></section>
<?php if(!$rows):?><div class="directory-empty">Todavía no hay locales publicados con esos criterios.</div><?php endif;?>
<?php require __DIR__.'/../partials/footer.php';
