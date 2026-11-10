<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/vip_product_effects.php';
function vip_check(bool $valid,string $description): void {
 echo ($valid?'PASS ':'FAIL ').$description.PHP_EOL;
 if(!$valid)exit(1);
}
$catalog=vip_effect_catalog();
vip_check(count($catalog)>=7 && isset($catalog['none'],$catalog['gold'],$catalog['rainbow']),'preset catalogue populated');
foreach(array_keys($catalog) as $key)vip_check(vip_effect_validate_key($key)===$key,'preset '.$key.' accepted');
foreach(['<script>','custom-css','glow;drop','javascript:alert(1)'] as $bad) {
 try{vip_effect_validate_key($bad);vip_check(false,'unsafe effect denied');}
 catch(InvalidArgumentException){vip_check(true,'unsafe effect denied');}
}
vip_check(vip_effect_class('gold')==='vip-fx vip-fx-gold','fixed CSS class output');
vip_check(vip_effect_class('not_defined')==='' && vip_effect_class(null)==='','unknown and absent effects hidden');
$root=dirname(__DIR__);
$sql=(string)file_get_contents($root.'/database/migrations/2026_11_10_000014_vip_product_effects.sql');
vip_check(substr_count($sql,'CREATE TABLE IF NOT EXISTS')===2,'schema additive');
vip_check(!preg_match('/\b(?:DROP|TRUNCATE|ALTER)\b/i',$sql),'no destructive schema changes');
$seller=(string)file_get_contents($root.'/public/vip_product_effects.php');
$admin=(string)file_get_contents($root.'/admin/vip_product_effects.php');
vip_check(str_contains($seller,'require_login()')&&str_contains($seller,'verify_csrf()'),'seller authentication and CSRF');
vip_check(str_contains($admin,'require_admin()')&&str_contains($admin,'verify_csrf()'),'admin role and CSRF');
vip_check(str_contains($seller,'vip_effect_assign($pdo,$uid'),'seller changes owner-only products');
vip_check(str_contains($admin,'vip_grant($pdo,'),'VIP grants only from admin');
$integration=(string)file_get_contents($root.'/admin/integration_center.php');
vip_check(str_contains($integration,"'vip_effects'=>")&&str_contains($integration,'vip_effect_install(db())'),'Integration Center manual activation');
foreach(['public/index.php','public/marketplace.php','public/product.php'] as $file){
 $source=(string)file_get_contents($root.'/'.$file);
 vip_check(str_contains($source,'vip_effect_map(')&&str_contains($source,'vip_effect_class('),'public visual effects on '.$file);
}
$css=(string)file_get_contents($root.'/public/assets/css/vip-product-effects.css');
vip_check(str_contains($css,'prefers-reduced-motion'),'reduced motion accessible');
