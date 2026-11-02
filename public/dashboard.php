<?php
require_once __DIR__.'/../app/bootstrap.php';require_login();
$me=user();$uid=(int)$me['id'];$pro=profile_pro($uid);
$q=db()->prepare("SELECT COUNT(*) total, COALESCE(SUM(views),0) views, SUM(status='published') published, SUM(status='pending') pending, SUM(status='sold') sold FROM products WHERE user_id=?");$q->execute([$uid]);$stats=$q->fetch();
$q=db()->prepare("SELECT id,title,price_clp,views,status,cover_image,created_at FROM products WHERE user_id=? ORDER BY created_at DESC LIMIT 8");$q->execute([$uid]);$items=$q->fetchAll();
$pageTitle='Mi panel comercial';require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/profile-pro.css')?>?v=5">
<div class="seller-dashboard">
 <div class="cse-seller-hero"><div><span class="cse-label">COMERCIOSOCIAL ENTERPRISE · CENTRO DE VENDEDOR</span><h1>Hola, <?=e($me['display_name']?:$me['username'])?> 👋</h1><p>Gestiona tu vitrina y revisa cómo se están viendo tus artículos.</p></div><div class="cse-seller-actions"><a class="btn" href="<?=url('product_create.php')?>">+ Publicar artículo</a><a class="btn" href="<?=url('profile_edit.php')?>">Editar comercio</a><a class="btn" href="<?=url('inventory.php')?>">Inventario</a><a class="btn" href="<?=url('suite_inbox.php')?>">Consultas</a><a class="btn" href="<?=url('suite_requests.php')?>">Solicitudes</a></div></div>
 <div class="cse-seller-stats"><article><small>Artículos publicados</small><strong><?= (int)$stats['published']?></strong></article><article><small>Vistas acumuladas de artículos</small><strong><?= (int)$stats['views']?></strong></article><article><small>Pendientes de moderación</small><strong><?= (int)$stats['pending']?></strong></article><article><small>Artículos vendidos</small><strong><?= (int)$stats['sold']?></strong></article></div>
 <div class="cse-seller-layout"><section class="cse-seller-panel"><div class="cse-panel-heading"><h2>📦 Mis últimas publicaciones</h2><a href="<?=url('product_create.php')?>">Nueva publicación →</a></div>
 <?php if(!$items):?><p>Todavía no tienes artículos. Publica el primero para comenzar.</p><?php else:?><div class="cse-product-rows"><?php foreach($items as $item):?><div class="cse-product-row"><img src="<?=url($item['cover_image'])?>" alt=""><div><b><?=e($item['title'])?></b><small><?=e($item['status'])?> · <?=money_clp($item['price_clp'])?> CLP</small></div><span>👁 <?= (int)$item['views']?></span></div><?php endforeach;?></div><?php endif;?></section>
 <aside class="cse-seller-panel"><h2>🏪 Estado de mi negocio</h2><p><?=e($me['business_local']?:'Aún sin nombre de local')?></p><div class="cse-status-list"><div><span>Portada y logo</span><b><?= $me['cover']&&$me['avatar']?'✓ Completo':'Pendiente'?></b></div><div><span>Horario de atención</span><b><?= $pro['opening_hours']?'✓ Completo':'Pendiente'?></b></div><div><span>WhatsApp</span><b><?= $me['whatsapp']?'✓ Completo':'Pendiente'?></b></div><div><span>Especialidades</span><b><?= $pro['specialties']?'✓ Completo':'Pendiente'?></b></div></div><a class="btn" href="<?=url('profile.php?u='.urlencode($me['username']))?>">Ver mi tienda pública →</a></aside></div>
</div>
<?php require __DIR__.'/../partials/footer.php';
