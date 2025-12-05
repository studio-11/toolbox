<?php
/**
 * IFEN Toolbox - Page de Connexion
 * =================================
 * Vérification IAM via mdl_user + système blacklist
 */

session_start();

// Si déjà connecté, rediriger vers l'accueil
if (isset($_SESSION['toolbox_user']) && !empty($_SESSION['toolbox_user']['id'])) {
    header('Location: index.php');
    exit;
}

// Configuration
require_once __DIR__ . '/includes/config.php';

$error = '';
$success = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    
    if (empty($username)) {
        $error = 'Veuillez entrer votre identifiant IAM.';
    } else {
        try {
            $pdo = getDbConnection();
            
            // 1. Vérifier si l'utilisateur existe dans mdl_user
            $stmt = $pdo->prepare("
                SELECT id, username, firstname, lastname, email 
                FROM mdl_user 
                WHERE username = ? AND deleted = 0 AND suspended = 0
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $mdlUser = $stmt->fetch();
            
            if (!$mdlUser) {
                $error = 'Identifiant IAM non reconnu. Veuillez vérifier votre saisie.';
            } else {
                // 2. Vérifier/créer dans toolbox_users
                $stmt = $pdo->prepare("SELECT * FROM toolbox_users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $toolboxUser = $stmt->fetch();
                
                if ($toolboxUser && $toolboxUser['is_blacklisted'] == 1) {
                    $error = 'Votre accès à la Toolbox a été restreint. Contactez l\'administrateur.';
                } else {
                    // Créer l'utilisateur s'il n'existe pas
                    if (!$toolboxUser) {
                        $stmt = $pdo->prepare("
                            INSERT INTO toolbox_users (username, mdl_user_id, is_admin, created_at) 
                            VALUES (?, ?, 0, NOW())
                        ");
                        $stmt->execute([$username, $mdlUser['id']]);
                        $toolboxUserId = $pdo->lastInsertId();
                        $isAdmin = 0;
                    } else {
                        $toolboxUserId = $toolboxUser['id'];
                        $isAdmin = $toolboxUser['is_admin'];
                    }
                    
                    // Mettre à jour last_login
                    $pdo->prepare("UPDATE toolbox_users SET last_login = NOW() WHERE id = ?")
                        ->execute([$toolboxUserId]);
                    
                    // Créer la session
                    $_SESSION['toolbox_user'] = [
                        'id' => $toolboxUserId,
                        'mdl_user_id' => $mdlUser['id'],
                        'username' => $mdlUser['username'],
                        'name' => trim($mdlUser['firstname'] . ' ' . $mdlUser['lastname']),
                        'email' => $mdlUser['email'],
                        'is_admin' => (bool)$isAdmin
                    ];
                    
                    // Rediriger vers la page d'accueil
                    header('Location: index.php');
                    exit;
                }
            }
            
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                $error = 'Erreur base de données: ' . $e->getMessage();
            } else {
                $error = 'Une erreur est survenue. Veuillez réessayer.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Toolbox IFEN</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://lms.ifen.lu/ifenCSS/images/favicon.png">
    
    <!-- Fonts -->
    <link rel="stylesheet" href="<?php echo FONT_URL; ?>">
    <link rel="stylesheet" href="<?php echo FONTAWESOME_URL; ?>">
    
    <style>
        :root {
            --primary: #20164D;
            --primary-light: #2d1f6b;
            --secondary: #00b2bb;
            --accent: #ffc107;
            --white: #ffffff;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-500: #64748b;
            --gray-700: #334155;
            --danger: #ef4444;
            --success: #10b981;
            --border-radius: 15px;
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Barlow Semi Condensed', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            background-size: 400% 400%;
            animation: gradientBg 15s ease infinite;
            padding: 20px;
        }
        
        @keyframes gradientBg {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        
        .login-header {
            background: var(--primary);
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-logo {
            width: 80px;
            height: auto;
            margin-bottom: 15px;
        }
        
        .login-title {
            color: var(--white);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .login-title i {
            color: var(--accent);
            margin-right: 10px;
        }
        
        .login-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.95rem;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 1.1rem;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            font-family: inherit;
            font-size: 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            background: var(--gray-100);
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(32, 22, 77, 0.1);
        }
        
        .form-input::placeholder {
            color: var(--gray-500);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px 20px;
            font-family: inherit;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            background: linear-gradient(135deg, var(--accent), #ffca28);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 193, 7, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .login-info {
            margin-top: 25px;
            padding: 15px;
            background: var(--gray-100);
            border-radius: 10px;
            text-align: center;
        }
        
        .login-info p {
            color: var(--gray-500);
            font-size: 0.9rem;
            margin: 0;
        }
        
        .login-info i {
            color: var(--secondary);
            margin-right: 5px;
        }
        
        .login-footer {
            text-align: center;
            padding: 20px 30px;
            background: var(--gray-100);
            border-top: 1px solid var(--gray-200);
        }
        
        .login-footer p {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin: 0;
        }
        
        /* Animation de chargement */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn-login.loading i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-header {
                padding: 30px 20px;
            }
            
            .login-body {
                padding: 30px 20px;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="https://lms.ifen.lu/ifen_images/IFEN_logo.png" alt="IFEN" class="login-logo" onerror="this.style.display='none'">
                <h1 class="login-title"><i class="fas fa-toolbox"></i>Toolbox</h1>
                <p class="login-subtitle">Connectez-vous avec votre compte IAM</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="login-form">
                    <div class="form-group">
                        <label for="username">Identifiant IAM</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-input" 
                                placeholder="Entrez votre identifiant IAM"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                autocomplete="username"
                                autofocus
                                required
                            >
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login" id="btn-submit">
                        <i class="fas fa-sign-in-alt"></i>
                        Se connecter
                    </button>
                </form>
                
                <div class="login-info">
                    <p><i class="fas fa-info-circle"></i>Utilisez votre identifiant LearningSphere / IAM</p>
                </div>
            </div>
            
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> IFEN - Institut de Formation de l'Éducation Nationale</p>
            </div>
        </div>
    </div>
    
    <script>
        // Animation au submit
        document.getElementById('login-form').addEventListener('submit', function() {
            const btn = document.getElementById('btn-submit');
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner"></i> Connexion...';
        });
    </script>
</body>
</html>
