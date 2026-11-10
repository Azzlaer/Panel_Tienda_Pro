<?php
declare(strict_types=1);
/**
 * hCaptcha admin settings are stored OUTSIDE public/ in a private local PHP file.
 * Existing HCAPTCHA_* constants remain the fallback on installations without it.
 */
function hcaptcha_settings_path(): string {
    return dirname(__DIR__).'/config/hcaptcha.local.php';
}
function hcaptcha_settings(?string $path=null): array {
    $defaults=[
      'enabled'=>defined('HCAPTCHA_ENABLED') && HCAPTCHA_ENABLED===true,
      'login'=>!defined('HCAPTCHA_LOGIN_ENABLED') || HCAPTCHA_LOGIN_ENABLED===true,
      'register'=>!defined('HCAPTCHA_REGISTER_ENABLED') || HCAPTCHA_REGISTER_ENABLED===true,
      'sitekey'=>defined('HCAPTCHA_SITEKEY')?(string)HCAPTCHA_SITEKEY:'',
      'secret'=>defined('HCAPTCHA_SECRET')?(string)HCAPTCHA_SECRET:'',
    ];
    $path=$path??hcaptcha_settings_path();
    if(!is_file($path) || is_link($path))return $defaults;
    try {
        $stored=(static fn(string $file): mixed => require $file)($path);
        if(!is_array($stored))return $defaults;
        foreach(['enabled','login','register'] as $key){
            if(array_key_exists($key,$stored)&&is_bool($stored[$key]))$defaults[$key]=$stored[$key];
        }
        foreach(['sitekey','secret'] as $key){
            if(isset($stored[$key]) && is_string($stored[$key]))$defaults[$key]=$stored[$key];
        }
    } catch(Throwable $e) {
        error_log('hCaptcha local settings could not be read: '.$e->getMessage());
        // A broken saved configuration must not silently disable previously enabled protection.
        $defaults['enabled']=true;
    }
    return $defaults;
}
function hcaptcha_settings_validate(array $settings): void {
    foreach(['enabled','login','register'] as $key) {
        if(!isset($settings[$key])||!is_bool($settings[$key]))throw new InvalidArgumentException('Opción hCaptcha inválida.');
    }
    foreach(['sitekey'=>255,'secret'=>512] as $key=>$limit){
        if(!isset($settings[$key])||!is_string($settings[$key])||strlen($settings[$key])>$limit||preg_match('/[\x00-\x1f\x7f]/',$settings[$key]))throw new InvalidArgumentException('Clave hCaptcha inválida.');
    }
    if($settings['enabled'] && ($settings['sitekey']===''||$settings['secret']===''))throw new InvalidArgumentException('Para activar hCaptcha debes configurar Sitekey y Secret.');
    if($settings['enabled'] && !function_exists('curl_init'))throw new RuntimeException('PHP cURL es necesario para verificar hCaptcha.');
}
function hcaptcha_settings_write(array $settings,?string $path=null): void {
    hcaptcha_settings_validate($settings);
    $path=$path??hcaptcha_settings_path();
    $dir=dirname($path);
    if(!is_dir($dir)||!is_writable($dir)||is_link($path))throw new RuntimeException('No se puede guardar: revisa permisos de config/.');
    $data="<?php\n// Generado desde Security Center PRO. No publicar ni versionar.\nreturn ".var_export($settings,true).";\n";
    $temp=tempnam($dir,'.hcaptcha-');
    if($temp===false)throw new RuntimeException('No se pudo crear archivo temporal de configuración.');
    try {
        if(file_put_contents($temp,$data,LOCK_EX)!==strlen($data))throw new RuntimeException('No se pudo escribir configuración.');
        @chmod($temp,0600);
        if(!rename($temp,$path))throw new RuntimeException('No se pudo reemplazar la configuración.');
    } finally {
        if(is_file($temp))@unlink($temp);
    }
}
