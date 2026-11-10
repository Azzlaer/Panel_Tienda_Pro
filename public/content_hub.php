<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/content_hub.php';
if(!hub_ready(db())){http_response_code(503);exit('Content Hub todavía no está configurado.');}
$search=mb_substr(trim((string)($_GET['q']??'')),0,100);
$catSlug=mb_substr(trim((string)($_GET['category']??'')),0,130);
$page=max(1,min(10000,(int)($_GET['page']??1)));$per=12;
$categories=db()->query('SELECT c.name,c.slug,COUNT(a.id) article_count FROM hub_categories c LEFT JOIN hub_articles a ON a.category_id=c.id AND a.status=\'published\' AND a.published_at<=NOW() GROUP BY c.id,c.name,c.slug ORDER BY c.name')->fetchAll();
$where=["a.status='published'","a.published_at<=NOW()"];$params=[];
if($catSlug!==''){$where[]='c.slug=?';$params[]=$catSlug;}
if($search!==''){$where[]='(a.title LIKE ? OR a.summary LIKE ? OR a.tags LIKE ?)';$term='%'.$search.'%';array_push($params,$term,$term,$term);}
$filter=implode(' AND ',$where);
$counter=db()->prepare('SELECT COUNT(*) FROM hub_articles a LEFT JOIN hub_categories c ON c.id=a.category_id WHERE '.$filter);
$counter->execute($params);$count=(int)$counter->fetchColumn();$pages=max(1,(int)ceil($count/$per));$page=min($page,$pages);$offset=($page-1)*$per;
$stmt=db()->prepare('SELECT a.id,a.title,a.slug,a.summary,a.cover_image,a.tags,a.featured,a.published_at,c.name AS category_name,c.slug AS category_slug,u.username AS author FROM hub_articles a LEFT JOIN hub_categories c ON c.id=a.category_id JOIN users u ON u.id=a.author_id WHERE '.$filter.' ORDER BY a.featured DESC,a.published_at DESC,a.id DESC LIMIT '.$per.' OFFSET '.$offset);
$stmt->execute($params);$items=$stmt->fetchAll();
$pageTitle='Centro de Contenido';$pageDescription='Guías, entrevistas, eventos, artículos y recursos de nuestros comercios.';$pageCanonical=url('content_hub.php');
require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/content-hub.css')?>">
<section class="hub-public">
<header class="hub-public-hero"><span>CONTENT HUB · COMERCIOSOCIAL</span><h1>Historias, recursos y novedades</h1><p>Explora artículos, guías, entrevistas y contenidos para nuestra comunidad de negocios.</p></header>
<form method="get" class="hub-search"><label>Buscar contenidos<input type="search" name="q" value="<?=e($search)?>" placeholder="Guías, eventos, consejos..."></label><label>Categoría<select name="category"><option value="">Todas las categorías</option><?php foreach($categories as $c):?><option value="<?=e($c['slug'])?>" <?=$catSlug===$c['slug']?'selected':''?>><?=e($c['name'])?> (<?=(int)$c['article_count']?>)</option><?php endforeach;?></select></label><button type="submit">Buscar</button></form>
<div class="hub-articles"><?php foreach($items as $a):?>
<article class="hub-article-card"><a href="<?=url('content_article.php?slug='.rawurlencode($a['slug']))?>">
<div class="hub-card-image"><img loading="lazy" src="<?=url($a['cover_image']?:'assets/cover.svg')?>" alt="<?=e($a['title'])?>"></div>
<div class="hub-card-content"><small><?=e($a['category_name']??'Editorial')?> · <?=date('d/m/Y',strtotime($a['published_at']))?></small><h2><?=e($a['title'])?></h2><p><?=e($a['summary'])?></p><span>Leer artículo ›</span></div></a></article>
<?php endforeach;?></div>
<?php if(!$items):?><p class="hub-empty">No se encontraron artículos con esos filtros.</p><?php endif;?>
<?php if($pages>1):?><nav class="hub-pagination" aria-label="Paginación"><?php for($n=max(1,$page-2);$n<=min($pages,$page+2);$n++):?><a <?=$n===$page?'aria-current="page"':''?> href="<?=url('content_hub.php?'.http_build_query(['page'=>$n,'q'=>$search,'category'=>$catSlug]))?>"><?=$n?></a><?php endfor;?></nav><?php endif;?>
</section>
<?php require __DIR__.'/../partials/footer.php';?>
