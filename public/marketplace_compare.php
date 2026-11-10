<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/marketplace.php';
$ids=marketplace_selected_ids(is_array($_GET['ids']??null)?$_GET['ids']:[]);
$items=marketplace_comparison(db(),$ids);
$pageTitle='Comparador de artículos';$pageDescription='Compara productos publicados por los comercios de nuestra plataforma.';
require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/marketplace-enterprise.css')?>">
<section class="mkt mkt-compare-page"><header class="mkt-results-head"><div><span class="mkt-kicker">MARKETPLACE ENTERPRISE</span><h1>Comparador de artículos</h1><p>Compara hasta cuatro productos públicos y contacta a los vendedores para confirmar detalles.</p></div><a href="<?=url('marketplace.php')?>">Volver al catálogo</a></header>
<?php if(!$items):?><p class="mkt-empty">Selecciona productos desde el Marketplace para compararlos.</p><?php else:?>
<div class="mkt-compare-scroll"><table class="mkt-compare-table"><thead><tr><th>Característica</th><?php foreach($items as $a):?><th><img src="<?=url($a['cover_image']?:'assets/cover.svg')?>" alt=""><a href="<?=url('product.php?id='.(int)$a['id'])?>"><?=e($a['title'])?></a></th><?php endforeach;?></tr></thead><tbody>
<tr><th>Precio CLP</th><?php foreach($items as $a):?><td><?=money_clp((int)$a['price_clp'])?></td><?php endforeach;?></tr>
<tr><th>Comercio</th><?php foreach($items as $a):?><td><?=e($a['merchant_name'])?></td><?php endforeach;?></tr>
<tr><th>Categoría</th><?php foreach($items as $a):?><td><?=e($a['category_name'])?></td><?php endforeach;?></tr>
<tr><th>Existencias</th><?php foreach($items as $a):?><td><?=$a['stock']===null?'Consultar':(int)$a['stock']?></td><?php endforeach;?></tr>
<tr><th>Vistas</th><?php foreach($items as $a):?><td><?=(int)$a['views']?></td><?php endforeach;?></tr>
<tr><th>Descripción</th><?php foreach($items as $a):?><td><?=e(mb_strimwidth(strip_tags((string)$a['description']),0,350,'…'))?></td><?php endforeach;?></tr>
</tbody></table></div>
<?php endif;?></section>
<?php require __DIR__.'/../partials/footer.php';?>
