<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/marketplace.php';
require_once __DIR__.'/../app/vip_product_effects.php';
$pdo=db();$ready=marketplace_ready($pdo);
$f=marketplace_filters($_GET);$page=max(1,(int)($_GET['page']??1));$result=marketplace_query($pdo,$f,$page);
$vipEffects=vip_effect_map($pdo,array_column($result['items'],'id'));
$categories=$pdo->query("SELECT id,name,parent_id FROM categories WHERE active=1 ORDER BY name ASC")->fetchAll();
$sellers=$pdo->query("SELECT DISTINCT u.id,p.display_name FROM users u JOIN profiles p ON p.user_id=u.id JOIN products pr ON pr.user_id=u.id AND pr.status='published' WHERE u.status='active' ORDER BY p.display_name LIMIT 200")->fetchAll();
$favoriteIds=[];
if($ready&&user()){
 $q=$pdo->prepare('SELECT product_id FROM marketplace_favorites WHERE user_id=?');
 $q->execute([(int)user()['id']]);$favoriteIds=array_fill_keys(array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN)),true);
}
$pageTitle='Marketplace Enterprise';$pageDescription='Descubre productos y comercios con filtros avanzados, favoritos y comparador.';$pageCanonical=url('marketplace.php');
require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/marketplace-enterprise.css')?>">
<link rel="stylesheet" href="<?=url('assets/css/vip-product-effects.css')?>">
<section class="mkt">
<header class="mkt-hero"><span>MARKETPLACE · COMERCIOSOCIAL</span><h1>Descubre negocios y oportunidades</h1><p>Encuentra productos, compara precios y guarda tus búsquedas. Contacta directamente con los vendedores.</p><div class="mkt-hero-links"><a href="<?=url('commerces.php')?>">Explorar comercios</a><?php if(user()):?><a href="<?=url('marketplace_saved.php')?>">Mis favoritos y búsquedas</a><?php endif;?></div></header>
<div class="mkt-layout">
<aside class="mkt-sidebar">
 <form method="get" class="mkt-filters" action="<?=url('marketplace.php')?>">
 <h2>Filtrar catálogo</h2>
 <label>Buscar<input type="search" name="q" maxlength="100" value="<?=e($f['q'])?>" placeholder="Nombre, categoría, comercio"></label>
 <label>Categoría<select name="category"><option value="0">Todas las categorías</option><?php foreach($categories as $cat):?><option value="<?=(int)$cat['id']?>" <?=$f['category']==(int)$cat['id']?'selected':''?>><?=e($cat['name'])?></option><?php endforeach;?></select></label>
 <label>Vendedor<select name="seller"><option value="0">Todos los comercios</option><?php foreach($sellers as $seller):?><option value="<?=(int)$seller['id']?>" <?=$f['seller']==(int)$seller['id']?'selected':''?>><?=e($seller['display_name'])?></option><?php endforeach;?></select></label>
 <div class="mkt-price-fields"><label>Desde CLP<input name="min" type="number" min="0" max="999999999999" value="<?=$f['min']?:''?>" placeholder="0"></label><label>Hasta CLP<input name="max" type="number" min="0" max="999999999999" value="<?=$f['max']?:''?>" placeholder="Sin límite"></label></div>
 <label class="mkt-check"><input type="checkbox" name="available" value="1" <?=$f['available']?'checked':''?>> Con stock disponible o no especificado</label>
 <label>Orden<select name="sort"><?php foreach(['recent'=>'Destacados y recientes','popular'=>'Más vistos','price_asc'=>'Menor precio','price_desc'=>'Mayor precio','title'=>'Nombre A–Z'] as $key=>$text):?><option value="<?=$key?>" <?=$f['sort']===$key?'selected':''?>><?=e($text)?></option><?php endforeach;?></select></label>
 <button class="mkt-button" type="submit">Aplicar filtros</button><a class="mkt-reset" href="<?=url('marketplace.php')?>">Limpiar filtros</a>
 </form>
 <?php if(user()&&$ready):?><form class="mkt-save-search" method="post" action="<?=url('marketplace_actions.php')?>"><?=csrf_field()?><input type="hidden" name="action" value="save_search"><input type="hidden" name="filters" value="<?=e(json_encode($f,JSON_UNESCAPED_UNICODE))?>"><label>Guardar esta búsqueda<input type="text" name="name" maxlength="100" required placeholder="Ej.: Tecnología hasta $50.000"></label><button type="submit">Guardar búsqueda</button></form><?php endif;?>
