<?php
function cfg(): array { static $c; if(!$c){ $c = require dirname(__DIR__).'/data/config.php'; } return $c; }
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function start_sess(){ if(session_status()===PHP_SESSION_NONE){ session_name('mm_admin'); session_start(); } }
function is_admin(){ start_sess(); return !empty($_SESSION['is_admin']); }
function csrf_token(){ start_sess(); if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_check(){ if(($_POST['csrf']??'') !== ($_SESSION['csrf']??'')){ http_response_code(403); exit('CSRF invalide'); } }
function base_href(){ $b = rtrim(dirname($_SERVER['SCRIPT_NAME']),'/'); return $b===''?'.':$b; }
function read_json($f,$fb=null){ return is_file($f) ? (json_decode(file_get_contents($f),true) ?: $fb) : $fb; }
function write_json($f,$arr){ $dir=dirname($f); if(!is_dir($dir)) mkdir($dir,0775,true); $tmp=tempnam($dir,'tmp_'); file_put_contents($tmp,json_encode($arr,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)); return rename($tmp,$f); }
function ensure_dirs(){ $c=cfg(); foreach ([$c['dir_private'],$c['dir_public']] as $d){ if(!is_dir($d)) mkdir($d,0775,true); } }
function deltree($dir){ if(!is_dir($dir)) return; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $f){ $f->isDir()? rmdir($f->getRealPath()) : unlink($f->getRealPath()); } rmdir($dir); }

// Conversion → WebP (Imagick si dispo, sinon GD)
function to_webp_variant($src,$dest,$maxLong){
  if(class_exists('Imagick')){
    $im=new Imagick($src); $im->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    $w=$im->getImageWidth(); $h=$im->getImageHeight(); $scale = ($w>$h? $maxLong/$w : $maxLong/$h);
    if($scale<1) $im->resizeImage((int)($w*$scale),(int)($h*$scale), Imagick::FILTER_LANCZOS, 1);
    $im->setImageFormat('webp'); $im->setImageCompressionQuality(82); $im->stripImage();
    return $im->writeImage($dest);
  }
  $info=@getimagesize($src); if(!$info) return false; [$w,$h]=$info; $type=$info[2];
  switch($type){case IMAGETYPE_JPEG:$srcim=@imagecreatefromjpeg($src);break;case IMAGETYPE_PNG:$srcim=@imagecreatefrompng($src);break;case IMAGETYPE_WEBP:$srcim=@imagecreatefromwebp($src);break;default:return false;}
  if(!$srcim) return false;
  $scale=($w>$h? $maxLong/$w : $maxLong/$h); if($scale>1){$nw=$w;$nh=$h;} else {$nw=(int)($w*$scale);$nh=(int)($h*$scale);} $dst=imagecreatetruecolor($nw,$nh);
  imagecopyresampled($dst,$srcim,0,0,0,0,$nw,$nh,$w,$h); $ok=imagewebp($dst,$dest,82); imagedestroy($srcim); imagedestroy($dst); return $ok;
}

// Boot
ensure_dirs(); $c=cfg(); $dirP=$c['dir_private']; $dirPub=$c['dir_public'];
start_sess(); $action=$_POST['action']??$_GET['action']??''; $err=$_SESSION['err']??''; $ok=$_SESSION['ok']??''; unset($_SESSION['err'],$_SESSION['ok']);

// Auth
if($action==='login'){
  csrf_check(); $u=trim($_POST['user']??''); $p=(string)($_POST['pass']??'');
  if($u===$c['admin_user'] && $p===$c['admin_pass_plain']){ $_SESSION['is_admin']=true; header('Location: '.base_href().'/index.php'); exit; }
  $_SESSION['err']='Identifiants invalides'; header('Location: '.base_href().'/index.php'); exit;
}
if($action==='logout'){ session_unset(); session_destroy(); header('Location: '.base_href().'/index.php'); exit; }

