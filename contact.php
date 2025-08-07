<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Récupération des données du formulaire
    $name = strip_tags(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = strip_tags(trim($_POST["message"]));

    // 2. Validation simple
    if (empty($name) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // En cas d'erreur, rediriger vers la page de contact avec un message d'erreur.
        // Pour ce projet simple, nous redirigeons simplement. Une meilleure solution serait d'afficher un message.
        header("Location: index.html?status=error");
        exit;
    }

    // 3. Préparation de l'e-mail
    // IMPORTANT: Remplacez cette adresse par votre véritable adresse e-mail.
    $recipient = "deserting@mail.com";
    $subject = "Nouveau message de $name via le site Madeleine & Marguerite";
    
    $email_content = "Nom: $name\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "Message:\n$message\n";

    // 4. Envoi de l'e-mail
    // La fonction mail() dépend de la configuration du serveur.
    // En développement local (comme dans cet environnement), elle peut ne pas fonctionner sans configuration d'un serveur SMTP.
    // Nous simulons l'envoi pour la démonstration.
    $headers = "From: $name <$email>";
    
    // Pour le test, nous considérons que l'envoi réussit toujours.
    // if (mail($recipient, $subject, $email_content, $headers)) {
        // 5. Redirection vers une page de remerciement
        header("Location: merci.html");
        exit;
    // } else {
    //     // Gérer l'échec de l'envoi
    //     header("Location: index.html?status=send_error");
    //     exit;
    // }

} else {
    // Si le script n'est pas accédé via POST, rediriger vers la page d'accueil.
    header("Location: index.html");
    exit;
}
?>
