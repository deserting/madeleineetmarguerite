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
start_sess();

$slug = $_SESSION['client_gallery'] ?? '';
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

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Mrs+Saint+Delafield&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../css/variables.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="galerie-privee.css">

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
      <input type="hidden" name="csrf" value="<?=csrf_token() ?>">
      <input type="hidden" name="action" value="login">
      <p class="field">
        <label>Identifiant</label>
        <input name="user" required type="text" autocomplete="username">
      </p>
      <p class="field">
        <label>Mot de passe</label>
        <input name="pass" type="password" autocomplete="current-password">
      </p>
      <p><button class="btn-primary">Se connecter</button></p>
    </form>
<?php else:
    $gdir = $dirP.'/'.$slug;
    $meta = read_json($gdir.'/meta.json', []);
    $photos = read_json($gdir.'/photos.json', []);
?>
    <h1 class="section-heading" style="margin-bottom:1rem;color:var(--color-accent);"><?=e($meta['title']??'Galerie')?></h1>
    <?php if(!empty($meta['intro_text'])): ?>
      <p class="client-intro" style="text-align:center;"><?=nl2br(e($meta['intro_text']))?></p>
    <?php endif; ?>
    <p class="quality-note" style="text-align:center;"><em>Ces photos sont optimisées pour un affichage fluide. Pour obtenir l’intégralité des fichiers en haute définition, utilisez le lien de téléchargement ci-dessous.</em></p>
    <ul class="grid-gallery" role="list">
      <?php foreach($photos as $p): if(empty($p['visible']) || empty($p['grid']) || empty($p['hd'])) continue; ?>
        <li class="gallery-item">
          <a href="<?=e('../public/galeries/'.rawurlencode($slug).'/hd/'.basename($p['hd']))?>" class="glightbox" data-gallery="gal" aria-label="Agrandir la photo">
            <img src="<?=e('../public/galeries/'.rawurlencode($slug).'/grid/'.basename($p['grid']))?>" alt="" loading="lazy" width="400" height="400">
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php if(!empty($meta['wetransfer_url'])): ?>
      <div class="download-all" style="text-align:center;">
        <a class="btn-primary" style="display:inline-block;padding:0.75rem 1.5rem;text-align:center;" href="<?=e($meta['wetransfer_url'])?>" target="_blank" rel="noopener">Télécharger tout</a>
      </div>
    <?php endif; ?>
    <p class="logout" style="text-align:center;margin-top:2rem;"><a href="?logout=1">← Se déconnecter</a></p>
<?php endif; ?>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>GLightbox({selector:'.glightbox', loop:true, touchNavigation:true});</script>
</body>
</html>
