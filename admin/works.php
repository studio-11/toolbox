<?php
/**
 * IFEN Toolbox Admin - Travaux & Statut Plateforme
 * Adapté à la structure BDD existante
 */
$pageTitle = 'Travaux';
require_once __DIR__ . '/includes/header.php';

$pdo = getDbConnection();

$workTypes = [
    'maintenance' => 'Maintenance',
    'upgrade' => 'Mise à jour',
    'feature' => 'Nouvelle fonctionnalité',
    'bugfix' => 'Correction de bug',
    'security' => 'Sécurité',
    'performance' => 'Performance',
    'other' => 'Autre',
];

$workStatuses = [
    'planned' => ['label' => 'Planifié', 'class' => 'planned'],
    'unplanned' => ['label' => 'Non planifié', 'class' => 'unplanned'],
    'in_progress' => ['label' => 'En cours', 'class' => 'in-progress'],
    'completed' => ['label' => 'Terminé', 'class' => 'completed'],
    'cancelled' => ['label' => 'Annulé', 'class' => 'cancelled'],
];

$priorities = [
    'low' => ['label' => 'Basse', 'class' => 'low'],
    'medium' => ['label' => 'Moyenne', 'class' => 'medium'],
    'high' => ['label' => 'Haute', 'class' => 'high'],
    'critical' => ['label' => 'Critique', 'class' => 'critical'],
];

$platformStatuses = [
    'operational' => ['label' => 'Opérationnel', 'class' => 'operational', 'icon' => 'check-circle'],
    'maintenance' => ['label' => 'Maintenance', 'class' => 'maintenance', 'icon' => 'tools'],
    'upgrading' => ['label' => 'Mise à jour', 'class' => 'upgrading', 'icon' => 'sync'],
    'partial_outage' => ['label' => 'Panne partielle', 'class' => 'partial-outage', 'icon' => 'exclamation-triangle'],
    'major_outage' => ['label' => 'Panne majeure', 'class' => 'major-outage', 'icon' => 'times-circle'],
];

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$error = '';

// Statut plateforme actuel
$platformStatus = null;
try {
    $stmt = $pdo->query("SELECT * FROM toolbox_platform_status ORDER BY id LIMIT 1");
    $platformStatus = $stmt->fetch();
} catch (PDOException $e) {}

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    // Mise à jour statut plateforme
    if ($postAction === 'update_platform_status') {
        $newStatus = $_POST['platform_status'] ?? '';
        $message = trim($_POST['status_message'] ?? '');
        
        if (array_key_exists($newStatus, $platformStatuses)) {
            try {
                $oldStatus = $platformStatus['status'] ?? 'operational';
                
                // Update status
                if ($platformStatus) {
                    $stmt = $pdo->prepare("UPDATE toolbox_platform_status SET status = ?, status_message = ?, status_updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$newStatus, $message, $platformStatus['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO toolbox_platform_status (platform_name, platform_version, status, status_message) VALUES ('LearningSphere', '1.0', ?, ?)");
                    $stmt->execute([$newStatus, $message]);
                }
                
                // Log history
                $adminName = $_SESSION['admin_name'] ?? 'Admin';
                $stmt = $pdo->prepare("INSERT INTO toolbox_platform_status_history (previous_status, new_status, status_message, changed_by_name) VALUES (?, ?, ?, ?)");
                $stmt->execute([$oldStatus, $newStatus, $message, $adminName]);
                
                setFlash('success', 'Statut plateforme mis à jour.');
                header('Location: ' . url('works.php')); exit;
            } catch (PDOException $e) { $error = $e->getMessage(); }
        }
    }
    
    // Créer/Modifier un travail
    if ($postAction === 'save_work') {
        $d = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'work_type' => $_POST['work_type'] ?? 'other',
            'status' => $_POST['status'] ?? 'planned',
            'priority' => $_POST['priority'] ?? 'medium',
            'affected_services' => trim($_POST['affected_services'] ?? ''),
            'causes_downtime' => isset($_POST['causes_downtime']) ? 1 : 0,
            'estimated_downtime_minutes' => intval($_POST['estimated_downtime_minutes'] ?? 0) ?: null,
            'planned_start_date' => $_POST['planned_start_date'] ?: null,
            'planned_end_date' => $_POST['planned_end_date'] ?: null,
            'target_version' => trim($_POST['target_version'] ?? ''),
            'from_version' => trim($_POST['from_version'] ?? ''),
            'work_notes' => trim($_POST['work_notes'] ?? ''),
        ];
        
        if (empty($d['title'])) {
            $error = 'Le titre est obligatoire.';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE toolbox_works SET title=?, description=?, work_type=?, status=?, priority=?, affected_services=?, causes_downtime=?, estimated_downtime_minutes=?, planned_start_date=?, planned_end_date=?, target_version=?, from_version=?, work_notes=?, updated_at=NOW() WHERE id=?");
                    $stmt->execute([$d['title'], $d['description'], $d['work_type'], $d['status'], $d['priority'], $d['affected_services'], $d['causes_downtime'], $d['estimated_downtime_minutes'], $d['planned_start_date'], $d['planned_end_date'], $d['target_version'], $d['from_version'], $d['work_notes'], $id]);
                    
                    if ($d['status'] === 'completed') {
                        $pdo->prepare("UPDATE toolbox_works SET actual_end_date = NOW() WHERE id = ? AND actual_end_date IS NULL")->execute([$id]);
                    }
                    if ($d['status'] === 'in_progress') {
                        $pdo->prepare("UPDATE toolbox_works SET actual_start_date = NOW() WHERE id = ? AND actual_start_date IS NULL")->execute([$id]);
                    }
                } else {
                    $adminName = $_SESSION['admin_name'] ?? 'Admin';
                    $stmt = $pdo->prepare("INSERT INTO toolbox_works (title, description, work_type, status, priority, affected_services, causes_downtime, estimated_downtime_minutes, planned_start_date, planned_end_date, target_version, from_version, work_notes, created_by_name) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$d['title'], $d['description'], $d['work_type'], $d['status'], $d['priority'], $d['affected_services'], $d['causes_downtime'], $d['estimated_downtime_minutes'], $d['planned_start_date'], $d['planned_end_date'], $d['target_version'], $d['from_version'], $d['work_notes'], $adminName]);
                }
                setFlash('success', 'Travail enregistré.'); header('Location: ' . url('works.php')); exit;
            } catch (PDOException $e) { $error = $e->getMessage(); }
        }
    }
    
    // Supprimer
    if ($postAction === 'delete_work' && $id > 0) {
        try {
            $pdo->prepare("DELETE FROM toolbox_works_notifications WHERE work_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM toolbox_works WHERE id = ?")->execute([$id]);
            setFlash('success', 'Supprimé.'); header('Location: ' . url('works.php')); exit;
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }
}

