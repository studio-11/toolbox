<?php
/**
 * IFEN Toolbox Admin - Page de connexion
 */

session_start();

define('TOOLBOX_INTERNAL', true);
require_once(__DIR__ . '/includes/auth.php');

// Si déjà connecté, rediriger vers dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (adminLogin($username, $password)) {
        $redirect = $_GET['redirect'] ?? 'index.php';
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Identifiants incorrects';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - IFEN Toolbox Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-rocket"></i>
                </div>
                <h1>IFEN Toolbox</h1>
                <p>Administration</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Entrez votre nom d'utilisateur"
                        required 
                        autofocus
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Entrez votre mot de passe"
                        required
                    >
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>
            
            <div class="text-center mt-20 text-muted" style="font-size: 0.85rem;">
                <p>Accès réservé aux administrateurs</p>
                <p style="margin-top: 10px;">
                    <i class="fas fa-shield-alt"></i> 
                    Connexion sécurisée
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Animation d'entrée
        document.querySelector('.login-card').style.animation = 'fadeIn 0.5s ease';
    </script>
</body>
</html>