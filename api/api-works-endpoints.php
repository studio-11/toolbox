<?php
/**
 * IFEN Toolbox - Endpoints API pour Brique "Travaux & Mise à jour"
 * =================================================================
 * À ajouter dans api/api.php
 */

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
            'status' => 'operational',
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
            $currentStmt = $pdo->query("SELECT status FROM toolbox_platform_status LIMIT 1");
            $current = $currentStmt->fetch();
            
            // Mettre à jour le statut
            $stmt = $pdo->prepare("
                UPDATE toolbox_platform_status SET
                    status = ?,
                    status_message = ?,
                    next_maintenance_date = ?,
                    next_maintenance_message = ?,
                    updated_by = ?
                WHERE id = 1
            ");
            $stmt->execute([
                $data['status'] ?? 'operational',
                $data['status_message'] ?? null,
                $data['next_maintenance_date'] ?? null,
                $data['next_maintenance_message'] ?? null,
                $currentUser['id']
            ]);
            
            // Ajouter à l'historique si statut changé
            if ($current && $current['status'] !== $data['status']) {
                $histStmt = $pdo->prepare("
                    INSERT INTO toolbox_platform_status_history 
                    (previous_status, new_status, status_message, changed_by, changed_by_name)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $histStmt->execute([
                    $current['status'],
                    $data['status'],
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

// ==================== SUBSCRIBE TO WORK NOTIFICATIONS ====================

case 'work_subscribe':
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $workId = $data['work_id'] ?? 0;
        
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO toolbox_works_notifications 
            (work_id, user_id, user_email, notification_type)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $workId,
            $currentUser['id'],
            $currentUser['email'],
            $data['notification_type'] ?? 'all'
        ]);
        
        sendSuccess(['message' => 'Abonnement enregistré']);
    }
    break;

case 'work_unsubscribe':
    if ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $workId = $data['work_id'] ?? $_GET['work_id'] ?? 0;
        
        $stmt = $pdo->prepare("
            DELETE FROM toolbox_works_notifications 
            WHERE work_id = ? AND user_id = ?
        ");
        $stmt->execute([$workId, $currentUser['id']]);
        
        sendSuccess(['message' => 'Désabonnement effectué']);
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
