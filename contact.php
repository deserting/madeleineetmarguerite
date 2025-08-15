<?php
// contact.php

/***** CONFIG À ADAPTER *****/
$to      = 'bonjour@madeleineetmarguerite.fr'; // boîte de réception
$subject = 'Nouveau message depuis le site Madeleine & Marguerite';
$from    = 'no-reply@madeleineetmarguerite.fr'; // expéditeur technique (même domaine !) 
/***************************/

header('X-Content-Type-Options: nosniff');

function is_ajax() {
  return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'fetch')
      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}
function out_json($arr){
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}
function clean($s){ return trim(filter_var((string)$s, FILTER_SANITIZE_STRING)); }
function valid_email($e){ return (bool)filter_var($e, FILTER_VALIDATE_EMAIL); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  if (is_ajax()) out_json(['ok'=>false, 'error'=>'Méthode non autorisée']);
  http_response_code(405);
  echo 'Méthode non autorisée';
  exit;
}

// Honeypot anti-spam
if (!empty($_POST['website'] ?? '')) {
  if (is_ajax()) out_json(['ok'=>true]); // on fait semblant d’avoir réussi
  echo 'Merci !';
  exit;
}

// Champs
$name    = clean($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $message === '' || !valid_email($email)) {
  $err = "Merci de renseigner un nom, un e‑mail valide et un message.";
  if (is_ajax()) out_json(['ok'=>false, 'error'=>$err]);
  echo $err;
  exit;
}

// Sécurité : limite la taille du message
if (mb_strlen($message) > 5000) {
  $err = "Message trop long.";
  if (is_ajax()) out_json(['ok'=>false, 'error'=>$err]);
  echo $err;
  exit;
}

// Prépare l’e-mail
$body = "Nom: {$name}\nEmail: {$email}\nIP: ".($_SERVER['REMOTE_ADDR'] ?? '???')."\n\nMessage:\n{$message}\n";

$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'From: Madeleine & Marguerite <'.$from.'>';
$headers[] = 'Reply-To: '.$email;
$headers[] = 'X-Mailer: PHP/'.phpversion();

$ok = @mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $body, implode("\r\n", $headers));

if ($ok) {
  if (is_ajax()) out_json(['ok'=>true]);
  echo "Merci ! Votre message a bien été envoyé.";
} else {
  $err = "L’envoi a échoué. Réessayez plus tard ou écrivez-moi à ".$to;
  if (is_ajax()) out_json(['ok'=>false, 'error'=>$err]);
  echo $err;
}
