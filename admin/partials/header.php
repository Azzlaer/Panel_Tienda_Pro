<?php
ensure_feedback_schema();
ensure_donations_schema();

$adminPage = $adminPage ?? 'dashboard';
$adminTitle = $adminTitle ?? 'Panel administrativo';
$adminSubtitle = $adminSubtitle ?? 'Control general de la vitrina informativa';
$me = user();

$pendingUsers = (int) db()->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$pendingProducts = (int) db()->query("SELECT COUNT(*) FROM products WHERE status='pending'")->fetchColumn();
$unreadFeedback = (int) db()->query("SELECT COUNT(*) FROM feedback_threads WHERE admin_read=0")->fetchColumn();

$navGroups = [
    'Principal' => [
        'dashboard' => ['🏠', 'Resumen', 'index.php'],
        'suite' => ['🧩', 'Commerce Suite', 'commerce_suite.php'],
        'system' => ['🪟', 'Modo vitrina', 'system.php'],
    ],
    'Contenido' => [
        'products' => ['📦', 'Artículos', 'products.php'],
        'content' => ['💬', 'Comunidad', 'content.php'],
        'categories' => ['🗂️', 'Categorías', 'categories.php'],
        'news' => ['📰', 'Noticias', 'news.php'],
        'commerces' => ['🏪', 'Comercios', 'commerces.php'],
        'map' => ['🗺️', 'Mapa de locales', 'map.php'],
        'slides' => ['🖼️', 'Slider de portada', 'slides.php'],
        'ads' => ['📣', 'Publicidad', 'ads.php'],
    ],
    'Comunidad' => [
        'users' => ['👥', 'Usuarios', 'users.php'],
        'feedback' => ['🤝', 'Feedback', 'feedback.php'],
        'donations' => ['💰', 'Aportes', 'donations.php'],
    ],
    'Apariencia' => [
        'builder' => ['🎨', 'Diseño y portada', 'site_builder.php'],
        'studio' => ['🖌️', 'Theme Studio PRO', 'theme_studio.php'],
        'design' => ['🧰', 'Diseño avanzado', 'design.php'],
        'templates' => ['🧩', 'Plantillas', 'templates.php'],
    ],
    'Sistema' => [
        'chile' => ['🇨🇱', 'Configuración Chile', 'chile.php'],
        'health' => ['🛡️', 'Salud del sistema', 'health.php'],
        'security_center' => ['🔐', 'Security Center PRO', 'security_center.php'],
        'updates' => ['⬆️', 'Actualizaciones', 'updates.php'],
        'audit' => ['📜', 'Auditoría', 'audit.php'],
        'backups' => ['💾', 'Copias de seguridad', 'backups.php'],
    ],
];

function admin_nav_badge(string $key, int $pendingUsers, int $pendingProducts, int $unreadFeedback): int
{
    return match ($key) {
        'users' => $pendingUsers,
        'products' => $pendingProducts,
        'feedback' => $unreadFeedback,
        default => 0,
    };
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($adminTitle) ?> · <?= e(setting('site_name', APP_NAME)) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/admin-pro.css') ?>"><link rel="stylesheet" href="<?= url('assets/css/enterprise-admin.css') ?>?v=2.0.0">
<link rel="stylesheet" href="<?=url('assets/css/enterprise-v5.css')?>?v=5.0.0">
</head>
<body class="admin-pro-body enterprise-admin">
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-head">
            <a class="admin-logo" href="index.php">
                <span><?= e(setting('logo_icon', '🛍️')) ?></span>
                <div>
                    <b><?= e(setting('site_name', APP_NAME)) ?></b>
                    <small>Administración</small>
                </div>
            </a>
            <button class="admin-sidebar-collapse" type="button" id="adminSidebarCollapse" aria-label="Contraer menú" title="Contraer o ampliar menú">‹</button>
        </div>

        <nav class="admin-nav" aria-label="Menú administrativo">
            <?php foreach ($navGroups as $groupLabel => $items): ?>
                <section class="admin-nav-group">
                    <button class="enterprise-group-toggle" type="button" aria-expanded="true"><span><?= e($groupLabel) ?></span><span aria-hidden="true">⌄</span></button>
                    <div class="enterprise-nav-items">
                    <?php foreach ($items as $key => $item): ?>
                        <?php $badge = admin_nav_badge($key, $pendingUsers, $pendingProducts, $unreadFeedback); ?>
                        <a class="<?= $adminPage === $key ? 'active' : '' ?>" href="<?= e($item[2]) ?>" title="<?= e($item[1]) ?>">
                            <span class="admin-nav-icon"><?= $item[0] ?></span>
                            <b><?= e($item[1]) ?></b>
                            <?php if ($badge > 0): ?><em><?= $badge ?></em><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar-foot">
            <a href="<?= url('index.php') ?>"><span>↗</span><b>Ver sitio público</b></a>
            <a href="<?= url('logout.php') ?>"><span>⏻</span><b>Cerrar sesión</b></a>
        </div>
    </aside>

    <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="admin-menu-toggle" id="adminMenuToggle" type="button" aria-label="Abrir menú">☰</button>
            <div class="admin-topbar-title">
                <h1><?= e($adminTitle) ?></h1>
                <p><?= e($adminSubtitle) ?></p>
            </div>
            <div class="admin-user">
                <span><?= e($me['display_name'] ?? $me['username']) ?></span>
                <small>Administrador</small>
            </div>
        </header>

        <?php foreach (flashes() as $f): ?>
            <div class="admin-alert <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>

        <main class="admin-content">
