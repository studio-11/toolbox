<?php
/**
 * IFEN Toolbox - API Endpoint
 * ============================
 * API REST pour la Toolbox IFEN
 */

// Headers CORS et JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Pour les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Charger la configuration
require_once __DIR__ . '/../includes/config.php';

// Connexion PDO
try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    sendError('Erreur de connexion à la base de données', 500);
}

// Récupérer l'action et la méthode
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Utilisateur courant
$currentUser = getCurrentUser();

try {
    switch ($action) {
        
        // ==================== STATS ====================
        case 'stats':
            $stmt = $pdo->query("
                SELECT 
                    (SELECT COUNT(*) FROM toolbox_tools WHERE status IN ('stable', 'new')) AS tools_count,
                    (SELECT COUNT(*) FROM toolbox_tools WHERE status = 'beta') AS beta_count,
                    (SELECT COUNT(*) FROM toolbox_ideas WHERE status = 'proposed') AS ideas_count
            ");
            $stats = $stmt->fetch();
            sendSuccess($stats);
            break;
        
        // ==================== TOOLS ====================
        case 'tools':
            $status = $_GET['status'] ?? 'available';
            
            if ($status === 'available') {
                $stmt = $pdo->query("SELECT * FROM v_tools_available");
            } elseif ($status === 'beta') {
                $stmt = $pdo->query("SELECT * FROM v_tools_beta");
            } else {
                $stmt = $pdo->query("SELECT t.*, c.name AS category_name FROM toolbox_tools t LEFT JOIN toolbox_categories c ON t.category_id = c.id ORDER BY t.created_at DESC");
            }
            
            $tools = $stmt->fetchAll();
            
            // Charger les features pour chaque outil
            foreach ($tools as &$tool) {
                $stmtFeatures = $pdo->prepare("SELECT feature_text FROM toolbox_tool_features WHERE tool_id = ? ORDER BY display_order");
                $stmtFeatures->execute([$tool['id']]);
                $tool['features'] = $stmtFeatures->fetchAll(PDO::FETCH_COLUMN);
            }
            
            sendSuccess($tools);
            break;
        
        case 'tool':
            $id = $_GET['id'] ?? 0;
            
            $stmt = $pdo->prepare("
                SELECT t.*, c.name AS category_name, c.icon AS category_icon
                FROM toolbox_tools t
                LEFT JOIN toolbox_categories c ON t.category_id = c.id
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            $tool = $stmt->fetch();
            
            if (!$tool) {
                sendError('Outil non trouvé', 404);
            }
            
            // Charger les features
            $stmtFeatures = $pdo->prepare("SELECT feature_text FROM toolbox_tool_features WHERE tool_id = ? ORDER BY display_order");
            $stmtFeatures->execute([$id]);
            $tool['features'] = $stmtFeatures->fetchAll(PDO::FETCH_COLUMN);
            
            // Incrémenter les vues
            $pdo->prepare("UPDATE toolbox_tools SET views_count = views_count + 1 WHERE id = ?")->execute([$id]);
            
            sendSuccess($tool);
            break;
        
        case 'tool_reviews':
            $toolId = $_GET['tool_id'] ?? 0;
            
            $stmt = $pdo->prepare("
                SELECT * FROM toolbox_tool_reviews 
                WHERE tool_id = ? 
                ORDER BY review_date DESC
            ");
            $stmt->execute([$toolId]);
            $reviews = $stmt->fetchAll();
            
            sendSuccess($reviews);
            break;
        
        // ==================== CATEGORIES ====================
        case 'categories':
            $stmt = $pdo->query("SELECT * FROM toolbox_categories ORDER BY display_order, name");
            $categories = $stmt->fetchAll();
            sendSuccess($categories);
            break;
        
        // ==================== IDEAS ====================
        case 'ideas':
            $status = $_GET['status'] ?? 'pending';
            
            if ($status === 'pending') {
                $stmt = $pdo->query("SELECT * FROM v_ideas_pending");
            } elseif ($status === 'planned') {
                $stmt = $pdo->query("SELECT * FROM v_ideas_planned");
            } else {
                $stmt = $pdo->query("SELECT * FROM toolbox_ideas ORDER BY votes_count DESC, created_at DESC");
            }
            
            $ideas = $stmt->fetchAll();
            sendSuccess($ideas);
            break;
        
        case 'idea':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $stmt = $pdo->prepare("
                    INSERT INTO toolbox_ideas (title, type, problem, details, user_id, user_name, user_email)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['title'] ?? '',
                    $data['type'] ?? 'improvement',
                    $data['problem'] ?? '',
                    $data['details'] ?? '',
                    $currentUser['id'],
                    $currentUser['name'],
                    $currentUser['email']
                ]);
                
                sendSuccess(['id' => $pdo->lastInsertId(), 'message' => 'Idée créée avec succès']);
            }
            break;
        
        case 'vote':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $ideaId = $data['idea_id'] ?? 0;
                
                // Vérifier si déjà voté
                $stmt = $pdo->prepare("SELECT id FROM toolbox_votes WHERE idea_id = ? AND user_id = ?");
                $stmt->execute([$ideaId, $currentUser['id']]);
                
                if ($stmt->fetch()) {
                    sendError('Vous avez déjà voté pour cette idée', 400);
                }
                
                // Ajouter le vote
                $stmt = $pdo->prepare("INSERT INTO toolbox_votes (idea_id, user_id) VALUES (?, ?)");
                $stmt->execute([$ideaId, $currentUser['id']]);
                
                // Mettre à jour le compteur
                $pdo->prepare("UPDATE toolbox_ideas SET votes_count = votes_count + 1 WHERE id = ?")->execute([$ideaId]);
                
                sendSuccess(['message' => 'Vote enregistré']);
            }
            break;
        
        case 'user_votes':
            $stmt = $pdo->prepare("SELECT idea_id FROM toolbox_votes WHERE user_id = ?");
            $stmt->execute([$currentUser['id']]);
            $votes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            sendSuccess($votes);
            break;
        
        case 'plan_idea':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $ideaId = $data['idea_id'] ?? 0;
                
                $pdo->beginTransaction();
                
                try {
                    // Mettre à jour le statut de l'idée
                    $pdo->prepare("UPDATE toolbox_ideas SET status = 'in_progress' WHERE id = ?")->execute([$ideaId]);
                    
                    // Créer la planification
                    $stmt = $pdo->prepare("
                        INSERT INTO toolbox_idea_planning 
                        (idea_id, planned_start_date, planned_end_date, priority, assigned_to, assigned_to_id, dev_notes, current_phase)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'analysis')
                    ");
                    $stmt->execute([
                        $ideaId,
                        $data['planned_start_date'] ?? null,
                        $data['planned_end_date'] ?? null,
                        $data['priority'] ?? 'medium',
                        $data['assigned_to'] ?? null,
                        $data['assigned_to_id'] ?? null,
                        $data['dev_notes'] ?? null
                    ]);
                    
                    $pdo->commit();
                    sendSuccess(['message' => 'Idée planifiée avec succès']);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
            break;
        
        case 'update_planning':
            if ($method === 'PUT') {
                $data = json_decode(file_get_contents('php://input'), true);
                $ideaId = $data['idea_id'] ?? 0;
                
                $stmt = $pdo->prepare("
                    UPDATE toolbox_idea_planning SET
                        planned_start_date = ?,
                        planned_end_date = ?,
                        current_phase = ?,
                        progress_percent = ?,
                        priority = ?,
                        assigned_to = ?,
                        assigned_to_id = ?,
                        dev_notes = ?
                    WHERE idea_id = ?
                ");
                $stmt->execute([
                    $data['planned_start_date'] ?? null,
                    $data['planned_end_date'] ?? null,
                    $data['current_phase'] ?? 'analysis',
                    $data['progress_percent'] ?? 0,
                    $data['priority'] ?? 'medium',
                    $data['assigned_to'] ?? null,
                    $data['assigned_to_id'] ?? null,
                    $data['dev_notes'] ?? null,
                    $ideaId
                ]);
                
                sendSuccess(['message' => 'Planification mise à jour']);
            }
            break;
        
        case 'unplan_idea':
            if ($method === 'DELETE') {
                $data = json_decode(file_get_contents('php://input'), true);
                $ideaId = $data['idea_id'] ?? 0;
                
                $pdo->beginTransaction();
                
                try {
                    $pdo->prepare("DELETE FROM toolbox_idea_planning WHERE idea_id = ?")->execute([$ideaId]);
                    $pdo->prepare("UPDATE toolbox_ideas SET status = 'proposed' WHERE id = ?")->execute([$ideaId]);
                    
                    $pdo->commit();
                    sendSuccess(['message' => 'Idée retirée de la programmation']);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
            break;
        
        // ==================== BETA ====================
        case 'beta_register':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $toolId = $data['tool_id'] ?? 0;
                
                // Vérifier si déjà inscrit
                $stmt = $pdo->prepare("SELECT id FROM toolbox_beta_testers WHERE tool_id = ? AND user_id = ?");
                $stmt->execute([$toolId, $currentUser['id']]);
                
                if ($stmt->fetch()) {
                    sendError('Vous êtes déjà inscrit à ce beta test', 400);
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO toolbox_beta_testers (tool_id, user_id, user_name, user_email)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $toolId,
                    $currentUser['id'],
                    $currentUser['name'],
                    $currentUser['email']
                ]);
                
                sendSuccess(['message' => 'Inscription au beta test réussie']);
            }
            break;
        
        case 'user_beta_registrations':
            $stmt = $pdo->prepare("SELECT tool_id FROM toolbox_beta_testers WHERE user_id = ?");
            $stmt->execute([$currentUser['id']]);
            $registrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
            sendSuccess($registrations);
            break;
        
        case 'beta_feedback':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $stmt = $pdo->prepare("
                    INSERT INTO toolbox_beta_feedback (tool_id, user_id, user_name, feedback_type, title, content, rating)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['tool_id'] ?? 0,
                    $currentUser['id'],
                    $currentUser['name'],
                    $data['feedback_type'] ?? 'general',
                    $data['title'] ?? '',
                    $data['content'] ?? '',
                    $data['rating'] ?? null
                ]);
                
                sendSuccess(['message' => 'Feedback envoyé avec succès']);
            }
            break;
        
        case 'beta_feedbacks':
            $toolId = $_GET['tool_id'] ?? 0;
            
            $stmt = $pdo->prepare("
                SELECT * FROM toolbox_beta_feedback 
                WHERE tool_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$toolId]);
            $feedbacks = $stmt->fetchAll();
            
            sendSuccess($feedbacks);
            break;
        
        // ==================== FAVORITES ====================
        case 'favorite':
            $data = json_decode(file_get_contents('php://input'), true);
            $toolId = $data['tool_id'] ?? 0;
            
            if ($method === 'POST') {
                $stmt = $pdo->prepare("INSERT IGNORE INTO toolbox_favorites (tool_id, user_id) VALUES (?, ?)");
                $stmt->execute([$toolId, $currentUser['id']]);
                sendSuccess(['message' => 'Ajouté aux favoris']);
            } elseif ($method === 'DELETE') {
                $stmt = $pdo->prepare("DELETE FROM toolbox_favorites WHERE tool_id = ? AND user_id = ?");
                $stmt->execute([$toolId, $currentUser['id']]);
                sendSuccess(['message' => 'Retiré des favoris']);
            }
            break;
        
        case 'user_favorites':
            $stmt = $pdo->prepare("SELECT tool_id FROM toolbox_favorites WHERE user_id = ?");
            $stmt->execute([$currentUser['id']]);
            $favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);
            sendSuccess($favorites);
            break;
        
        // ==================== TRACKING ====================
        case 'track':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $stmt = $pdo->prepare("
                    INSERT INTO toolbox_tool_stats (tool_id, action_type, user_id, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['tool_id'] ?? 0,
                    $data['action_type'] ?? 'view',
                    $currentUser['id'],
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                // Incrémenter le compteur approprié
                if (($data['action_type'] ?? '') === 'install') {
                    $pdo->prepare("UPDATE toolbox_tools SET installations_count = installations_count + 1 WHERE id = ?")->execute([$data['tool_id']]);
                }
                
                sendSuccess(['message' => 'Action trackée']);
            }
            break;
        
        // ==================== PLATFORM STATUS ====================
        case 'platform_status':
            $stmt = $pdo->query("SELECT * FROM toolbox_platform_status ORDER BY id DESC LIMIT 1");
            $status = $stmt->fetch();
            
            if (!$status) {
                // Statut par défaut si table vide
                $status = [
                    'platform_name' => 'LearningSphere',
                    'platform_version' => '4.3.2',
                    'moodle_version' => 'Moodle 4.3.2+',
                    'current_status' => 'operational',
                    'status_message' => 'Tous les systèmes fonctionnent normalement.'
                ];
            }
            
            sendSuccess($status);
            break;
        
        case 'update_platform_status':
            if ($method === 'PUT') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $pdo->beginTransaction();
                
                try {
                    // Récupérer le statut actuel pour l'historique
                    $currentStmt = $pdo->query("SELECT current_status FROM toolbox_platform_status LIMIT 1");
                    $current = $currentStmt->fetch();
                    
                    // Mettre à jour le statut
                    $stmt = $pdo->prepare("
                        UPDATE toolbox_platform_status SET
                            current_status = ?,
                            status_message = ?,
                            next_planned_maintenance = ?,
                            updated_by = ?
                        WHERE id = 1
                    ");
                    $stmt->execute([
                        $data['current_status'] ?? 'operational',
                        $data['status_message'] ?? null,
                        $data['next_planned_maintenance'] ?? null,
                        $currentUser['id']
                    ]);
                    
                    // Ajouter à l'historique si statut changé
                    if ($current && $current['current_status'] !== $data['current_status']) {
                        $histStmt = $pdo->prepare("
                            INSERT INTO toolbox_platform_status_history 
                            (previous_status, new_status, status_message, changed_by, changed_by_name)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $histStmt->execute([
                            $current['current_status'],
                            $data['current_status'],
                            $data['status_message'] ?? null,
                            $currentUser['id'],
                            $currentUser['name']
                        ]);
                    }
                    
                    $pdo->commit();
                    sendSuccess(['message' => 'Statut mis à jour']);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
            break;
        
        // ==================== WORKS STATS ====================
        case 'works_stats':
            $stmt = $pdo->query("
                SELECT 
                    SUM(CASE WHEN status = 'planned' THEN 1 ELSE 0 END) AS planned,
                    SUM(CASE WHEN status = 'unplanned' THEN 1 ELSE 0 END) AS unplanned,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
                FROM toolbox_works
            ");
            $stats = $stmt->fetch();
            sendSuccess($stats);
            break;
        
        // ==================== WORKS LIST ====================
        case 'works':
            $status = $_GET['status'] ?? '';
            $type = $_GET['work_type'] ?? $_GET['type'] ?? '';
            $search = $_GET['search'] ?? '';
            $downtime = $_GET['downtime'] ?? '';
            $dateFrom = $_GET['dateFrom'] ?? '';
            $dateTo = $_GET['dateTo'] ?? '';
            $upcoming = $_GET['upcoming'] ?? '';
            $limit = $_GET['limit'] ?? 50;
            
            $sql = "SELECT * FROM toolbox_works WHERE 1=1";
            $params = [];
            
            // Filtre par statut (peut être multiple séparé par virgule)
            if ($status) {
                $statuses = explode(',', $status);
                $placeholders = implode(',', array_fill(0, count($statuses), '?'));
                $sql .= " AND status IN ($placeholders)";
                $params = array_merge($params, $statuses);
            }
            
            // Filtre par type
            if ($type) {
                $sql .= " AND work_type = ?";
                $params[] = $type;
            }
            
            // Filtre par interruption
            if ($downtime !== '') {
                $sql .= " AND causes_downtime = ?";
                $params[] = (int)$downtime;
            }
            
            // Filtre par date
            if ($dateFrom) {
                $sql .= " AND (planned_start_date >= ? OR actual_start_date >= ?)";
                $params[] = $dateFrom;
                $params[] = $dateFrom;
            }
            if ($dateTo) {
                $sql .= " AND (planned_start_date <= ? OR actual_start_date <= ?)";
                $params[] = $dateTo . ' 23:59:59';
                $params[] = $dateTo . ' 23:59:59';
            }
            
            // Filtre upcoming (travaux à venir)
            if ($upcoming) {
                $sql .= " AND planned_start_date >= NOW() AND planned_start_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)";
            }
            
            // Recherche
            if ($search) {
                $sql .= " AND (title LIKE ? OR description LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Tri
            $sql .= " ORDER BY 
                CASE status 
                    WHEN 'in_progress' THEN 1 
                    WHEN 'planned' THEN 2 
                    WHEN 'unplanned' THEN 3 
                    WHEN 'completed' THEN 4 
                    ELSE 5 
                END,
                CASE WHEN planned_start_date IS NULL THEN 1 ELSE 0 END,
                planned_start_date ASC,
                created_at DESC
            ";
            
            // Limite
            $sql .= " LIMIT " . (int)$limit;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $works = $stmt->fetchAll();
            
            sendSuccess($works);
            break;
        
        // ==================== SINGLE WORK ====================
        case 'work':
            $id = $_GET['id'] ?? 0;
            
            $stmt = $pdo->prepare("SELECT * FROM toolbox_works WHERE id = ?");
            $stmt->execute([$id]);
            $work = $stmt->fetch();
            
            if (!$work) {
                sendError('Travail non trouvé', 404);
            }
            
            sendSuccess($work);
            break;
        
        // ==================== CREATE WORK ====================
        case 'work_create':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $stmt = $pdo->prepare("
                    INSERT INTO toolbox_works (
                        title, description, work_type, status, priority,
                        causes_downtime, estimated_downtime_minutes, affected_services,
                        planned_start_date, planned_end_date,
                        target_version, from_version,
                        work_notes, assigned_to,
                        created_by, created_by_name
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $data['title'] ?? '',
                    $data['description'] ?? '',
                    $data['work_type'] ?? 'maintenance',
                    $data['status'] ?? 'unplanned',
                    $data['priority'] ?? 'medium',
                    $data['causes_downtime'] ?? 0,
                    $data['estimated_downtime_minutes'] ?? null,
                    $data['affected_services'] ?? null,
                    $data['planned_start_date'] ?? null,
                    $data['planned_end_date'] ?? null,
                    $data['target_version'] ?? null,
                    $data['from_version'] ?? null,
                    $data['work_notes'] ?? null,
                    $data['assigned_to'] ?? null,
                    $currentUser['id'],
                    $currentUser['name']
                ]);
                
                sendSuccess([
                    'id' => $pdo->lastInsertId(),
                    'message' => 'Travail créé avec succès'
                ]);
            }
            break;
        
        // ==================== UPDATE WORK ====================
        case 'work_update':
            if ($method === 'PUT') {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;
                
                if (!$id) {
                    sendError('ID du travail manquant', 400);
                }
                
                // Si le statut passe à "in_progress", enregistrer la date de début réelle
                $actualStartDate = null;
                if (($data['status'] ?? '') === 'in_progress') {
                    $checkStmt = $pdo->prepare("SELECT status, actual_start_date FROM toolbox_works WHERE id = ?");
                    $checkStmt->execute([$id]);
                    $current = $checkStmt->fetch();
                    
                    if ($current && $current['status'] !== 'in_progress' && !$current['actual_start_date']) {
                        $actualStartDate = date('Y-m-d H:i:s');
                    }
                }
                
                // Si le statut passe à "completed", enregistrer la date de fin réelle
                $actualEndDate = null;
                if (($data['status'] ?? '') === 'completed') {
                    $checkStmt = $pdo->prepare("SELECT status, actual_end_date FROM toolbox_works WHERE id = ?");
                    $checkStmt->execute([$id]);
                    $current = $checkStmt->fetch();
                    
                    if ($current && $current['status'] !== 'completed' && !$current['actual_end_date']) {
                        $actualEndDate = date('Y-m-d H:i:s');
                    }
                }
                
                $sql = "
                    UPDATE toolbox_works SET
                        title = ?,
                        description = ?,
                        work_type = ?,
                        status = ?,
                        priority = ?,
                        causes_downtime = ?,
                        estimated_downtime_minutes = ?,
                        affected_services = ?,
                        planned_start_date = ?,
                        planned_end_date = ?,
                        target_version = ?,
                        from_version = ?,
                        work_notes = ?,
                        assigned_to = ?
                ";
                
                $params = [
                    $data['title'] ?? '',
                    $data['description'] ?? '',
                    $data['work_type'] ?? 'maintenance',
                    $data['status'] ?? 'unplanned',
                    $data['priority'] ?? 'medium',
                    $data['causes_downtime'] ?? 0,
                    $data['estimated_downtime_minutes'] ?? null,
                    $data['affected_services'] ?? null,
                    $data['planned_start_date'] ?? null,
                    $data['planned_end_date'] ?? null,
                    $data['target_version'] ?? null,
                    $data['from_version'] ?? null,
                    $data['work_notes'] ?? null,
                    $data['assigned_to'] ?? null
                ];
                
                if ($actualStartDate) {
                    $sql .= ", actual_start_date = ?";
                    $params[] = $actualStartDate;
                }
                
                if ($actualEndDate) {
                    $sql .= ", actual_end_date = ?";
                    $params[] = $actualEndDate;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $id;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                sendSuccess(['message' => 'Travail mis à jour']);
            }
            break;
        
        // ==================== DELETE WORK ====================
        case 'work_delete':
            if ($method === 'DELETE') {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? $_GET['id'] ?? 0;
                
                if (!$id) {
                    sendError('ID du travail manquant', 400);
                }
                
                $stmt = $pdo->prepare("DELETE FROM toolbox_works WHERE id = ?");
                $stmt->execute([$id]);
                
                sendSuccess(['message' => 'Travail supprimé']);
            }
            break;
        
        // ==================== COMPLETE WORK ====================
        case 'work_complete':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? 0;
                
                if (!$id) {
                    sendError('ID du travail manquant', 400);
                }
                
                $stmt = $pdo->prepare("
                    UPDATE toolbox_works SET
                        status = 'completed',
                        actual_end_date = NOW(),
                        completion_notes = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['completion_notes'] ?? null,
                    $id
                ]);
                
                sendSuccess(['message' => 'Travail marqué comme terminé']);
            }
            break;
        
        // ==================== PLATFORM STATUS HISTORY ====================
        case 'platform_status_history':
            $limit = $_GET['limit'] ?? 10;
            
            $stmt = $pdo->prepare("
                SELECT * FROM toolbox_platform_status_history 
                ORDER BY changed_at DESC 
                LIMIT ?
            ");
            $stmt->execute([(int)$limit]);
            $history = $stmt->fetchAll();
            
            sendSuccess($history);
            break;
        
        default:
            sendError('Action inconnue: ' . $action, 400);
    }
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        sendError($e->getMessage(), 500);
    } else {
        sendError('Une erreur est survenue', 500);
    }
}

// ==================== HELPER FUNCTIONS ====================

function sendSuccess($data) {
    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>