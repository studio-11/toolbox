<?php
/**
 * IFEN Toolbox Admin - Authentification
 * 
 * Gestion de l'authentification des administrateurs
 * 
 * @package    local_toolbox
 * @copyright  2024 IFEN
 * @author     Boris Merens
 */

// Démarrer la session si pas encore démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger la configuration
define('TOOLBOX_INTERNAL', true);
require_once(__DIR__ . '/../../config.php');

/**
 * Vérifier si l'utilisateur est connecté
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Vérifier si l'utilisateur est admin
 * Redirige vers login si non connecté
 */
function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Connexion administrateur
 * 
 * @param string $username
 * @param string $password
 * @return bool
 */
function adminLogin($username, $password) {
    try {
        $pdo = getDBConnection();
        
        // Pour le moment, vérification simple
        // TODO: Intégrer avec l'authentification Moodle
        
        // Version simple pour démarrer
        $admin_users = [
            'admin' => password_hash('admin123', PASSWORD_DEFAULT),
            'boris' => password_hash('boris2024', PASSWORD_DEFAULT)
        ];
        
        if (isset($admin_users[$username]) && password_verify($password, $admin_users[$username])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_login_time'] = time();
            
            logInfo('Admin login réussi', ['username' => $username]);
            return true;
        }
        
        logInfo('Tentative de login échouée', ['username' => $username]);
        return false;
        
    } catch (Exception $e) {
        logError('Erreur login admin', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Déconnexion
 */
function adminLogout() {
    if (isset($_SESSION['admin_username'])) {
        logInfo('Admin logout', ['username' => $_SESSION['admin_username']]);
    }
    
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Obtenir le nom d'utilisateur connecté
 * 
 * @return string
 */
function getAdminUsername() {
    return $_SESSION['admin_username'] ?? 'Admin';
}

/**
 * Vérifier le token CSRF
 * 
 * @param string $token
 * @return bool
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Générer un token CSRF
 * 
 * @return string
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Logger une action admin
 * 
 * @param string $action
 * @param array $details
 */
function logAdminAction($action, $details = []) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'username' => getAdminUsername(),
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    logInfo('Admin action', $log);
    
    // TODO: Enregistrer dans une table d'historique
}