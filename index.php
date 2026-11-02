<?php
declare(strict_types=1);
$installed = is_file(__DIR__ . '/config/config.php') && is_file(__DIR__ . '/storage/installed.lock');
header('Location: ' . ($installed ? 'public/' : 'install/'));
exit;
