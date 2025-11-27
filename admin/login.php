<?php
/**
 * IFEN Toolbox Admin - Page de connexion
 */
require_once __DIR__ . '/includes/config.php';

// Si déjà connecté, rediriger
if (isAdminLoggedIn()) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else if (loginAdmin($email, $password)) {
        header('Location: ' . url('index.php'));
        exit;
    } else {
        $error = 'Email ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= SITE_TITLE ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="<?= FONT_URL ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= FONTAWESOME_URL ?>">
    <link rel="stylesheet" href="css/admin.css?v=<?= APP_VERSION ?>">
</head>
<body class="login-page">
    
    <div class="login-card">
        <div class="login-logo">
            <i class="fas fa-toolbox"></i>
            <h1>Toolbox Admin</h1>
            <p>Connectez-vous pour accéder à l'administration</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error mb-2">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="email">Adresse email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="votre@email.lu"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    required
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Mot de passe</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Votre mot de passe"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                <i class="fas fa-sign-in-alt"></i>
                Se connecter
            </button>
        </form>
        
        <p class="text-center text-muted mt-2" style="font-size: 0.875rem;">
            <a href="<?= frontendUrl() ?>">
                <i class="fas fa-arrow-left"></i>
                Retour au site
            </a>
        </p>
    </div>
    
</body>
</html>
