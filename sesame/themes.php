<?php
// sesame/themes.php — Gestion visuels du site (hero/portrait/interlude/strip/portfolio)

function cfg(): array { static $c; if(!$c){ $c = require dirname(__DIR__).'/data/config.php'; } return $c; }
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function start_sess(){ if(session_status()===PHP_SESSION_NONE){ session_name('mm_admin'); session_start(); } }
function is_admin(){ start_sess(); return !empty($_SESSION['is_admin']); }
function csrf_token(){ start_sess(); if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_check(){ if(($_POST['csrf']??'') !== ($_SESSION['csrf']??'')){ http_response_code(403); exit('CSRF invalide'); } }
function base_href(){ $b = rtrim(dirname($_SERVER['SCRIPT_NAME']),'/'); return $b===''?'.':$b; }

function ensure_dir($d){ if(!is_dir($d)) mkdir($d, 0775, true); }

function to_webp_variant($src,$dest,$maxLong){
  if(class_exists('Imagick')){
    $im=new Imagick($src);
    $im->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    $w=$im->getImageWidth(); $h=$im->getImageHeight();
    $scale = ($w>$h? $maxLong/$w : $maxLong/$h);
    if($scale<1) $im->resizeImage((int)($w*$scale),(int)($h*$scale), Imagick::FILTER_LANCZOS, 1);
    $im->setImageFormat('webp'); $im->setImageCompressionQuality(72); $im->stripImage();
    ensure_dir(dirname($dest));
    return $im->writeImage($dest);
  }
  $info=@getimagesize($src); if(!$info) return false; [$w,$h]=$info; $type=$info[2];
  switch($type){case IMAGETYPE_JPEG:$srcim=@imagecreatefromjpeg($src);break;case IMAGETYPE_PNG:$srcim=@imagecreatefrompng($src);break;case IMAGETYPE_WEBP:$srcim=@imagecreatefromwebp($src);break;default:return false;}
  if(!$srcim) return false;
  $scale=($w>$h? $maxLong/$w : $maxLong/$h); if($scale>1){$nw=$w;$nh=$h;} else {$nw=(int)($w*$scale);$nh=(int)($h*$scale);}
  $dst=imagecreatetruecolor($nw,$nh);
  imagecopyresampled($dst,$srcim,0,0,0,0,$nw,$nh,$w,$h);
  ensure_dir(dirname($dest));
  $ok=imagewebp($dst,$dest,72);
  imagedestroy($srcim); imagedestroy($dst);
  return $ok;
}

// --- Slots gérés ----------------------------------------------------
//  - hero.webp, portrait.webp, interlude.webp
//  - photo strip (gallery/01.webp … 09.webp)
//  - portfolio (thumbs/NNthumb.webp + hd/NN.webp), NN = 01..18
$SLOTS = [
  // Singles
  'hero'      => ['type'=>'single',   'dest'=>'../assets/img/hero.webp',      'max'=>2000],
  'portrait'  => ['type'=>'single',   'dest'=>'../assets/img/portrait.webp',  'max'=>1000],
  'interlude' => ['type'=>'single',   'dest'=>'../assets/img/interlude.webp', 'max'=>2000],

  // Photo strip (bande pellicule)
  'strip01' => ['type'=>'single','dest'=>'../assets/img/gallery/01.webp','max'=>1200],
  'strip02' => ['type'=>'single','dest'=>'../assets/img/gallery/02.webp','max'=>1200],
  'strip03' => ['type'=>'single','dest'=>'../assets/img/gallery/03.webp','max'=>1200],
  'strip04' => ['type'=>'single','dest'=>'../assets/img/gallery/04.webp','max'=>1200],
  'strip05' => ['type'=>'single','dest'=>'../assets/img/gallery/05.webp','max'=>1200],
  'strip06' => ['type'=>'single','dest'=>'../assets/img/gallery/06.webp','max'=>1200],
  'strip07' => ['type'=>'single','dest'=>'../assets/img/gallery/07.webp','max'=>1200],
  'strip08' => ['type'=>'single','dest'=>'../assets/img/gallery/08.webp','max'=>1200],
  'strip09' => ['type'=>'single','dest'=>'../assets/img/gallery/09.webp','max'=>1200],
];

// Ajoute dynamiquement les 18 emplacements portfolio
for($i=1;$i<=18;$i++){
  $nn = str_pad((string)$i,2,'0',STR_PAD_LEFT);
  // Portfolio
$SLOTS["port$nn"] = [
  'type'=>'portfolio',
  'hd'    => "../assets/img/portfolio/hd/$nn.webp",        // HD lightbox
  'thumb' => "../assets/img/portfolio/thumbs/{$nn}thumb.webp", // vignette
  'max_hd'=> 1600,  // 1800 → 1600
  'max_th'=> 500    // 600 → 500
];
}

// --- Action upload AJAX ---------------------------------------------
start_sess();
if(!is_admin()){
  http_response_code(302);
  header('Location: '.base_href().'/index.php');
  exit;
}

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='theme-upload'){
  csrf_check();
  $slot = $_POST['slot'] ?? '';
  if(!isset($SLOTS[$slot])){ header('Content-Type: application/json'); http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Slot inconnu']); exit; }

  $file = $_FILES['image']['tmp_name'] ?? '';
  $err  = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
  if($err!==UPLOAD_ERR_OK || !$file || !is_uploaded_file($file)){
    header('Content-Type: application/json'); http_response_code(400); echo json_encode(['ok'=>false,'error'=>"Fichier invalide ($err)"]); exit;
  }

  $mime = @mime_content_type($file);
  if(!in_array($mime, ['image/jpeg','image/png','image/webp'], true)){
    header('Content-Type: application/json'); http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Format non supporté']); exit;
  }

  $slotDef = $SLOTS[$slot];
  $ok = false; $out=null;

  if($slotDef['type']==='single'){
    $ok = to_webp_variant($file, $slotDef['dest'], $slotDef['max']);
    $out = ['url'=>substr($slotDef['dest'],3)]; // ../assets/... => assets/...
  } else {
    // Portfolio = 2 variantes
    $ok1 = to_webp_variant($file, $slotDef['hd'],    $slotDef['max_hd']);
    $ok2 = to_webp_variant($file, $slotDef['thumb'], $slotDef['max_th']);
    $ok  = $ok1 && $ok2;
    $out = ['url_hd'=>substr($slotDef['hd'],3),'url_th'=>substr($slotDef['thumb'],3)];
  }

  if(!$ok){ header('Content-Type: application/json'); http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Conversion impossible']); exit; }

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>true,'slot'=>$slot,'out'=>$out,'ts'=>time()]);
  exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Sesame · Thèmes & images</title>
  <link rel="stylesheet" href="../css/variables.css">
  <link rel="stylesheet" href="sesame.css">
  <link rel="stylesheet" href="themes.css">
</head>
<body class="se">
<div class="wrap">

  <div class="topbar">
    <h1>Thèmes & images du site</h1>
    <div>
      <a href="index.php">← Retour aux galeries</a>
      ·
      <a href="../index.html" target="_blank">Voir le site</a>
    </div>
  </div>

  <p class="muted" style="margin:.5rem 0 1rem;">
    Téléverse une image ; elle est automatiquement optimisée (WebP) et remplacée sur le site.
    Pour le <strong>portfolio</strong>, la miniature et la version HD sont créées en une fois.
  </p>

  <!-- Singles -->
  <section class="card">
    <h2>Sections principales</h2>
    <div class="grid-tiles">
      <?php
        $singles = [
          ['slot'=>'hero','label'=>'Hero (plein écran)','path'=>'assets/img/hero.webp','hint'=>'≈2400 px max'],
          ['slot'=>'portrait','label'=>'Portrait “À propos”','path'=>'assets/img/portrait.webp','hint'=>'≈1400 px max'],
          ['slot'=>'interlude','label'=>'Interlude (fond plein écran)','path'=>'assets/img/interlude.webp','hint'=>'≈2400 px max'],
        ];
        foreach($singles as $s):
      ?>
      <article class="tile" data-slot="<?=e($s['slot'])?>">
        <figure class="thumb">
          <img src="../<?=e($s['path'])?>?t=<?=time()?>" alt="">
          <div class="spinner" hidden></div>
        </figure>
        <div class="tile-meta">
          <strong><?=e($s['label'])?></strong>
          <span class="muted"><?=e($s['hint'])?></span>
        </div>
        <form class="uploader" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="theme-upload">
          <input type="hidden" name="csrf" value="<?=csrf_token()?>">
          <input type="hidden" name="slot" value="<?=e($s['slot'])?>">
          <input type="file" name="image" accept="image/*">
        </form>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Bande pellicule -->
  <section class="card">
    <h2>Bande “pellicule” (9 images)</h2>
    <div class="grid-tiles">
      <?php for($i=1;$i<=9;$i++):
        $nn = str_pad((string)$i,2,'0',STR_PAD_LEFT);
        $slot="strip$nn"; $path="assets/img/gallery/$nn.webp";
      ?>
      <article class="tile" data-slot="<?=$slot?>">
        <figure class="thumb">
          <img src="../<?=$path?>?t=<?=time()?>" alt="">
          <div class="spinner" hidden></div>
        </figure>
        <div class="tile-meta">
          <strong>Pellicule #<?=$nn?></strong>
          <span class="muted">≈1600 px max</span>
        </div>
        <form class="uploader" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="theme-upload">
          <input type="hidden" name="csrf" value="<?=csrf_token()?>">
          <input type="hidden" name="slot" value="<?=$slot?>">
          <input type="file" name="image" accept="image/*">
        </form>
      </article>
      <?php endfor; ?>
    </div>
  </section>

  <!-- Portfolio -->
  <section class="card">
    <h2>Portfolio (miniature + HD)</h2>
    <div class="grid-tiles">
      <?php for($i=1;$i<=18;$i++):
        $nn = str_pad((string)$i,2,'0',STR_PAD_LEFT);
        $slot="port$nn"; $thumb="assets/img/portfolio/thumbs/{$nn}thumb.webp"; $hd="assets/img/portfolio/hd/$nn.webp";
      ?>
      <article class="tile" data-slot="<?=$slot?>">
        <figure class="thumb">
          <img src="../<?=$thumb?>?t=<?=time()?>" alt="" title="Miniature">
          <div class="spinner" hidden></div>
        </figure>
        <div class="tile-meta">
          <strong>Photo #<?=$nn?></strong>
          <span class="muted">Crée aussi la version HD (lightbox)</span>
        </div>
        <form class="uploader" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="theme-upload">
          <input type="hidden" name="csrf" value="<?=csrf_token()?>">
          <input type="hidden" name="slot" value="<?=$slot?>">
          <input type="file" name="image" accept="image/*">
        </form>
      </article>
      <?php endfor; ?>
    </div>
  </section>

  <p class="muted" style="margin-top:1rem">Astuce : pour garder la cohérence du site, évite les fichiers > 8 Mo. La conversion s’occupe du reste.</p>

</div>

<script>
  // Helper: charge une URL d'image, puis exécute cb(ok=true/false)
  function preloadImage(url, cb){
    const loader = new Image();
    // On évite lazy ici, on veut un "vrai" onload
    loader.onload = () => cb(true, url);
    loader.onerror = () => cb(false, url);
    loader.src = url;
  }

  // Upload immédiat + spinner garanti + cache-buster + préchargement
  document.querySelectorAll('.tile .uploader input[type="file"]').forEach((inp) => {
    inp.addEventListener('change', async () => {
      const form = inp.closest('form');
      const tile = inp.closest('.tile');
      const fig  = tile.querySelector('.thumb');
      const img  = fig.querySelector('img');
      const spin = fig.querySelector('.spinner');

      if (!inp.files || !inp.files[0]) return;

      // Spinner ON (en dur, pour ne pas dépendre d'un style extérieur)
      spin.hidden = false;
      fig.classList.add('is-loading');

      const data = new FormData(form);
      data.set('image', inp.files[0]); // remplace l'input file

      try {
        const res  = await fetch('themes.php', { method:'POST', body:data, headers:{'X-Requested-With':'fetch'} });
        const json = await res.json();
        if (!json.ok) throw new Error(json.error || 'Erreur');

        // Détermine l'URL d'aperçu renvoyée (single ou portfolio thumb)
        let url = null;
        if (json.out && json.out.url) {
          url = '../' + json.out.url;       // singles (hero/portrait/interlude/strip)
        } else if (json.out && json.out.url_th) {
          url = '../' + json.out.url_th;    // portfolio: vignette
        }

        if (!url) throw new Error('Aucune URL de prévisualisation');

        // Ajoute un cache-buster pour forcer un vrai rechargement
        const ts = (json.ts || Date.now());
        url = url + (url.includes('?') ? '&' : '?') + 'v=' + ts;

        // 1) Précharge la nouvelle image; 2) remplace le src; 3) coupe le spinner
        preloadImage(url, (ok) => {
          // Même si ok=false, on remplace quand même pour éviter de bloquer l'UI
          img.src = url;
          spin.hidden = true;
          fig.classList.remove('is-loading');

          tile.classList.add(ok ? 'success' : 'error');
          setTimeout(() => tile.classList.remove(ok ? 'success' : 'error'), 1400);
        });

      } catch (err) {
        // Erreur réseau/serveur: on coupe le spinner et on notifie
        spin.hidden = true;
        fig.classList.remove('is-loading');
        tile.classList.add('error');
        alert('Échec du téléversement : ' + (err && err.message ? err.message : 'inconnu'));
        setTimeout(() => tile.classList.remove('error'), 1500);
      } finally {
        // Réinitialise le champ pour permettre re-upload du même fichier
        form.reset();
      }
    });
  });
</script>

</body>
</html>
