<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/marketplace.php';
require_login();
$pdo=db();if(!marketplace_ready($pdo)){http_response_code(503);exit('Marketplace no activado.');}
$uid=(int)user()['id'];
$q=$pdo->prepare("SELECT f.product_id,pr.title,pr.price_clp,pr.stock,pr.cover_image,c.name AS category_name FROM marketplace_favorites f JOIN products pr ON pr.id=f.product_id JOIN users u ON u.id=pr.user_id JOIN categories c ON c.id=pr.category_id WHERE f.user_id=? AND pr.status='published' AND u.status='active' ORDER BY f.created_at DESC LIMIT 150");
$q->execute([$uid]);$favorites=$q->fetchAll();
$q=$pdo->prepare('SELECT id,name,filters_json,created_at FROM marketplace_saved_searches WHERE user_id=? ORDER BY id DESC LIMIT 30');
$q->execute([$uid]);$saved=$q->fetchAll();
$pageTitle='Favoritos y búsquedas guardadas';$pageDescription='Organiza tus artículos favoritos y búsquedas personales.';
require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/marketplace-enterprise.css')?>">
<section class="mkt"><header class="mkt-results-head"><div><span class="mkt-kicker">TU ESPACIO</span><h1>Favoritos y búsquedas guardadas</h1><p>Tu colección es privada. Solo tú puedes modificarla.</p></div><a href="<?=url('marketplace.php')?>">Explorar Marketplace</a></header>
<section class="mkt-saved-section"><h2>Artículos favoritos</h2><div class="mkt-grid"><?php foreach($favorites as $a):?><article class="mkt-card"><a class="mkt-card-image" href="<?=url('product.php?id='.(int)$a['product_id'])?>"><img loading="lazy" src="<?=url($a['cover_image']?:'assets/cover.svg')?>" alt="<?=e($a['title'])?>"></a><div class="mkt-card-body"><small><?=e($a['category_name'])?></small><h3><?=e($a['title'])?></h3><div class="mkt-price"><?=money_clp((int)$a['price_clp'])?></div><form method="post" action="<?=url('marketplace_actions.php')?>"><?=csrf_field()?><input type="hidden" name="action" value="remove_favorite"><button class="mkt-fav-button" name="product_id" value="<?=(int)$a['product_id']?>">♥ Quitar de favoritos</button></form></div></article><?php endforeach;?></div>
<?php if(!$favorites):?><p class="mkt-empty">Todavía no guardaste productos favoritos.</p><?php endif;?></section>
<section class="mkt-saved-section"><h2>Búsquedas guardadas</h2><div class="mkt-saved-list"><?php foreach($saved as $s):$f=json_decode((string)$s['filters_json'],true)?:[];?><div class="mkt-saved-row"><div><strong><?=e($s['name'])?></strong><small><?=e($s['created_at'])?></small></div><a href="<?=url('marketplace.php?'.marketplace_search_url($f))?>">Ejecutar búsqueda</a><form method="post" action="<?=url('marketplace_actions.php')?>"><?=csrf_field()?><input type="hidden" name="action" value="delete_search"><button name="search_id" value="<?=(int)$s['id']?>">Eliminar</button></form></div><?php endforeach;?></div><?php if(!$saved):?><p class="mkt-empty">Guarda una búsqueda desde el catálogo para encontrarla fácilmente después.</p><?php endif;?></section></section>
<?php require __DIR__.'/../partials/footer.php';?>
