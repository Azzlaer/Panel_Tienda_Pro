<?php
require_once __DIR__.'/../app/bootstrap.php';
ensure_news_schema();
ensure_home_slides_schema();
$homeSlides=db()->query('SELECT * FROM home_slides WHERE active=1 ORDER BY sort_order ASC,id DESC')->fetchAll();
$sliderSeconds=max(3,min(60,(int)setting('home_slider_seconds','6')));
$q=trim((string)($_GET['q']??''));
$cat=max(0,(int)($_GET['cat']??0));
$minPrice=max(0,(int)($_GET['min_price']??0));
$maxPrice=max(0,(int)($_GET['max_price']??0));
$sort=in_array($_GET['sort']??'', ['recent','popular','price_asc','price_desc'],true)?$_GET['sort']:'recent';
$page=max(1,(int)($_GET['page']??1));$perPage=24;
$where=["pr.status='published'"];$args=[];
if($q!==''){$where[]='(pr.title LIKE ? OR pr.description LIKE ? OR p.display_name LIKE ? OR p.business_local LIKE ? OR p.location LIKE ? OR p.business_address LIKE ? OR c.name LIKE ?)';$like='%'.$q.'%';for($i=0;$i<7;$i++)$args[]=$like;}
if($cat>0){$where[]='(c.id=? OR c.parent_id=?)';$args[]=$cat;$args[]=$cat;}
if($minPrice>0){$where[]='pr.price_clp>=?';$args[]=$minPrice;}
if($maxPrice>0){$where[]='pr.price_clp<=?';$args[]=$maxPrice;}
$whereSql=' WHERE '.implode(' AND ',$where);
$order=match($sort){'popular'=>'pr.views DESC,pr.featured DESC,pr.created_at DESC','price_asc'=>'pr.price_clp ASC,pr.created_at DESC','price_desc'=>'pr.price_clp DESC,pr.created_at DESC',default=>'pr.featured DESC,pr.created_at DESC'};
$count=db()->prepare("SELECT COUNT(*) FROM products pr JOIN categories c ON c.id=pr.category_id JOIN users u ON u.id=pr.user_id JOIN profiles p ON p.user_id=u.id{$whereSql}");$count->execute($args);$totalProducts=(int)$count->fetchColumn();$pages=max(1,(int)ceil($totalProducts/$perPage));$page=min($page,$pages);$offset=($page-1)*$perPage;
$sql="SELECT pr.*,c.name category_name,u.username,p.display_name,p.avatar,p.cover,p.business_local,p.business_number,p.business_address,p.location FROM products pr JOIN categories c ON c.id=pr.category_id JOIN users u ON u.id=pr.user_id JOIN profiles p ON p.user_id=u.id{$whereSql} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}";$st=db()->prepare($sql);$st->execute($args);$products=$st->fetchAll();
$cats=db()->query("SELECT c.*, (SELECT COUNT(*) FROM products pr WHERE pr.category_id=c.id AND pr.status='published') total, (SELECT pr.cover_image FROM products pr JOIN categories cc ON cc.id=pr.category_id WHERE pr.status='published' AND (cc.id=c.id OR cc.parent_id=c.id) ORDER BY pr.featured DESC,pr.created_at DESC LIMIT 1) cover_image FROM categories c WHERE c.active=1 AND c.parent_id IS NULL ORDER BY c.sort_order,c.name")->fetchAll();
$featured=array_values(array_filter($products,fn($p)=>(int)$p['featured']===1));
$newArrivals=array_slice($products,0,8);
$bestSellers=$products; usort($bestSellers,fn($a,$b)=>(int)$b['views']<=>(int)$a['views']); $bestSellers=array_slice($bestSellers,0,4);
$topRated=$products; usort($topRated,fn($a,$b)=>seller_stars((int)$b['user_id'])<=>seller_stars((int)$a['user_id'])); $topRated=array_slice($topRated,0,4);
$weekly=array_slice($featured ?: $products,0,4);
$latestNews=[]; if(setting('news_enabled','1')==='1'){$latestNews=db()->query("SELECT n.*,u.username,p.display_name FROM news n JOIN users u ON u.id=n.created_by LEFT JOIN profiles p ON p.user_id=u.id WHERE n.status='published' AND n.show_home=1 ORDER BY n.featured DESC,COALESCE(n.published_at,n.created_at) DESC LIMIT 4")->fetchAll();}
$highlightCommerces=[];ensure_commerce_directory_schema();$highlightCommerces=db()->query("SELECT d.*,u.username,p.avatar FROM commerce_directory d JOIN users u ON u.id=d.user_id JOIN profiles p ON p.user_id=u.id WHERE d.active=1 AND u.status='active' ORDER BY d.featured DESC,d.sort_order,d.id DESC LIMIT 4")->fetchAll();
$latestPosts=db()->query("SELECT po.*,u.username,p.display_name FROM posts po JOIN users u ON u.id=po.user_id JOIN profiles p ON p.user_id=u.id WHERE po.status='published' ORDER BY po.created_at DESC LIMIT 4")->fetchAll();
$pageTitle=$q!==''?'Resultados para “'.$q.'”':setting('site_name',APP_NAME);
$pageDescription=setting('seo_default_description',setting('site_tagline','Explora artículos, servicios y comercios locales en Chile.'));
$pageCanonical=url('index.php');
require __DIR__.'/../partials/header.php';
?>
<?php if($homeSlides): ?>
<section data-studio-section="hero" class="cs-slider" data-interval="<?=$sliderSeconds*1000?>" aria-roledescription="carrusel" aria-label="Promociones destacadas" tabindex="0">
 <div class="cs-slider-track">
 <?php foreach($homeSlides as $i=>$slide): ?>
 <article class="cs-slide<?=$i===0?' is-active':''?>" aria-hidden="<?=$i===0?'false':'true'?>" data-index="<?=$i?>">
 <img src="<?=e(url($slide['image']))?>" alt="<?=e($slide['title']?:'Promoción destacada')?>" <?=$i===0?'fetchpriority="high"':'loading="lazy"'?>>
 <?php if($slide['title']!==''||$slide['subtitle']!==''||($slide['button_text']!==''&&$slide['button_url']!=='')): ?>
 <div class="cs-slide-overlay"><div class="cs-slide-copy">
 <?php if($slide['title']!==''):?><h2><?=e($slide['title'])?></h2><?php endif;?>
 <?php if($slide['subtitle']!==''):?><p><?=e($slide['subtitle'])?></p><?php endif;?>
 <?php if($slide['button_text']!==''&&$slide['button_url']!==''):?><a class="cs-slide-cta" href="<?=e(slide_destination($slide['button_url']))?>"><?=e($slide['button_text'])?></a><?php endif;?>
 </div></div><?php endif;?>
 </article>
 <?php endforeach;?>
 </div>
 <?php if(count($homeSlides)>1): ?>
 <button class="cs-slider-arrow cs-prev" type="button" aria-label="Imagen anterior">‹</button><button class="cs-slider-arrow cs-next" type="button" aria-label="Imagen siguiente">›</button>
 <div class="cs-slider-dots" aria-label="Seleccionar imagen"><?php foreach($homeSlides as $i=>$slide):?><button type="button" data-slide="<?=$i?>" aria-label="Ver imagen <?=$i+1?>" aria-current="<?=$i===0?'true':'false'?>"></button><?php endforeach;?></div>
 <?php endif;?>
