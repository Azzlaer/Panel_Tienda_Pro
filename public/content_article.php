<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/content_hub.php';
if(!hub_ready(db())){http_response_code(503);exit('Contenido no configurado.');}
$slug=trim((string)($_GET['slug']??''));
if(strlen($slug)>190){http_response_code(404);exit('Artículo no encontrado.');}
$q=db()->prepare("SELECT a.*,c.name AS category_name,c.slug AS category_slug,u.username AS author FROM hub_articles a LEFT JOIN hub_categories c ON c.id=a.category_id JOIN users u ON u.id=a.author_id WHERE a.slug=? AND a.status='published' AND a.published_at<=NOW() LIMIT 1");
$q->execute([$slug]);$item=$q->fetch();
if(!$item){http_response_code(404);exit('Artículo no encontrado.');}
db()->prepare('UPDATE hub_articles SET views=views+1 WHERE id=?')->execute([(int)$item['id']]);
$pageTitle=$item['seo_title']?:$item['title'];$pageDescription=$item['seo_description']?:$item['summary'];
$pageCanonical=url('content_article.php?slug='.rawurlencode($item['slug']));
$pageImage=$item['cover_image']?:'assets/cover.svg';
require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/content-hub.css')?>">
<article class="hub-public hub-article-detail">
<nav class="hub-breadcrumb"><a href="<?=url('content_hub.php')?>">Centro de Contenido</a><?php if($item['category_slug']):?> / <a href="<?=url('content_hub.php?category='.rawurlencode($item['category_slug']))?>"><?=e($item['category_name'])?></a><?php endif;?></nav>
<header><span class="hub-kicker">ARTÍCULO · <?=e($item['category_name']??'Editorial')?></span><h1><?=e($item['title'])?></h1><p class="hub-lead"><?=e($item['summary'])?></p><small>Por <?=e($item['author'])?> · <?=date('d/m/Y',strtotime($item['published_at']))?></small></header>
<?php if($item['cover_image']):?><figure class="hub-cover"><img loading="lazy" src="<?=url($item['cover_image'])?>" alt="<?=e($item['title'])?>"></figure><?php endif;?>
<div class="hub-prose hub-article-body"><?=hub_paragraphs((string)$item['body'])?></div>
<?php if($item['tags']):?><div class="hub-tags"><?php foreach(explode(',',(string)$item['tags']) as $tag):if(trim($tag)==='')continue;?><span><?=e(trim($tag))?></span><?php endforeach;?></div><?php endif;?>
<footer><a class="hub-button" href="<?=url('content_hub.php')?>">Volver a artículos</a></footer>
</article>
<?php require __DIR__.'/../partials/footer.php';?>
