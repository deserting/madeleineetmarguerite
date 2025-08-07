<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Galerie Privée - Madeleine & Marguerite</title>
    <link rel="stylesheet" href="../css/variables.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            display: grid;
            place-items: center;
            min-height: 100vh;
            text-align: center;
            padding: 2rem;
            background: var(--color-bg);
        }
        .login-container {
            background: var(--color-surface);
            padding: var(--space-xl);
            border-radius: var(--radius-md);
            max-width: 400px;
            width: 100%;
        }
        .login-container h1 {
            font-size: 2.5rem;
            font-family: var(--font-display);
            color: var(--color-accent);
            margin-bottom: var(--space-md);
        }
        .login-container p {
            margin-bottom: var(--space-lg);
            color: var(--color-muted);
        }
        .login-form .field {
            margin-bottom: var(--space-md);
            text-align: left;
        }
        .login-form label {
            display: block;
            margin-bottom: var(--space-xs);
            color: var(--color-text);
        }
        .login-form input {
            width: 100%;
            padding: .75rem 1rem;
            border: 1px solid var(--color-accent-alt);
            border-radius: var(--radius-sm);
            background: var(--color-bg);
            color: var(--color-text);
        }
        .btn-login {
            display: inline-block;
            width: 100%;
            padding: .75rem 1.5rem;
            background: var(--color-accent);
            color: var(--color-text-dark);
            text-decoration: none;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background .3s;
        }
        .btn-login:hover {
            background: #e8b584;
        }
        .home-link {
            display: block;
            margin-top: var(--space-lg);
            color: var(--color-muted);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Galerie Privée</h1>
        <p>Veuillez entrer le mot de passe pour accéder à vos photos.</p>
        
        <form action="" method="post" class="login-form">
            <div class="field">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Entrer</button>
        </form>

        <a href="../index.html" class="home-link">Retour à l'accueil</a>
    </div>
</body>
</html>
