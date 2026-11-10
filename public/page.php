<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/content_hub.php';
if(!hub_ready(db())){http_response_code(503);exit('Páginas no configuradas.');}
$slug=trim((string)($_GET['slug']??''));
if(strlen($slug)>190){http_response_code(404);exit('Página no encontrada.');}
$q=db()->prepare("SELECT * FROM hub_pages WHERE slug=? AND status='published' AND published_at<=NOW() LIMIT 1");
$q->execute([$slug]);$page=$q->fetch();
if(!$page){http_response_code(404);exit('Página no encontrada.');}
$pageTitle=$page['seo_title']?:$page['title'];$pageDescription=$page['seo_description']?:$page['summary'];$pageCanonical=url('page.php?slug='.rawurlencode($page['slug']));
require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/content-hub.css')?>">
<article class="hub-public hub-custom-page"><nav class="hub-breadcrumb"><a href="<?=url('index.php')?>">Inicio</a> / <?=e($page['title'])?></nav><header class="hub-page-title"><span class="hub-kicker">PÁGINA INFORMATIVA</span><h1><?=e($page['title'])?></h1><?php if($page['summary']):?><p class="hub-lead"><?=e($page['summary'])?></p><?php endif;?></header>
<?=hub_render_blocks((string)$page['blocks_json'],rtrim(APP_URL,'/'))?>
</article>
<?php require __DIR__.'/../partials/footer.php';?>
