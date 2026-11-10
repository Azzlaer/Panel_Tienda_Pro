<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$configFile = $root . '/config/config.php';
$lockFile = $root . '/storage/installed.lock';
if (is_file($lockFile) || is_file($configFile)) {
    header('Location: ../public/');
    exit;
}

session_name('CS_INSTALLER');
session_start();
if (empty($_SESSION['installer_csrf'])) $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));

function h(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function writable_status(string $path): bool {
    if (file_exists($path)) return is_writable($path);
    $parent = dirname($path);
    return is_dir($parent) && is_writable($parent);
}
function detect_base_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/ComercioSocial/install/index.php');
    $base = preg_replace('~/install/index\.php$~', '', $script) ?: '';
    return $scheme . '://' . $host . rtrim($base, '/') . '/public';
}
function q(string $key, string $default=''): string { return trim((string)($_POST[$key] ?? $default)); }

$requirements = [
    ['PHP 8.2 o superior', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION],
    ['Extensión PDO', extension_loaded('pdo'), extension_loaded('pdo') ? 'Disponible' : 'Falta'],
    ['Extensión pdo_mysql', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'Disponible' : 'Falta'],
    ['Extensión mbstring', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'Disponible' : 'Falta'],
    ['Extensión fileinfo', extension_loaded('fileinfo'), extension_loaded('fileinfo') ? 'Disponible' : 'Falta'],
    ['Extensión openssl', extension_loaded('openssl'), extension_loaded('openssl') ? 'Disponible' : 'Falta'],
    ['Extensión json', extension_loaded('json'), extension_loaded('json') ? 'Disponible' : 'Falta'],
    ['Extensión zip', extension_loaded('zip'), extension_loaded('zip') ? 'Disponible' : 'Falta (necesaria para instalar plantillas ZIP)'],
    ['Carpeta config escribible', writable_status($configFile), $root . '/config'],
    ['Carpeta storage escribible', writable_status($lockFile), $root . '/storage'],
    ['Uploads de avatares escribible', is_writable($root.'/public/uploads/avatars'), $root.'/public/uploads/avatars'],
    ['Uploads de portadas escribible', is_writable($root.'/public/uploads/covers'), $root.'/public/uploads/covers'],
    ['Uploads de publicaciones escribible', is_writable($root.'/public/uploads/posts'), $root.'/public/uploads/posts'],
    ['Uploads de publicidad escribible', is_writable($root.'/public/uploads/ads'), $root.'/public/uploads/ads'],
    ['Carpeta de plantillas escribible', is_dir($root.'/public/templates') && is_writable($root.'/public/templates'), $root.'/public/templates'],
    ['Cache temporal escribible', is_writable($root.'/storage/cache'), $root.'/storage/cache'],
];
$requirementsOk = !in_array(false, array_column($requirements, 1), true);
$errors=[]; $success=false;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($_SESSION['installer_csrf'] ?? '', $_POST['csrf'] ?? '')) $errors[]='La sesión del instalador expiró. Recarga la página.';
    if (!$requirementsOk) $errors[]='Hay requisitos obligatorios sin cumplir.';

    $siteName=q('site_name'); $siteDescription=q('site_description'); $appUrl=rtrim(q('app_url'), '/');
    $dbHost=q('db_host','127.0.0.1'); $dbPort=q('db_port','3306'); $dbName=q('db_name','comercio_social');
    $dbUser=q('db_user','root'); $dbPass=(string)($_POST['db_pass'] ?? '');
    $cookieName=preg_replace('/[^A-Z0-9_]/i','',q('cookie_name','COMERCIOSOCIALSESSID')) ?: 'COMERCIOSOCIALSESSID';
    $cookieDomain=q('cookie_domain'); $cookiePath=q('cookie_path','/'); $cookieSameSite=q('cookie_samesite','Lax');
    $cookieSecure=isset($_POST['cookie_secure']);
    $mailFrom=q('mail_from'); $mailName=q('mail_name',$siteName); $mailHost=q('mail_host'); $mailPort=q('mail_port','587');
    $mailUser=q('mail_user'); $mailPass=(string)($_POST['mail_pass'] ?? ''); $mailEncryption=q('mail_encryption','tls');
    $hcaptchaEnabled=isset($_POST['hcaptcha_enabled']);
    $hcaptchaSitekey=q('hcaptcha_sitekey');
    $hcaptchaSecret=(string)($_POST['hcaptcha_secret']??'');
    $adminName=q('admin_name'); $adminUser=q('admin_user'); $adminEmail=q('admin_email'); $adminPass=(string)($_POST['admin_pass'] ?? '');
    $installDemo=isset($_POST['install_demo']);
    $freshStart=isset($_POST['fresh_start']);
    $freshConfirmation=q('fresh_confirmation');
    if ($freshStart && ($freshConfirmation !== 'REINICIAR '.$dbName)) $errors[]='Escribe exactamente REINICIAR '.$dbName.' para confirmar el borrado irreversible.';

    if ($siteName==='') $errors[]='El nombre del sitio es obligatorio.';
    if (!filter_var($appUrl,FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($appUrl,PHP_URL_SCHEME)),['http','https'],true) || parse_url($appUrl,PHP_URL_USER)!==null || parse_url($appUrl,PHP_URL_PASS)!==null) $errors[]='La URL pública debe ser HTTP/HTTPS, sin credenciales.';
    if (!preg_match('/^[A-Za-z0-9_]{3,40}$/',$adminUser)) $errors[]='El usuario administrador debe tener 3-40 caracteres, letras, números o guion bajo.';
    if (!filter_var($adminEmail,FILTER_VALIDATE_EMAIL)) $errors[]='El correo del administrador no es válido.';
    if (strlen($adminPass)<12) $errors[]='La contraseña del administrador debe tener al menos 12 caracteres.';
    if ($mailFrom!=='' && !filter_var($mailFrom,FILTER_VALIDATE_EMAIL)) $errors[]='El correo remitente no es válido.';
    if($hcaptchaEnabled && !extension_loaded('curl')) $errors[]='hCaptcha requiere PHP cURL.';
    if($hcaptchaEnabled && ($hcaptchaSitekey==='' || trim($hcaptchaSecret)==='')) $errors[]='hCaptcha requiere Sitekey y Secret.';
    if (!preg_match('/^[A-Za-z0-9_]+$/',$dbName)) $errors[]='El nombre de base de datos sólo puede contener letras, números y guion bajo.';
    if ($cookieSameSite==='None' && !$cookieSecure) $errors[]='SameSite=None requiere cookie Secure y HTTPS.';
    if (!in_array($cookieSameSite,['Lax','Strict','None'],true)) $errors[]='SameSite inválido.';
    if ($cookieSecure && parse_url($appUrl,PHP_URL_SCHEME)!=='https') $errors[]='La cookie Secure requiere URL HTTPS.';
    if (!ctype_digit($dbPort) || (int)$dbPort<1 || (int)$dbPort>65535) $errors[]='Puerto MySQL inválido.';

    if (!$errors) {
        try {
            $serverDsn="mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo=new PDO($serverDsn,$dbUser,$dbPass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (PDOException $createError) {
                throw new RuntimeException('No se pudo crear o consultar la base seleccionada. Revisa los permisos MySQL.');
            }
            $pdo=new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",$dbUser,$dbPass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
            $existingTables=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=".$pdo->quote($dbName))->fetchColumn();
            if ($existingTables>0 && !$freshStart) {
                throw new RuntimeException('La base seleccionada contiene tablas. Usa una base vacía o el modo Empezar de cero, que destruye todos sus datos.');
            }
            if ($freshStart && $existingTables>0) {
                // Solo la base seleccionada. No eliminamos usuarios ni privilegios globales de MySQL.
                // Desactivar claves externas evita fallos por el orden de las dependencias.
                $tables=$pdo->query("SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA=".$pdo->quote($dbName)." AND TABLE_TYPE='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
                $views=$pdo->query("SELECT TABLE_NAME FROM information_schema.views WHERE TABLE_SCHEMA=".$pdo->quote($dbName))->fetchAll(PDO::FETCH_COLUMN);
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                try {
                    foreach ($views as $name) $pdo->exec('DROP VIEW IF EXISTS '. chr(96).str_replace(chr(96),chr(96).chr(96),$name).chr(96));
                    foreach ($tables as $name) $pdo->exec('DROP TABLE IF EXISTS '.chr(96).str_replace(chr(96),chr(96).chr(96),$name).chr(96));
                } finally {
                    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
                }
            }
            $sql=file_get_contents($root.'/database/schema.sql');
            foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', (string)$sql))) as $statement) $pdo->exec($statement);

            $pdo->beginTransaction();
            $stmt=$pdo->prepare("INSERT INTO users(username,email,password_hash,role,status) VALUES(?,?,?,'admin','active')");
            $stmt->execute([$adminUser,$adminEmail,password_hash($adminPass,PASSWORD_DEFAULT)]);
            $adminId=(int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO profiles(user_id,display_name,bio) VALUES(?,?,?)')->execute([$adminId,$adminName,'Administrador principal de '.$siteName]);
            $settings=[
                'site_name'=>$siteName,'site_tagline'=>$siteDescription,'custom_css'=>'','visit_salt'=>bin2hex(random_bytes(32)),
                'contact_email'=>$mailFrom ?: $adminEmail,'mail_name'=>$mailName
            ];
            $st=$pdo->prepare('INSERT INTO settings(`key`,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
            foreach($settings as $k=>$v)$st->execute([$k,$v]);
            $defaults=['home_title'=>$siteName,'home_description'=>$siteDescription,'home_cover'=>'assets/cover.svg','home_show_register'=>'1','adsense_enabled'=>'0','usd_clp_manual'=>'0','primary_color'=>'#31b978','secondary_color'=>'#f5b942','button_radius'=>'2px','home_kicker'=>'TIENDAS LOCALES · GRANDES HISTORIAS','promo_kicker'=>'PROMOCIONES DE TEMPORADA','promo_title'=>'Descubre ofertas de comercios locales','promo_description'=>'Contacta directamente al vendedor y coordina retiro o despacho.','logo_icon'=>'🛍️','footer_address'=>'Chile'];
            foreach($defaults as $k=>$v)$st->execute([$k,$v]);
            $cat=$pdo->prepare('INSERT IGNORE INTO categories(parent_id,name,slug,icon,description,active,sort_order) VALUES(NULL,?,?,?,?,1,?)');
            foreach([['Tecnología','tecnologia','💻','Computación, celulares y electrónica',10],['Hogar','hogar','🏠','Productos para el hogar',20],['Moda','moda','👕','Ropa, calzado y accesorios',30],['Servicios','servicios','🛠️','Servicios ofrecidos por comercios',40]] as $c)$cat->execute($c);
            $pdo->commit();
            if ($installDemo) {
                $demoSql=file_get_contents($root.'/database/demo_seed.sql');
                foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', (string)$demoSql))) as $statement) $pdo->exec($statement);
            }

            $config = "<?php\ndeclare(strict_types=1);\n\n";
            $pairs=[
                'APP_NAME'=>$siteName,'APP_ENV'=>'local','APP_URL'=>$appUrl,
                'DB_HOST'=>$dbHost,'DB_PORT'=>$dbPort,'DB_NAME'=>$dbName,'DB_USER'=>$dbUser,'DB_PASS'=>$dbPass,
                'COOKIE_NAME'=>$cookieName,'COOKIE_DOMAIN'=>$cookieDomain,'COOKIE_PATH'=>$cookiePath,'COOKIE_SAMESITE'=>$cookieSameSite,
                'COOKIE_SECURE'=>$cookieSecure,
                'MAIL_FROM'=>$mailFrom,'MAIL_FROM_NAME'=>$mailName,'MAIL_HOST'=>$mailHost,'MAIL_PORT'=>$mailPort,
                'MAIL_USER'=>$mailUser,'MAIL_PASS'=>$mailPass,'MAIL_ENCRYPTION'=>$mailEncryption,
                'HCAPTCHA_ENABLED'=>$hcaptchaEnabled,'HCAPTCHA_SITEKEY'=>$hcaptchaSitekey,'HCAPTCHA_SECRET'=>$hcaptchaSecret,
                'HCAPTCHA_LOGIN_ENABLED'=>true,'HCAPTCHA_REGISTER_ENABLED'=>true,
            ];
            foreach($pairs as $key=>$value){$config.='const '.$key.' = '.var_export($value,true).";\n";}
            $config.="const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;\n";
            $config.="const ALLOWED_IMAGE_MIMES = ['image/jpeg','image/png','image/webp','image/gif'];\n\n";
            $config.="ini_set('session.use_strict_mode','1');\nini_set('session.cookie_httponly','1');\nini_set('session.cookie_samesite', COOKIE_SAMESITE);\nini_set('session.cookie_path', COOKIE_PATH);\nif (COOKIE_DOMAIN !== '') ini_set('session.cookie_domain', COOKIE_DOMAIN);\nif (COOKIE_SECURE) ini_set('session.cookie_secure','1');\nsession_name(COOKIE_NAME);\nif (session_status() !== PHP_SESSION_ACTIVE) session_start();\n";
            if (file_put_contents($configFile,$config,LOCK_EX)===false) throw new RuntimeException('No se pudo escribir config/config.php.');
            if (file_put_contents($lockFile,"Instalado: ".date(DATE_ATOM)."\n",LOCK_EX)===false) throw new RuntimeException('No se pudo escribir storage/installed.lock.');
            if (!is_file($lockFile)) throw new RuntimeException('No se pudo crear el bloqueo del instalador.');
            $success=true;
            unset($_SESSION['installer_csrf']);
        } catch(Throwable $e) {
            if(isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction())$pdo->rollBack();
            $errors[]='No se pudo completar la instalación: '.$e->getMessage();
        }
    }
}
$defaults=['site_name'=>'ComercioSocial','site_description'=>'Vitrina informativa de comercios, productos y servicios','app_url'=>detect_base_url(),'db_host'=>'127.0.0.1','db_port'=>'3306','db_name'=>'comercio_social','db_user'=>'root','cookie_name'=>'COMERCIOSOCIALSESSID','cookie_path'=>'/','cookie_samesite'=>'Lax','mail_port'=>'587','mail_encryption'=>'tls'];
function val(string $key, array $defaults): string { return h($_POST[$key] ?? $defaults[$key] ?? ''); }
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalador ComercioSocial</title><style>
:root{font-family:Inter,Segoe UI,Arial,sans-serif;color:#eaf0ff;background:#09101f}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at top,#18315d 0,#09101f 45%);min-height:100vh}.wrap{width:min(1120px,94%);margin:32px auto}.hero,.card{background:rgba(17,28,51,.94);border:1px solid #2c4370;border-radius:20px;box-shadow:0 18px 55px #0007}.hero{padding:28px;margin-bottom:20px}.hero h1{margin:0 0 8px;font-size:clamp(28px,5vw,48px)}.hero p{color:#aebbd5}.card{padding:22px;margin-bottom:18px}h2{margin-top:0}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.full{grid-column:1/-1}label{display:block;color:#bbc8e3;font-weight:600}input,select,textarea{width:100%;margin-top:7px;padding:12px 13px;border:1px solid #3a507b;border-radius:10px;background:#0d172a;color:#fff}small{display:block;color:#8192b3;margin-top:6px}.req{display:grid;grid-template-columns:1.4fr .7fr 1.8fr;gap:8px;padding:10px;border-bottom:1px solid #263958}.ok{color:#67e8a5}.bad{color:#ff8b8b}.alert{padding:14px;border-radius:12px;margin-bottom:12px}.error{background:#4a1721;border:1px solid #a73a4c}.success{background:#123d2e;border:1px solid #2e9d70}.btn{border:0;border-radius:12px;padding:14px 20px;background:linear-gradient(135deg,#4d8cff,#7457ff);color:#fff;font-weight:800;cursor:pointer;font-size:16px}.btn:disabled{opacity:.45;cursor:not-allowed}.hint{background:#0b1528;border-left:4px solid #4d8cff;padding:12px 15px;color:#b8c7e6}.check{display:flex;gap:10px;align-items:center}.check input{width:auto;margin:0}@media(max-width:760px){.grid{grid-template-columns:1fr}.full{grid-column:auto}.req{grid-template-columns:1fr}.wrap{margin:14px auto}}
</style></head><body><div class="wrap"><div class="hero"><h1>🪟 Instalador ComercioSocial Vitrina 3.0</h1><p>Instalación limpia y verificada para una vitrina informativa: sin carrito, pagos, reservas, boletas ni facturación.</p></div>
<?php if($success):?><div class="card success"><h2>✅ Instalación terminada</h2><p>La base de datos, configuración y cuenta administradora fueron creadas correctamente.</p><p><a class="btn" href="../public/">Abrir la web</a></p><p>Por seguridad, el instalador quedó bloqueado mediante <code>storage/installed.lock</code>.</p></div><?php else:?>
<?php foreach($errors as $error):?><div class="alert error"><?=h($error)?></div><?php endforeach;?>
<div class="card"><h2>1. Diagnóstico del servidor</h2><?php foreach($requirements as [$name,$ok,$detail]):?><div class="req"><strong><?=h($name)?></strong><span class="<?=$ok?'ok':'bad'?>"><?=$ok?'✔ Correcto':'✖ Requerido'?></span><small><?=h((string)$detail)?></small></div><?php endforeach;?><div class="hint"><b>php.ini recomendado:</b> habilita <code>extension=pdo_mysql</code>, <code>extension=mbstring</code>, <code>extension=fileinfo</code>, <code>extension=openssl</code>, <code>extension=zip</code>; usa <code>file_uploads=On</code>, <code>upload_max_filesize=32M</code>, <code>post_max_size=32M</code>. En XAMPP normalmente ya vienen habilitados.</div></div>
<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?=h($_SESSION['installer_csrf'])?>">
<div class="card"><h2>2. Información del sitio</h2><div class="grid"><label>Nombre del comercio<input name="site_name" required value="<?=val('site_name',$defaults)?>"></label><label>Descripción o eslogan<input name="site_description" required value="<?=val('site_description',$defaults)?>"></label><label class="full">URL pública de la aplicación<input name="app_url" required value="<?=val('app_url',$defaults)?>"><small>En XAMPP normalmente termina en <b>/ComercioSocial/public</b>.</small></label></div></div>
<div class="card"><h2>3. Base de datos MySQL/MariaDB</h2><div class="grid"><label>Servidor<input name="db_host" required value="<?=val('db_host',$defaults)?>"></label><label>Puerto<input name="db_port" required value="<?=val('db_port',$defaults)?>"></label><label>Nombre de la base<input name="db_name" required value="<?=val('db_name',$defaults)?>"></label><label>Usuario MySQL<input name="db_user" required value="<?=val('db_user',$defaults)?>"></label><label class="full">Contraseña MySQL<input type="password" name="db_pass"><small>En XAMPP nuevo suele quedar vacía para root. En hosting debes usar la asignada por el proveedor.</small></label>
<div class="hint full"><b>Instalación normal:</b> utiliza una base vacía y no elimina datos. Para restaurar una instalación utiliza un respaldo.</div>
<label class="check full"><input type="checkbox" name="fresh_start" value="1" <?=isset($_POST['fresh_start'])?'checked':''?>><span><b>Empezar de cero (BORRAR TODAS LAS TABLAS)</b><small>Elimina tablas y vistas de la base seleccionada, y crea el esquema, roles y administrador nuevamente. NO borra cuentas/privilegios globales de MySQL ni archivos del servidor.</small></span></label>
<label class="full">Confirmación de reinicio<input type="text" name="fresh_confirmation" autocomplete="off" placeholder="REINICIAR nombre_de_base" value=""><small>Solo para Empezar de cero: escribe exactamente REINICIAR seguido del nombre de la base. ¡Todos sus datos se perderán! Exporta un respaldo antes.</small></label>
<label class="check full"><input type="checkbox" name="install_demo"> <span><b>Instalar 10 vendedores y 10 publicaciones demo</b><small>Incluye imágenes locales para probar catálogo, perfiles y visor.</small></span></label></div></div>
<div class="card"><h2>4. Cookies y sesiones</h2><div class="grid"><label>Nombre de cookie<input name="cookie_name" value="<?=val('cookie_name',$defaults)?>"></label><label>Dominio de cookie<input name="cookie_domain" value="<?=val('cookie_domain',$defaults)?>" placeholder="Vacío en localhost"></label><label>Ruta<input name="cookie_path" value="<?=val('cookie_path',$defaults)?>"></label><label>SameSite<select name="cookie_samesite"><?php foreach(['Lax','Strict','None'] as $s):?><option <?=$s===($_POST['cookie_samesite']??'Lax')?'selected':''?>><?=$s?></option><?php endforeach;?></select></label><label class="check full"><input type="checkbox" name="cookie_secure" <?=isset($_POST['cookie_secure'])?'checked':''?>> Usar cookie Secure (actívalo sólo con HTTPS)</label></div></div>
<div class="card"><h2>5. Correo del sitio (opcional)</h2><div class="grid"><label>Correo remitente<input type="email" name="mail_from" value="<?=val('mail_from',$defaults)?>"></label><label>Nombre remitente<input name="mail_name" value="<?=val('mail_name',$defaults)?>"></label><label>Servidor SMTP<input name="mail_host" value="<?=val('mail_host',$defaults)?>" placeholder="smtp.ejemplo.com"></label><label>Puerto SMTP<input name="mail_port" value="<?=val('mail_port',$defaults)?>"></label><label>Usuario SMTP<input name="mail_user" value="<?=val('mail_user',$defaults)?>"></label><label>Contraseña SMTP<input type="password" name="mail_pass"></label><label>Cifrado<select name="mail_encryption"><option value="tls">TLS</option><option value="ssl">SSL</option><option value="">Sin cifrado</option></select></label></div></div>
<div class="card"><h2>Protección hCaptcha</h2><div class="grid"><label class="check full"><input type="checkbox" name="hcaptcha_enabled" <?=isset($_POST['hcaptcha_enabled'])?'checked':''?>> Activar hCaptcha para inicio de sesión y registro</label><label>Sitekey pública<input name="hcaptcha_sitekey" autocomplete="off" value="<?=val('hcaptcha_sitekey',$defaults)?>"></label><label>Secret privada<input type="password" name="hcaptcha_secret" autocomplete="off"><small>La clave privada se almacena en config/config.php, nunca se muestra al visitante. Requiere PHP cURL y conexión HTTPS a api.hcaptcha.com.</small></label></div></div>
<div class="card"><h2>6. Administrador principal</h2><div class="grid"><label>Nombre visible<input name="admin_name" required value="<?=val('admin_name',$defaults)?>"></label><label>Usuario<input name="admin_user" required pattern="[A-Za-z0-9_]{3,40}" value="<?=val('admin_user',$defaults)?>"></label><label>Correo<input type="email" name="admin_email" required value="<?=val('admin_email',$defaults)?>"></label><label>Contraseña<input type="password" name="admin_pass" minlength="10" required><small>Mínimo 10 caracteres.</small></label></div></div>
<div class="card"><button class="btn" <?=$requirementsOk?'':'disabled'?>>Verificar, instalar y crear administrador</button></div></form><?php endif;?></div></body></html>