// Edition
$currentWork = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM toolbox_works WHERE id = ?");
    $stmt->execute([$id]);
    $currentWork = $stmt->fetch();
    if (!$currentWork) { setFlash('error', 'Non trouvé.'); header('Location: ' . url('works.php')); exit; }
}

// Liste et filtres
$filterStatus = $_GET['filter_status'] ?? '';
$filterType = $_GET['filter_type'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM toolbox_works WHERE 1=1";
$params = [];
if ($filterStatus) { $sql .= " AND status = ?"; $params[] = $filterStatus; }
if ($filterType) { $sql .= " AND work_type = ?"; $params[] = $filterType; }
if ($search) { $sql .= " AND (title LIKE ? OR description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= " ORDER BY CASE status WHEN 'in_progress' THEN 1 WHEN 'planned' THEN 2 WHEN 'unplanned' THEN 3 ELSE 4 END, CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END, planned_start_date DESC";

$works = [];
try { $stmt = $pdo->prepare($sql); $stmt->execute($params); $works = $stmt->fetchAll(); } catch (PDOException $e) { $error = $e->getMessage(); }

// Historique statut
$statusHistory = [];
try { $stmt = $pdo->query("SELECT * FROM toolbox_platform_status_history ORDER BY changed_at DESC LIMIT 10"); $statusHistory = $stmt->fetchAll(); } catch (PDOException $e) {}
?>

<?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div><?php endif; ?>

<!-- Statut Plateforme -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-server"></i> Statut de la Plateforme</h3>
    </div>
    <div class="card-body">
        <?php $ps = $platformStatuses[$platformStatus['status'] ?? 'operational'] ?? $platformStatuses['operational']; ?>
        <div class="platform-status-banner status-<?= $ps['class'] ?>" style="display:flex;align-items:center;gap:1rem;padding:1rem;border-radius:var(--border-radius);margin-bottom:1rem;">
            <i class="fas fa-<?= $ps['icon'] ?>" style="font-size:2rem;"></i>
            <div>
                <strong style="font-size:1.25rem;"><?= $ps['label'] ?></strong>
                <?php if ($platformStatus['status_message'] ?? ''): ?><p style="margin:0.25rem 0 0;"><?= e($platformStatus['status_message']) ?></p><?php endif; ?>
                <small>Dernière mise à jour: <?= $platformStatus ? formatRelativeDate($platformStatus['status_updated_at']) : '-' ?></small>
            </div>
        </div>
        
        <form method="POST" class="d-flex gap-1 flex-wrap">
            <input type="hidden" name="action" value="update_platform_status">
            <select name="platform_status" id="platform_status_select" class="form-control" style="width:auto;">
                <?php foreach ($platformStatuses as $k => $s): ?><option value="<?= $k ?>" <?= ($platformStatus['status'] ?? '') === $k ? 'selected' : '' ?>><?= $s['label'] ?></option><?php endforeach; ?>
            </select>
            <input type="text" name="status_message" id="status_message_input" class="form-control" placeholder="Message (optionnel)" value="<?= e($platformStatus['status_message'] ?? '') ?>" style="flex:1;min-width:200px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Mettre à jour</button>
        </form>
        
        <script>
        // Textes suggérés par statut
        const statusMessages = {
            'operational': 'Tous les systèmes fonctionnent normalement.',
            'maintenance': 'Maintenance en cours. Certains services peuvent être temporairement indisponibles.',
            'upgrading': 'Mise à jour en cours. La plateforme sera de nouveau disponible dans quelques minutes.',
            'partial_outage': 'Certains services rencontrent des difficultés. Nous travaillons à résoudre le problème.',
            'major_outage': 'La plateforme est actuellement indisponible. Nos équipes travaillent activement à la résolution.'
        };
        
        document.getElementById('platform_status_select').addEventListener('change', function() {
            const messageInput = document.getElementById('status_message_input');
            const suggestedMessage = statusMessages[this.value] || '';
            if (messageInput.value === '' || Object.values(statusMessages).includes(messageInput.value)) {
                messageInput.value = suggestedMessage;
            }
        });
        </script>
        
        <?php if ($statusHistory): ?>
        <details style="margin-top:1rem;"><summary style="cursor:pointer;color:var(--text-muted);"><i class="fas fa-history"></i> Historique récent</summary>
            <table class="table" style="margin-top:0.5rem;font-size:0.875rem;">
                <thead><tr><th>Date</th><th>Changement</th><th>Message</th><th>Par</th></tr></thead>
                <tbody>
                <?php foreach ($statusHistory as $h): ?>
                    <tr><td><small><?= formatRelativeDate($h['changed_at']) ?></small></td><td><?= $platformStatuses[$h['previous_status']]['label'] ?? $h['previous_status'] ?> → <strong><?= $platformStatuses[$h['new_status']]['label'] ?? $h['new_status'] ?></strong></td><td><?= e($h['status_message'] ?: '-') ?></td><td><?= e($h['changed_by_name'] ?: '-') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </details>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-<?= $action === 'edit' ? 'edit' : 'plus' ?>"></i> <?= $action === 'edit' ? 'Modifier' : 'Nouveau' ?> travail</h3>
        <a href="<?= url('works.php') ?>" class="btn btn-sm btn-outline"><i class="fas fa-times"></i></a>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="save_work">
            
            <div class="form-group"><label class="form-label required">Titre</label><input type="text" name="title" class="form-control" required value="<?= e($currentWork['title'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= e($currentWork['description'] ?? '') ?></textarea></div>
            
            <div class="form-row">
                <div class="form-group"><label class="form-label">Type</label><select name="work_type" class="form-control"><?php foreach ($workTypes as $k => $v): ?><option value="<?= $k ?>" <?= ($currentWork['work_type'] ?? 'maintenance') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label class="form-label">Statut</label><select name="status" class="form-control"><?php foreach ($workStatuses as $k => $s): ?><option value="<?= $k ?>" <?= ($currentWork['status'] ?? 'planned') === $k ? 'selected' : '' ?>><?= $s['label'] ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label class="form-label">Priorité</label><select name="priority" class="form-control"><?php foreach ($priorities as $k => $p): ?><option value="<?= $k ?>" <?= ($currentWork['priority'] ?? 'medium') === $k ? 'selected' : '' ?>><?= $p['label'] ?></option><?php endforeach; ?></select></div>
            </div>
            
            <div class="form-row">
                <div class="form-group"><label class="form-label">Début planifié</label><input type="datetime-local" name="planned_start_date" class="form-control" value="<?= $currentWork['planned_start_date'] ? date('Y-m-d\TH:i', strtotime($currentWork['planned_start_date'])) : '' ?>"></div>
                <div class="form-group"><label class="form-label">Fin planifiée</label><input type="datetime-local" name="planned_end_date" class="form-control" value="<?= $currentWork['planned_end_date'] ? date('Y-m-d\TH:i', strtotime($currentWork['planned_end_date'])) : '' ?>"></div>
            </div>
            
            <div class="form-group"><label class="form-label">Services affectés</label><input type="text" name="affected_services" class="form-control" value="<?= e($currentWork['affected_services'] ?? '') ?>" placeholder="Ex: Moodle, BigBlueButton"></div>
            
            <div class="form-row">
                <div class="form-group"><label class="form-check"><input type="checkbox" name="causes_downtime" value="1" <?= ($currentWork['causes_downtime'] ?? 0) ? 'checked' : '' ?>> Cause une interruption de service</label></div>
                <div class="form-group"><label class="form-label">Durée estimée (min)</label><input type="number" name="estimated_downtime_minutes" class="form-control" value="<?= $currentWork['estimated_downtime_minutes'] ?? '' ?>" min="0"></div>
            </div>
            
            <div class="form-row">
                <div class="form-group"><label class="form-label">Version cible</label><input type="text" name="target_version" class="form-control" value="<?= e($currentWork['target_version'] ?? '') ?>" placeholder="Ex: 4.5.0"></div>
                <div class="form-group"><label class="form-label">Version actuelle</label><input type="text" name="from_version" class="form-control" value="<?= e($currentWork['from_version'] ?? '') ?>" placeholder="Ex: 4.4.2"></div>
            </div>
            
            <div class="form-group"><label class="form-label">Notes techniques</label><textarea name="work_notes" class="form-control" rows="2"><?= e($currentWork['work_notes'] ?? '') ?></textarea></div>
            
            <div class="btn-group"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button><a href="<?= url('works.php') ?>" class="btn btn-outline">Annuler</a></div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Liste des travaux -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-hard-hat"></i> Travaux <span class="badge badge-secondary"><?= count($works) ?></span></h3>
        <a href="<?= url('works.php?action=new') ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Nouveau</a>
    </div>
    <div class="card-body" style="border-bottom: 1px solid var(--border-color);">
        <form method="GET" class="toolbar" style="margin-bottom: 0;">
            <div class="search-box"><i class="fas fa-search"></i><input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?= e($search) ?>"></div>
            <div class="filters">
                <select name="filter_status" class="form-control" onchange="this.form.submit()"><option value="">Statut</option><?php foreach ($workStatuses as $k => $s): ?><option value="<?= $k ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= $s['label'] ?></option><?php endforeach; ?></select>
                <select name="filter_type" class="form-control" onchange="this.form.submit()"><option value="">Type</option><?php foreach ($workTypes as $k => $v): ?><option value="<?= $k ?>" <?= $filterType === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
                <?php if ($filterStatus || $filterType || $search): ?><a href="<?= url('works.php') ?>" class="btn btn-outline"><i class="fas fa-times"></i></a><?php endif; ?>
            </div>
        </form>
    </div>
    <?php if (empty($works)): ?><div class="empty-state"><i class="fas fa-hard-hat"></i><h3>Aucun travail</h3></div>
    <?php else: ?>
        <table class="table"><thead><tr><th>Travail</th><th>Type</th><th>Statut</th><th>Priorité</th><th>Planifié</th><th>Downtime</th><th class="text-right">Actions</th></tr></thead><tbody>
            <?php foreach ($works as $w): $ws = $workStatuses[$w['status']] ?? ['label' => $w['status'], 'class' => 'secondary']; $wp = $priorities[$w['priority']] ?? ['label' => $w['priority'], 'class' => 'secondary']; ?>
                <tr>
                    <td><strong><?= e($w['title']) ?></strong><?php if ($w['affected_services']): ?><br><small class="text-muted"><i class="fas fa-server"></i> <?= e($w['affected_services']) ?></small><?php endif; ?></td>
                    <td><span class="badge badge-secondary"><?= $workTypes[$w['work_type']] ?? $w['work_type'] ?></span></td>
                    <td><span class="badge badge-<?= $ws['class'] ?>"><?= $ws['label'] ?></span></td>
                    <td><span class="badge badge-<?= $wp['class'] ?>"><?= $wp['label'] ?></span></td>
                    <td><small><?= $w['planned_start_date'] ? formatDate($w['planned_start_date'], 'd/m/Y H:i') : '-' ?></small></td>
                    <td><?php if ($w['causes_downtime']): ?><span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> <?= $w['estimated_downtime_minutes'] ? $w['estimated_downtime_minutes'].'min' : 'Oui' ?></span><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                    <td><div class="table-actions"><a href="<?= url("works.php?action=edit&id={$w['id']}") ?>" class="btn btn-sm btn-icon btn-outline"><i class="fas fa-edit"></i></a><form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?');"><input type="hidden" name="action" value="delete_work"><input type="hidden" name="id" value="<?= $w['id'] ?>"><button class="btn btn-sm btn-icon btn-outline" style="color:var(--color-danger);"><i class="fas fa-trash"></i></button></form></div></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table>
    <?php endif; ?>
</div>

<style>
.platform-status-banner.status-operational { background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #155724; }
.platform-status-banner.status-maintenance { background: linear-gradient(135deg, #fff3cd, #ffeaa7); color: #856404; }
.platform-status-banner.status-upgrading { background: linear-gradient(135deg, #cce5ff, #b8daff); color: #004085; }
.platform-status-banner.status-partial-outage, .platform-status-banner.status-major-outage { background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #721c24; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
