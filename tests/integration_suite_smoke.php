<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function check_integration(bool $pass,string $description): void {
 echo ($pass?'PASS ':'FAIL ').$description.PHP_EOL;
 if(!$pass)exit(1);
}
function src(string $path): string {return (string)file_get_contents(dirname(__DIR__).'/'.$path);}
$header=src('admin/partials/header.php');
$login=src('public/login.php');
$register=src('public/register.php');
$installer=src('install/index.php');
$pos=src('app/pos.php');
$orders=src('app/orders_pro.php');
$inventory=src('public/inventory.php');
$core=src('app/inventory_core.php');
$workflow=src('.github/workflows/php-lint.yml');
check_integration(str_contains($header,"'integration' =>") && str_contains($header,"'pos' =>") && str_contains($header,"'orders_pro' =>") && str_contains($header,"'security_center' =>"),'complete AdminCP navigation');
check_integration(str_contains($header,'admin-nexus.css') && str_contains(src('admin/partials/footer.php'),'admin-nexus.js'),'Nexus design retained');
check_integration(str_contains($login,"hcaptcha_verify_request('login')") && str_contains($login,'Cache-Control: no-store'),'combined login security');
check_integration(str_contains($register,"hcaptcha_verify_request('register')") && str_contains($register,'security_registration_role()'),'combined registration security');
check_integration(!str_contains($register,'$count===0?'),'public admin escalation removed');
check_integration(str_contains($installer,'fresh_confirmation') && str_contains($installer,"'HCAPTCHA_SECRET'=>"),'installer and hCaptcha settings combined');
check_integration(str_contains($pos,'inventory_core_decrease(') && str_contains($orders,'inventory_core_decrease('),'POS and orders share stock service');
check_integration(str_contains($pos,'inventory_core_total(') && str_contains($orders,'inventory_core_total('),'POS and orders share price validation');
check_integration(str_contains($core,'stock>=?') && str_contains($core,'inTransaction()'),'stock atomic conditional guard');
check_integration(str_contains($inventory,"url('pos.php')") && str_contains($inventory,"url('orders_pro.php')"),'seller navigation retained');
check_integration(str_contains(src('public/commerces.php'),"url('kiosk.php')"),'kiosk entry point retained');
check_integration(str_contains(src('admin/integration_center.php'),'require_admin()') && str_contains(src('admin/integration_center.php'),'verify_csrf()'),'integration admin authorization and CSRF');
foreach(['installer_safety','admin_nexus_smoke','commerce_qr_kiosk_smoke','pos_smoke','orders_pro_smoke','enterprise_core_smoke','hcaptcha_auth_test','security_center_smoke'] as $test){
 check_integration(str_contains($workflow,'tests/'.$test.'.php'),'CI includes '.$test);
}
