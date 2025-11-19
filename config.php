<?php
/**
 * IFEN Toolbox - Configuration
 * 
 * Ce fichier charge les credentials depuis le fichier centralisé
 * et configure la connexion à la base de données
 * 
 * @package    local_toolbox
 * @copyright  2024 IFEN
 * @author     Boris Merens
 */

// Sécurité : empêcher l'accès direct
defined('TOOLBOX_INTERNAL') || die();

// Charger les credentials depuis le fichier centralisé
$db_credentials = require('/export/hosting/men/ifen/htdocs-lms/ifen_credentials/db_credentials_learningsphere.php');

// Configuration de la base de données
define('DB_HOST', $db_credentials['host']);
define('DB_NAME', $db_credentials['db']);
define('DB_USER', $db_credentials['user']);
define('DB_PASS', $db_credentials['pass']);
define('DB_CHARSET', 'utf8mb4');

// Préfixe des tables Moodle (si différent de 'mdl_')
define('MOODLE_PREFIX', 'mdl_');

// Configuration de l'application
define('TOOLBOX_DEBUG', false); // Mettre à true pour le développement
define('TOOLBOX_VERSION', '1.0.0');

// URLs
define('TOOLBOX_BASE_URL', '/ifen_html/toolbox/');
define('TOOLBOX_API_URL', TOOLBOX_BASE_URL . 'api.php');

// Chemins
define('TOOLBOX_ROOT', __DIR__);
define('TOOLBOX_UPLOADS', TOOLBOX_ROOT . '/uploads/');

// Sécurité
define('TOOLBOX_HASH_ALGO', 'sha256');
define('TOOLBOX_SESSION_NAME', 'ifen_toolbox_session');

/**
 * Créer une connexion PDO à la base de données
 * 
 * @return PDO
 * @throws PDOException
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            if (TOOLBOX_DEBUG) {
                error_log('Toolbox: Connexion BDD établie avec succès');
            }
        } catch (PDOException $e) {
            error_log('Toolbox: Erreur connexion BDD - ' . $e->getMessage());
            throw $e;
        }
    }
    
    return $pdo;
}

/**
 * Logger une erreur
 * 
 * @param string $message
 * @param array $context
 */
function logError($message, $context = []) {
    $log_message = sprintf(
        '[%s] %s | Context: %s',
        date('Y-m-d H:i:s'),
        $message,
        json_encode($context)
    );
    
    error_log('Toolbox Error: ' . $log_message);
    
    if (TOOLBOX_DEBUG) {
        echo "<!-- DEBUG: $log_message -->\n";
    }
}

/**
 * Logger une info (debug uniquement)
 * 
 * @param string $message
 * @param array $context
 */
function logInfo($message, $context = []) {
    if (!TOOLBOX_DEBUG) {
        return;
    }
    
    $log_message = sprintf(
        '[%s] %s | Context: %s',
        date('Y-m-d H:i:s'),
        $message,
        json_encode($context)
    );
    
    error_log('Toolbox Info: ' . $log_message);
}