</section>
<link rel="stylesheet" href="<?=url('assets/css/home-slider.css')?>?v=4">
<script src="<?=url('assets/js/home-slider.js')?>?v=4" defer></script>
<?php else: ?>
<section data-studio-section="hero" class="urban-hero" style="background-image:linear-gradient(90deg,rgba(255,255,255,.05),rgba(255,255,255,.72)),url('<?=e(setting('home_cover','assets/cover.svg'))?>')">
  <div class="urban-hero-copy"><span class="urban-kicker"><?=e(setting('home_kicker','TIENDAS LOCALES · GRANDES HISTORIAS'))?></span><h1><?=e(setting('home_title',setting('site_name',APP_NAME)))?></h1><p><?=e(setting('home_description',setting('site_tagline','Explora artículos y servicios publicados y contacta directamente a cada anunciante.')))?></p><div class="hero-actions"><a class="btn urban-btn" href="#catalogo">🔎 Explorar vitrina</a><?php if(!user()&&setting('home_show_register','1')==='1'):?><a class="btn urban-btn ghost" href="register.php">🏪 Registrar mi vitrina</a><?php endif;?></div></div>
</section>
<?php endif; ?>

<?php if($cats): ?><section data-studio-section="categories" class="urban-category-grid"><?php foreach(array_slice($cats,0,3) as $c):?><a class="urban-category-card" href="?cat=<?=$c['id']?>" style="background-image:linear-gradient(0deg,rgba(0,0,0,.62),rgba(0,0,0,.05)),url('<?=url($c['cover_image']?:'assets/cover.svg')?>')"><span><?=e($c['icon']?:'📦')?> <?=e($c['name'])?></span><small><?=$c['total']?> publicaciones</small></a><?php endforeach;?></section><?php endif;?>

