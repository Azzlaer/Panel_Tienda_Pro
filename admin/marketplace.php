<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_admin();
require_once __DIR__.'/../app/marketplace.php';
$adminPage='marketplace';$adminTitle='Marketplace Enterprise';$adminSubtitle='Estadísticas y configuración de colecciones del Marketplace';
$ready=marketplace_ready(db());
$stats=['favorites'=>0,'searches'=>0,'users'=>0];
if($ready){
 $stats['favorites']=(int)db()->query('SELECT COUNT(*) FROM marketplace_favorites')->fetchColumn();
 $stats['searches']=(int)db()->query('SELECT COUNT(*) FROM marketplace_saved_searches')->fetchColumn();
 $stats['users']=(int)db()->query('SELECT COUNT(DISTINCT user_id) FROM marketplace_favorites')->fetchColumn();
}
require __DIR__.'/partials/header.php';
?>
<section class="panel-card"><div class="panel-head"><div><h2>🛍️ Marketplace Enterprise</h2><p>Explorador de productos, comparador, favoritos y búsquedas privadas.</p></div><a class="admin-btn" href="<?=url('marketplace.php')?>" target="_blank" rel="noopener">Abrir Marketplace público</a></div>
<?php if(!$ready):?><p>Las tablas personales del Marketplace todavía no están activadas. El catálogo se puede explorar sin ellas.</p><a class="admin-btn" href="integration_center.php">Activar desde Integration Center</a>
<?php else:?><div class="admin-grid cols-3">
<div class="panel-card"><h3><?=(int)$stats['favorites']?></h3><p>Favoritos guardados</p></div>
<div class="panel-card"><h3><?=(int)$stats['searches']?></h3><p>Búsquedas guardadas</p></div>
<div class="panel-card"><h3><?=(int)$stats['users']?></h3><p>Usuarios con favoritos</p></div>
</div><?php endif;?>
<p>El Marketplace utiliza los artículos publicados del sistema existente y no almacena una segunda copia del catálogo. Los favoritos y filtros guardados son privados para cada usuario.</p>
<p>Las ventas, pedidos, precios y existencias continúan gestionándose mediante los módulos comerciales existentes.</p>
</section>
<?php require __DIR__.'/partials/footer.php';
