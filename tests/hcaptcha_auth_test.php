<?php
declare(strict_types=1);
define('HCAPTCHA_ENABLED',true);
define('HCAPTCHA_SITEKEY','test-sitekey');
define('HCAPTCHA_SECRET','test-secret');
define('HCAPTCHA_LOGIN_ENABLED',true);
define('HCAPTCHA_REGISTER_ENABLED',true);
require_once __DIR__.'/../app/hcaptcha.php';
function check(bool $pass,string $name): void {echo ($pass?'PASS ':'FAIL ').$name.PHP_EOL;if(!$pass)exit(1);}
check(hcaptcha_is_enabled('login')&&hcaptcha_is_enabled('register'),'both authentication forms protected');
check(!hcaptcha_is_enabled('unknown'),'unknown actions disabled');
check(!hcaptcha_verify_token(''),'missing token denied');
check(!hcaptcha_verify_token(str_repeat('x',8193)),'oversized token denied');
$transport=function(array $fields): array {
 if(($fields['secret']??'')!=='test-secret'||($fields['sitekey']??'')!=='test-sitekey'||($fields['response']??'')!=='sample')throw new RuntimeException('Payload incorrecto.');
 return ['success'=>true];
};
check(hcaptcha_verify_token('sample',$transport),'successful server verification');
check(!hcaptcha_verify_token('invalid',fn(array $data)=>['success'=>false]),'invalid challenge denied');
check(!hcaptcha_verify_token('sample',fn(array $data)=>['success'=>'true']),'string success denied');
check(!hcaptcha_verify_token('sample',function(){throw new RuntimeException('Network down');}),'network error denied');
check(str_contains(hcaptcha_widget('login'),'data-sitekey="test-sitekey"'),'public sitekey in widget');
check(!str_contains(hcaptcha_widget('login'),'test-secret'),'secret excluded from HTML');
$login=(string)file_get_contents(__DIR__.'/../public/login.php');
$register=(string)file_get_contents(__DIR__.'/../public/register.php');
$installer=(string)file_get_contents(__DIR__.'/../install/index.php');
check(str_contains($login,"hcaptcha_verify_request('login')") && str_contains($register,"hcaptcha_verify_request('register')"),'both POST handlers verify');
check(str_contains($installer,"'HCAPTCHA_SECRET'=>\$hcaptchaSecret"),'installer writes private secret');
