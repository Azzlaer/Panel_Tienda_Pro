<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
ensure_commerce_directory_schema();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id){http_response_code(404);exit('Comercio no encontrado.');}
$st=db()->prepare("SELECT d.name,d.local_number,u.username FROM commerce_directory d JOIN users u ON u.id=d.user_id WHERE d.id=? AND d.active=1 AND u.status='active' LIMIT 1");$st->execute([$id]);$store=$st->fetch();
if(!$store){http_response_code(404);exit('Comercio no disponible.');}
$link=url('profile.php?u='.rawurlencode($store['username']));
$pageTitle='QR de '.$store['name'];require __DIR__.'/../partials/header.php';
?>
<link rel="stylesheet" href="<?=url('assets/css/commerce-kiosk.css')?>?v=1"><section class="kiosk-app"><div class="qr-sheet"><span>COMMERCE QR CONNECT</span><h1><?=e($store['name'])?></h1><p>Local <?=e($store['local_number']?:'sin número')?></p><div id="qrPrint" class="qr-large" data-qr="<?=e($link)?>"></div><p>Escanea para visitar nuestro perfil comercial</p><a href="<?=e($link)?>"><?=e($link)?></a><p class="print-hide"><button type="button" onclick="window.print()">Imprimir cartel QR</button> <a href="<?=url('kiosk.php')?>">Volver al kiosco</a></p></div></section>
<script src="<?=url('assets/js/vendor/qrcode.min.js')?>"></script><script src="<?=url('assets/js/commerce-kiosk.js')?>?v=1" defer></script>
<?php require __DIR__.'/../partials/footer.php';
