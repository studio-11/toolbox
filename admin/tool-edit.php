<?php
/**
 * IFEN Toolbox Admin - Édition/Création d'outil
 */

require_once(__DIR__ . '/includes/auth.php');
requireAdmin();

$pdo = getDBConnection();

// Déterminer si création ou édition
$tool_id = $_GET['id'] ?? null;
$is_edit = !empty($tool_id);

// Charger l'outil si édition
$tool = null;
$features = [];

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM toolbox_tools WHERE id = ?");
    $stmt->execute([$tool_id]);
    $tool = $stmt->fetch();
    
    if (!$tool) {
        header('Location: tools.php?error=not_found');
        exit;
    }
    
    // Charger les features
    $stmt = $pdo->prepare("SELECT * FROM toolbox_tool_features WHERE tool_id = ? ORDER BY display_order");
    $stmt->execute([$tool_id]);
    $features = $stmt->fetchAll();
}

// Charger les catégories
$stmt = $pdo->query("SELECT * FROM toolbox_categories ORDER BY name");
$categories = $stmt->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation
    $errors = [];
    
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $type = $_POST['type'] ?? 'course';
    $category_id = $_POST['category_id'] ?? null;
    $status = $_POST['status'] ?? 'stable';
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $short_description = trim($_POST['short_description'] ?? '');
    $long_description = trim($_POST['long_description'] ?? '');
    $audience = trim($_POST['audience'] ?? '');
    $time_to_use = trim($_POST['time_to_use'] ?? '');
    $difficulty = $_POST['difficulty'] ?? 'intermediaire';
    $icon = trim($_POST['icon'] ?? '');
    $gradient = trim($_POST['gradient'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $tutorial_text = trim($_POST['tutorial_text'] ?? '');
    $code_snippet = trim($_POST['code_snippet'] ?? '');
    
    // Validation basique
    if (empty($name)) $errors[] = 'Le nom est obligatoire';
    if (empty($slug)) $errors[] = 'Le slug est obligatoire';
    if (empty($short_description)) $errors[] = 'La description courte est obligatoire';
    
    if (empty($errors)) {
        try {
            if ($is_edit) {
                // UPDATE
                $sql = "UPDATE toolbox_tools SET
                        name = ?, slug = ?, type = ?, category_id = ?, status = ?, is_hot = ?,
                        short_description = ?, long_description = ?, audience = ?, time_to_use = ?,
                        difficulty = ?, icon = ?, gradient = ?, video_url = ?,
                        tutorial_text = ?, code_snippet = ?, updated_at = NOW()
                        WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $name, $slug, $type, $category_id, $status, $is_hot,
                    $short_description, $long_description, $audience, $time_to_use,
                    $difficulty, $icon, $gradient, $video_url,
                    $tutorial_text, $code_snippet, $tool_id
                ]);
                
                $saved_id = $tool_id;
                $action = 'updated';
            } else {
                // INSERT
                $sql = "INSERT INTO toolbox_tools 
                        (name, slug, type, category_id, status, is_hot, short_description, long_description,
                         audience, time_to_use, difficulty, icon, gradient, video_url, tutorial_text, code_snippet)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $name, $slug, $type, $category_id, $status, $is_hot, $short_description, $long_description,
                    $audience, $time_to_use, $difficulty, $icon, $gradient, $video_url, $tutorial_text, $code_snippet
                ]);
                
                $saved_id = $pdo->lastInsertId();
                $action = 'created';
            }
            
            // Gérer les features
            if (!empty($_POST['features'])) {
                // Supprimer les anciennes features
                $stmt = $pdo->prepare("DELETE FROM toolbox_tool_features WHERE tool_id = ?");
                $stmt->execute([$saved_id]);
                
                // Ajouter les nouvelles
                $stmt = $pdo->prepare("INSERT INTO toolbox_tool_features (tool_id, feature_text, display_order) VALUES (?, ?, ?)");
                
                foreach ($_POST['features'] as $index => $feature) {
                    if (!empty(trim($feature))) {
                        $stmt->execute([$saved_id, trim($feature), $index + 1]);
                    }
                }
            }
            
            logAdminAction('tool_' . $action, ['tool_id' => $saved_id, 'tool_name' => $name]);
            
            header('Location: tools.php?success=' . $action);
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la sauvegarde: ' . $e->getMessage();
        }
    }
}

$page_title = $is_edit ? 'Éditer l\'outil' : 'Nouvel outil';
require_once(__DIR__ . '/includes/header.php');
?>

