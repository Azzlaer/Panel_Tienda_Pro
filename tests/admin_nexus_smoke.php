<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$header=(string)file_get_contents($root.'/admin/partials/header.php');
$footer=(string)file_get_contents($root.'/admin/partials/footer.php');
$home=(string)file_get_contents($root.'/admin/index.php');
$checks=[
 'isolated CSS exists'=>is_file($root.'/public/assets/css/admin-nexus.css'),
 'isolated JS exists'=>is_file($root.'/public/assets/js/admin-nexus.js'),
 'CSS included'=>str_contains($header,'admin-nexus.css'),
 'JS included'=>str_contains($footer,'admin-nexus.js'),
 'search input present'=>str_contains($header,'nexusNavSearch'),
 'dashboard command present'=>str_contains($home,'nexus-command'),
 'original navigation preserved'=>str_contains($header,'$navGroups'),
];
foreach($checks as $label=>$pass){echo ($pass?'PASS':'FAIL').' '.$label.PHP_EOL;if(!$pass)exit(1);}
