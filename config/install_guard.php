<?php
if (!defined('BASE_URL')) { require_once __DIR__ . '/config.php'; }
if (!is_file(__DIR__ . '/installed.lock') || !is_file(__DIR__ . '/database.php')) {
    header('Location: ' . BASE_URL . 'install/index.php');
    exit;
}
