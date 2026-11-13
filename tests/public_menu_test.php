<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/public_menu.php';
function pm_check(bool $v,string $name): void {echo ($v?'PASS ':'FAIL ').$name.PHP_EOL;if(!$v)exit(1);}
$default=public_menu_defaults();
pm_check(count($default)>=12,'Default menu includes all classic links');
pm_check($default[0]['href']==='index.php' && $default[0]['group']==='','Classic layout is the default');
pm_check(public_menu_items('')===$default,'Missing config returns defaults');
pm_check(public_menu_items('not json')===$default,'Corrupted config falls back');
pm_check(public_menu_items('[]')===[],'Intentional empty menu is valid');
pm_check(public_menu_href('index.php?sort=popular#catalogo')==='index.php?sort=popular#catalogo','Safe local query and anchor');
foreach(['https://evil.example','//evil','../config/config.php','%2e%2e/config.php','javascript:alert(1)','marketplace.php?next=https://evil.test'] as $evil) {
 try{public_menu_href($evil);pm_check(false,'Unsafe URL refused: '.$evil);}catch(InvalidArgumentException){}
}
try{public_menu_normalize(array_fill(0,61,$default[0]));pm_check(false,'Menu size bounded');}catch(InvalidArgumentException){}
pm_check(public_menu_validate_item(['label'=>'Nuevo','href'=>'offers.php','enabled'=>1])['enabled'],'Valid item sanitized');
$groups=public_menu_sections([['group'=>'','label'=>'Inicio'],['group'=>'Comprar','label'=>'Productos'],['group'=>'Comprar','label'=>'Promos']]);
pm_check(count($groups)===2 && count($groups[1]['items'])===2,'Sections grouped without changing standalone items');
$root=dirname(__DIR__);
$admin=file_get_contents($root.'/admin/public_menu.php');
$header=file_get_contents($root.'/partials/header.php');
$nav=file_get_contents($root.'/admin/partials/header.php');
pm_check(str_contains($admin,'require_admin()')&&str_contains($admin,'verify_csrf()'),'Editing admin only with CSRF');
pm_check(str_contains($admin,'public_menu_save')&&str_contains($admin,'audit_log'),'Changes saved and audited');
pm_check(str_contains($header,'public_menu_items')&&str_contains($header,'public_menu_visible'),'Public site consumes stored settings');
pm_check(str_contains($nav,"'public_menu'"),'Admin sidebar contains manager');
pm_check(is_file($root.'/public/assets/css/public-menu.css'),'Public menu styles present');
