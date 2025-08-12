<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: index.html"); exit;
}

// 0) Honeypot (champ invisible côté HTML : name="website")
if (!empty($_POST["website"])) {
  // bot détecté → fait comme si tout allait bien
  header("Location: merci.html"); exit;
}

// 1) Récup & nettoyage
$name    = trim(strip_tags($_POST["name"] ?? ""));
$email   = filter_var(trim($_POST["email"] ?? ""), FILTER_SANITIZE_EMAIL);
$message = trim(strip_tags($_POST["message"] ?? ""));

// 2) Validation
if ($name === "" || $message === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header("Location: index.html?status=error"); exit;
}

// 3) Préparation de l’e-mail
$recipient = "bonjour@madeleine-marguerite.fr"; // ← ta vraie adresse
$subject   = "Nouveau message de $name via le site Madeleine & Marguerite";

$body  = "Nom: $name\n";
$body .= "Email: $email\n\n";
$body .= "Message:\n$message\n";

// From = ton domaine (meilleure délivrabilité) ; l’expéditeur réel en Reply-To
$headers  = "From: Madeleine & Marguerite <no-reply@madeleine-marguerite.fr>\r\n";
$headers .= "Reply-To: $name <$email>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// 4) Envoi (décommente en prod si mail() dispo ; sinon configure SMTP)
$ok = true; // $ok = mail($recipient, $subject, $body, $headers);

if ($ok) {
  header("Location: merci.html"); exit;
} else {
  header("Location: index.html?status=send_error"); exit;
}
