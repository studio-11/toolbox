<?php
/**
 * IFEN Toolbox Admin - API
 * ========================
 * Endpoint pour les opérations AJAX
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

// Vérifier l'authentification pour les requêtes API
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$pdo = getDbConnection();
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        
        // ============================================
        // PLATFORM STATUS
        // ============================================
        
        case 'get_platform_status':
            $stmt = $pdo->query("SELECT * FROM toolbox_platform_status WHERE id = 1");
            $status = $stmt->fetch();
            echo json_encode(['success' => true, 'data' => $status]);
            break;
            
        case 'update_platform_status':
            $newStatus = $_POST['status'] ?? '';
            $message = $_POST['message'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE toolbox_platform_status SET status = ?, message = ?, updated_at = NOW() WHERE id = 1");
            $stmt->execute([$newStatus, $message]);
            
            // Log history
            $admin = getCurrentAdmin();
            $pdo->prepare("INSERT INTO toolbox_platform_status_history (status, message, changed_by) VALUES (?, ?, ?)")
                ->execute([$newStatus, $message, $admin['name']]);
            
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
            break;
        
        // ============================================
        // WORKS
        // ============================================
        
        case 'get_works':
            $status = $_GET['status'] ?? '';
            $type = $_GET['type'] ?? '';
            
            $sql = "SELECT * FROM toolbox_works WHERE 1=1";
            $params = [];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $works = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $works]);
            break;
            
        case 'update_work_status':
            $id = intval($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            if (!$id || !$status) {
                throw new Exception('Paramètres manquants');
            }
            
            $stmt = $pdo->prepare("UPDATE toolbox_works SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            // Si completed, mettre actual_end
            if ($status === 'completed') {
                $pdo->prepare("UPDATE toolbox_works SET actual_end = NOW() WHERE id = ? AND actual_end IS NULL")
                    ->execute([$id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
            break;
            
        case 'delete_work':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID manquant');
            
            $pdo->prepare("DELETE FROM toolbox_works WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Travail supprimé']);
            break;
        
        // ============================================
        // TOOLS
        // ============================================
        
        case 'get_tools':
            $category = $_GET['category_id'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT t.*, c.name as category_name 
                    FROM toolbox_tools t 
                    LEFT JOIN toolbox_categories c ON t.category_id = c.id 
                    WHERE 1=1";
            $params = [];
            
            if ($category) {
                $sql .= " AND t.category_id = ?";
                $params[] = $category;
            }
            if ($status) {
                $sql .= " AND t.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY t.sort_order, t.name";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tools = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $tools]);
            break;
            
        case 'toggle_tool_featured':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID manquant');
            
            $pdo->prepare("UPDATE toolbox_tools SET is_featured = NOT is_featured WHERE id = ?")
                ->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Statut featured modifié']);
            break;
            
        case 'update_tool_order':
            $id = intval($_POST['id'] ?? 0);
            $order = intval($_POST['sort_order'] ?? 0);
            
            if (!$id) throw new Exception('ID manquant');
            
            $pdo->prepare("UPDATE toolbox_tools SET sort_order = ? WHERE id = ?")
                ->execute([$order, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Ordre mis à jour']);
            break;
            
        case 'delete_tool':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID manquant');
            
            $pdo->prepare("DELETE FROM toolbox_tools WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Outil supprimé']);
            break;
        
        // ============================================
        // CATEGORIES
        // ============================================
        
        case 'get_categories':
            $stmt = $pdo->query("SELECT c.*, COUNT(t.id) as tools_count 
                                FROM toolbox_categories c 
                                LEFT JOIN toolbox_tools t ON c.id = t.category_id 
                                GROUP BY c.id 
                                ORDER BY c.sort_order, c.name");
            $categories = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $categories]);
            break;
            
        case 'delete_category':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID manquant');
            
            // Vérifier les outils liés
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM toolbox_tools WHERE category_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cette catégorie contient des outils');
            }
            
            $pdo->prepare("DELETE FROM toolbox_categories WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Catégorie supprimée']);
            break;
        
        // ============================================
        // IDEAS
        // ============================================
        
        case 'get_ideas':
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT * FROM toolbox_ideas WHERE 1=1";
            $params = [];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY votes DESC, created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $ideas = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $ideas]);
            break;
            
        case 'update_idea_status':
            $id = intval($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $response = $_POST['admin_response'] ?? '';
            
            if (!$id || !$status) throw new Exception('Paramètres manquants');
            
            $stmt = $pdo->prepare("UPDATE toolbox_ideas SET status = ?, admin_response = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $response, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Idée mise à jour']);
            break;
            
        case 'delete_idea':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID manquant');
            
            $pdo->prepare("DELETE FROM toolbox_idea_votes WHERE idea_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM toolbox_ideas WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Idée supprimée']);
            break;
        
        // ============================================
        // BETA TESTING
        // ============================================
        
        case 'get_beta_programs':
            $stmt = $pdo->query("SELECT bp.*, t.name as tool_name,
                                (SELECT COUNT(*) FROM toolbox_beta_testers bt WHERE bt.program_id = bp.id) as testers_count
                                FROM toolbox_beta_programs bp 
                                LEFT JOIN toolbox_tools t ON bp.tool_id = t.id 
                                ORDER BY bp.created_at DESC");
            $programs = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $programs]);
            break;
            
        case 'get_beta_testers':
            $programId = intval($_GET['program_id'] ?? 0);
            if (!$programId) throw new Exception('ID programme manquant');
            
            $stmt = $pdo->prepare("SELECT * FROM toolbox_beta_testers WHERE program_id = ? ORDER BY created_at DESC");
            $stmt->execute([$programId]);
            $testers = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $testers]);
            break;
            
        case 'update_tester_status':
            $id = intval($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            if (!$id || !$status) throw new Exception('Paramètres manquants');
            
            $pdo->prepare("UPDATE toolbox_beta_testers SET status = ? WHERE id = ?")
                ->execute([$status, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Statut testeur mis à jour']);
            break;
            
        case 'delete_beta_program':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID manquant');
            
            $pdo->prepare("DELETE FROM toolbox_beta_testers WHERE program_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM toolbox_beta_feedback WHERE program_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM toolbox_beta_programs WHERE id = ?")->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Programme supprimé']);
            break;
        
        // ============================================
        // STATISTICS
        // ============================================
        
        case 'get_stats':
            $stats = [
                'tools' => 0,
                'categories' => 0,
                'ideas' => 0,
                'ideas_pending' => 0,
                'works' => 0,
                'works_in_progress' => 0,
                'beta_programs' => 0,
                'beta_testers' => 0,
            ];
            
            try {
                $stats['tools'] = $pdo->query("SELECT COUNT(*) FROM toolbox_tools")->fetchColumn();
                $stats['categories'] = $pdo->query("SELECT COUNT(*) FROM toolbox_categories")->fetchColumn();
                $stats['ideas'] = $pdo->query("SELECT COUNT(*) FROM toolbox_ideas")->fetchColumn();
                $stats['ideas_pending'] = $pdo->query("SELECT COUNT(*) FROM toolbox_ideas WHERE status = 'submitted'")->fetchColumn();
                $stats['works'] = $pdo->query("SELECT COUNT(*) FROM toolbox_works")->fetchColumn();
                $stats['works_in_progress'] = $pdo->query("SELECT COUNT(*) FROM toolbox_works WHERE status = 'in_progress'")->fetchColumn();
                $stats['beta_programs'] = $pdo->query("SELECT COUNT(*) FROM toolbox_beta_programs")->fetchColumn();
                $stats['beta_testers'] = $pdo->query("SELECT COUNT(*) FROM toolbox_beta_testers")->fetchColumn();
            } catch (PDOException $e) {
                // Tables might not exist yet
            }
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
        
        // ============================================
        // DEFAULT
        // ============================================
        
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action inconnue: ' . $action]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
