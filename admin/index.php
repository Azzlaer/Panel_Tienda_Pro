<?php
require_once __DIR__.'/../app/bootstrap.php';require_admin();ensure_feedback_schema();ensure_donations_schema();
$adminPage='dashboard';$adminTitle='Panel administrativo';$adminSubtitle='Centro de operaciones, actividad y estado general de ComercioSocial';
$stats=[
 'users'=>(int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
 'pending_users'=>(int)db()->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn(),
 'products'=>(int)db()->query('SELECT COUNT(*) FROM products')->fetchColumn(),
 'pending_products'=>(int)db()->query("SELECT COUNT(*) FROM products WHERE status='pending'")->fetchColumn(),
 'published_products'=>(int)db()->query("SELECT COUNT(*) FROM products WHERE status='published'")->fetchColumn(),
 'visits'=>(int)db()->query('SELECT COUNT(*) FROM visits')->fetchColumn(),
 'visits_today'=>(int)db()->query('SELECT COUNT(*) FROM visits WHERE DATE(created_at)=CURDATE()')->fetchColumn(),
 'visits_week'=>(int)db()->query('SELECT COUNT(*) FROM visits WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)')->fetchColumn(),
 'unique'=>(int)db()->query('SELECT COUNT(DISTINCT ip_hash) FROM visits')->fetchColumn(),
 'feedback'=>(int)db()->query("SELECT COUNT(*) FROM feedback_threads WHERE admin_read=0")->fetchColumn(),
 'donations'=>(int)db()->query("SELECT COUNT(*) FROM donations WHERE status='pending'")->fetchColumn(),
];
ensure_commerce_directory_schema();ensure_home_slides_schema();ensure_news_schema();ensure_profile_pro_schema();
$hubStats=[
 'stores'=>(int)db()->query('SELECT COUNT(*) FROM commerce_directory WHERE active=1')->fetchColumn(),
 'slides'=>(int)db()->query('SELECT COUNT(*) FROM home_slides WHERE active=1')->fetchColumn(),
 'news'=>(int)db()->query("SELECT COUNT(*) FROM news WHERE status='published'")->fetchColumn(),
 'pro'=>(int)db()->query('SELECT COUNT(*) FROM profile_pro')->fetchColumn(),
];
$topSellers=db()->query("SELECT u.username,p.display_name,COUNT(pr.id) articles,COALESCE(SUM(pr.views),0) views FROM users u JOIN profiles p ON p.user_id=u.id LEFT JOIN products pr ON pr.user_id=u.id AND pr.status='published' WHERE u.status='active' GROUP BY u.id,u.username,p.display_name ORDER BY articles DESC,views DESC LIMIT 5")->fetchAll();
$recentUsers=db()->query('SELECT u.*,p.display_name FROM users u LEFT JOIN profiles p ON p.user_id=u.id ORDER BY u.created_at DESC LIMIT 6')->fetchAll();
$recentProducts=db()->query('SELECT pr.id,pr.title,pr.status,pr.price_clp,pr.created_at,p.display_name FROM products pr JOIN profiles p ON p.user_id=pr.user_id ORDER BY pr.created_at DESC LIMIT 6')->fetchAll();
$daily=db()->query("SELECT DATE(created_at) d,COUNT(*) n FROM visits WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 13 DAY) GROUP BY DATE(created_at) ORDER BY d")->fetchAll();
$map=[];foreach($daily as $d)$map[$d['d']]=(int)$d['n'];$max=max(1,...array_values($map?:[1]));
$topCats=db()->query("SELECT c.name,COUNT(*) total FROM products pr JOIN categories c ON c.id=pr.category_id WHERE pr.status='published' GROUP BY c.id,c.name ORDER BY total DESC LIMIT 5")->fetchAll();
$maxCat=max(1,...array_map(fn($x)=>(int)$x['total'],$topCats?:[['total'=>1]]));
require __DIR__.'/partials/header.php';
?>
<section class="nexus-command" aria-label="Centro de operaciones Nexus">
  <div><small>ADMINCP NEXUS 2.0</small><h2>Centro de operaciones</h2><p>Supervisa tus locales, publicaciones y herramientas administrativas.</p></div>
  <div class="nexus-links"><a href="products.php">Moderar productos</a><a href="commerces.php">Directorio</a><a href="theme_studio.php">Theme Studio</a><a href="health.php">Diagnóstico</a></div>
</section>
<div class="admin-grid cols-4">
 <div class="metric-card"><div class="metric-icon">👥</div><div><strong><?=$stats['users']?></strong><small>Vendedores registrados</small><span class="dashboard-kpi-note"><?=$stats['pending_users']?> pendientes</span></div></div>
 <div class="metric-card info"><div class="metric-icon">📦</div><div><strong><?=$stats['published_products']?></strong><small>Artículos publicados</small><span class="dashboard-kpi-note"><?=$stats['pending_products']?> por moderar</span></div></div>
 <div class="metric-card"><div class="metric-icon">👁️</div><div><strong><?=$stats['visits_today']?></strong><small>Visitas hoy</small><span class="dashboard-kpi-note"><?=$stats['visits_week']?> últimos 7 días</span></div></div>
 <div class="metric-card warning"><div class="metric-icon">🔔</div><div><strong><?=$stats['feedback']+$stats['donations']+$stats['pending_users']+$stats['pending_products']?></strong><small>Pendientes operativos</small><span class="dashboard-kpi-note">requieren revisión</span></div></div>
</div>
<section class="panel-card cse-command-panel"><div class="panel-head"><div><span class="cse-command-kicker">CENTRO DE OPERACIONES</span><h2>🚀 ComercioSocial Commerce Hub</h2><p>Gestiona contenido, locales y vendedores desde un único lugar.</p></div><a class="admin-btn small" href="commerces.php">Gestionar comercios →</a></div>
<div class="cse-hub-grid"><a href="commerces.php"><b>🏪 <?=$hubStats['stores']?></b><small>Locales visibles</small></a><a href="news.php"><b>📰 <?=$hubStats['news']?></b><small>Noticias publicadas</small></a><a href="slides.php"><b>🖼️ <?=$hubStats['slides']?></b><small>Banners activos</small></a><a href="users.php"><b>✨ <?=$hubStats['pro']?></b><small>Perfiles PRO configurados</small></a></div></section>
<section class="panel-card"><div class="panel-head"><div><h2>⚡ Acciones rápidas</h2><p>Atajos para las tareas de administración más frecuentes</p></div></div><div class="quick-actions"><a class="quick-action" href="users.php">✅ Aprobar vendedores</a><a class="quick-action" href="products.php">🔎 Moderar artículos</a><a class="quick-action" href="content.php">💬 Moderar comunidad</a><a class="quick-action" href="site_builder.php">🎨 Editar portada</a></div></section>
<div class="dashboard-split">
 <section class="panel-card"><div class="panel-head"><div><h2>📈 Actividad últimos 14 días</h2><p><?=$stats['visits']?> visitas históricas · <?=$stats['unique']?> visitantes únicos estimados</p></div></div><div class="mini-bars"><?php for($i=13;$i>=0;$i--):$date=date('Y-m-d',strtotime("-$i days"));$n=$map[$date]??0;$h=max(5,round($n/$max*100));?><span style="height:<?=$h?>%" data-label="<?=date('d/m',strtotime($date))?> · <?=$n?>"></span><?php endfor;?></div></section>
 <section class="panel-card"><div class="panel-head"><div><h2>🚨 Bandeja de pendientes</h2><p>Lo que necesita atención administrativa</p></div></div><div class="dashboard-list"><a href="users.php"><span>Usuarios por aprobar</span><b class="dashboard-alert-number"><?=$stats['pending_users']?></b></a><a href="products.php"><span>Artículos por moderar</span><b class="dashboard-alert-number"><?=$stats['pending_products']?></b></a><a href="feedback.php"><span>Feedback sin leer</span><b class="dashboard-alert-number"><?=$stats['feedback']?></b></a><a href="donations.php"><span>Aportes por revisar</span><b class="dashboard-alert-number"><?=$stats['donations']?></b></a></div></section>
</div>
<div class="admin-grid cols-2">
 <section class="panel-card"><div class="panel-head"><div><h2>🗂️ Categorías con más publicaciones</h2><p>Distribución del catálogo publicado</p></div></div><div class="dashboard-list"><?php foreach($topCats as $c):?><div><span><b><?=e($c['name'])?></b><div class="dashboard-progress"><i style="width:<?=max(4,round(((int)$c['total']/$maxCat)*100))?>%"></i></div></span><strong><?=$c['total']?></strong></div><?php endforeach;?><?php if(!$topCats):?><div class="dashboard-empty">Aún no hay artículos publicados.</div><?php endif;?></div></section>
 <section class="panel-card"><div class="panel-head"><div><h2>🇨🇱 Estado comercial</h2><p>Configuración esencial para operar en Chile</p></div><a class="admin-btn small secondary" href="chile.php">Configurar</a></div><div class="health-row"><span>Moneda principal</span><b>CLP · Peso chileno</b></div><div class="health-row"><span>Dólar referencial</span><b><?=e(setting('usd_clp_manual','0')==='0'?'Automático':'Manual')?></b></div><div class="health-row"><span>WhatsApp de contacto</span><b><?=setting('market_whatsapp')?'Configurado':'Pendiente'?></b></div><div class="health-row"><span>Información legal</span><b><?=setting('legal_business_name')?'Configurada':'Pendiente'?></b></div></section>
</div>
<section class="panel-card"><div class="panel-head"><div><h2>🏆 Comercios con más artículos publicados</h2><p>Clasificación de perfiles activos según sus productos visibles.</p></div></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Comercio</th><th>Artículos</th><th>Vistas de artículos</th><th>Perfil</th></tr></thead><tbody><?php foreach($topSellers as $sellerRow):?><tr><td><?=e($sellerRow['display_name'])?></td><td><?= (int)$sellerRow['articles']?></td><td><?= (int)$sellerRow['views']?></td><td><a target="_blank" rel="noopener" href="<?=url('profile.php?u='.urlencode($sellerRow['username']))?>">Ver →</a></td></tr><?php endforeach;?></tbody></table></div></section>
<div class="admin-grid cols-2">
 <section class="panel-card"><div class="panel-head"><div><h2>👥 Últimos vendedores</h2><p>Cuentas creadas recientemente</p></div><a href="users.php">Ver todos →</a></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Vendedor</th><th>Estado</th></tr></thead><tbody><?php foreach($recentUsers as $u):?><tr><td><b><?=e($u['display_name']?:$u['username'])?></b><small>@<?=e($u['username'])?> · <?=e($u['email'])?></small></td><td><span class="status <?=e($u['status'])?>"><?=e($u['status'])?></span></td></tr><?php endforeach;?></tbody></table></div></section>
 <section class="panel-card"><div class="panel-head"><div><h2>📦 Últimos artículos</h2><p>Contenido incorporado al catálogo</p></div><a href="products.php">Ver todos →</a></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Artículo</th><th>Estado</th></tr></thead><tbody><?php foreach($recentProducts as $p):?><tr><td><b><?=e($p['title'])?></b><small><?=e($p['display_name'])?> · <?=money_clp($p['price_clp'])?></small></td><td><span class="status <?=e($p['status'])?>"><?=e($p['status'])?></span></td></tr><?php endforeach;?></tbody></table></div></section>
</div>
<?php require __DIR__.'/partials/footer.php'; ?>
