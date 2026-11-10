<?php
declare(strict_types=1);
function regression_assert(bool $condition,string $description): void {
 echo ($condition?'PASS ':'FAIL ').$description.PHP_EOL;
 if(!$condition)exit(1);
}
$root=dirname(__DIR__);
$header=(string)file_get_contents($root.'/admin/partials/header.php');
$products=(string)file_get_contents($root.'/admin/products.php');
regression_assert(str_contains($products,'$items=$st->fetchAll()'),'Products has its own result collection');
regression_assert(str_contains($products,'require __DIR__.\'/partials/header.php\''),'Products includes shared admin header');
regression_assert(!preg_match('/foreach\s*\(\s*\$navGroups\s+as\s+\$[A-Za-z_][A-Za-z0-9_]*\s*=>\s*\$items\s*\)/',$header),'Admin sidebar must never overwrite $items');
regression_assert(!preg_match('/foreach\s*\(\s*\$items\s+as\b/',$header),'Admin sidebar never iterates product collection');
regression_assert(str_contains($header,'$__adminNavItems'),'Admin sidebar has distinct variable names');
regression_assert(str_contains($header,'$__adminNavItem'),'Sidebar nested loop also uses isolated variable name');
regression_assert(str_contains($products,'money_clp($i[\'price_clp\'])'),'Price formatting receives product row');
regression_assert(str_contains($products,'SELECT pr.*,p.display_name,c.name category'),'SQL selects product fields and display labels');
echo "Admin product list variable collision regression passed\n";
