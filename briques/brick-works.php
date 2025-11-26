<!-- ============================================== -->
<!-- BRIQUE TRAVAUX - TEMPLATES LIGHTBOX UNIQUEMENT -->
<!-- ============================================== -->

<!-- Template : Modal liste des travaux -->
<template id="template-works-list-modal">
    <div class="works-modal-content">
        <!-- En-tête avec statut plateforme -->
        <div class="works-modal-header">
            <div class="platform-status-card" id="modal-platform-status">
                <div class="platform-status-main">
                    <div class="platform-status-indicator">
                        <span class="status-dot operational"></span>
                        <span class="status-text">Chargement...</span>
                    </div>
                    <div class="platform-version">
                        <span class="version-label">LearningSphere</span>
                        <span class="version-value">--</span>
                    </div>
                </div>
                <div class="platform-status-details">
                    <div class="platform-moodle">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Moodle <span class="moodle-version">--</span></span>
                    </div>
                    <div class="platform-next-maintenance" id="next-maintenance-info" style="display: none;">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Prochaine maintenance : <span class="next-date">--</span></span>
                    </div>
                </div>
                <div class="platform-message" id="platform-message-display" style="display: none;"></div>
            </div>
        </div>
        
        <!-- Statistiques rapides -->
        <div class="works-quick-stats">
            <div class="quick-stat" data-filter="in_progress">
                <span class="quick-stat-value" id="modal-stat-in-progress">0</span>
                <span class="quick-stat-label">En cours</span>
            </div>
            <div class="quick-stat" data-filter="planned">
                <span class="quick-stat-value" id="modal-stat-planned">0</span>
                <span class="quick-stat-label">Planifiés</span>
            </div>
            <div class="quick-stat" data-filter="unplanned">
                <span class="quick-stat-value" id="modal-stat-unplanned">0</span>
                <span class="quick-stat-label">Non planifiés</span>
            </div>
            <div class="quick-stat" data-filter="completed">
                <span class="quick-stat-value" id="modal-stat-completed">0</span>
                <span class="quick-stat-label">Terminés</span>
            </div>
        </div>
        
        <!-- Filtres -->
        <div class="works-filters">
            <div class="filter-row">
                <div class="filter-group filter-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="works-search" placeholder="Rechercher un travail..." class="form-control">
                </div>
                <div class="filter-group">
                    <select id="works-filter-status" class="form-control">
                        <option value="">Tous les statuts</option>
                        <option value="in_progress">En cours</option>
                        <option value="planned">Planifiés</option>
                        <option value="unplanned">Non planifiés</option>
                        <option value="completed">Terminés</option>
                        <option value="cancelled">Annulés</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="works-filter-type" class="form-control">
                        <option value="">Tous les types</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="upgrade">Mise à jour</option>
                        <option value="feature">Nouvelle fonctionnalité</option>
                        <option value="bugfix">Correction de bug</option>
                        <option value="security">Sécurité</option>
                        <option value="performance">Performance</option>
                        <option value="other">Autre</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="checkbox-filter">
                        <input type="checkbox" id="works-filter-downtime">
                        <span><i class="fas fa-exclamation-triangle"></i> Avec interruption</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Liste des travaux -->
        <div class="works-list" id="works-list-container">
            <div class="loading-placeholder">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Chargement des travaux...</span>
            </div>
        </div>
    </div>
</template>

<!-- Template : Carte travail dans la liste -->
<template id="template-work-card">
    <div class="work-card" data-work-id="">
        <div class="work-card-status-bar"></div>
        <div class="work-card-content">
            <div class="work-card-header">
                <div class="work-card-badges">
                    <span class="badge badge-type"></span>
                    <span class="badge badge-status"></span>
                    <span class="badge badge-priority"></span>
                </div>
                <span class="work-downtime-indicator" style="display: none;">
                    <i class="fas fa-clock"></i>
                    <span class="downtime-duration"></span>
                </span>
            </div>
            <h4 class="work-card-title"></h4>
            <p class="work-card-description"></p>
            <div class="work-card-meta">
                <span class="work-date">
                    <i class="fas fa-calendar"></i>
                    <span class="date-value"></span>
                </span>
                <span class="work-version" style="display: none;">
                    <i class="fas fa-code-branch"></i>
                    <span class="version-value"></span>
                </span>
            </div>
        </div>
    </div>
</template>

<!-- Template : Modal détails d'un travail -->
<template id="template-work-details-modal">
    <div class="work-details-content">
        <div class="work-details-header">
            <div class="work-details-badges">
                <span class="badge badge-type badge-lg"></span>
                <span class="badge badge-status badge-lg"></span>
                <span class="badge badge-priority badge-lg"></span>
            </div>
            <h2 class="work-details-title"></h2>
        </div>
        
        <div class="work-details-body">
            <div class="work-details-section">
                <h4><i class="fas fa-align-left"></i> Description</h4>
                <p class="work-description-text"></p>
            </div>
            
            <div class="work-details-grid">
                <div class="work-detail-item">
                    <span class="detail-label"><i class="fas fa-calendar-plus"></i> Date planifiée</span>
                    <span class="detail-value planned-dates"></span>
                </div>
                <div class="work-detail-item actual-dates-item" style="display: none;">
                    <span class="detail-label"><i class="fas fa-calendar-check"></i> Date réelle</span>
                    <span class="detail-value actual-dates"></span>
                </div>
                <div class="work-detail-item version-item" style="display: none;">
                    <span class="detail-label"><i class="fas fa-code-branch"></i> Version</span>
                    <span class="detail-value version-info"></span>
                </div>
                <div class="work-detail-item downtime-item" style="display: none;">
                    <span class="detail-label"><i class="fas fa-power-off"></i> Interruption service</span>
                    <span class="detail-value downtime-info"></span>
                </div>
                <div class="work-detail-item services-item" style="display: none;">
                    <span class="detail-label"><i class="fas fa-server"></i> Services affectés</span>
                    <span class="detail-value services-list"></span>
                </div>
            </div>
            
            <div class="work-details-section notes-section" style="display: none;">
                <h4><i class="fas fa-sticky-note"></i> Notes techniques</h4>
                <div class="work-notes-text"></div>
            </div>
            
            <div class="work-details-section completion-section" style="display: none;">
                <h4><i class="fas fa-check-circle"></i> Notes de complétion</h4>
                <div class="work-completion-text"></div>
            </div>
        </div>
        
        <div class="work-details-footer">
            <button type="button" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </button>
        </div>
    </div>
</template>
