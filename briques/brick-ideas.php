<?php
/**
 * BRIQUE 2 : IDÉES & VOTES
 * ========================
 * Fichier : briques/brick-ideas.php
 * 
 * Modifications:
 * - Nouveaux types: Activité de cours, Ressource de cours, Fonctionnalité plateforme, Autres
 * - Bouton "Programmer" visible uniquement pour admin
 */

// Vérifier si admin pour afficher le bouton Programmer
$showProgrammerButton = isAdmin();
?>

<div class="brick-container brick-ideas" id="brick-ideas">
    <!-- Header de la brique -->
    <div class="brick-header">
        <div class="brick-title-section">
            <h2 class="brick-title">
                <i class="fas fa-lightbulb"></i>
                Idées & Votes
            </h2>
            <p class="brick-subtitle">Proposez vos idées et votez pour celles de la communauté</p>
        </div>
        
        <div class="brick-actions">
            <button class="btn btn-accent btn-lg" id="btn-new-idea">
                <i class="fas fa-plus"></i> Proposer une idée
            </button>
        </div>
    </div>
    
    <!-- Onglets -->
    <div class="brick-tabs">
        <button class="tab-btn active" data-tab="pending">
            <i class="fas fa-inbox"></i>
            Idées en attente
            <span class="tab-count" id="count-pending">0</span>
        </button>
        <button class="tab-btn" data-tab="planned">
            <i class="fas fa-calendar-check"></i>
            En programmation
            <span class="tab-count" id="count-planned">0</span>
        </button>
    </div>
    
    <!-- Contenu onglet : Idées en attente -->
    <div class="tab-content active" id="tab-pending">
        <!-- Filtres -->
        <div class="brick-filters">
            <div class="filter-group">
                <label for="ideas-filter-type">Type</label>
                <select id="ideas-filter-type" class="filter-select">
                    <option value="">Tous les types</option>
                    <option value="course_activity">📚 Activité de cours</option>
                    <option value="course_resource">📄 Ressource de cours</option>
                    <option value="platform_feature">⚙️ Fonctionnalité plateforme</option>
                    <option value="other">📌 Autres</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="ideas-filter-sort">Trier par</label>
                <select id="ideas-filter-sort" class="filter-select">
                    <option value="votes">🔥 Plus votées</option>
                    <option value="recent">🕐 Plus récentes</option>
                    <option value="oldest">📅 Plus anciennes</option>
                </select>
            </div>
        </div>
        
        <!-- Liste des idées en attente -->
        <div class="ideas-list" id="ideas-pending-list">
            <div class="loading-state">
                <div class="spinner"></div>
                <p>Chargement des idées...</p>
            </div>
        </div>
        
        <!-- État vide -->
        <div class="empty-state" id="ideas-pending-empty" style="display: none;">
            <i class="fas fa-lightbulb"></i>
            <h3>Aucune idée pour le moment</h3>
            <p>Soyez le premier à proposer une idée d'outil !</p>
            <button class="btn btn-accent" onclick="BrickIdeas.showNewIdeaModal()">
                <i class="fas fa-plus"></i> Proposer une idée
            </button>
        </div>
        
        <?php if ($showProgrammerButton): ?>
        <!-- Bouton Programmer - ADMIN UNIQUEMENT -->
        <div class="admin-actions" id="admin-programmer-section" style="margin-top: 30px; padding-top: 20px; border-top: 2px dashed var(--gray-200);">
            <div class="admin-actions-header">
                <span class="admin-badge-inline">
                    <i class="fas fa-shield-alt"></i> Admin
                </span>
                <span>Actions administrateur</span>
            </div>
            <button class="btn btn-primary" id="btn-open-programmer" onclick="BrickIdeas.openProgrammerPanel()">
                <i class="fas fa-calendar-plus"></i> Programmer une idée
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Contenu onglet : En programmation (Kanban) -->
    <div class="tab-content" id="tab-planned">
        <div class="kanban-container" id="kanban-container">
            <!-- Colonnes Kanban -->
            <div class="kanban-column" data-phase="analysis">
                <div class="kanban-column-header">
                    <span class="phase-icon">🔍</span>
                    <h4>Analyse</h4>
                    <span class="phase-count">0</span>
                </div>
                <div class="kanban-cards"></div>
            </div>
            
            <div class="kanban-column" data-phase="design">
                <div class="kanban-column-header">
                    <span class="phase-icon">🎨</span>
                    <h4>Design</h4>
                    <span class="phase-count">0</span>
                </div>
                <div class="kanban-cards"></div>
            </div>
            
            <div class="kanban-column" data-phase="development">
                <div class="kanban-column-header">
                    <span class="phase-icon">💻</span>
                    <h4>Développement</h4>
                    <span class="phase-count">0</span>
                </div>
                <div class="kanban-cards"></div>
            </div>
            
            <div class="kanban-column" data-phase="testing">
                <div class="kanban-column-header">
                    <span class="phase-icon">🧪</span>
                    <h4>Tests</h4>
                    <span class="phase-count">0</span>
                </div>
                <div class="kanban-cards"></div>
            </div>
            
            <div class="kanban-column" data-phase="deployment">
                <div class="kanban-column-header">
                    <span class="phase-icon">🚀</span>
                    <h4>Déploiement</h4>
                    <span class="phase-count">0</span>
                </div>
                <div class="kanban-cards"></div>
            </div>
            
            <div class="kanban-column" data-phase="completed">
                <div class="kanban-column-header">
                    <span class="phase-icon">✅</span>
                    <h4>Terminé</h4>
                    <span class="phase-count">0</span>
                </div>
                <div class="kanban-cards"></div>
            </div>
        </div>
        
        <!-- État vide Kanban -->
        <div class="empty-state" id="ideas-planned-empty" style="display: none;">
            <i class="fas fa-calendar-check"></i>
            <h3>Aucune idée en programmation</h3>
            <p>Les idées populaires seront bientôt planifiées pour développement.</p>
        </div>
    </div>