<?=adsense('home')?>

<section data-studio-section="catalog" class="urban-catalog-tools" id="catalogo">
<form method="get" class="urban-filter-form">
 <div class="urban-filter-search"><label>Buscar</label><input name="q" value="<?=e($q)?>" placeholder="Artículo, servicio, tienda, comuna..."></div>
 <div><label>Categoría</label><select name="cat"><option value="0">Todas</option><?php foreach($cats as $c):?><option value="<?=$c['id']?>" <?=$cat===(int)$c['id']?'selected':''?>><?=e($c['icon'].' '.$c['name'])?></option><?php endforeach;?></select></div>
 <div><label>Precio desde</label><input type="number" min="0" step="1000" name="min_price" value="<?=$minPrice?:''?>" placeholder="$0"></div>
 <div><label>Precio hasta</label><input type="number" min="0" step="1000" name="max_price" value="<?=$maxPrice?:''?>" placeholder="Sin tope"></div>
 <div><label>Ordenar</label><select name="sort"><option value="recent" <?=$sort==='recent'?'selected':''?>>Más recientes</option><option value="popular" <?=$sort==='popular'?'selected':''?>>Más vistos</option><option value="price_asc" <?=$sort==='price_asc'?'selected':''?>>Menor precio</option><option value="price_desc" <?=$sort==='price_desc'?'selected':''?>>Mayor precio</option></select></div>
 <div class="urban-filter-actions"><button class="btn urban-btn">🔎 Buscar</button><a href="index.php#catalogo">Limpiar</a></div>
</form>
<div class="urban-results-summary"><b><?=number_format($totalProducts)?></b> publicaciones encontradas<?php if($q!==''):?> para <strong>“<?=e($q)?>”</strong><?php endif;?></div>
</section>
<section data-studio-section="catalog" class="urban-section"><div class="urban-section-title"><div><span>VITRINA COMERCIAL</span><h2><?=($q!==''||$cat>0||$minPrice>0||$maxPrice>0)?'Resultados de búsqueda':'Artículos y servicios recientes'?></h2></div><a href="<?=url('index.php')?>">Ver todo →</a></div>
<div class="urban-product-grid"><?php foreach($products as $p):?><article class="urban-product-card"><a class="urban-product-image" href="product.php?id=<?=$p['id']?>"><img src="<?=url($p['cover_image'])?>" alt="<?=e($p['title'])?>"><?php if($p['featured']):?><b class="urban-sale">DESTACADO</b><?php endif;?></a><div class="urban-product-info"><small><?=e($p['category_name'])?></small><h3><a href="product.php?id=<?=$p['id']?>"><?=e($p['title'])?></a></h3><div class="urban-price"><?=money_clp($p['price_clp'])?></div><div class="urban-seller"><img src="<?=url($p['avatar']?:'assets/img-avatar.svg')?>"><span><a href="profile.php?u=<?=urlencode($p['username'])?>"><?=e($p['display_name'])?></a><?=stars_html(seller_stars((int)$p['user_id']))?></span></div></div></article><?php endforeach;?></div></section>

<?php if($pages>1):?><nav class="urban-pagination" aria-label="Paginación"><?php $base=$_GET;for($i=1;$i<=$pages;$i++):if($i>1&&$i<$page-2)continue;if($i<$pages&&$i>$page+2)continue;$base['page']=$i;?><a class="<?=$i===$page?'active':''?>" href="?<?=e(http_build_query($base))?>#catalogo"><?=$i?></a><?php endfor;?></nav><?php endif;?>

<section data-studio-section="promo" class="urban-promo" style="background-image:linear-gradient(90deg,rgba(34,34,34,.88),rgba(34,34,34,.38)),url('<?=e(setting('promo_cover',setting('home_cover','assets/cover.svg')))?>')"><div><span><?=e(setting('promo_kicker','VITRINA DESTACADA'))?></span><h2><?=e(setting('promo_title','Descubre publicaciones de comercios locales'))?></h2><p><?=e(setting('promo_description','Solicita información directamente al anunciante mediante sus canales publicados.'))?></p><a class="btn urban-btn" href="#catalogo">⭐ Ver destacados</a></div></section>

