<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/marketplace.php';
function smart_check(bool $v,string $msg): void {echo ($v?'PASS ':'FAIL ').$msg.PHP_EOL;if(!$v)exit(1);}
$exp=marketplace_search_terms('audifonos');
smart_check(in_array('auriculares',$exp,true) && count($exp)<=5,'Known alias expansion is bounded');
smart_check(marketplace_search_terms('foo')===['foo'],'Other words are not expanded');
smart_check(marketplace_search_terms('')===[],'Empty query is safe');
$f=marketplace_filters(['q'=>'audifonos','sort'=>'relevance','stock_only'=>1]);
smart_check($f['sort']==='relevance' && $f['stock_only']===1,'Smart sort and confirmed stock filter');
$args=[];$where=marketplace_where($f,$args);
smart_check(str_contains($where,'p.business_local')&&str_contains($where,'pr.stock>0'),'Search local and known stock');
smart_check(count($args)===5*count($exp),'All synonyms passed as bound params');
smart_check(!str_contains($where,'auriculares'),'Synonym values never interpolated in SQL');
$root=dirname(__DIR__);
$view=(string)file_get_contents($root.'/public/marketplace.php');
smart_check(str_contains($view,'mkt-hero-search') && str_contains($view,'business_number') && str_contains($view,'stock_only'),'Search-first UI and local number');
$api=(string)file_get_contents($root.'/public/marketplace_suggest.php');
smart_check(str_contains($api,"'GET'") && str_contains($api,'json_encode') && str_contains($api,'marketplace_suggestions'),'Safe JSON suggestion route');
$js=(string)file_get_contents($root.'/public/assets/js/marketplace-enterprise.js');
smart_check(str_contains($js,'document.createElement') && str_contains($js,'list.replaceChildren') && !str_contains($js,'innerHTML'),'Autocomplete uses DOM methods not HTML');
