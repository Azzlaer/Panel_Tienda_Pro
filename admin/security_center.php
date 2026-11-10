<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_admin();
$adminPage='security_center';
$adminTitle='Security Center PRO';
$adminSubtitle='Diagnóstico preventivo de autenticación, permisos y entorno';
$root=dirname(__DIR__);
$https=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off');
$hcaptchaEnabled=defined('HCAPTCHA_ENABLED') && HCAPTCHA_ENABLED===true;
$checks=[
 ['Registro sin administrador público',is_file($root.'/app/security_auth.php'),'Todas las altas públicas reciben rol user y estado pending.'],
 ['Instalación bloqueada',is_file($root.'/storage/installed.lock') && is_file($root.'/config/config.php'),'El bloqueo y la configuración deben existir juntos.'],
 ['Sesiones estrictas',ini_get('session.use_strict_mode')==='1','Evita aceptar identificadores de sesión no emitidos por el servidor.'],
 ['Cookies HttpOnly',ini_get('session.cookie_httponly')==='1','Evita acceso directo a la cookie desde JavaScript.'],
 ['Cookies de sesión exclusivas',ini_get('session.use_only_cookies')==='1','No aceptar identificadores de sesión por URL.'],
 ['HTTPS en entorno público',$https || APP_ENV==='local','HTTPS es obligatorio para proteger credenciales fuera de desarrollo local.'],
 ['Cookies Secure',!$https || ini_get('session.cookie_secure')==='1','Las cookies deben enviarse solo mediante HTTPS.'],
 ['Errores ocultos en producción',APP_ENV==='local' || ini_get('display_errors')==='0','No mostrar trazas ni rutas internas a visitantes.'],
 ['Protección de carpetas privadas',is_file($root.'/.htaccess'),'Verificar que Apache aplique .htaccess (AllowOverride).'],
 ['Protección de archivos subidos',is_file($root.'/public/uploads/.htaccess'),'Verificar que Apache no ejecute archivos subidos.'],
 ['hCaptcha configurado',$hcaptchaEnabled,'Opcional. Activar tras integrar y probar el módulo hCaptcha.'],
];
$failedLogins=0;
try{$failedLogins=(int)db()->query("SELECT COUNT(*) FROM login_attempts WHERE success=0 AND attempted_at>=DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();}
catch(Throwable){/* Informe disponible incluso si esta tabla falta. */}
$adminCount=0;
try{$adminCount=(int)db()->query("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active'")->fetchColumn();}
catch(Throwable){}
require __DIR__.'/partials/header.php';
?>
<section class="panel-card"><div class="panel-head"><div><h2>🛡️ Security Center PRO</h2><p>Comprobaciones de configuración y autenticación. No sustituye una auditoría de penetración.</p></div></div>
<div class="admin-grid cols-2"><div class="metric-card"><div><strong><?=$failedLogins?></strong><small>Intentos fallidos de acceso en 24 horas</small></div></div><div class="metric-card"><div><strong><?=$adminCount?></strong><small>Cuentas administradoras activas</small></div></div></div>
<?php foreach($checks as [$label,$ok,$note]):?><div class="health-row" style="padding:13px 4px;border-bottom:1px solid #e4eaf3;display:flex;justify-content:space-between;gap:15px"><div><strong><?=e($label)?></strong><p style="margin:5px 0;color:#64748b"><?=e($note)?></p></div><span class="<?=$ok?'health-ok':'health-bad'?>"><?=$ok?'✓':'⚠'?></span></div><?php endforeach;?>
<p>Nota: las comprobaciones de archivos indican que existe una configuración, pero no garantizan que Apache la aplique. Revisa permisos, registros de acceso, copias de seguridad y versiones PHP.</p></section>
<?php require __DIR__.'/partials/footer.php';
