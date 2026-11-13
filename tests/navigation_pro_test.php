<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$head=(string)file_get_contents($root.'/partials/header.php');
$css=(string)file_get_contents($root.'/public/assets/css/calle-cristal-navigation.css');
$js=(string)file_get_contents($root.'/public/assets/js/calle-cristal-navigation.js');
function nav_pro_check(bool $ok,string $label): void {echo ($ok?'PASS ':'FAIL ').$label.PHP_EOL;if(!$ok)exit(1);}
nav_pro_check(str_contains($head,'cc-navigation')&&str_contains($head,'cc-mobile-menu'),'Nav PRO responsive layout present');
nav_pro_check(str_contains($head,'cc-global-search')&&str_contains($head,"action=\"<?=url('marketplace.php')?>\""),'Search directs to marketplace');
foreach(['marketplace.php','index.php#catalogo','index.php?sort=popular#catalogo','offers.php','commerces.php','map.php','news_list.php','content_hub.php'] as $link)nav_pro_check(str_contains($head,$link),'Nav preserves '.$link);
nav_pro_check(str_contains($head,'$ccHubNav')&&str_contains($head,'$hubEnabled'),'Dynamic content-hub links retained and scoped');
nav_pro_check(str_contains($head,"\$me['role']")&&str_contains($head,"'admin'"),'Admin role link remains conditional');
nav_pro_check(str_contains($head,'$me') && str_contains($head,'logout.php')&&str_contains($head,'merchant_center.php'),'Authenticated account links retained');
nav_pro_check(str_contains($head,'product_create.php')&&str_contains($head,'register.php'),'Publishing and signup actions retained');
nav_pro_check(str_contains($head,'cc-nav-group')&&str_contains($head,'<summary>'),'Keyboard operable native details');
nav_pro_check(str_contains($css,'@media(max-width:760px)')&&str_contains($css,'cc-nav-list'),'Mobile menu CSS bundled');
nav_pro_check(str_contains($js,"event.key!=='Escape'")&&str_contains($js,"pointerdown"),'Escape and outside-click handling');
nav_pro_check(!str_contains($js,'innerHTML'),'No unsafe HTML injection from menu JS');
