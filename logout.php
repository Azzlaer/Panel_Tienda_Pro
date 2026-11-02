<?php
require __DIR__.'/config/config.php';
require __DIR__.'/config/install_guard.php';
require __DIR__.'/config/session.php';
$_SESSION=[];session_regenerate_id(true);session_destroy();header('Location: '.BASE_URL.'index.php');exit;
