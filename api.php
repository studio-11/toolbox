<?php
/**
 * IFEN Toolbox - API REST avec connexion PDO
 * 
 * Cette API se connecte directement à la base de données
 * en utilisant les credentials IFEN
 * 
 * @package    local_toolbox
 * @copyright  2024 IFEN
 * @author     Boris Merens
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Démarrer la session
session_start();

// Charger la configuration
define('TOOLBOX_INTERNAL', true);
require_once(__DIR__ . '/config.php');

// Headers CORS et JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestion des requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// CLASSE API PRINCIPALE
// ============================================

class ToolboxAPI {
    
    private $pdo;
    private $user_id;
    
    public function __construct() {
        try {
            $this->pdo = getDBConnection();
            
            // Pour le moment, on utilise l'ID de session
            // À remplacer par l'authentification Moodle réelle
            $this->user_id = $_SESSION['user_id'] ?? 1;
            
            logInfo('API initialisée', ['user_id' => $this->user_id]);
        } catch (Exception $e) {
            logError('Erreur initialisation API', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Router principal
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        logInfo('Requête reçue', ['method' => $method, 'action' => $action]);
        
        try {
            switch ($action) {
                case 'tools':
                    return $this->handleTools($method);
                    
                case 'tool':
                    return $this->handleTool($method);
                    
                case 'categories':
                    return $this->getCategories();
                    
                case 'ideas':
                    return $this->handleIdeas($method);
                    
                case 'vote':
                    return $this->handleVote($method);
                    
                case 'favorite':
                    return $this->handleFavorite($method);
                    
                case 'comment':
                    return $this->handleComment($method);
                    
                case 'stats':
                    return $this->getStats();
                    
                case 'track':
                    return $this->trackAction();
                    
                default:
                    throw new Exception('Action non reconnue: ' . $action, 404);
            }
        } catch (Exception $e) {
            logError('Erreur traitement requête', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
    
    // ============================================
    // GESTION DES CATÉGORIES
    // ============================================
    
    /**
     * GET /api.php?action=categories
     */
    private function getCategories() {
        $sql = "SELECT * FROM toolbox_categories ORDER BY display_order ASC, name ASC";
        $stmt = $this->pdo->query($sql);
        $categories = $stmt->fetchAll();
        
        return $this->success($categories);
    }
    
    // ============================================
    // GESTION DES OUTILS
    // ============================================
    
    /**
     * GET /api.php?action=tools
     */
    private function handleTools($method) {
        if ($method !== 'GET') {
            throw new Exception('Méthode non autorisée', 405);
        }
        
        $filters = [
            'type' => $_GET['type'] ?? null,
            'category' => $_GET['category'] ?? null,
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['search'] ?? null,
            'hot_only' => isset($_GET['hot'])
        ];
        
        $tools = $this->getTools($filters);
        return $this->success($tools);
    }
    
    /**
     * GET /api.php?action=tool&id=123
     */
    private function handleTool($method) {
        if ($method !== 'GET') {
            throw new Exception('Méthode non autorisée', 405);
        }
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            throw new Exception('ID manquant', 400);
        }
        
        $tool = $this->getToolDetails($id);
        if (!$tool) {
            throw new Exception('Outil non trouvé', 404);
        }
        
        // Incrémenter le compteur de vues
        $this->incrementViews($id);
        
        return $this->success($tool);
    }
    
    /**
     * Récupérer la liste des outils avec filtres
     */
    private function getTools($filters) {
        $sql = "SELECT 
                    t.*,
                    c.name as category_name,
                    c.icon as category_icon,
                    COUNT(DISTINCT f.user_id) as favorites_count,
                    COUNT(DISTINCT com.id) as comments_count,
                    EXISTS(SELECT 1 FROM toolbox_favorites 
                           WHERE tool_id = t.id AND user_id = :user_id) as is_favorited
                FROM toolbox_tools t
                LEFT JOIN toolbox_categories c ON t.category_id = c.id
                LEFT JOIN toolbox_favorites f ON t.id = f.tool_id
                LEFT JOIN toolbox_comments com ON t.id = com.tool_id AND com.is_approved = 1
                WHERE 1=1";
        
        $params = ['user_id' => $this->user_id];
        
        // Appliquer les filtres
        if ($filters['type']) {
            $sql .= " AND t.type = :type";
            $params['type'] = $filters['type'];
        }
        
        if ($filters['category']) {
            $sql .= " AND t.category_id = :category";
            $params['category'] = $filters['category'];
        }
        
        if ($filters['status']) {
            $sql .= " AND t.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if ($filters['hot_only']) {
            $sql .= " AND (t.is_hot = 1 OR t.status = 'new')";
        }
        
        if ($filters['search']) {
            $sql .= " AND (t.name LIKE :search OR t.short_description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " GROUP BY t.id ORDER BY t.is_hot DESC, t.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $tools = $stmt->fetchAll();
        
        // Récupérer les features pour chaque outil
        foreach ($tools as &$tool) {
            $tool['features'] = $this->getToolFeatures($tool['id']);
        }
        
        return $tools;
    }
    
    /**
     * Récupérer les détails complets d'un outil
     */
    private function getToolDetails($id) {
        $sql = "SELECT t.*, c.name as category_name
                FROM toolbox_tools t
                LEFT JOIN toolbox_categories c ON t.category_id = c.id
                WHERE t.id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $tool = $stmt->fetch();
        
        if (!$tool) {
            return null;
        }
        
        // Features
        $tool['features'] = $this->getToolFeatures($id);
        
        // Commentaires
        $tool['comments'] = $this->getToolComments($id);
        
        // Changelog
        $tool['changelog'] = $this->getToolChangelog($id);
        
        // Est-ce un favori ?
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM toolbox_favorites WHERE tool_id = :tool_id AND user_id = :user_id"
        );
        $stmt->execute(['tool_id' => $id, 'user_id' => $this->user_id]);
        $tool['is_favorited'] = $stmt->fetchColumn() > 0;
        
        return $tool;
    }
    
    /**
     * Récupérer les features d'un outil
     */
    private function getToolFeatures($tool_id) {
        $sql = "SELECT * FROM toolbox_tool_features 
                WHERE tool_id = :tool_id 
                ORDER BY display_order ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tool_id' => $tool_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer les commentaires d'un outil
     */
    private function getToolComments($tool_id) {
        $sql = "SELECT c.*, u.firstname, u.lastname
                FROM toolbox_comments c
                JOIN " . MOODLE_PREFIX . "user u ON c.user_id = u.id
                WHERE c.tool_id = :tool_id AND c.is_approved = 1
                ORDER BY c.created_at DESC
                LIMIT 50";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tool_id' => $tool_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupérer le changelog d'un outil
     */
    private function getToolChangelog($tool_id) {
        $sql = "SELECT * FROM toolbox_changelog 
                WHERE tool_id = :tool_id 
                ORDER BY release_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tool_id' => $tool_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Incrémenter le compteur de vues
     */
    private function incrementViews($tool_id) {
        // Enregistrer dans les stats
        $sql = "INSERT INTO toolbox_tool_stats 
                (tool_id, action_type, user_id, ip_address, user_agent) 
                VALUES (:tool_id, 'view', :user_id, :ip, :ua)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'tool_id' => $tool_id,
            'user_id' => $this->user_id,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Incrémenter le compteur global
        $sql = "UPDATE toolbox_tools SET views_count = views_count + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $tool_id]);
    }
    
    // ============================================
    // GESTION DES IDÉES
    // ============================================
    
    /**
     * GET/POST /api.php?action=ideas
     */
    private function handleIdeas($method) {
        if ($method === 'GET') {
            return $this->getIdeas();
        } else if ($method === 'POST') {
            return $this->createIdea();
        }
        throw new Exception('Méthode non autorisée', 405);
    }
    
    /**
     * Récupérer toutes les idées
     */
    private function getIdeas() {
        $sql = "SELECT 
                    i.*,
                    u.firstname,
                    u.lastname,
                    (SELECT COUNT(*) FROM toolbox_votes WHERE idea_id = i.id) as votes_count,
                    EXISTS(SELECT 1 FROM toolbox_votes 
                           WHERE idea_id = i.id AND user_id = :user_id) as has_voted
                FROM toolbox_ideas i
                LEFT JOIN " . MOODLE_PREFIX . "user u ON i.user_id = u.id
                ORDER BY i.votes_count DESC, i.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $this->user_id]);
        $ideas = $stmt->fetchAll();
        
        return $this->success($ideas);
    }
    
    /**
     * Créer une nouvelle idée
     */
    private function createIdea() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['title']) || empty($data['type']) || empty($data['problem'])) {
            throw new Exception('Données manquantes', 400);
        }
        
        $sql = "INSERT INTO toolbox_ideas 
                (title, type, problem, details, user_id, status) 
                VALUES (:title, :type, :problem, :details, :user_id, 'proposed')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title' => strip_tags($data['title']),
            'type' => $data['type'],
            'problem' => strip_tags($data['problem']),
            'details' => strip_tags($data['details'] ?? ''),
            'user_id' => $this->user_id
        ]);
        
        $id = $this->pdo->lastInsertId();
        
        logInfo('Nouvelle idée créée', ['id' => $id, 'title' => $data['title']]);
        
        return $this->success(['id' => $id, 'message' => 'Idée créée avec succès']);
    }
    
    // ============================================
    // GESTION DES VOTES
    // ============================================
    
    /**
     * POST /api.php?action=vote
     */
    private function handleVote($method) {
        if ($method !== 'POST') {
            throw new Exception('Méthode non autorisée', 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $idea_id = $data['idea_id'] ?? null;
        
        if (!$idea_id) {
            throw new Exception('ID de l\'idée manquant', 400);
        }
        
        // Vérifier si déjà voté
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM toolbox_votes WHERE idea_id = :idea_id AND user_id = :user_id"
        );
        $stmt->execute(['idea_id' => $idea_id, 'user_id' => $this->user_id]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Vous avez déjà voté pour cette idée', 409);
        }
        
        // Enregistrer le vote
        $sql = "INSERT INTO toolbox_votes (idea_id, user_id) VALUES (:idea_id, :user_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idea_id' => $idea_id, 'user_id' => $this->user_id]);
        
        // Le trigger SQL met à jour automatiquement le compteur
        
        logInfo('Vote enregistré', ['idea_id' => $idea_id, 'user_id' => $this->user_id]);
        
        return $this->success(['message' => 'Vote enregistré']);
    }
    
    // ============================================
    // GESTION DES FAVORIS
    // ============================================
    
    /**
     * POST/DELETE /api.php?action=favorite
     */
    private function handleFavorite($method) {
        $data = json_decode(file_get_contents('php://input'), true);
        $tool_id = $data['tool_id'] ?? null;
        
        if (!$tool_id) {
            throw new Exception('ID de l\'outil manquant', 400);
        }
        
        if ($method === 'POST') {
            // Vérifier si déjà en favoris
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM toolbox_favorites WHERE tool_id = :tool_id AND user_id = :user_id"
            );
            $stmt->execute(['tool_id' => $tool_id, 'user_id' => $this->user_id]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Déjà dans les favoris', 409);
            }
            
            // Ajouter aux favoris
            $sql = "INSERT INTO toolbox_favorites (tool_id, user_id) VALUES (:tool_id, :user_id)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['tool_id' => $tool_id, 'user_id' => $this->user_id]);
            
            return $this->success(['message' => 'Ajouté aux favoris']);
            
        } else if ($method === 'DELETE') {
            // Retirer des favoris
            $sql = "DELETE FROM toolbox_favorites WHERE tool_id = :tool_id AND user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['tool_id' => $tool_id, 'user_id' => $this->user_id]);
            
            return $this->success(['message' => 'Retiré des favoris']);
        }
        
        throw new Exception('Méthode non autorisée', 405);
    }
    
    // ============================================
    // GESTION DES COMMENTAIRES
    // ============================================
    
    /**
     * POST /api.php?action=comment
     */
    private function handleComment($method) {
        if ($method !== 'POST') {
            throw new Exception('Méthode non autorisée', 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['tool_id']) || empty($data['comment'])) {
            throw new Exception('Données manquantes', 400);
        }
        
        $sql = "INSERT INTO toolbox_comments 
                (tool_id, user_id, comment, rating, is_approved) 
                VALUES (:tool_id, :user_id, :comment, :rating, 0)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'tool_id' => $data['tool_id'],
            'user_id' => $this->user_id,
            'comment' => strip_tags($data['comment']),
            'rating' => $data['rating'] ?? null
        ]);
        
        $id = $this->pdo->lastInsertId();
        
        return $this->success([
            'id' => $id,
            'message' => 'Commentaire envoyé (en attente de modération)'
        ]);
    }
    
    // ============================================
    // STATISTIQUES
    // ============================================
    
    /**
     * GET /api.php?action=stats
     */
    private function getStats() {
        $stats = [];
        
        // Nombre total d'outils
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM toolbox_tools WHERE status != 'deprecated'");
        $stats['total_tools'] = $stmt->fetchColumn();
        
        // Outils HOT
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM toolbox_tools WHERE is_hot = 1 OR status = 'new'");
        $stats['hot_tools'] = $stmt->fetchColumn();
        
        // Installations totales
        $stmt = $this->pdo->query("SELECT SUM(installations_count) FROM toolbox_tools");
        $stats['total_installations'] = $stmt->fetchColumn() ?: 0;
        
        // Idées en attente
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM toolbox_ideas WHERE status = 'proposed'");
        $stats['pending_ideas'] = $stmt->fetchColumn();
        
        // Utilisateurs actifs
        $stmt = $this->pdo->query("SELECT COUNT(DISTINCT user_id) FROM toolbox_favorites");
        $stats['active_users'] = $stmt->fetchColumn();
        
        return $this->success($stats);
    }
    
    /**
     * POST /api.php?action=track
     */
    private function trackAction() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['tool_id']) || empty($data['action_type'])) {
            throw new Exception('Données manquantes', 400);
        }
        
        $sql = "INSERT INTO toolbox_tool_stats 
                (tool_id, action_type, user_id, course_id, ip_address, user_agent) 
                VALUES (:tool_id, :action_type, :user_id, :course_id, :ip, :ua)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'tool_id' => $data['tool_id'],
            'action_type' => $data['action_type'],
            'user_id' => $this->user_id,
            'course_id' => $data['course_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Incrémenter le compteur correspondant
        if ($data['action_type'] === 'install') {
            $sql = "UPDATE toolbox_tools SET installations_count = installations_count + 1 WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $data['tool_id']]);
        }
        
        return $this->success(['message' => 'Action trackée']);
    }
    
    // ============================================
    // HELPERS
    // ============================================
    
    private function success($data) {
        return [
            'success' => true,
            'data' => $data
        ];
    }
    
    private function error($message, $code = 500) {
        http_response_code($code);
        return [
            'success' => false,
            'error' => $message,
            'code' => $code
        ];
    }
}

// ============================================
// EXÉCUTION
// ============================================

try {
    $api = new ToolboxAPI();
    $result = $api->handleRequest();
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    logError('Erreur fatale API', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => TOOLBOX_DEBUG ? $e->getMessage() : 'Erreur serveur',
        'code' => 500
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
