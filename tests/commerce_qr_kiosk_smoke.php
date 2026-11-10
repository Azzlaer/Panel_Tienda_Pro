<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$k=(string)file_get_contents($root.'/public/kiosk.php');
$q=(string)file_get_contents($root.'/public/commerce_qr.php');
$d=(string)file_get_contents($root.'/public/commerces.php');
$checks=[
 'kiosk route'=>is_file($root.'/public/kiosk.php'),
 'QR route'=>is_file($root.'/public/commerce_qr.php'),
 'bundled QR generator'=>is_file($root.'/public/assets/js/vendor/qrcode.min.js'),
 'MIT license'=>is_file($root.'/public/assets/js/vendor/QR_CODE_LICENSE.txt'),
 'active store filtering'=>str_contains($k,"d.active=1 AND u.status='active'") && str_contains($q,"d.active=1 AND u.status='active'"),
 'safe parameter binding'=>str_contains($q,'$st->execute([$id])'),
 'kiosk link in directory'=>str_contains($d,"url('kiosk.php')"),
 'store id for print button'=>str_contains($k,'data-id='),
 'HTML escaping'=>str_contains($k,'e($r['),
];
$failed=0;
foreach($checks as $label=>$ok){echo ($ok?'PASS ':'FAIL ').$label.PHP_EOL;if(!$ok)$failed++;}
exit($failed?1:0);