</aside>
<div class="mkt-results">
 <div class="mkt-results-head"><div><span class="mkt-kicker">CATÁLOGO EMPRESARIAL</span><h2><?=number_format($result['total'],0,',','.')?> artículos encontrados</h2></div><a href="<?=url('marketplace_compare.php')?>">Comparador de productos</a></div>
 <form method="get" action="<?=url('marketplace_compare.php')?>" id="marketplaceCompareForm">
 <div class="mkt-grid">
 <?php foreach($result['items'] as $item):?>
 <article class="mkt-card">
   <a class="mkt-card-image" href="<?=url('product.php?id='.(int)$item['id'])?>"><img loading="lazy" src="<?=url($item['cover_image']?:'assets/cover.svg')?>" alt="<?=e($item['title'])?>"></a>
   <div class="mkt-card-body"><small><?=e($item['category_name'])?> · <?=e($item['merchant_name'])?></small><h3><a href="<?=url('product.php?id='.(int)$item['id'])?>"><span class="<?=e(vip_effect_class($vipEffects[(int)$item['id']]??null))?>"><?=e($item['title'])?></span></a></h3><div class="mkt-price"><?=money_clp((int)$item['price_clp'])?></div>
   <p class="mkt-availability"><?=($item['stock']===null?'Consultar disponibilidad':((int)$item['stock']>0?'Stock: '.(int)$item['stock']:'Sin existencias'))?></p>
   <div class="mkt-card-actions"><a class="mkt-detail" href="<?=url('product.php?id='.(int)$item['id'])?>">Ver artículo</a><label><input class="mkt-compare-checkbox" type="checkbox" name="ids[]" value="<?=(int)$item['id']?>"> Comparar</label></div>
   <?php if(user()&&$ready):?><button class="mkt-fav-button" form="mkt-fav-<?=(int)$item['id']?>" type="submit"><?=isset($favoriteIds[(int)$item['id']])?'♥ Quitar de favoritos':'♡ Añadir a favoritos'?></button><?php endif;?>
   </div>
 </article>
 <?php endforeach;?></div>
 <div class="mkt-compare-footer"><button class="mkt-button" type="submit">Comparar seleccionados (máximo 4)</button><span id="mktCompareCount">Selecciona hasta 4 artículos</span></div>
 </form>
 <?php if(user()&&$ready):?>
 <?php foreach($result['items'] as $item):?><form id="mkt-fav-<?=(int)$item['id']?>" method="post" action="<?=url('marketplace_actions.php')?>"><?=csrf_field()?><input type="hidden" name="action" value="<?=isset($favoriteIds[(int)$item['id']])?'remove_favorite':'add_favorite'?>"><input type="hidden" name="product_id" value="<?=(int)$item['id']?>"></form><?php endforeach;?>
 <?php endif;?>
 <?php if(!$result['items']):?><p class="mkt-empty">No hay artículos con estos filtros. Prueba ampliando la búsqueda.</p><?php endif;?>
 <?php if($result['pages']>1):?><nav class="mkt-pages" aria-label="Páginas de catálogo"><?php for($p=max(1,$result['page']-2);$p<=min($result['pages'],$result['page']+2);$p++):?><a <?=$p===$result['page']?'aria-current="page"':''?> href="<?=url('marketplace.php?'.http_build_query(array_merge($f,['page'=>$p])))?>"><?=$p?></a><?php endfor;?></nav><?php endif;?>
</div>
</div>
</section>
<script src="<?=url('assets/js/marketplace-enterprise.js')?>" defer></script>
<?php require __DIR__.'/../partials/footer.php';?>
