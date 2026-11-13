<?php
require_once __DIR__.'/../app/bootstrap.php'; record_visit(); $me=user();
$hubEnabled=false;$hubNavPages=[];
try {
 $hubEnabled=(int)db()->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='hub_pages'")->fetchColumn()===1;
 if($hubEnabled)$hubNavPages=db()->query("SELECT title,slug FROM hub_pages WHERE status='published' AND show_navigation=1 AND published_at<=NOW() ORDER BY sort_order,id LIMIT 6")->fetchAll();
}catch(Throwable){$hubEnabled=false;$hubNavPages=[];}
$siteName=setting('site_name',APP_NAME);
$metaTitle=trim((string)($pageTitle??$siteName));
if($metaTitle!==$siteName && !str_contains($metaTitle,$siteName))$metaTitle.=' · '.$siteName;
$metaDescription=trim((string)($pageDescription??setting('seo_default_description',setting('site_tagline','Explora publicaciones y comercios locales.'))));
$metaCanonical=(string)($pageCanonical??url(basename(parse_url($_SERVER['REQUEST_URI']??'index.php',PHP_URL_PATH)?:'index.php')));
$metaImage=(string)($pageImage??setting('home_cover','assets/cover.svg'));
if(!preg_match('~^https?://~i',$metaImage))$metaImage=url($metaImage);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($metaTitle)?></title><meta name="description" content="<?=e(mb_strimwidth(strip_tags($metaDescription),0,300,'…'))?>"><link rel="canonical" href="<?=e($metaCanonical)?>"><meta property="og:type" content="website"><meta property="og:site_name" content="<?=e($siteName)?>"><meta property="og:title" content="<?=e($metaTitle)?>"><meta property="og:description" content="<?=e(mb_strimwidth(strip_tags($metaDescription),0,300,'…'))?>"><meta property="og:url" content="<?=e($metaCanonical)?>"><meta property="og:image" content="<?=e($metaImage)?>"><meta name="twitter:card" content="summary_large_image"><link rel="stylesheet" href="<?=public_asset_url('assets/css/app.css')?>"><link rel="stylesheet" href="<?=public_asset_url('assets/css/responsive-fixes.css')?>"><link rel="stylesheet" href="<?=public_asset_url('assets/css/login-pro.css')?>"><link rel="stylesheet" href="<?=public_asset_url('assets/css/urban.css')?>"><?php if(setting('adsense_enabled')==='1'&&setting('adsense_client')):?><script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?=e(setting('adsense_client'))?>" crossorigin="anonymous"></script><?php endif;?><style>:root{--primary:<?=e(setting('primary_color','#2563eb'))?>;--secondary:<?=e(setting('secondary_color','#f5b942'))?>;--button-radius:<?=e(setting('button_radius','2px'))?>}<?=setting('custom_css')?></style><link rel="stylesheet" href="<?=public_asset_url('assets/css/enterprise-blue.css')?>?v=3.0.0"><?php foreach(active_template_styles() as $themeCss):?><link rel="stylesheet" href="<?=e($themeCss)?>"><?php endforeach;?><style id="commerce-theme-studio"><?=studio_css(studio_config())?></style><link rel="stylesheet" href="<?=public_asset_url('assets/css/calle-cristal-navigation.css')?>?v=1.0.0"><script src="<?=public_asset_url('assets/js/calle-cristal-navigation.js')?>" defer></script></head><body class="enterprise-public theme-<?=e(active_template_slug())?>">
<div class="urban-contactbar"><div><span>☎ <?=e(setting('top_phone','Teléfono del comercio'))?></span><span>✉ <?=e(setting('top_email',setting('mail_from_address','contacto@comercio.cl')))?></span></div><div><span>🇨🇱 Español</span><span>CLP $</span></div></div>
<header class="urban-header cc-header">
  <a class="urban-brand cc-brand" href="<?=url('index.php')?>">
   <?php if(setting('logo_image')):?><img class="site-logo-image" src="<?=url(setting('logo_image'))?>" alt="Logo de <?=e($siteName)?>"><?php else:?><span aria-hidden="true"><?=e(setting('logo_icon','🛍️'))?></span><?php endif;?>
   <span class="cc-brand-copy"><b><?=e($siteName)?></b><small>GALERÍA COMERCIAL</small></span>
  </a>
  <form class="urban-search cc-search" role="search" action="<?=url('marketplace.php')?>" method="get">
   <label class="cc-visually-hidden" for="cc-global-search">Buscar productos, comercios y categorías</label>
   <input id="cc-global-search" type="search" name="q" maxlength="100" value="<?=e(is_string($_GET['q']??null)?$_GET['q']:'')?>" placeholder="Buscar productos, comercios y categorías…" aria-label="Buscar en Calle Cristal">
   <button type="submit" aria-label="Buscar en Marketplace">⌕ <span>Buscar</span></button>
  </form>
  <div class="cc-header-actions">
   <?php if($me):?>
    <details class="cc-dropdown cc-account">
     <summary><span aria-hidden="true">♙</span> Mi cuenta <span class="cc-chevron" aria-hidden="true">⌄</span></summary>
     <div class="cc-dropdown-panel cc-account-panel">
      <a href="<?=url('dashboard.php')?>">Mi panel</a>
      <a href="<?=url('profile.php?u='.urlencode($me['username']))?>">Mi perfil</a>
      <a href="<?=url('merchant_center.php')?>">Mi negocio PRO</a>
      <a href="<?=url('profile_edit.php')?>">Editar negocio</a>
      <a href="<?=url('inventory.php')?>">Inventario</a>
      <a href="<?=url('suite_inbox.php')?>">Consultas</a>
      <a href="<?=url('suite_requests.php')?>">Solicitudes</a>
      <a href="<?=url('feedback.php')?>">Feedback</a>
      <a href="<?=url('donations.php')?>">Donaciones</a>
      <?php if($me['role']==='admin'):?><a href="<?=url('../admin/index.php')?>">Panel admin</a><?php endif;?>
      <a href="<?=url('logout.php')?>">Cerrar sesión</a>
     </div>
    </details>
    <a class="cc-publish" href="<?=url('product_create.php')?>">+ Publicar aviso</a>
   <?php else:?>
    <a class="cc-signin" href="<?=url('login.php')?>">Ingresar</a>
    <a class="cc-publish" href="<?=url('register.php')?>">Crear cuenta vendedor</a>
   <?php endif;?>
  </div>
