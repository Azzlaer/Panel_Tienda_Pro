<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function integration_three_check(bool $ok,string $description): void {
 echo ($ok?'PASS ':'FAIL ').$description.PHP_EOL;
 if(!$ok)exit(1);
}
function file_source(string $path): string {return (string)file_get_contents(dirname(__DIR__).'/'.$path);}
$integration=file_source('admin/integration_center.php');
$admin=file_source('admin/partials/header.php');
$public=file_source('partials/header.php');
$workflow=file_source('.github/workflows/php-lint.yml');
foreach(['hub','marketplace','merchant'] as $key){
 integration_three_check(str_contains($integration,"'".$key."'=>"),$key.' integration center registration');
 integration_three_check(str_contains($integration,"if(\$module==='".$key."')"),$key.' ready check');
 integration_three_check(str_contains($integration,"elseif(\$module==='".$key."')"),$key.' migration install');
}
foreach(['hub','page_builder','marketplace','merchant_center'] as $nav){
 integration_three_check(str_contains($admin,"'".$nav."' =>"),$nav.' admin navigation');
}
foreach(['content_hub.php','marketplace.php','merchant_center.php','page.php?slug='] as $link){
 integration_three_check(str_contains($public,$link),$link.' public navigation');
}
foreach(['tests/content_hub_test.php','tests/marketplace_test.php','tests/merchant_center_test.php','tests/mariadb_integration.php','tests/marketplace_mariadb.php','tests/merchant_center_mariadb.php'] as $test){
 integration_three_check(str_contains($workflow,$test),$test.' CI included');
}
integration_three_check(str_contains($workflow,'content-page-builder.js')&&str_contains($workflow,'marketplace-enterprise.js'),'both JavaScript checks present');
integration_three_check(str_contains(file_source('public/product.php'),'marketplace_saved.php'),'product page Marketplace shortcut retained');
integration_three_check(str_contains(file_source('partials/footer.php'),'content_hub.php'),'Content Hub footer preserved');
integration_three_check(str_contains(file_source('public/merchant_crm.php'),'merchant_context('),'CRM permission scope retained');
integration_three_check(str_contains(file_source('public/marketplace_actions.php'),'verify_csrf()'),'Marketplace actions CSRF preserved');
echo "Content Hub + Marketplace + Merchant Center integration checks completed\n";
