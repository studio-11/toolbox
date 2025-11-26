<?php
/**
 * IFEN Toolbox - Configuration
 * ============================
 */

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
define('APP_VERSION', '2.0.0');

// Ressources externes
define('FONT_URL', 'https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@400;500;600;700&display=swap');
define('FONTAWESOME_URL', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
define('IFEN_BG_URL', 'https://lms.ifen.lu/ifen_images/Backgrounds_transverse.jpg');
define('IFEN_BG_PASTEL_URL', 'https://lms.ifen.lu/ifen_images/Fond_pastel_transverse.jpg');

// CSS IFEN existant (Moodle)
define('IFEN_MOODLE_CSS', '/ifenCSS/custom-moodle-styles.css');

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
// UTILISATEUR COURANT
// ============================================

function getCurrentUser() {
    // Intégration Moodle
    // Décommenter ces lignes quand intégré à Moodle :
    /*
    global $USER;
    if (isset($USER) && !empty($USER->id)) {
        return [
            'id' => $USER->id,
            'name' => trim($USER->firstname . ' ' . $USER->lastname),
            'email' => $USER->email
        ];
    }
    */
    
    // Pour les tests (utilisateur par défaut)
    return [
        'id' => 1,
        'name' => 'Utilisateur Test',
        'email' => 'test@ifen.lu'
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

// Vérifier si l'utilisateur est admin
function isAdmin() {
    // À adapter selon votre système
    // Exemple Moodle : return is_siteadmin();
    return true; // Pour les tests
}
?>