<section data-studio-section="featured" class="urban-three-columns"><div><h3>🏆 MÁS CONSULTADOS</h3><?php foreach($bestSellers as $p):?><a class="urban-mini-product" href="product.php?id=<?=$p['id']?>"><img src="<?=url($p['cover_image'])?>"><span><?=e($p['title'])?><b><?=money_clp($p['price_clp'])?></b></span></a><?php endforeach;?></div><div><h3>⭐ ANUNCIANTES DESTACADOS</h3><?php foreach($topRated as $p):?><a class="urban-mini-product" href="product.php?id=<?=$p['id']?>"><img src="<?=url($p['cover_image'])?>"><span><?=e($p['title'])?><b><?=stars_html(seller_stars((int)$p['user_id']))?></b></span></a><?php endforeach;?></div><div><h3>🔥 DESTACADOS</h3><?php foreach($weekly as $p):?><a class="urban-mini-product" href="product.php?id=<?=$p['id']?>"><img src="<?=url($p['cover_image'])?>"><span><?=e($p['title'])?><b><?=money_clp($p['price_clp'])?></b></span></a><?php endforeach;?></div></section>

<?php if($highlightCommerces):?><section data-studio-section="commerces" class="urban-section"><div class="urban-section-title"><div><span>LOCALES Y TIENDAS</span><h2>Conoce nuestros comercios</h2></div><a href="commerces.php">Ver todos los comercios →</a></div><div class="directory-grid"><?php foreach($highlightCommerces as $c):?><a class="directory-card" href="profile.php?u=<?=urlencode($c['username'])?>"><div class="directory-image"><img loading="lazy" src="<?=url($c['image']?:$c['avatar']?:'assets/cover.svg')?>" alt="<?=e($c['name'])?>"></div><div class="directory-info"><small>LOCAL <?=e($c['local_number']?:'—')?></small><h2><?=e($c['name'])?></h2><p><?=e(mb_strimwidth($c['description'],0,130,'…','UTF-8'))?></p><strong>Ver productos →</strong></div></a><?php endforeach;?></div></section><?php endif;?>
<?php if($latestNews):?><section data-studio-section="news" id="noticias" class="urban-section news-home-section"><div class="urban-section-title"><div><span>ACTUALIDAD</span><h2>Noticias del comercio</h2></div><a href="news_list.php">Ver todas las noticias →</a></div><div class="urban-article-grid news-home-grid"><?php foreach($latestNews as $n):?><article class="news-home-card"><?php if($n['featured']):?><b class="news-featured-badge">⭐ DESTACADA</b><?php endif;?><a href="news.php?id=<?=$n['id']?>"><img src="<?=url($n['cover_image']?:'assets/cover.svg')?>" alt="<?=e($n['title'])?>"></a><div><h3><a href="news.php?id=<?=$n['id']?>"><?=e($n['title'])?></a></h3><small><?=date('d/m/Y',strtotime($n['published_at']?:$n['created_at']))?> · Por <?=e($n['display_name']?:$n['username'])?></small><p><?=e(news_excerpt($n,145))?></p><a class="urban-readmore" href="news.php?id=<?=$n['id']?>">LEER NOTICIA</a></div></article><?php endforeach;?></div></section><?php endif;?>

<?php if($latestPosts):?><section data-studio-section="community" class="urban-section"><div class="urban-section-title"><div><span>COMUNIDAD</span><h2>Últimas noticias y publicaciones</h2></div></div><div class="urban-article-grid"><?php foreach($latestPosts as $post):?><article><a href="post.php?id=<?=$post['id']?>"><img src="<?=url($post['image']?:'assets/cover.svg')?>" alt="<?=e($post['title']?:'Publicación')?>"></a><div><h3><a href="post.php?id=<?=$post['id']?>"><?=e($post['title']?:'Publicación de '.$post['display_name'])?></a></h3><small>Por <?=e($post['display_name'])?> · <?=date('d/m/Y',strtotime($post['created_at']))?></small><p><?=e(mb_strimwidth(strip_tags($post['body']),0,150,'…'))?></p><a class="urban-readmore" href="post.php?id=<?=$post['id']?>">LEER MÁS</a></div></article><?php endforeach;?></div></section><?php endif;?>
<?php if(!$products):?><div class="empty">📭 Todavía no existen publicaciones visibles.</div><?php endif;?>
<?php require __DIR__.'/../partials/footer.php'; ?>
