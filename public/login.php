<?php
require_once __DIR__.'/../app/bootstrap.php';header('Cache-Control: no-store, private');
if(user()) redirect('index.php');
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $identity=trim($_POST['login']??'');
    if(login_is_limited($identity)){flash('error','Demasiados intentos. Intenta nuevamente en 15 minutos.');redirect('login.php');}
    $q=db()->prepare('SELECT * FROM users WHERE email=? OR username=? LIMIT 1');
    $q->execute([$identity,$identity]); $u=$q->fetch();
    $valid=$u&&password_verify($_POST['password']??'',$u['password_hash']);
    record_login_attempt($identity,(bool)$valid);
    if($valid){
        if(($u['status']??'')==='blocked'){flash('error','Esta cuenta está bloqueada. Contacta a la administración.');redirect('login.php');}
        if(($u['status']??'')==='pending'){flash('error','Tu cuenta todavía está pendiente de aprobación.');redirect('login.php');}
        session_regenerate_id(true); $_SESSION['uid']=$u['id']; redirect('index.php');
    }
    flash('error','Usuario, correo o contraseña incorrectos.'); redirect('login.php');
}
require __DIR__.'/../partials/header.php';
?>
<section class="login-pro-wrap">
  <div class="login-pro-visual" style="background-image:linear-gradient(135deg,rgba(11,24,39,.90),rgba(37,99,235,.60)),url('<?=e(setting('home_cover','assets/cover.svg'))?>')">
    <div class="login-pro-brand"><span><?=e(setting('logo_icon','🛍️'))?></span><b><?=e(setting('site_name',APP_NAME))?></b></div>
    <div class="login-pro-copy"><span class="login-kicker">COMERCIO LOCAL · CONFIANZA REAL</span><h1>Administra tu negocio desde un solo lugar.</h1><p>Publica artículos, conversa con la comunidad y potencia la visibilidad de tu local en Chile.</p><div class="login-benefits"><span>✓ Perfil comercial</span><span>✓ Catálogo multimedia</span><span>✓ Contacto directo</span></div></div>
  </div>
  <div class="login-pro-panel">
    <div class="login-pro-card">
      <div class="login-mobile-brand"><span><?=e(setting('logo_icon','🛍️'))?></span><b><?=e(setting('site_name',APP_NAME))?></b></div>
      <span class="login-kicker">BIENVENIDO</span><h2>Iniciar sesión</h2><p class="login-subtitle">Acceso exclusivo para vendedores y administradores.</p>
      <form method="post" class="login-pro-form"><?=csrf_field()?>
        <label><span>Usuario o correo electrónico</span><div class="login-field"><i>👤</i><input name="login" required autocomplete="username" autofocus placeholder="usuario@correo.cl"></div></label>
        <label><span>Contraseña</span><div class="login-field"><i>🔒</i><input id="loginPassword" type="password" name="password" required autocomplete="current-password" placeholder="Tu contraseña"><button type="button" class="password-toggle" onclick="const p=document.getElementById('loginPassword');p.type=p.type==='password'?'text':'password';this.textContent=p.type==='password'?'Ver':'Ocultar'">Ver</button></div></label>
        <button class="btn login-submit">Ingresar a mi cuenta <span>→</span></button>
      </form>
      <div class="login-register"><span>¿Quieres publicar tus productos?</span><a href="<?=url('register.php')?>">Crear cuenta de vendedor</a></div>
      <div class="login-security">🛡️ Sesión protegida, límite de intentos y contraseñas cifradas.</div>
    </div>
  </div>
</section>
<?php require __DIR__.'/../partials/footer.php';?>
