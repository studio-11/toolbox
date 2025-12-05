<?php
/**
 * IFEN Toolbox - Configuration
 * ============================
 * Version mise à jour avec gestion de session et login
 */

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mode debug (mettre à false en production)
define('DEBUG_MODE', true);

// Chemins
define('BASE_PATH', __DIR__ . '/..');
define('BASE_URL', '/ifen_html/toolbox');

// API
define('API_URL', BASE_URL . '/api/api.php');

// Titre du site
define('SITE_TITLE', 'Toolbox IFEN');

// Version
define('APP_VERSION', '2.1.0');

// Ressources externes
define('FONT_URL', 'https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@400;500;600;700&display=swap');
define('FONTAWESOME_URL', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
define('IFEN_BG_URL', 'https://lms.ifen.lu/ifen_images/Backgrounds_transverse.jpg');
define('IFEN_BG_PASTEL_URL', 'https://lms.ifen.lu/ifen_images/Fond_pastel_transverse.jpg');

// CSS IFEN existant (Moodle)
define('IFEN_MOODLE_CSS', '/ifenCSS/custom-moodle-styles.css');

// URL Moodle pour les cours beta
define('MOODLE_COURSE_URL', 'https://learningsphere.ifen.lu/course/view.php?id=');

// ============================================
// CONNEXION BASE DE DONNÉES
// ============================================

// Charger les credentials depuis le fichier sécurisé
$credentials_file = '/export/hosting/men/ifen/htdocs-lms/ifen_credentials/db_credentials_learningsphere.php';

if (file_exists($credentials_file)) {
    $db_credentials = require $credentials_file;
    define('DB_HOST', $db_credentials['host']);
    define('DB_NAME', $db_credentials['db']);
    define('DB_USER', $db_credentials['user']);
    define('DB_PASS', $db_credentials['pass']);
} else {
    // Fallback si le fichier n'existe pas (pour dev local)
    define('DB_HOST', 'mysql.restena.lu');
    define('DB_NAME', 'ifenlmsmain1db');
    define('DB_USER', 'ifen');
    define('DB_PASS', '5Qmeytvw9JTyNMnL');
}

// Fonction de connexion PDO
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die('Erreur de connexion : ' . $e->getMessage());
            } else {
                die('Erreur de connexion à la base de données');
            }
        }
    }
    
    return $pdo;
}

// ============================================
// GESTION DE SESSION / AUTHENTIFICATION
// ============================================

/**
 * Vérifier si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['toolbox_user']) && !empty($_SESSION['toolbox_user']['id']);
}

/**
 * Vérifier si l'utilisateur est admin
 */
function isAdmin() {
    return isLoggedIn() && !empty($_SESSION['toolbox_user']['is_admin']);
}

/**
 * Exiger une connexion (rediriger vers login si non connecté)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Exiger les droits admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        die('Accès non autorisé');
    }
}

/**
 * Déconnecter l'utilisateur
 */
function logout() {
    $_SESSION['toolbox_user'] = null;
    unset($_SESSION['toolbox_user']);
    session_destroy();
}

// ============================================
// UTILISATEUR COURANT
// ============================================

function getCurrentUser() {
    // Si connecté via la session toolbox
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['toolbox_user']['id'],
            'mdl_user_id' => $_SESSION['toolbox_user']['mdl_user_id'] ?? null,
            'name' => $_SESSION['toolbox_user']['name'],
            'email' => $_SESSION['toolbox_user']['email'],
            'username' => $_SESSION['toolbox_user']['username'],
            'is_admin' => $_SESSION['toolbox_user']['is_admin'] ?? false
        ];
    }
    
    // Intégration Moodle (fallback)
    /*
    global $USER;
    if (isset($USER) && !empty($USER->id)) {
        return [
            'id' => $USER->id,
            'name' => trim($USER->firstname . ' ' . $USER->lastname),
            'email' => $USER->email,
            'username' => $USER->username,
            'is_admin' => is_siteadmin()
        ];
    }
    */
    
    // Non connecté - utilisateur anonyme
    return [
        'id' => 0,
        'name' => 'Visiteur',
        'email' => '',
        'username' => '',
        'is_admin' => false
    ];
}

// ============================================
// HELPERS
// ============================================

// Échapper le HTML
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Générer une URL
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

// URL pour les assets (avec versioning)
function asset($path) {
    return url($path) . '?v=' . APP_VERSION;
}

// Générer l'URL d'un cours Moodle
function moodleCourseUrl($courseId) {
    return MOODLE_COURSE_URL . intval($courseId);
}

// ============================================
// CONSTANTES POUR LES OPTIONS
// ============================================

// Types d'idées (mis à jour)
define('IDEA_TYPES', [
    'course_activity' => 'Activité de cours',
    'course_resource' => 'Ressource de cours',
    'platform_feature' => 'Fonctionnalité plateforme',
    'other' => 'Autres'
]);

// Public cible pour les outils
define('TARGET_AUDIENCES', [
    'participant' => 'Participant',
    'manager' => 'Manager IFEN',
    'admin' => 'Admin only'
]);

// Niveaux de difficulté
define('DIFFICULTY_LEVELS', [
    'easy' => 'Facile',
    'medium' => 'Intermédiaire',
    'hard' => 'Avancé'
]);

// Statuts de plateforme
define('PLATFORM_STATUSES', [
    'operational' => 'Opérationnel',
    'maintenance' => 'Mise à jour',
    'upgrading' => 'Mise à jour majeure',
    'partial_outage' => 'Dégradé',
    'major_outage' => 'Indisponible'
]);
?>
