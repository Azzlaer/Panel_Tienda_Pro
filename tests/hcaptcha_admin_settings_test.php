<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/hcaptcha_settings.php';
function settings_check(bool $pass,string $message): void {
 echo ($pass?'PASS ':'FAIL ').$message.PHP_EOL;
 if(!$pass)exit(1);
}
$settings=hcaptcha_settings('/nonexistent/hcaptcha.local.php');
settings_check($settings['enabled']===false,'disabled by default');
$base=sys_get_temp_dir().'/commerce-hcaptcha-test-'.bin2hex(random_bytes(8));
if(!mkdir($base,0700))throw new RuntimeException('Temp directory inaccessible');
$path=$base.'/hcaptcha.local.php';
try {
 $settings=['enabled'=>false,'login'=>true,'register'=>true,'sitekey'=>'public-test-key','secret'=>'private-test-secret'];
 hcaptcha_settings_write($settings,$path);
 settings_check(file_exists($path),'private file written');
 settings_check(hcaptcha_settings($path)===$settings,'settings reload correctly');
 $source=file_get_contents($path);
 settings_check(str_contains($source,'return array'),'settings are valid PHP array');
 $settings['enabled']=true;
 hcaptcha_settings_write($settings,$path);
 settings_check(hcaptcha_settings($path)['enabled']===true,'admin settings activate protection');
 try {
   hcaptcha_settings_write(['enabled'=>true,'login'=>true,'register'=>true,'sitekey'=>'','secret'=>''], $path);
   settings_check(false,'enabled without keys rejected');
 } catch(InvalidArgumentException){settings_check(true,'enabled without keys rejected');}
 try {
   hcaptcha_settings_write(['enabled'=>false,'login'=>true,'register'=>true,'sitekey'=>"abc\n", 'secret'=>'valid'],$path);
   settings_check(false,'control chars rejected');
 } catch(InvalidArgumentException){settings_check(true,'control chars rejected');}
 settings_check(hcaptcha_settings($path)['secret']==='private-test-secret','failed update leaves old secret intact');
} finally {
 if(file_exists($path))unlink($path);
 rmdir($base);
}
$ui=file_get_contents(dirname(__DIR__).'/admin/hcaptcha_settings.php');
settings_check(str_contains($ui,'require_admin()')&&str_contains($ui,'verify_csrf()'),'admin authenticated and CSRF protected');
settings_check(!str_contains($ui,'value="<?=e($s[\'secret\'])?>'),'admin never prefills private secret');
$ignore=file_get_contents(dirname(__DIR__).'/.gitignore');
settings_check(str_contains($ignore,'/config/hcaptcha.local.php'),'secrets excluded from git');
$login=file_get_contents(dirname(__DIR__).'/public/login.php');
$register=file_get_contents(dirname(__DIR__).'/public/register.php');
settings_check(str_contains($login,"hcaptcha_verify_request('login')")&&str_contains($register,"hcaptcha_verify_request('register')"),'both forms verify at backend');