// CREATE
if($action==='create' && is_admin()){
  csrf_check();
  $title=trim($_POST['title']??'');
  $slug=trim($_POST['slug']??'');
  if($slug===''){ $slug=strtolower(preg_replace('~[^a-z0-9\\-]+~','-', iconv('UTF-8','ASCII//TRANSLIT',$title?:'galerie'))); $slug=trim($slug,'-'); }
  $client_user=trim($_POST['client_user']??'');
  $client_pass=trim($_POST['client_pass']??'');
  $intro_text=trim($_POST['intro_text']??'');
  $wtu=trim($_POST['wetransfer']??'');
  $published=!empty($_POST['published']);

  $gdir="$dirP/$slug"; if(is_dir($gdir)){ $_SESSION['err']='Slug déjà existant'; header('Location: '.base_href().'/index.php'); exit; }
  mkdir($gdir,0775,true); mkdir("$gdir/originals",0775,true);
  foreach(['thumb','grid','hd'] as $d) mkdir("$dirPub/$slug/$d",0775,true);

  $meta=[
    'slug'=>$slug,
    'title'=>$title,
    'client_user'=>$client_user,
    'client_pass_hash'=>$client_pass? password_hash($client_pass,PASSWORD_BCRYPT):null,
    'client_pass_plain'=>$client_pass ?: null, // affichage clair en édition
    'intro_text'=>$intro_text,
    'wetransfer_url'=>$wtu,
    'is_published'=>$published,
    'created_at'=>date('c')
  ];
  write_json("$gdir/meta.json",$meta);
  write_json("$gdir/photos.json",[]);
  $_SESSION['ok']='Galerie créée';
  header('Location: '.base_href()."/index.php?edit=".rawurlencode($slug)); exit;
}

// SAVE META
if($action==='save-meta' && is_admin()){
  csrf_check(); $slug=(string)$_POST['slug']; $gdir="$dirP/$slug"; $meta=read_json("$gdir/meta.json",[]);
  if(!$meta){ $_SESSION['err']='Galerie introuvable'; header('Location: '.base_href().'/index.php'); exit; }

  $meta['title']=trim($_POST['title']??$meta['title']);
  $meta['client_user']=trim($_POST['client_user']??($meta['client_user']??''));
  $pwd=trim($_POST['client_pass']??'');
  if($pwd!==''){ $meta['client_pass_hash']=password_hash($pwd,PASSWORD_BCRYPT); $meta['client_pass_plain']=$pwd; }
  if(!empty($_POST['clear_pass'])) { $meta['client_pass_hash']=null; $meta['client_pass_plain']=null; }

  $meta['intro_text']=trim($_POST['intro_text']??($meta['intro_text']??''));
  $meta['wetransfer_url']=trim($_POST['wetransfer']??'');
  $meta['is_published']=!empty($_POST['published']);

  write_json("$gdir/meta.json",$meta);
  $_SESSION['ok']='Méta enregistrées';
  header('Location: '.base_href()."/index.php?edit=".rawurlencode($slug)); exit;
}

