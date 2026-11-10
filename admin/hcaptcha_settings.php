<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_admin();
require_once __DIR__.'/../app/hcaptcha.php';
$adminPage='security_center';$adminTitle='Configurar hCaptcha';$adminSubtitle='Seguridad del registro e inicio de sesión';
$path=hcaptcha_settings_path();
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $current=hcaptcha_settings();
        $new=[
          'enabled'=>isset($_POST['enabled']),
          'login'=>isset($_POST['login']),
          'register'=>isset($_POST['register']),
          'sitekey'=>trim((string)($_POST['sitekey']??'')),
          'secret'=>trim((string)($_POST['secret']??'')),
        ];
        if($new['secret']==='')$new['secret']=$current['secret'];
        if($new['sitekey']==='')$new['sitekey']=$current['sitekey'];
        if(!$new['login']&&!$new['register']&&$new['enabled'])throw new InvalidArgumentException('Selecciona login, registro o ambos.');
        hcaptcha_settings_write($new);
        audit_log('security.hcaptcha_settings_changed','configuration','hcaptcha');
        flash('success','Configuración hCaptcha guardada. Prueba login y registro antes de publicar.');
    }catch(Throwable $e){
        error_log('Could not save hCaptcha settings: '.get_class($e).' '.$e->getMessage());
        $safe=$e instanceof InvalidArgumentException || $e instanceof HcaptchaSettingsStorageException || ($e instanceof RuntimeException && $e->getMessage()==='PHP cURL es necesario para verificar hCaptcha.');
        flash('error',$safe?$e->getMessage():'No fue posible guardar hCaptcha; consulta el registro de errores PHP de Apache.');
    }
    redirect('../admin/hcaptcha_settings.php');
}
$s=hcaptcha_settings();
$curl=function_exists('curl_init');
$hasSecret=$s['secret']!=='';
$hasSitekey=$s['sitekey']!=='';
$diag=hcaptcha_settings_diagnostics($path);
$writable=$diag['directory_writable']&&$diag['file_writable'];
require __DIR__.'/partials/header.php';
?>
<section class="panel-card">
 <div class="panel-head"><div><h2>🛡️ Protección hCaptcha</h2><p>Controla los desafíos de registro y acceso. La comprobación se realiza en el servidor.</p></div></div>
 <p><strong>Estado:</strong> <?=$s['enabled']?'Activado':'Desactivado'?> · PHP cURL: <?=$curl?'Disponible':'No disponible'?> · Secret: <?=$hasSecret?'Configurado':'Pendiente'?> · Escritura: <?=$writable?'Disponible':'Sin permisos'?></p>
 <div class="panel-card" style="padding:14px;margin:14px 0">
 <strong>Diagnóstico del servidor (sin revelar claves):</strong>
 <ul>
 <li>Carpeta config/: <?=$diag['directory_exists']?'Encontrada':'No encontrada'?></li>
 <li>Escritura de la carpeta: <?=$diag['directory_writable']?'Permitida':'Bloqueada'?></li>
 <li>Archivo hcaptcha.local.php: <?=$diag['file_exists']?'Existe':'Se creará al guardar'?></li>
 <li>Escritura del archivo: <?=$diag['file_writable']?'Permitida':'Bloqueada'?></li>
 <li>Extensión PHP cURL: <?=$diag['curl']?'Disponible':'No disponible'?></li>
 <li>Restricción open_basedir: <?=$diag['open_basedir']?'Activa':'No establecida'?></li>
 </ul>
 <p>Si aparece «Bloqueada», revisa los permisos NTFS de la cuenta que ejecuta Apache. No habilites escritura pública para todos.</p>
 </div>
 <form method="post" class="admin-form" autocomplete="off" style="max-width:650px;display:grid;gap:15px">
 <?=csrf_field()?>
 <label><input type="checkbox" name="enabled" value="1" <?=$s['enabled']?'checked':''?>> Activar protección hCaptcha</label>
 <label><input type="checkbox" name="login" value="1" <?=$s['login']?'checked':''?>> Proteger inicio de sesión</label>
 <label><input type="checkbox" name="register" value="1" <?=$s['register']?'checked':''?>> Proteger registro de cuentas</label>
 <label>Sitekey pública
 <input name="sitekey" maxlength="255" autocomplete="off" value="<?=e($s['sitekey'])?>" placeholder="Sitekey hCaptcha"></label>
 <label>Secret privada
 <input name="secret" type="password" maxlength="512" autocomplete="new-password" placeholder="<?=$hasSecret?'Clave configurada (dejar vacío para conservarla)':'Introduce la Secret de hCaptcha'?>"></label>
 <p>La Secret jamás se muestra en el formulario ni se guarda en la base de datos. Se almacena en <code>config/hcaptcha.local.php</code>, fuera de la carpeta pública.</p>
 <button class="admin-btn" type="submit">Guardar protección hCaptcha</button>
 </form>
 <p>Obtén las claves en <a href="https://dashboard.hcaptcha.com/" target="_blank" rel="noopener noreferrer">dashboard.hcaptcha.com</a>. Primero prueba con una cuenta de pruebas; un problema de conectividad bloquea los formularios protegidos.</p>
 <p><a href="security_center.php">← Volver a Security Center PRO</a></p>
</section>
<?php require __DIR__.'/partials/footer.php';
