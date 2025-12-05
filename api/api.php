<?php
/**
 * IFEN Toolbox - API Endpoints
 * ============================
 * Version mise à jour avec login IAM et nouvelles fonctionnalités
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/config.php';

// Connexion PDO
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            jsonError('Database connection failed', 500);
        }
    }
    return $pdo;
}

// Réponse JSON
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// Récupérer les données POST
function getPostData() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: $_POST;
}

// Router
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        
        // ==================== AUTHENTIFICATION ====================
        
        case 'login':
            if ($method !== 'POST') jsonError('Method not allowed', 405);
            
            $data = getPostData();
            $username = trim($data['username'] ?? '');
            
            if (empty($username)) {
                jsonError('Identifiant requis');
            }
            
            $pdo = getDB();
            
            // ÉTAPE 1 : Vérifier si le IAM existe dans mdl_user
            $stmt = $pdo->prepare("
                SELECT id, username, firstname, lastname, email 
                FROM mdl_user 
                WHERE username = ? AND deleted = 0 AND suspended = 0
            ");
            $stmt->execute([$username]);
            $mdlUser = $stmt->fetch();
            
            if (!$mdlUser) {
                jsonError('Identifiant non reconnu. Utilisez votre identifiant IAM.');
            }
            
            // ÉTAPE 2 : Vérifier dans toolbox_users (blacklist + whitelist)
            $stmt = $pdo->prepare("
                SELECT id, is_blacklisted, is_whitelisted, is_admin 
                FROM toolbox_users 
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $toolboxUser = $stmt->fetch();
            
            // ÉTAPE 2a : Vérifier si blacklisté
            if ($toolboxUser && $toolboxUser['is_blacklisted']) {
                jsonError('Accès refusé. Contactez l\'administrateur.');
            }
            
            // ÉTAPE 3 : Vérifier si dans la whitelist
            if (!$toolboxUser || !$toolboxUser['is_whitelisted']) {
                jsonError('Accès non autorisé. Veuillez contacter l\'administrateur pour avoir accès à cette page.');
            }
            
            // Utilisateur autorisé - mettre à jour last_login
            $stmt = $pdo->prepare("UPDATE toolbox_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$toolboxUser['id']]);
            
            $toolboxUserId = $toolboxUser['id'];
            $isAdmin = (bool)$toolboxUser['is_admin'];
            
            // Créer la session
            session_start();
            $_SESSION['toolbox_user'] = [
                'id' => $toolboxUserId,
                'mdl_user_id' => $mdlUser['id'],
                'username' => $mdlUser['username'],
                'name' => $mdlUser['firstname'] . ' ' . $mdlUser['lastname'],
                'email' => $mdlUser['email'],
                'is_admin' => $isAdmin
            ];
            
            jsonResponse([
                'user' => $_SESSION['toolbox_user'],
                'message' => 'Connexion réussie'
            ]);
            break;
            
        case 'logout':
            session_start();
            session_destroy();
            jsonResponse(['message' => 'Déconnexion réussie']);
            break;
            
        case 'check_auth':
            session_start();
            if (isset($_SESSION['toolbox_user'])) {
                jsonResponse(['authenticated' => true, 'user' => $_SESSION['toolbox_user']]);
            } else {
                jsonResponse(['authenticated' => false]);
            }
            break;
        
        // ==================== OUTILS ====================
        
        case 'tools':
            $pdo = getDB();
            $status = $_GET['status'] ?? 'available';
            
            if ($status === 'beta') {
                $stmt = $pdo->query("
                    SELECT t.*, c.name as category_name,
                           (SELECT COUNT(*) FROM toolbox_beta_testers bt WHERE bt.tool_id = t.id) as testers_count,
                           (SELECT COUNT(*) FROM toolbox_beta_feedback bf WHERE bf.tool_id = t.id) as feedback_count
                    FROM toolbox_tools t
                    LEFT JOIN toolbox_categories c ON t.category_id = c.id
                    WHERE t.status = 'beta'
                    ORDER BY t.created_at DESC
                ");
            } else {
                $stmt = $pdo->query("
                    SELECT t.*, c.name as category_name
                    FROM toolbox_tools t
                    LEFT JOIN toolbox_categories c ON t.category_id = c.id
                    WHERE t.status NOT IN ('beta', 'deprecated', 'hidden')
                    ORDER BY t.is_hot DESC, t.name ASC
                ");
            }
            
            jsonResponse($stmt->fetchAll());
            break;
            
        case 'tool':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) jsonError('ID requis');
            
            $pdo = getDB();
            $stmt = $pdo->prepare("
                SELECT t.*, c.name as category_name
                FROM toolbox_tools t
                LEFT JOIN toolbox_categories c ON t.category_id = c.id
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            $tool = $stmt->fetch();
            
            if (!$tool) jsonError('Outil non trouvé', 404);
            
            // Récupérer les features
            $stmt = $pdo->prepare("SELECT * FROM toolbox_tool_features WHERE tool_id = ? ORDER BY sort_order");
            $stmt->execute([$id]);
            $tool['features'] = $stmt->fetchAll();
            
            jsonResponse($tool);
            break;
            
        case 'categories':
            $pdo = getDB();
            $stmt = $pdo->query("SELECT * FROM toolbox_categories ORDER BY sort_order, name");
            jsonResponse($stmt->fetchAll());
            break;
        
        // ==================== BETA TESTING ====================
        
        case 'beta_register':
            if ($method !== 'POST') jsonError('Method not allowed', 405);
            
            session_start();
            if (!isset($_SESSION['toolbox_user'])) jsonError('Non authentifié', 401);
            
            $data = getPostData();
            $toolId = (int)($data['tool_id'] ?? 0);
            $userId = $_SESSION['toolbox_user']['id'];
            
            if (!$toolId) jsonError('ID outil requis');
            
            $pdo = getDB();
            
            // Vérifier si déjà inscrit
            $stmt = $pdo->prepare("SELECT id FROM toolbox_beta_testers WHERE tool_id = ? AND user_id = ?");
            $stmt->execute([$toolId, $userId]);
            if ($stmt->fetch()) {
                jsonError('Déjà inscrit à ce beta test');
            }
            
            // Inscrire
            $stmt = $pdo->prepare("INSERT INTO toolbox_beta_testers (tool_id, user_id, registered_at) VALUES (?, ?, NOW())");
            $stmt->execute([$toolId, $userId]);
            
            jsonResponse(['message' => 'Inscription réussie', 'id' => $pdo->lastInsertId()]);
            break;
            
        case 'user_beta_registrations':
            session_start();
            if (!isset($_SESSION['toolbox_user'])) jsonError('Non authentifié', 401);
            
            $userId = $_SESSION['toolbox_user']['id'];
            $pdo = getDB();
            
            $stmt = $pdo->prepare("SELECT tool_id FROM toolbox_beta_testers WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $toolIds = array_column($stmt->fetchAll(), 'tool_id');
            jsonResponse(array_map('intval', $toolIds));
            break;
            
        case 'beta_feedback':
            if ($method !== 'POST') jsonError('Method not allowed', 405);
            
            session_start();
            if (!isset($_SESSION['toolbox_user'])) jsonError('Non authentifié', 401);
            
            $data = getPostData();
            $toolId = (int)($data['tool_id'] ?? 0);
            $userId = $_SESSION['toolbox_user']['id'];
            
            if (!$toolId) jsonError('ID outil requis');
            if (empty($data['content'])) jsonError('Contenu requis');
            
            $pdo = getDB();
            $stmt = $pdo->prepare("
                INSERT INTO toolbox_beta_feedback (tool_id, user_id, feedback_type, title, content, rating, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $toolId,
                $userId,
                $data['feedback_type'] ?? 'general',
                $data['title'] ?? null,
                $data['content'],
                $data['rating'] ?? null
            ]);
            
            jsonResponse(['message' => 'Feedback enregistré', 'id' => $pdo->lastInsertId()]);
            break;
            
        case 'beta_feedbacks':
            $toolId = (int)($_GET['tool_id'] ?? 0);
            if (!$toolId) jsonError('ID outil requis');
            
            $pdo = getDB();
            $stmt = $pdo->prepare("
                SELECT bf.*, 
                       CONCAT(mu.firstname, ' ', LEFT(mu.lastname, 1), '.') as user_name
                FROM toolbox_beta_feedback bf
                LEFT JOIN toolbox_users tu ON bf.user_id = tu.id
                LEFT JOIN mdl_user mu ON tu.mdl_user_id = mu.id
                WHERE bf.tool_id = ?
                ORDER BY bf.created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$toolId]);
            jsonResponse($stmt->fetchAll());
            break;
        
        // ==================== IDÉES & VOTES ====================
        
        case 'ideas':
            $pdo = getDB();
            $status = $_GET['status'] ?? 'pending';
            
            if ($status === 'planned') {
                $stmt = $pdo->query("
                    SELECT i.*, 
                           CONCAT(mu.firstname, ' ', LEFT(mu.lastname, 1), '.') as user_name,
                           (SELECT COUNT(*) FROM toolbox_idea_votes iv WHERE iv.idea_id = i.id) as votes_count
                    FROM toolbox_ideas i
                    LEFT JOIN toolbox_users tu ON i.user_id = tu.id
                    LEFT JOIN mdl_user mu ON tu.mdl_user_id = mu.id
                    WHERE i.status IN ('planned', 'in_progress')
                    ORDER BY i.priority DESC, i.planned_start_date ASC
                ");
            } else {
                $stmt = $pdo->query("
                    SELECT i.*, 
                           CONCAT(mu.firstname, ' ', LEFT(mu.lastname, 1), '.') as user_name,
                           (SELECT COUNT(*) FROM toolbox_idea_votes iv WHERE iv.idea_id = i.id) as votes_count
                    FROM toolbox_ideas i
                    LEFT JOIN toolbox_users tu ON i.user_id = tu.id
                    LEFT JOIN mdl_user mu ON tu.mdl_user_id = mu.id
                    WHERE i.status IN ('proposed', 'under_review')
                    ORDER BY votes_count DESC, i.created_at DESC
                ");
            }
            
            jsonResponse($stmt->fetchAll());
            break;
            
        case 'idea':
            if ($method === 'POST') {
                session_start();
                if (!isset($_SESSION['toolbox_user'])) jsonError('Non authentifié', 401);
                
                $data = getPostData();
                
                if (empty($data['title'])) jsonError('Titre requis');
                if (empty($data['type'])) jsonError('Type requis');
                if (empty($data['problem'])) jsonError('Description du problème requise');
                
                // Valider le type
                $validTypes = ['course_activity', 'course_resource', 'platform_feature', 'other'];
                if (!in_array($data['type'], $validTypes)) {
                    jsonError('Type invalide');
                }
                
                $pdo = getDB();
                $stmt = $pdo->prepare("
                    INSERT INTO toolbox_ideas (user_id, title, type, problem, details, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'proposed', NOW())
                ");
                $stmt->execute([
                    $_SESSION['toolbox_user']['id'],
                    $data['title'],
                    $data['type'],
                    $data['problem'],
                    $data['details'] ?? null
                ]);
                
                jsonResponse(['message' => 'Idée soumise', 'id' => $pdo->lastInsertId()]);
            }
            break;
            
        case 'vote':
            if ($method !== 'POST') jsonError('Method not allowed', 405);
            
            session_start();
            if (!isset($_SESSION['toolbox_user'])) jsonError('Non authentifié', 401);
            
            $data = getPostData();
            $ideaId = (int)($data['idea_id'] ?? 0);
            $userId = $_SESSION['toolbox_user']['id'];
            
            if (!$ideaId) jsonError('ID idée requis');
            
            $pdo = getDB();
            
            // Vérifier si déjà voté
            $stmt = $pdo->prepare("SELECT id FROM toolbox_idea_votes WHERE idea_id = ? AND user_id = ?");
            $stmt->execute([$ideaId, $userId]);
            if ($stmt->fetch()) {
                jsonError('Vous avez déjà voté pour cette idée');
            }
            
            // Voter
            $stmt = $pdo->prepare("INSERT INTO toolbox_idea_votes (idea_id, user_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$ideaId, $userId]);
            
            jsonResponse(['message' => 'Vote enregistré']);
            break;
            
        case 'user_votes':
            session_start();
            if (!isset($_SESSION['toolbox_user'])) jsonError('Non authentifié', 401);
            
            $userId = $_SESSION['toolbox_user']['id'];
            $pdo = getDB();
            
            $stmt = $pdo->prepare("SELECT idea_id FROM toolbox_idea_votes WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $ideaIds = array_column($stmt->fetchAll(), 'idea_id');
            jsonResponse(array_map('intval', $ideaIds));
            break;
            
        case 'plan_idea':
            if ($method !== 'POST') jsonError('Method not allowed', 405);
            
            session_start();
            if (!isset($_SESSION['toolbox_user']) || !$_SESSION['toolbox_user']['is_admin']) {
                jsonError('Accès refusé', 403);
            }
            
            $data = getPostData();
            $ideaId = (int)($data['idea_id'] ?? 0);
            
            if (!$ideaId) jsonError('ID idée requis');
            
            $pdo = getDB();
            $stmt = $pdo->prepare("
                UPDATE toolbox_ideas 
                SET status = 'planned',
                    planned_start_date = ?,
                    planned_end_date = ?,
                    priority = ?,
                    current_phase = ?,
                    assigned_to = ?,
                    dev_notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $data['planned_start_date'] ?? null,
                $data['planned_end_date'] ?? null,
                $data['priority'] ?? 'medium',
                $data['current_phase'] ?? 'analysis',
                $data['assigned_to'] ?? null,
                $data['dev_notes'] ?? null,
                $ideaId
            ]);
            
            jsonResponse(['message' => 'Idée programmée']);
            break;
        
        // ==================== FAVORIS ====================
        
        case 'favorite':
            session_start();
            if (!isset($_SESSION['toolbox_user'])) jsonError('Non authentifié', 401);
            
            $userId = $_SESSION['toolbox_user']['id'];
            $pdo = getDB();
            
            if ($method === 'POST') {
                $data = getPostData();
                $toolId = (int)($data['tool_id'] ?? 0);
                if (!$toolId) jsonError('ID outil requis');
                
                $stmt = $pdo->prepare("INSERT IGNORE INTO toolbox_favorites (user_id, tool_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$userId, $toolId]);
                jsonResponse(['message' => 'Ajouté aux favoris']);
                
            } elseif ($method === 'DELETE') {
                $data = getPostData();
                $toolId = (int)($data['tool_id'] ?? 0);
                if (!$toolId) jsonError('ID outil requis');
                
                $stmt = $pdo->prepare("DELETE FROM toolbox_favorites WHERE user_id = ? AND tool_id = ?");
                $stmt->execute([$userId, $toolId]);
                jsonResponse(['message' => 'Retiré des favoris']);
            }
            break;
            
        case 'user_favorites':
            session_start();
            if (!isset($_SESSION['toolbox_user'])) jsonError('Non authentifié', 401);
            
            $userId = $_SESSION['toolbox_user']['id'];
            $pdo = getDB();
            
            $stmt = $pdo->prepare("SELECT tool_id FROM toolbox_favorites WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $toolIds = array_column($stmt->fetchAll(), 'tool_id');
            jsonResponse(array_map('intval', $toolIds));
            break;
        
        // ==================== STATS & DIVERS ====================
        
        case 'stats':
            $pdo = getDB();
            
            $tools = $pdo->query("SELECT COUNT(*) FROM toolbox_tools WHERE status NOT IN ('hidden', 'deprecated')")->fetchColumn();
            $beta = $pdo->query("SELECT COUNT(*) FROM toolbox_tools WHERE status = 'beta'")->fetchColumn();
            $ideas = $pdo->query("SELECT COUNT(*) FROM toolbox_ideas WHERE status IN ('proposed', 'under_review')")->fetchColumn();
            
            jsonResponse([
                'tools_count' => (int)$tools,
                'beta_count' => (int)$beta,
                'ideas_count' => (int)$ideas
            ]);
            break;
            
        case 'platform_status':
            $pdo = getDB();
            
            // Récupérer le statut actuel
            $stmt = $pdo->query("
                SELECT * FROM toolbox_platform_status 
                WHERE is_current = 1 
                ORDER BY updated_at DESC 
                LIMIT 1
            ");
            $status = $stmt->fetch();
            
            if (!$status) {
                $status = [
                    'status' => 'operational',
                    'message' => null,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
            
            jsonResponse($status);
            break;
            
        case 'track':
            if ($method !== 'POST') jsonError('Method not allowed', 405);
            
            $data = getPostData();
            $toolId = (int)($data['tool_id'] ?? 0);
            $actionType = $data['action_type'] ?? 'view';
            
            if (!$toolId) jsonResponse(['tracked' => false]);
            
            $pdo = getDB();
            
            // Incrémenter le compteur de vues
            if ($actionType === 'view') {
                $stmt = $pdo->prepare("UPDATE toolbox_tools SET views_count = views_count + 1 WHERE id = ?");
                $stmt->execute([$toolId]);
            }
            
            jsonResponse(['tracked' => true]);
            break;
        
        default:
            jsonError('Action non reconnue', 404);
    }
    
} catch (Exception $e) {
    error_log('Toolbox API Error: ' . $e->getMessage());
    jsonError('Erreur serveur', 500);
}
