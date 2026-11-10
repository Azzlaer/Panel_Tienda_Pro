<?php
declare(strict_types=1);

/**
 * hCaptcha login/registration protection. Secrets are read only from private configuration.
 * Activation is opt-in to preserve existing XAMPP installations.
 */
require_once __DIR__.'/hcaptcha_settings.php';
function hcaptcha_is_enabled(string $action): bool {
    $settings=hcaptcha_settings();
    if (!$settings['enabled']) return false;
    return match ($action) {
        'login' => $settings['login'],
        'register' => $settings['register'],
        default => false,
    };
}
function hcaptcha_sitekey(): string {
    return trim((string)hcaptcha_settings()['sitekey']);
}
function hcaptcha_verify_token(string $token, ?callable $transport = null): bool {
    $sitekey=hcaptcha_sitekey();
    $secret=trim((string)hcaptcha_settings()['secret']);
    // Fail closed whenever protection is enabled but credentials are missing.
    if($token==='' || strlen($token)>8192 || $sitekey==='' || $secret==='') return false;
    $ip=(string)($_SERVER['REMOTE_ADDR']??'');
    $fields=['secret'=>$secret,'response'=>$token,'sitekey'=>$sitekey];
    if(filter_var($ip,FILTER_VALIDATE_IP)) $fields['remoteip']=$ip;

    // Injectable transport for unit testing: real deployments use PHP cURL.
    if($transport!==null){
        try {$data=$transport($fields);return is_array($data)&&($data['success']??null)===true;}
        catch(Throwable){return false;}
    }
    if(!function_exists('curl_init'))return false;
    $handle=curl_init('https://api.hcaptcha.com/siteverify');
    if($handle===false)return false;
    try{
        curl_setopt_array($handle,[
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>http_build_query($fields,'','&',PHP_QUERY_RFC3986),
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_CONNECTTIMEOUT=>4,
            CURLOPT_TIMEOUT=>10,
            CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded','Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER=>true,
            CURLOPT_SSL_VERIFYHOST=>2,
            CURLOPT_FOLLOWLOCATION=>false,
        ]);
        $body=curl_exec($handle);
        $status=(int)curl_getinfo($handle,CURLINFO_HTTP_CODE);
        if(!is_string($body)||$status!==200)return false;
        $data=json_decode($body,true,16,JSON_THROW_ON_ERROR);
        return is_array($data)&&($data['success']??null)===true;
    }catch(Throwable){return false;}
    finally{curl_close($handle);}
}
function hcaptcha_verify_request(string $action): bool {
    if(!hcaptcha_is_enabled($action))return true;
    $token=$_POST['h-captcha-response']??'';
    return is_string($token)&&hcaptcha_verify_token(trim($token));
}
function hcaptcha_widget(string $action): string {
    if(!hcaptcha_is_enabled($action))return '';
    $key=htmlspecialchars(hcaptcha_sitekey(),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    if($key==='')return '<p class="auth-hcaptcha-error" role="alert">hCaptcha no está configurado. Contacta a la administración.</p>';
    return '<div class="h-captcha" data-sitekey="'.$key.'"></div>';
}
function hcaptcha_script(string $action): string {
    if(!hcaptcha_is_enabled($action)||hcaptcha_sitekey()==='')return '';
    return '<script src="https://js.hcaptcha.com/1/api.js" async defer></script>';
}
