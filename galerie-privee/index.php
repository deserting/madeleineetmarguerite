<?php
// PUBLIC — Un seul point d'entrée, pas de slug dans l'URL.
function cfg(): array { static $c; if(!$c){ $c = require dirname(__DIR__).'/data/config.php'; } return $c; }
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function start_sess(){ if(session_status()===PHP_SESSION_NONE){ session_name('mm_cli'); session_start(); } }
function csrf_token(){ start_sess(); if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_check(){ if(($_POST['csrf']??'') !== ($_SESSION['csrf']??'')){ http_response_code(403); exit('CSRF invalide'); } }
function read_json($f,$fb=null){ return is_file($f) ? (json_decode(file_get_contents($f),true) ?: $fb) : $fb; }

$c = cfg();
$dirP = $c['dir_private'];
$dirPub = $c['dir_public'];
start_sess();

$slug = $_SESSION['client_gallery'] ?? ''; // déjà connecté ?

$err = '';
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='login'){
  csrf_check();
  $u = trim($_POST['user'] ?? '');
  $p = (string)($_POST['pass'] ?? '');
  if($u===''){ $err='Identifiant requis'; }
  else {
    $foundSlug = ''; $targetMeta = null;
    foreach (glob($dirP.'/*/meta.json') as $mf) {
      $meta = read_json($mf, []);
      if(!$meta || empty($meta['is_published'])) continue;
      if (($meta['client_user'] ?? '') === $u) { $foundSlug = basename(dirname($mf)); $targetMeta=$meta; break; }
    }
    if($foundSlug===''){ $err='Identifiants incorrects'; }
    else {
      $ok = empty($targetMeta['client_pass_hash']) ? ($p==='') : password_verify($p, $targetMeta['client_pass_hash']);
      if($ok){ $_SESSION['client_gallery']=$foundSlug; header('Location: ./'); exit; }
      else { $err='Identifiants incorrects'; }
    }
  }
}

if(isset($_GET['logout'])){ unset($_SESSION['client_gallery']); header('Location: ./'); exit; }
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Galerie privée</title>

  <!-- EXACTEMENT comme la home pour la display font -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Mrs+Saint+Delafield&display=swap" rel="stylesheet">

  <!-- CSS du site (reprend la grille/typos/boutons identiques au portfolio) -->
  <link rel="stylesheet" href="../css/variables.css">
  <link rel="stylesheet" href="../css/style.css">

  <!-- Mini-override spécifique galerie (login + micro-ajustements) -->
  <link rel="stylesheet" href="galerie-privee.css">

  <!-- Lightbox -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
</head>
<body>
<main class="py-24" style="min-height:100vh">
  <div class="container">
<?php if(!$slug): ?>
    <h1 class="section-heading" style="margin-bottom:1rem;">Galerie privée</h1>
    <p class="intro">Entrez vos identifiants pour accéder à vos photos.</p>
    <?php if($err): ?><p class="notice is-error"><strong>Oups.</strong> <span><?=e($err)?></span></p><?php endif; ?>

    <form method="post" class="login-card">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <input type="hidden" name="action" value="login">

      <p class="field">
        <label for="login-user">Identifiant</label>
        <input id="login-user" name="user" required type="text" autocomplete="username">
      </p>
      <p class="field">
        <label for="login-pass">Mot de passe</label>
        <input id="login-pass" name="pass" type="password" autocomplete="current-password">
      </p>
      <p><button class="btn-primary" type="submit" style="width:100%">Se connecter</button></p>
    </form>

<?php else:
    $gdir = $dirP.'/'.$slug;
    $meta = read_json($gdir.'/meta.json', []);
    $photos = read_json($gdir.'/photos.json', []);
?>
    <p class="logout"><a href="?logout=1">← Se déconnecter</a></p>
    <h1 class="section-heading" style="margin-bottom:1rem;"><?=e($meta['title']??'Galerie')?></h1>
    <p class="intro" style="margin-bottom:2rem;">Client · <?=e($meta['client_name']??'—')?></p>

    <ul class="grid-gallery" role="list">
      <?php
      $hasAny = false;
      foreach($photos as $p):
        if(empty($p['visible']) || empty($p['grid']) || empty($p['hd'])) continue;
        $hasAny = true;
      ?>
        <li class="gallery-item">
          <a href="<?=e('../public/galeries/'.rawurlencode($slug).'/hd/'.basename($p['hd']))?>" class="glightbox" data-gallery="gal" aria-label="Agrandir la photo">
            <img src="<?=e('../public/galeries/'.rawurlencode($slug).'/grid/'.basename($p['grid']))?>" alt="" loading="lazy" width="400" height="400">
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if(!$hasAny): ?>
      <p class="intro" style="margin-top:1.5rem">Les photos ne sont pas encore disponibles. Revenez un peu plus tard ✨</p>
    <?php endif; ?>

    <?php if(!empty($meta['wetransfer_url'])): ?>
      <div class="download-all">
        <a class="btn-primary" href="<?=e($meta['wetransfer_url'])?>" target="_blank" rel="noopener">Télécharger tout</a>
      </div>
    <?php endif; ?>
<?php endif; ?>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>GLightbox({selector:'.glightbox', loop:true, touchNavigation:true});</script>
</body>
</html>