// UPLOAD
if($action==='upload' && is_admin()){
  csrf_check(); $slug=(string)$_POST['slug']; $gdir="$dirP/$slug"; $meta=read_json("$gdir/meta.json",[]);
  if(!$meta){ $_SESSION['err']='Galerie introuvable'; header('Location: '.base_href().'/index.php'); exit; }

  $allowed=$c['allowed_mime']; $maxN=(int)$c['max_files_per_upload']; $maxMB=(int)$c['max_total_mb_per_upload'];
  $names=$_FILES['photos']['name']??[]; $tmps=$_FILES['photos']['tmp_name']??[]; $errs=$_FILES['photos']['error']??[]; $sizes=$_FILES['photos']['size']??[];
  $count = is_array($names)? count($names) : 0;
  if($count===0){ $_SESSION['err']='Aucun fichier'; header('Location: '.base_href()."/index.php?edit=".rawurlencode($slug)); exit; }
  if($count>$maxN){ $_SESSION['err']="Trop de fichiers d'un coup (max $maxN)"; header('Location: '.base_href()."/index.php?edit=".rawurlencode($slug)); exit; }
  $totalMB = array_sum($sizes) / (1024*1024);
  if($totalMB > $maxMB){ $_SESSION['err']="Trop volumineux (max {$maxMB}MB)"; header('Location: '.base_href()."/index.php?edit=".rawurlencode($slug)); exit; }

  $photos = read_json("$gdir/photos.json", []);
  $added=0; $skipped=0; $start=microtime(true);
  for($i=0; $i<$count; $i++){
    $tmp=$tmps[$i]; $errc=$errs[$i]; if($errc!==UPLOAD_ERR_OK || !$tmp || !is_uploaded_file($tmp)){ $skipped++; continue; }
    $mime=@mime_content_type($tmp); if(!in_array($mime,$allowed,true)){ $skipped++; continue; }
    $uuid=bin2hex(random_bytes(8)); $ext=image_type_to_extension(@exif_imagetype($tmp)) ?: '.jpg';
    if(!in_array(strtolower($ext), ['.jpg','.jpeg','.png','.webp'])) $ext='.jpg';
    $orig="$gdir/originals/$uuid$ext"; if(!@move_uploaded_file($tmp,$orig)){ $skipped++; continue; }
    $sz=@getimagesize($orig);
    $photos[]=[ 'id'=>$uuid,'file'=>basename($orig),'w'=>$sz[0]??null,'h'=>$sz[1]??null,'thumb'=>null,'grid'=>null,'hd'=>null,'visible'=>true,'caption'=>'','variants_ready'=>false ];
    $added++;
  }
  write_json("$gdir/photos.json",$photos);
  $dur = round((microtime(true)-$start),2);
  $_SESSION['ok']="Upload terminé : $added ajoutée(s), $skipped ignorée(s) — ${dur}s";
  header('Location: '.base_href()."/index.php?edit=".rawurlencode($slug)); exit;
}

// GENERATE
if($action==='generate' && is_admin()){
  csrf_check(); $slug=(string)$_POST['slug']; $gdir="$dirP/$slug"; $meta=read_json("$gdir/meta.json",[]); $photos=read_json("$gdir/photos.json",[]);
  if(!$meta){ $_SESSION['err']='Galerie introuvable'; header('Location: '.base_href().'/index.php'); exit; }
  foreach(['thumb','grid','hd'] as $d) if(!is_dir($dirPub.'/'.$slug.'/'.$d)) mkdir($dirPub.'/'.$slug.'/'.$d,0775,true);
  $sizes=$c['sizes']; $batch=(int)$c['batch']; if($batch<1) $batch=10;
  $done=0; $already=0; $failed=0; $start=microtime(true);
  foreach($photos as &$p){ if($done>>= $batch) break; if(!empty($p['variants_ready'])){ $already++; continue; }
    $id=$p['id']; $origPath="$gdir/originals/".$p['file']; if(!is_file($origPath)){ $failed++; continue; }
    $thumbRel="thumb/$id.webp"; $gridRel="grid/$id.webp"; $hdRel="hd/$id.webp";
    $ok1=to_webp_variant($origPath, $dirPub.'/'.$slug.'/'.$thumbRel, $sizes['thumb']);
    $ok2=to_webp_variant($origPath, $dirPub.'/'.$slug.'/'.$gridRel,  $sizes['grid']);
    $ok3=to_webp_variant($origPath, $dirPub.'/'.$slug.'/'.$hdRel,    $sizes['hd']);
    if($ok1 && $ok2 && $ok3){ $p['thumb']=$thumbRel; $p['grid']=$gridRel; $p['hd']=$hdRel; $p['variants_ready']=true; $done++; }
    else { $failed++; }
  }
  unset($p);
  write_json("$gdir/photos.json",$photos);
  $left = count(array_filter($photos, fn($x)=>empty($x['variants_ready'])));
  $dur = round((microtime(true)-$start),2);
  $_SESSION['ok']="Génération : $done traité(s), $already prêt(s), $failed échec(s), reste $left — ${dur}s";
  header('Location: '.base_href()."/index.php?edit=".rawurlencode($slug)); exit;
}

// TOGGLE
if($action==='toggle' && is_admin()){
  csrf_check(); $slug=(string)$_POST['slug']; $pid=(string)$_POST['pid']; $gdir="$dirP/$slug"; $photos=read_json("$gdir/photos.json",[]);
  foreach($photos as &$p){ if($p['id']===$pid){ $p['visible']=!($p['visible']??true); break; } }
  unset($p); write_json("$gdir/photos.json",$photos); header('Location: '.base_href()."/index.php?edit=".rawurlencode($slug)); exit;
}

