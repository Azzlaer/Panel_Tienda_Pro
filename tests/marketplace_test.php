<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/marketplace.php';
function chk(bool $ok,string $name): void {
 echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
 if(!$ok)exit(1);
}
$f=marketplace_filters(['q'=>str_repeat('x',110),'category'=>'5','seller'=>'2','min'=>'999','max'=>'100','available'=>'1','sort'=>'price_asc']);
chk(mb_strlen($f['q'])===100,'search bounded');
chk($f['category']===5 && $f['seller']===2,'category and seller numeric');
chk($f['min']===100 && $f['max']===999,'price bounds normalized');
chk($f['available']===1 && $f['sort']==='price_asc','sort and availability allowlisted');
$unsafe=marketplace_filters(['sort'=>'price_clp; DROP TABLE products','category'=>'1 OR 1=1','seller'=>[]]);
chk($unsafe['sort']==='recent' && $unsafe['category']===0 && $unsafe['seller']===0,'unsafe raw filters rejected');
$args=[];$where=marketplace_where($f,$args);
chk(str_contains($where,"pr.status='published'") && str_contains($where,"u.status='active'"),'only published products from active sellers');
chk(str_contains($where,'pr.price_clp>=?') && str_contains($where,'pr.user_id=?'),'filters use PDO placeholders');
chk(!str_contains($where,'999') && count($args)===9,'all user values bound');
chk(marketplace_order('price_asc')==='pr.price_clp ASC,pr.id DESC','sort mapped to fixed SQL');
chk(marketplace_order('evil')==='pr.featured DESC,pr.created_at DESC,pr.id DESC','sort fallback safe');
chk(marketplace_selected_ids(['9','2','2','0','foo','1','3','4'])===[9,2,1,3],'compare selection bounded and distinct');
$root=dirname(__DIR__);
$actions=(string)file_get_contents($root.'/public/marketplace_actions.php');
$saved=(string)file_get_contents($root.'/public/marketplace_saved.php');
$nav=(string)file_get_contents($root.'/partials/header.php');
$admin=(string)file_get_contents($root.'/admin/marketplace.php');
$integration=(string)file_get_contents($root.'/admin/integration_center.php');
chk(str_contains($actions,'require_login()') && str_contains($actions,'verify_csrf()'),'actions authenticated and CSRF checked');
chk(str_contains($actions,'WHERE id=? AND user_id=?'),'saved search deletion owner scoped');
chk(str_contains($saved,'f.user_id=?') && str_contains($saved,'WHERE user_id=?'),'personal collections owner scoped');
chk(str_contains($admin,'require_admin()'),'admin analytics restricted');
chk(str_contains($nav,'marketplace.php'),'public navigation includes marketplace');
chk(str_contains($integration,'marketplace_install(db())'),'manual migration activation wired');
$sql=(string)file_get_contents($root.'/database/migrations/2026_11_10_000012_marketplace.sql');
chk(substr_count($sql,'CREATE TABLE IF NOT EXISTS')===2,'two additive tables');
chk(!preg_match('/\b(?:DROP|TRUNCATE|ALTER)\b/i',$sql),'no destructive migration');