<form method="POST" action="" id="tool-form">
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Erreurs :</strong>
                <ul style="margin: 5px 0 0 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Informations de base -->
    <div class="section">
        <h3 class="section-title">
            <i class="fas fa-info-circle"></i>
            Informations de base
        </h3>
        
        <div class="form-row">
            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="name">Nom de l'outil <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-control" required 
                       value="<?php echo htmlspecialchars($tool['name'] ?? ''); ?>"
                       placeholder="Ex: Auto Link Activity">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="slug">Slug (URL) <span class="required">*</span></label>
                <input type="text" id="slug" name="slug" class="form-control" required 
                       value="<?php echo htmlspecialchars($tool['slug'] ?? ''); ?>"
                       placeholder="auto-link-activity">
                <div class="form-help">Utilisé dans l'URL, sans espaces ni caractères spéciaux</div>
            </div>
            
            <div class="form-group">
                <label for="type">Type <span class="required">*</span></label>
                <select id="type" name="type" class="form-control" required>
                    <option value="course" <?php echo ($tool['type'] ?? '') === 'course' ? 'selected' : ''; ?>>
                        🧩 Outil de cours
                    </option>
                    <option value="platform" <?php echo ($tool['type'] ?? '') === 'platform' ? 'selected' : ''; ?>>
                        ⚙️ Fonctionnalité plateforme
                    </option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="category_id">Catégorie</label>
                <select id="category_id" name="category_id" class="form-control">
                    <option value="">— Non catégorisé —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo ($tool['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="status">Statut</label>
                <select id="status" name="status" class="form-control">
                    <option value="stable" <?php echo ($tool['status'] ?? 'stable') === 'stable' ? 'selected' : ''; ?>>Stable</option>
                    <option value="beta" <?php echo ($tool['status'] ?? '') === 'beta' ? 'selected' : ''; ?>>Beta</option>
                    <option value="new" <?php echo ($tool['status'] ?? '') === 'new' ? 'selected' : ''; ?>>Nouveau</option>
                    <option value="deprecated" <?php echo ($tool['status'] ?? '') === 'deprecated' ? 'selected' : ''; ?>>Déprécié</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="difficulty">Difficulté</label>
                <select id="difficulty" name="difficulty" class="form-control">
                    <option value="debutant" <?php echo ($tool['difficulty'] ?? '') === 'debutant' ? 'selected' : ''; ?>>Débutant</option>
                    <option value="intermediaire" <?php echo ($tool['difficulty'] ?? 'intermediaire') === 'intermediaire' ? 'selected' : ''; ?>>Intermédiaire</option>
                    <option value="avance" <?php echo ($tool['difficulty'] ?? '') === 'avance' ? 'selected' : ''; ?>>Avancé</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>&nbsp;</label>
                <div style="display: flex; align-items: center; height: 46px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="is_hot" value="1" 
                               <?php echo ($tool['is_hot'] ?? 0) ? 'checked' : ''; ?>
                               style="width: 20px; height: 20px;">
                        <span style="font-weight: 600; color: var(--dark);">🔥 Outil HOT</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Descriptions -->
    <div class="section">
        <h3 class="section-title">
            <i class="fas fa-file-alt"></i>
            Descriptions
        </h3>
        
        <div class="form-group">
            <label for="short_description">Description courte <span class="required">*</span></label>
            <textarea id="short_description" name="short_description" class="form-control" rows="2" required
                      placeholder="Description d'une ou deux lignes qui apparaît sur la carte"><?php echo htmlspecialchars($tool['short_description'] ?? ''); ?></textarea>
            <div class="form-help">Maximum 200 caractères recommandé</div>
        </div>
        
        <div class="form-group">
            <label for="long_description">Description détaillée</label>
            <textarea id="long_description" name="long_description" class="form-control" rows="5"
                      placeholder="Description complète qui apparaît dans la modal"><?php echo htmlspecialchars($tool['long_description'] ?? ''); ?></textarea>
        </div>
    </div>
    
    <!-- Features / Points clés -->
    <div class="section">
        <h3 class="section-title">
            <i class="fas fa-list-check"></i>
            Points clés / Features
        </h3>
        
        <div id="features-container">
            <?php if (!empty($features)): ?>
                <?php foreach ($features as $index => $feature): ?>
                    <div class="form-group feature-item">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span class="feature-handle" style="cursor: move; color: var(--gray);">
                                <i class="fas fa-grip-vertical"></i>
                            </span>
                            <input type="text" name="features[]" class="form-control" 
                                   value="<?php echo htmlspecialchars($feature['feature_text']); ?>"
                                   placeholder="Ex: Plus besoin de créer le lien à la main">
                            <button type="button" class="btn btn-danger btn-icon btn-sm" onclick="removeFeature(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="form-group feature-item">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span class="feature-handle" style="cursor: move; color: var(--gray);">
                            <i class="fas fa-grip-vertical"></i>
                        </span>
                        <input type="text" name="features[]" class="form-control" 
                               placeholder="Ex: Plus besoin de créer le lien à la main">
                        <button type="button" class="btn btn-danger btn-icon btn-sm" onclick="removeFeature(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <button type="button" class="btn btn-secondary" onclick="addFeature()">
            <i class="fas fa-plus"></i> Ajouter un point clé
        </button>
    </div>
    
    <!-- Métadonnées -->
    <div class="section">
        <h3 class="section-title">
            <i class="fas fa-tags"></i>
            Métadonnées
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="audience">Public cible</label>
                <input type="text" id="audience" name="audience" class="form-control" 
                       value="<?php echo htmlspecialchars($tool['audience'] ?? ''); ?>"
                       placeholder="Ex: Formateurs (éditeur du cours)">
            </div>
            
            <div class="form-group">
                <label for="time_to_use">Temps d'utilisation</label>
                <input type="text" id="time_to_use" name="time_to_use" class="form-control" 
                       value="<?php echo htmlspecialchars($tool['time_to_use'] ?? ''); ?>"
                       placeholder="Ex: 2 minutes">
            </div>
        </div>
    </div>
    
    <!-- Apparence -->
    <div class="section">
        <h3 class="section-title">
            <i class="fas fa-palette"></i>
            Apparence
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="icon">Icône Font Awesome</label>
                <input type="text" id="icon" name="icon" class="form-control" 
                       value="<?php echo htmlspecialchars($tool['icon'] ?? ''); ?>"
                       placeholder="fa-link">
                <div class="form-help">
                    Voir : <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com/icons</a>
                </div>
            </div>
            
            <div class="form-group">
                <label for="gradient">Dégradé CSS</label>
                <input type="text" id="gradient" name="gradient" class="form-control" 
                       value="<?php echo htmlspecialchars($tool['gradient'] ?? ''); ?>"
                       placeholder="linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
            </div>
        </div>
    </div>
    
    <!-- Tutoriel & Code -->
    <div class="section">
        <h3 class="section-title">
            <i class="fas fa-book"></i>
            Tutoriel & Code
        </h3>
        
        <div class="form-group">
            <label for="video_url">URL vidéo (YouTube, etc.)</label>
            <input type="url" id="video_url" name="video_url" class="form-control" 
                   value="<?php echo htmlspecialchars($tool['video_url'] ?? ''); ?>"
                   placeholder="https://www.youtube.com/watch?v=...">
        </div>
        
        <div class="form-group">
            <label for="tutorial_text">Texte du tutoriel</label>
            <textarea id="tutorial_text" name="tutorial_text" class="form-control" rows="8"
                      placeholder="Instructions détaillées pour utiliser l'outil..."><?php echo htmlspecialchars($tool['tutorial_text'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="code_snippet">Code snippet</label>
            <textarea id="code_snippet" name="code_snippet" class="form-control" rows="12"
                      placeholder="Code HTML/CSS/JS à copier..."><?php echo htmlspecialchars($tool['code_snippet'] ?? ''); ?></textarea>
        </div>
    </div>
    
    <!-- Actions -->
    <div style="display: flex; gap: 15px; justify-content: flex-end; padding: 20px 0;">
        <a href="tools.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> <?php echo $is_edit ? 'Mettre à jour' : 'Créer l\'outil'; ?>
        </button>
    </div>
</form>

<script>
// Auto-génération du slug
document.getElementById('name').addEventListener('input', function() {
    const slug = this.value
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Supprimer les accents
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    
    document.getElementById('slug').value = slug;
});

// Gestion des features
function addFeature() {
    const container = document.getElementById('features-container');
    const newFeature = document.createElement('div');
    newFeature.className = 'form-group feature-item';
    newFeature.innerHTML = `
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="feature-handle" style="cursor: move; color: var(--gray);">
                <i class="fas fa-grip-vertical"></i>
            </span>
            <input type="text" name="features[]" class="form-control" 
                   placeholder="Ex: Plus besoin de créer le lien à la main">
            <button type="button" class="btn btn-danger btn-icon btn-sm" onclick="removeFeature(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(newFeature);
}

function removeFeature(button) {
    button.closest('.feature-item').remove();
}

// Validation avant envoi
document.getElementById('tool-form').addEventListener('submit', function(e) {
    if (!AdminUtils.validateForm('tool-form')) {
        e.preventDefault();
    }
});
</script>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>