// MOVE
if($action==='move' && is_admin()){
  csrf_check(); $slug=(string)$_POST['slug']; $pid=(string)$_POST['pid']; $dir=(string)$_POST['dir']; $gdir="$dirP/$slug"; $photos=read_json("$gdir/photos.json",[]);
  $i=null; foreach($photos as $k=>$p){ if($p['id']===$pid){ $i=$k; break; } }
  if($i!==null){ $j=$dir==='up'? $i-1 : $i+1; if($j>=0 && $j<count($photos)){ $tmp=$photos[$i]; $photos[$i]=$photos[$j]; $photos[$j]=$tmp; } }
  write_json("$gdir/photos.json",$photos); header('Location: '.base_href()."/index.php?edit=".rawurlencode($slug)); exit;
}

// DELETE PHOTO
if($action==='del-photo' && is_admin()){
  csrf_check(); $slug=(string)$_POST['slug']; $pid=(string)$_POST['pid']; $gdir="$dirP/$slug"; $photos=read_json("$gdir/photos.json",[]);
  $photos=array_values(array_filter($photos, fn($p)=>$p['id']!==$pid)); write_json("$gdir/photos.json",$photos);
  foreach(['thumb','grid','hd'] as $sz){ @unlink($dirPub.'/'.$slug.'/'.$sz.'/'.$pid.'.webp'); }
  foreach(glob($gdir.'/originals/'.$pid.'.*') as $f) @unlink($f);
  header('Location: '.base_href()."/index.php?edit=".rawurlencode($slug)); exit;
}

// DELETE GALLERY
if($action==='del-gallery' && is_admin()){
  csrf_check(); $slug=(string)$_POST['slug']; $confirm=trim($_POST['confirm']??''); if($confirm!==$slug){ $_SESSION['err']='Confirmation incorrecte'; header('Location: '.base_href()."/index.php?edit=".rawurlencode($slug)); exit; }
  deltree($dirP.'/'.$slug); deltree($dirPub.'/'.$slug);
  $_SESSION['ok']='Galerie supprimée'; header('Location: '.base_href().'/index.php'); exit;
}
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Sesame · Admin</title>
  <link rel="stylesheet" href="../css/variables.css">
  <link rel="stylesheet" href="sesame.css">
  <link rel="stylesheet" href="sesame-easter.css">
  <script src="sesame-easter.js" defer></script>

</head>
<body class="se">
<div class="wrap">

<?php if(!is_admin()): ?>
  <main class="py-24" style="min-height:100vh">
    <div class="container">
      <h1 class="section-heading" style="margin-bottom:1rem;">Accès photographe</h1>
      <p class="intro">Identifiez‑vous pour créer et gérer vos galeries privées.</p>

      <?php if($ok): ?>
        <p class="notice is-good"><strong>OK.</strong> <span><?=e($ok)?></span></p>
      <?php endif; ?>
      <?php if($err): ?>
        <p class="notice is-error"><strong>Oups.</strong> <span><?=e($err)?></span></p>
      <?php endif; ?>

      <form method="post" action="<?=e(base_href())?>/index.php" class="login-card">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">

        <p class="field">
          <label>Identifiant</label>
          <input name="user" required type="text" autocomplete="username" placeholder="ex. margouille">
        </p>

        <p class="field">
          <label>Mot de passe</label>
          <input name="pass" type="password" required autocomplete="current-password" placeholder="Votre mot de passe">
        </p>

        <p><button class="btn-primary" type="submit">Se connecter</button></p>
        <p class="logout" style="margin-top:.5rem"><a href="../index.html" target="_blank">← Retour au site</a></p>
      </form>
    </div>
  </main>
<?php else: $edit = $_GET['edit'] ?? ''; ?>

  <?php if(!$edit): ?>
    <div class="topbar">
  <h1>Galeries</h1>
  <div>
    <a href="themes.php">🎨 Apparence du site</a>
    ·
    <a href="../galerie-privee/" target="_blank">→ Page de login publique</a>
    ·
    <form method="post" action="<?=e(base_href())?>/index.php" style="display:inline">
      <input type="hidden" name="action" value="logout"><button class="btn">Déconnexion</button>
    </form>
  </div>