</header>
<nav class="urban-menu cc-navigation" aria-label="Navegación principal">
 <div class="cc-nav-container">
  <details class="cc-mobile-menu">
   <summary aria-label="Abrir menú de navegación"><span aria-hidden="true">☰</span> Menú <span class="cc-chevron" aria-hidden="true">⌄</span></summary>
   <div class="cc-nav-list">
    <a class="cc-nav-home" href="<?=url('index.php')?>">Inicio</a>
    <details class="cc-dropdown cc-nav-group">
     <summary>Comprar <span class="cc-chevron" aria-hidden="true">⌄</span></summary>
     <div class="cc-dropdown-panel">
      <a href="<?=url('marketplace.php')?>">Marketplace</a>
      <a href="<?=url('index.php#catalogo')?>">Explorar catálogo</a>
      <a href="<?=url('index.php?sort=popular#catalogo')?>">Productos destacados</a>
      <a href="<?=url('offers.php')?>">Promociones y campañas</a>
     </div>
    </details>
    <details class="cc-dropdown cc-nav-group">
     <summary>La galería <span class="cc-chevron" aria-hidden="true">⌄</span></summary>
     <div class="cc-dropdown-panel">
      <a href="<?=url('commerces.php')?>">Directorio de comercios</a>
      <a href="<?=url('map.php')?>">Mapa de locales</a>
     </div>
    </details>
    <details class="cc-dropdown cc-nav-group">
     <summary>Actualidad <span class="cc-chevron" aria-hidden="true">⌄</span></summary>
     <div class="cc-dropdown-panel">
      <?php if(setting('news_enabled','1')==='1'):?><a href="<?=url('news_list.php')?>">Noticias</a><?php endif;?>
      <a href="<?=url('index.php#catalogo')?>">Novedades del catálogo</a>
      <?php if($hubEnabled):?>
       <a href="<?=url('content_hub.php')?>">Contenido y guías</a>
       <?php foreach($hubNavPages as $ccHubNav):?><a href="<?=url('page.php?slug='.rawurlencode($ccHubNav['slug']))?>"><?=e($ccHubNav['title'])?></a><?php endforeach;?>
      <?php endif;?>
      <?php if(setting('feedback_public_enabled','0')==='1'):?><a href="<?=url('feedback_public.php')?>">Comunidad</a><?php endif;?>
     </div>
    </details>
    <?php if($me):?><a class="cc-nav-business" href="<?=url('merchant_center.php')?>">Mi negocio PRO</a><?php endif;?>
   </div>
  </details>
  <a class="cc-marketplace-cta" href="<?=url('marketplace.php')?>"><span aria-hidden="true">▣</span> Explorar Marketplace <span aria-hidden="true">→</span></a>
 </div>
</nav>
<?=showcase_notice_html('showcase-top-notice')?>
<?php foreach(flashes() as $f):?><div class="flash <?=e($f['type'])?>"><?=e($f['message'])?></div><?php endforeach;?><main class="layout urban-layout">
