<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/session.php';
if (is_file(BASE_PATH . '/config/installed.lock')) {
    header('Location: ' . BASE_URL . 'index.php'); exit;
}
if (empty($_SESSION['installer_csrf'])) $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
$errors = [];
$values = ['host'=>'127.0.0.1','port'=>'3306','name'=>'mitienda','user'=>'root','admin_name'=>'','admin_email'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) $values[$key] = trim((string)($_POST[$key] ?? $default));
    $password = (string)($_POST['password'] ?? '');
    $adminPass = (string)($_POST['admin_password'] ?? '');
    $adminPass2 = (string)($_POST['admin_password_confirm'] ?? '');
    if (!hash_equals($_SESSION['installer_csrf'], (string)($_POST['csrf'] ?? ''))) $errors[] = 'Formulario vencido. Recarga la página.';
    if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/D', $values['name'])) $errors[] = 'El nombre de la base de datos solo puede contener letras, números y guiones bajos.';
    if (!filter_var($values['port'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>65535]])) $errors[] = 'Puerto MySQL inválido.';
    if ($values['host'] === '' || strlen($values['host']) > 255 || $values['user'] === '') $errors[] = 'Completa los datos de conexión MySQL.';
    if (mb_strlen($values['admin_name']) < 2 || mb_strlen($values['admin_name']) > 120) $errors[] = 'El nombre del administrador debe tener entre 2 y 120 caracteres.';
    if (!filter_var($values['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo del administrador inválido.';
    if (strlen($adminPass) < 10 || $adminPass !== $adminPass2) $errors[] = 'La contraseña del administrador debe tener al menos 10 caracteres y coincidir con la confirmación.';
    if (!is_writable(BASE_PATH . '/config')) $errors[] = 'La carpeta config/ no tiene permisos de escritura.';
    if (!extension_loaded('mysqli')) $errors[] = 'Activa la extensión mysqli de PHP.';
    if (!$errors) {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $db = new mysqli($values['host'], $values['user'], $password, '', (int)$values['port']);
            $db->set_charset('utf8mb4');
            $dbName = $values['name'];
            $db->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $db->select_db($dbName);
            // La base de datos se crea si no existe. Nunca se borran tablas existentes.
            $existing = $db->query("SHOW TABLES");
            if ($existing->num_rows !== 0) throw new RuntimeException('La base de datos ya contiene tablas. Usa una base de datos nueva y vacía para evitar alterar información existente.');
            $sql = file_get_contents(BASE_PATH . '/database.sql');
            if ($sql === false) throw new RuntimeException('No se encontró database.sql.');
            // El esquema incluido usa sentencias CREATE TABLE. Se ejecutan en la base elegida.
            $sql = preg_replace('/^CREATE DATABASE[^;]*;\s*/i', '', $sql);
            $sql = preg_replace('/^USE\s+\w+\s*;\s*/i', '', $sql);
            if (!$db->multi_query($sql)) throw new RuntimeException('No se pudieron crear las tablas.');
            do { if ($result = $db->store_result()) $result->free(); } while ($db->more_results() && $db->next_result());
            if ($db->errno) throw new RuntimeException('No se pudo completar la estructura SQL.');
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, correo, password, rol, estado) VALUES (?, ?, ?, 'admin', 'activo')");
            $stmt->bind_param('sss', $values['admin_name'], $values['admin_email'], $hash);
            $stmt->execute();
            $config = "<?php\n// Generado por el instalador de MiTienda. No publiques este archivo.\nreturn " . var_export([
                'host'=>$values['host'],'port'=>(int)$values['port'],'name'=>$dbName,
                'user'=>$values['user'],'password'=>$password
            ], true) . ";\n";
            $configFile = BASE_PATH . '/config/database.php';
            if (file_put_contents($configFile, $config, LOCK_EX) === false) throw new RuntimeException('No se pudo guardar config/database.php.');
            if (file_put_contents(BASE_PATH . '/config/installed.lock', 'Instalado: '.date('c')."\n", LOCK_EX) === false) {
                @unlink($configFile);
                throw new RuntimeException('No se pudo crear el bloqueo del instalador.');
            }
            unset($_SESSION['installer_csrf']);
            header('Location: ' . BASE_URL . 'install/success.php'); exit;
        } catch (Throwable $e) {
            // Evitar mostrar mensajes internos con datos de acceso MySQL.
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Error de instalación: verifica las credenciales, permisos MySQL y que la base de datos esté vacía.';
        }
    }
}
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar MiTienda</title><link rel="stylesheet" href="<?=BASE_URL?>install/style.css"></head><body>
<main class="shell"><div class="brand">🛍️ MiTienda <span>Setup</span></div><div class="heading"><span class="step">INSTALACIÓN INICIAL · PASO 1 DE 1</span><h1>Configura tu marketplace</h1><p>El asistente preparará MySQL, creará las tablas y registrará tu cuenta administradora.</p></div>
<?php if($errors):?><div class="alert" role="alert"><strong>Revisa lo siguiente:</strong><ul><?php foreach($errors as $error):?><li><?=h($error)?></li><?php endforeach;?></ul></div><?php endif;?>
<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?=h($_SESSION['installer_csrf'])?>">
<section class="card"><div class="card-title"><span class="num">01</span><div><h2>Conexión a MySQL</h2><p>En XAMPP, normalmente el usuario es <code>root</code> y la contraseña está vacía.</p></div></div>
<div class="grid"><label>Servidor MySQL<input required name="host" value="<?=h($values['host'])?>"></label><label>Puerto<input required type="number" min="1" max="65535" name="port" value="<?=h($values['port'])?>"></label><label>Base de datos nueva<input required name="name" pattern="[A-Za-z0-9_]+" value="<?=h($values['name'])?>"></label><label>Usuario MySQL<input required name="user" value="<?=h($values['user'])?>"></label><label class="full">Contraseña MySQL <small>(déjala vacía si usas XAMPP sin contraseña)</small><input type="password" name="password" autocomplete="new-password"></label></div></section>
<section class="card"><div class="card-title"><span class="num">02</span><div><h2>Cuenta administradora</h2><p>Esta será la primera cuenta con acceso al AdminCP.</p></div></div>
<div class="grid"><label>Nombre<input required minlength="2" maxlength="120" name="admin_name" value="<?=h($values['admin_name'])?>"></label><label>Correo electrónico<input required type="email" name="admin_email" value="<?=h($values['admin_email'])?>"></label><label>Contraseña (mínimo 10 caracteres)<input required type="password" minlength="10" name="admin_password" autocomplete="new-password"></label><label>Confirmar contraseña<input required type="password" minlength="10" name="admin_password_confirm" autocomplete="new-password"></label></div></section>
<div class="notice">🔒 Este asistente solo funciona antes de la primera instalación. No importa datos existentes ni borra tablas.</div><button type="submit" class="submit">Instalar MiTienda <span>→</span></button></form><footer>MiTienda · Instalador para PHP 8.1+ y MySQL/MariaDB</footer></main></body></html>
