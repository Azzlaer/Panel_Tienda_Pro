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
/** Safe admin-facing errors; never interpolate credentials or absolute paths. */
class HcaptchaSettingsStorageException extends RuntimeException {}
function hcaptcha_settings_diagnostics(?string $path=null): array {
    $path=$path??hcaptcha_settings_path();
    $dir=dirname($path);
    return [
        'directory_exists'=>is_dir($dir),
        'directory_writable'=>is_dir($dir)&&is_writable($dir),
        'file_exists'=>is_file($path),
        'file_writable'=>!file_exists($path)||is_writable($path),
        'curl'=>function_exists('curl_init'),
        'open_basedir'=>(string)ini_get('open_basedir')!=='',
    ];
}
function hcaptcha_settings_write(array $settings,?string $path=null): void {
    hcaptcha_settings_validate($settings);
    $path=$path??hcaptcha_settings_path();
    $dir=dirname($path);
    if(!is_dir($dir))throw new HcaptchaSettingsStorageException('No existe la carpeta config/.');
    if(!is_writable($dir))throw new HcaptchaSettingsStorageException('La carpeta config/ no tiene permisos de escritura para Apache.');
    if(is_link($path))throw new HcaptchaSettingsStorageException('El archivo de configuración no puede ser un enlace simbólico.');
    if(file_exists($path) && (!is_file($path)||!is_writable($path)))throw new HcaptchaSettingsStorageException('El archivo config/hcaptcha.local.php no tiene permisos de escritura.');
    $data="<?php\n// Generado desde Security Center PRO. No publicar ni versionar.\nreturn ".var_export($settings,true).";\n";
    $temp=tempnam($dir,'.hcaptcha-');
    if($temp===false)throw new HcaptchaSettingsStorageException('PHP no pudo crear un archivo temporal en config/. Revisa permisos y open_basedir.');
    try {
        if(file_put_contents($temp,$data,LOCK_EX)!==strlen($data))throw new HcaptchaSettingsStorageException('PHP no pudo escribir el archivo temporal en config/.');
        @chmod($temp,0600);
        if(!@rename($temp,$path))throw new HcaptchaSettingsStorageException('Windows/Apache no pudo reemplazar config/hcaptcha.local.php. Revisa permisos de modificación y eliminación del archivo para la cuenta del servicio Apache.');
        if(function_exists('opcache_invalidate'))@opcache_invalidate($path,true);
    } finally {
        if(is_file($temp))@unlink($temp);
    }
}