</div>

<!-- Template pour une carte idée (liste) -->
<template id="template-idea-card">
    <div class="idea-card" data-idea-id="">
        <div class="idea-card-vote">
            <button class="vote-btn" title="Voter pour cette idée">
                <i class="fas fa-chevron-up"></i>
            </button>
            <span class="vote-count">0</span>
            <span class="vote-label">votes</span>
        </div>
        
        <div class="idea-card-content">
            <div class="idea-header">
                <span class="idea-type-badge">Type</span>
                <span class="idea-date">Il y a X jours</span>
            </div>
            
            <h3 class="idea-title">Titre de l'idée</h3>
            <p class="idea-problem">Problème à résoudre...</p>
            
            <div class="idea-footer">
                <span class="idea-author">
                    <i class="fas fa-user"></i>
                    <span>Auteur</span>
                </span>
                <button class="btn btn-sm idea-details-btn">
                    <i class="fas fa-info-circle"></i> Détails
                </button>
                <?php if ($showProgrammerButton): ?>
                <button class="btn btn-sm btn-primary idea-plan-btn admin-only-btn" title="Programmer cette idée">
                    <i class="fas fa-calendar-plus"></i> Programmer
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</template>

<!-- Template pour le modal nouvelle idée -->
<template id="template-new-idea-modal">
    <div class="new-idea-form">
        <form id="form-new-idea">
            <div class="form-group">
                <label for="idea-title-input">Titre de l'idée *</label>
                <input type="text" id="idea-title-input" name="title" class="form-input" 
                       placeholder="Ex: Outil de création de quiz interactifs" required>
            </div>
            
            <div class="form-group">
                <label for="idea-type-input">Type d'idée *</label>
                <select id="idea-type-input" name="type" class="form-input" required>
                    <option value="">-- Sélectionner un type --</option>
                    <option value="course_activity">📚 Activité de cours</option>
                    <option value="course_resource">📄 Ressource de cours</option>
                    <option value="platform_feature">⚙️ Fonctionnalité plateforme</option>
                    <option value="other">📌 Autres</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="idea-problem-input">Quel problème cela résout-il ? *</label>
                <textarea id="idea-problem-input" name="problem" class="form-textarea" rows="3" 
                          placeholder="Décrivez le besoin ou le problème que cette idée adresse..." required></textarea>
            </div>
            
            <div class="form-group">
                <label for="idea-details-input">Détails supplémentaires (optionnel)</label>
                <textarea id="idea-details-input" name="details" class="form-textarea" rows="3"
                          placeholder="Ajoutez des précisions, exemples, liens utiles..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Soumettre l'idée
                </button>
            </div>
        </form>
    </div>
</template>

<!-- Template pour une carte Kanban -->
<template id="template-kanban-card">
    <div class="kanban-card" data-idea-id="">
        <div class="kanban-card-header">
            <span class="idea-type-badge">Type</span>
            <span class="priority-badge">Priorité</span>
        </div>
        
        <h4 class="kanban-card-title">Titre de l'idée</h4>
        
        <div class="kanban-card-progress">
            <div class="progress-bar">
                <div class="progress-fill" style="width: 0%"></div>
            </div>
            <span class="progress-text">0%</span>
        </div>
        
        <div class="kanban-card-meta">
            <span class="assigned-to" title="Assigné à">
                <i class="fas fa-user"></i>
                <span>Non assigné</span>
            </span>
            <span class="due-date" title="Date prévue">
                <i class="fas fa-calendar"></i>
                <span>--</span>
            </span>
        </div>
        
        <div class="kanban-card-actions">
            <button class="btn btn-xs btn-secondary" title="Voir détails">
                <i class="fas fa-eye"></i>
            </button>
            <?php if ($showProgrammerButton): ?>
            <button class="btn btn-xs btn-secondary admin-only-btn" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
</template>

<style>
/* Styles pour les éléments admin */
.admin-actions {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(32, 22, 77, 0.05) 100%);
    padding: 20px;
    border-radius: var(--border-radius);
    border: 2px dashed var(--accent);
}

.admin-actions-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    font-weight: 600;
    color: var(--primary);
}

.admin-badge-inline {
    background: var(--accent);
    color: var(--primary);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}

.admin-only-btn {
    border: 2px solid var(--accent) !important;
}

.admin-only-btn:hover {
    background: var(--accent) !important;
    color: var(--primary) !important;
}
</style>
