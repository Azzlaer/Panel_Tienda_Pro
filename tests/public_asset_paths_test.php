<?php
declare(strict_types=1);
require_once __DIR__.'/../app/public_asset_paths.php';
function asset_assert(bool $ok,string $message): void {
 echo ($ok?'PASS ':'FAIL ').$message.PHP_EOL;
 if(!$ok)exit(1);
}
asset_assert(public_asset_url('assets/css/app.css','/ComercioSocial/public/index.php')==='/ComercioSocial/public/assets/css/app.css','XAMPP subfolder path');
asset_assert(public_asset_url('assets/css/enterprise-blue.css','/ComercioSocial/admin/index.php')==='/ComercioSocial/public/assets/css/enterprise-blue.css','admin folder asset path');
asset_assert(public_asset_url('assets/css/urban.css','/index.php')==='/assets/css/urban.css','virtual host public root');
asset_assert(public_asset_url('templates/urban/assets/theme.css','/store/public/product.php')==='/store/public/templates/urban/assets/theme.css','theme asset path');
try{public_asset_url('../config/config.php','/store/public/index.php');asset_assert(false,'traversal rejected');}
catch(InvalidArgumentException){asset_assert(true,'traversal rejected');}
$main=(string)file_get_contents(__DIR__.'/../partials/header.php');
$admin=(string)file_get_contents(__DIR__.'/../admin/partials/header.php');
$boot=(string)file_get_contents(__DIR__.'/../app/bootstrap.php');
asset_assert(substr_count($main,"public_asset_url('assets/css/")>=5,'all public core styles use local path');
asset_assert(substr_count($admin,"public_asset_url('assets/css/")>=4,'all admin core styles use local path');
asset_assert(str_contains($boot,"return public_asset_url('templates/'"),'theme styles use local path');
echo "XAMPP asset-path regression checks complete\n";
