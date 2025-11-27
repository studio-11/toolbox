<?php
/**
 * IFEN Toolbox Admin - Configuration
 * ===================================
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mode debug
define('DEBUG_MODE', true);

// Chemins
define('ADMIN_PATH', __DIR__ . '/..');
define('TOOLBOX_PATH', dirname(ADMIN_PATH));
define('BASE_URL', '/ifen_html/toolbox');
define('ADMIN_URL', BASE_URL . '/admin');

// API
define('API_URL', ADMIN_URL . '/api/api.php');
define('FRONTEND_API_URL', BASE_URL . '/api/api.php');

// Titre
define('SITE_TITLE', 'Toolbox Admin - IFEN');
define('APP_VERSION', '1.0.0');

// Ressources externes
define('FONT_URL', 'https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@400;500;600;700&display=swap');
define('FONTAWESOME_URL', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
define('IFEN_CSS_URL', 'https://learningsphere.ifen.lu/ifen_css/custom-moodle-styles.css');

// ============================================
// CONNEXION BASE DE DONNÉES
// ============================================

$credentials_file = '/export/hosting/men/ifen/htdocs-lms/ifen_credentials/db_credentials_learningsphere.php';

if (file_exists($credentials_file)) {
    $db_credentials = require $credentials_file;
    define('DB_HOST', $db_credentials['host']);
    define('DB_NAME', $db_credentials['db']);
    define('DB_USER', $db_credentials['user']);
    define('DB_PASS', $db_credentials['pass']);
} else {
    // Fallback pour dev local
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
// AUTHENTIFICATION ADMIN
// ============================================

// Liste des admins autorisés (à adapter selon votre système)
define('ADMIN_USERS', [
    1 => ['name' => 'Admin IFEN', 'email' => 'admin@ifen.lu', 'password' => password_hash('admin2024', PASSWORD_DEFAULT)],
]);

function getCurrentAdmin() {
    if (isset($_SESSION['admin_user'])) {
        return $_SESSION['admin_user'];
    }
    return null;
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_user']) && !empty($_SESSION['admin_user']['id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

function loginAdmin($email, $password) {
    $pdo = getDbConnection();
    
    // Vérifier dans la table admin si elle existe, sinon utiliser la constante
    try {
        $stmt = $pdo->prepare("SELECT * FROM toolbox_admins WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_user'] = [
                'id' => $admin['id'],
                'name' => $admin['name'],
                'email' => $admin['email'],
                'role' => $admin['role']
            ];
            
            // Mettre à jour last_login
            $pdo->prepare("UPDATE toolbox_admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
            
            return true;
        }
    } catch (PDOException $e) {
        // Table n'existe pas, utiliser les admins en dur
        foreach (ADMIN_USERS as $id => $user) {
            if ($user['email'] === $email && password_verify($password, $user['password'])) {
                $_SESSION['admin_user'] = [
                    'id' => $id,
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => 'super_admin'
                ];
                return true;
            }
        }
    }
    
    return false;
}

function logoutAdmin() {
    unset($_SESSION['admin_user']);
    session_destroy();
}

// ============================================
// HELPERS
// ============================================

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function url($path = '') {
    return ADMIN_URL . '/' . ltrim($path, '/');
}

function asset($path) {
    return url($path) . '?v=' . APP_VERSION;
}

function frontendUrl($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

// Messages flash
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Pagination
function paginate($total, $perPage = 20, $currentPage = 1) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Format date
function formatDate($date, $format = 'd/m/Y H:i') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

// Format relative date
function formatRelativeDate($date) {
    if (!$date) return '-';
    
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'À l\'instant';
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' h';
    if ($diff < 604800) return floor($diff / 86400) . ' j';
    
    return date('d/m/Y', $timestamp);
}
?>