</div>

    <?php if($ok): ?><p class="notice is-good"><strong>OK.</strong> <span><?=e($ok)?></span></p><?php endif; ?>
    <?php if($err): ?><p class="notice is-bad"><strong>Oups.</strong> <span><?=e($err)?></span></p><?php endif; ?>

    <details open class="card">
      <summary><strong>+ Nouvelle galerie</strong></summary>
      <form method="post" action="<?=e(base_href())?>/index.php" style="margin-top:12px">
        <input type="hidden" name="action" value="create"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <p><label>Titre · <input name="title" required></label></p>
        <p><label>Slug (auto si vide) · <input name="slug" placeholder="ex: martine-martine"></label></p>
        <p>
          <label>Texte d’introduction<br>
            <textarea name="intro_text" rows="3" style="min-width:520px" placeholder="Un petit mot pour vos clients… (optionnel)"></textarea>
          </label>
        </p>
        <p>
          <label>Identifiant client · <input name="client_user" placeholder="ex: martine"></label>
          <label>Mot de passe client · <input name="client_pass" placeholder="ex: vacances2025"></label>
        </p>
        <p>
          <label>WeTransfer · <input name="wetransfer" style="min-width:420px" placeholder="https://…"></label>
          <label><input type="checkbox" name="published"> Publiée</label>
        </p>
        <p><button class="btn">Créer</button></p>
      </form>
    </details>

    <?php $rows=[]; foreach(glob($dirP.'/*/meta.json') as $mf){ $m=read_json($mf,[]); if($m) $rows[]=$m; } usort($rows, fn($a,$b)=>strcmp($b['created_at']??'',$a['created_at']??'')); ?>
    <table>
      <tr><th>Titre</th><th>Identifiant client</th><th>Slug</th><th>Statut</th><th></th></tr>
      <?php foreach($rows as $g): $slug=$g['slug']; ?>
        <tr>
          <td><?=e($g['title']??'')?></td>
          <td><?=e($g['client_user']??'')?></td>
          <td><code><?=e($slug)?></code></td>
          <td><?=!empty($g['is_published'])?'Publiée':'Brouillon'?></td>
          <td>
            <a href="<?=e(base_href())?>/index.php?edit=<?=rawurlencode($slug)?>">Gérer</a> ·
            <a href="<?=e('../galerie-privee/') ?>" target="_blank">Tester côté client</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>

  <?php else:
    $slug=$edit; $gdir=$dirP.'/'.$slug; $meta=read_json($gdir.'/meta.json',[]); $photos=read_json($gdir.'/photos.json',[]); $pending=count(array_filter($photos, fn($p)=>empty($p['variants_ready'])));
  ?>
    <p><a href="<?=e(base_href())?>/index.php">← Retour</a> · <a href="<?=e('../galerie-privee/') ?>" target="_blank" class="muted">Tester la page publique</a></p>
    <h1>Éditer · <?=e($meta['title']??$slug)?> <small style="font-weight:400">(<?=e($slug)?>)</small></h1>
    <?php if($ok): ?><p class="notice is-good"><strong>OK.</strong> <span><?=e($ok)?></span></p><?php endif; ?>
    <?php if($err): ?><p class="notice is-bad"><strong>Oups.</strong> <span><?=e($err)?></span></p><?php endif; ?>

    <form method="post" action="<?=e(base_href())?>/index.php" class="card">
      <input type="hidden" name="action" value="save-meta"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <input type="hidden" name="slug" value="<?=e($slug)?>">
      <p><label>Titre · <input name="title" value="<?=e($meta['title']??'')?>"></label></p>
      <p>
        <label>Texte d’introduction<br>
          <textarea name="intro_text" rows="3" style="min-width:520px" placeholder="Un petit mot pour vos clients… (optionnel)"><?= e($meta['intro_text'] ?? '') ?></textarea>
        </label>
      </p>
      <p>
        <label>Identifiant client · <input name="client_user" value="<?=e($meta['client_user']??'')?>"></label>
        <label>Nouveau mot de passe · <input name="client_pass" placeholder="(laisser vide pour conserver)"></label>
        <label><input type="checkbox" name="clear_pass" value="1"> Supprimer le mot de passe</label>
      </p>
      <?php if(array_key_exists('client_pass_plain',$meta) && $meta['client_pass_plain']!==null && $meta['client_pass_plain']!==''): ?>
        <p class="muted">
          Mot de passe actuel (mémo)&nbsp;:
          <input type="text" readonly value="<?= e($meta['client_pass_plain']) ?>" style="min-width:260px" onclick="this.select()">
          <button type="button" class="btn" onclick="navigator.clipboard.writeText('<?= e($meta['client_pass_plain']) ?>')">Copier</button>
        </p>
      <?php endif; ?>
      <p>
        <label>WeTransfer · <input name="wetransfer" style="min-width:420px" value="<?=e($meta['wetransfer_url']??'')?>"></label>
        <label><input type="checkbox" name="published" <?=!empty($meta['is_published'])?'checked':''?>> Publiée</label>
      </p>
      <p><button class="btn">Enregistrer</button></p>
    </form>

    <h2>1) Téléverser des photos (originaux)</h2>
    <form method="post" action="<?=e(base_href())?>/index.php" enctype="multipart/form-data" class="card">
      <input type="hidden" name="action" value="upload"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="slug" value="<?=e($slug)?>">
      <input type="file" name="photos[]" multiple accept="image/*" required>
      <p class="muted">Astuce : uploade par paquets (ex. 50 par 50) pour éviter les erreurs.</p>
      <button class="btn">Téléverser</button>
    </form>

    <h2>2) Générer les variantes (par lot)</h2>
    <p>En attente : <strong><?= (int)$pending ?></strong> — clique plusieurs fois si besoin (<?= (int)$c['batch'] ?> max par clic).</p>
    <form method="post" action="<?=e(base_href())?>/index.php" class="card">
      <input type="hidden" name="action" value="generate"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="slug" value="<?=e($slug)?>">
      <button class="btn">Générer les variantes maintenant</button>
    </form>

    <h2>Photos</h2>
    <div class="thumbs">
      <?php foreach($photos as $p): ?>
        <figure class="thumb">
          <?php if(!empty($p['variants_ready'])): ?>
            <img src="<?=e('../public/galeries/'.rawurlencode($slug).'/thumb/'.basename($p['thumb']??''))?>" alt="">
          <?php else: ?>
            <div class="ph">En attente</div>
          <?php endif; ?>

          <div class="thumb-actions">
            <form method="post" action="<?=e(base_href())?>/index.php">
              <input type="hidden" name="action" value="move"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="slug" value="<?=e($slug)?>"><input type="hidden" name="pid" value="<?=e($p['id'])?>">
              <button class="btn" name="dir" value="up">↑</button>
              <button class="btn" name="dir" value="down">↓</button>
            </form>

            <form method="post" action="<?=e(base_href())?>/index.php">
              <input type="hidden" name="action" value="toggle"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="slug" value="<?=e($slug)?>"><input type="hidden" name="pid" value="<?=e($p['id'])?>">
              <button class="btn"><?=!empty($p['visible'])?'Masquer':'Afficher'?></button>
            </form>

            <form method="post" action="<?=e(base_href())?>/index.php" onsubmit="return confirm('Supprimer cette photo ?')">
              <input type="hidden" name="action" value="del-photo"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="slug" value="<?=e($slug)?>"><input type="hidden" name="pid" value="<?=e($p['id'])?>">
              <button class="btn btn-danger">Supprimer</button>
            </form>
          </div>
        </figure>
      <?php endforeach; ?>
    </div>

    <h2>Supprimer la galerie</h2>
    <form method="post" action="<?=e(base_href())?>/index.php" class="card" onsubmit="return confirm('Supprimer DÉFINITIVEMENT la galerie ?')">
      <input type="hidden" name="action" value="del-gallery"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="slug" value="<?=e($slug)?>">
      <p>Tape <code><?=e($slug)?></code> pour confirmer : <input name="confirm" required></p>
      <button class="btn btn-danger">Supprimer définitivement</button>
    </form>
  <?php endif; ?>
<?php endif; ?>

</div>
</body>
</html>
