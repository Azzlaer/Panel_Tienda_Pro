<?php
require_once dirname(__DIR__) . '/config/config.php';
if (!is_file(BASE_PATH . '/config/installed.lock') || !is_file(BASE_PATH . '/config/database.php')) {
    header('Location: ' . BASE_URL . 'install/index.php'); exit;
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MiTienda instalado</title><link rel="stylesheet" href="<?=BASE_URL?>install/style.css"></head><body><main class="shell"><div class="brand">🛍️ MiTienda <span>Setup</span></div><section class="card success"><div class="success-icon">✓</div><h1>¡Instalación completada!</h1><p>Se crearon las tablas y tu cuenta de administrador. Ya puedes ingresar al marketplace.</p><a class="submit" href="<?=BASE_URL?>index.php">Abrir MiTienda →</a><p class="hint">Por seguridad, el instalador quedó bloqueado. Puedes eliminar la carpeta <code>install/</code> después de instalar.</p></section></main></body></html>
