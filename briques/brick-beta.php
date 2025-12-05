<?php
/**
 * BRIQUE 3 : BETA TESTING
 * =======================
 * Fichier : briques/brick-beta.php
 * 
 * Modifications:
 * - Affichage du lien vers le cours Moodle (courseid)
 * - Infos détaillées dans l'icône (i): description, période, testeurs, retours, inscription
 * - Après inscription: prochaines étapes + lien vers le cours
 */

$currentUser = getCurrentUser();
$isLoggedIn = isLoggedIn();
?>

<div class="brick-container brick-beta" id="brick-beta">
    <!-- Header de la brique -->
    <div class="brick-header">
        <div class="brick-title-section">
            <h2 class="brick-title">
                <i class="fas fa-flask"></i>
                Beta Testing
            </h2>
            <p class="brick-subtitle">Testez les nouveaux outils en avant-première et aidez-nous à les améliorer</p>
        </div>
        
        <div class="brick-actions">
            <div class="beta-legend">
                <span class="legend-item">
                    <span class="legend-dot active"></span> Actif
                </span>
                <span class="legend-item">
                    <span class="legend-dot ending"></span> Bientôt terminé
                </span>
            </div>
        </div>
    </div>
    
    <!-- Info banner -->
    <div class="beta-info-banner">
        <i class="fas fa-info-circle"></i>
        <div class="banner-content">
            <strong>Comment ça marche ?</strong>
            <p>Inscrivez-vous aux outils en beta, testez-les sur la plateforme et partagez vos retours pour améliorer les futures versions.</p>
        </div>
    </div>
    
    <!-- Liste des outils en beta -->
    <div class="beta-tools-grid" id="beta-tools-grid">
        <div class="loading-state">
            <div class="spinner"></div>
            <p>Chargement des outils en beta...</p>
        </div>
    </div>
    
    <!-- État vide -->
    <div class="empty-state" id="beta-empty" style="display: none;">
        <i class="fas fa-flask"></i>
        <h3>Aucun outil en beta test</h3>
        <p>Revenez bientôt pour découvrir les prochains outils à tester !</p>
    </div>
</div>

<!-- Template pour une carte beta tool -->
<template id="template-beta-card">
    <div class="beta-card" data-tool-id="">
        <div class="beta-card-header">
            <div class="beta-icon">
                <i class="fas fa-flask"></i>
            </div>
            <div class="beta-status">
                <span class="beta-badge">BETA</span>
                <span class="beta-version">v0.0.0</span>
            </div>
            <!-- Bouton info avec détails -->
            <button class="beta-info-btn" title="Voir les informations détaillées">
                <i class="fas fa-info-circle"></i>
            </button>
        </div>
        
        <div class="beta-card-body">
            <h3 class="beta-name">Nom de l'outil</h3>
            <p class="beta-description">Description de l'outil en beta...</p>
            
            <div class="beta-countdown">
                <i class="fas fa-clock"></i>
                <span class="countdown-text">X jours restants</span>
            </div>
            
            <div class="beta-stats">
                <div class="beta-stat">
                    <i class="fas fa-users"></i>
                    <span class="stat-value testers-count">0</span>
                    <span class="stat-label">testeurs</span>
                </div>
                <div class="beta-stat">
                    <i class="fas fa-comments"></i>
                    <span class="stat-value feedback-count">0</span>
                    <span class="stat-label">retours</span>
                </div>
            </div>
        </div>
        
        <div class="beta-card-footer">
            <!-- Bouton d'inscription (affiché si non inscrit) -->
            <button class="btn btn-primary beta-register-btn">
                <i class="fas fa-user-plus"></i> S'inscrire
            </button>
            <!-- Bouton feedback (affiché si inscrit) -->
            <button class="btn btn-secondary beta-feedback-btn" style="display: none;">
                <i class="fas fa-comment-dots"></i> Feedback
            </button>
            <!-- Lien vers le cours Moodle (affiché si inscrit et courseid existe) -->
            <a href="#" class="btn btn-accent beta-course-link" style="display: none;" target="_blank">
                <i class="fas fa-external-link-alt"></i> Accéder à l'outil
            </a>
        </div>
        
        <!-- Badge inscrit -->
        <div class="beta-registered-badge" style="display: none;">
            <i class="fas fa-check-circle"></i>
            Vous êtes inscrit !
        </div>
    </div>
</template>

<!-- Template pour le popup d'informations détaillées -->
<template id="template-beta-info-popup">
    <div class="beta-info-popup">
        <div class="info-section">
            <h4><i class="fas fa-align-left"></i> Description</h4>
            <p class="info-description">Description détaillée...</p>
        </div>
        
        <div class="info-section">
            <h4><i class="fas fa-calendar-alt"></i> Période de test</h4>
            <div class="info-dates">
                <span class="date-item">
                    <i class="fas fa-play"></i> Début: <strong class="info-start-date">--</strong>
                </span>
                <span class="date-item">
                    <i class="fas fa-flag-checkered"></i> Fin: <strong class="info-end-date">--</strong>
                </span>
            </div>
        </div>
        
        <div class="info-section">
            <h4><i class="fas fa-chart-bar"></i> Statistiques</h4>
            <div class="info-stats">
                <div class="info-stat">
                    <span class="stat-number info-testers">0</span>
                    <span class="stat-text">Testeurs inscrits</span>
                </div>
                <div class="info-stat">
                    <span class="stat-number info-feedbacks">0</span>
                    <span class="stat-text">Retours reçus</span>
                </div>
            </div>
        </div>
        
        <div class="info-section info-registration-status">
            <h4><i class="fas fa-user-check"></i> Votre inscription</h4>
            <div class="registration-status not-registered">
                <i class="fas fa-times-circle"></i>
                <span>Vous n'êtes pas inscrit à ce beta test</span>
            </div>
            <div class="registration-status registered" style="display: none;">
                <i class="fas fa-check-circle"></i>
                <span>Vous êtes inscrit à ce beta test</span>
            </div>
        </div>
        
        <div class="info-section info-course-access" style="display: none;">
            <h4><i class="fas fa-graduation-cap"></i> Accès au test</h4>
            <a href="#" class="btn btn-accent btn-block info-course-link" target="_blank">
                <i class="fas fa-external-link-alt"></i> Accédez à l'outil et testez-le
            </a>
        </div>
    </div>
</template>

<!-- Template pour le modal après inscription -->
<template id="template-registration-success">
    <div class="registration-success-content">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h3>Inscription confirmée !</h3>
        <p>Vous êtes maintenant inscrit au beta test de <strong class="tool-name">cet outil</strong>.</p>
        
        <div class="next-steps">
            <h4><i class="fas fa-list-ol"></i> Prochaines étapes</h4>
            <ol>
                <li>
                    <i class="fas fa-external-link-alt"></i>
                    <span>Accédez à l'outil via le lien ci-dessous</span>
                </li>
                <li>
                    <i class="fas fa-vial"></i>
                    <span>Testez les différentes fonctionnalités</span>
                </li>
                <li>
                    <i class="fas fa-sticky-note"></i>
                    <span>Notez ce qui fonctionne bien et les problèmes rencontrés</span>
                </li>
                <li>
                    <i class="fas fa-comment-dots"></i>
                    <span>Partagez vos retours via le bouton "Feedback"</span>
                </li>
            </ol>
        </div>
        
        <div class="course-access-section" style="display: none;">
            <a href="#" class="btn btn-accent btn-lg btn-block course-access-link" target="_blank">
                <i class="fas fa-rocket"></i> Accédez à l'outil et testez-le
            </a>
        </div>
        
        <div class="no-course-section" style="display: none;">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                L'accès à l'outil sera disponible prochainement. Vous recevrez une notification.
            </div>
        </div>
    </div>
</template>

<!-- Template pour le modal feedback -->
<template id="template-feedback-modal">
    <div class="feedback-modal-content">
        <div class="feedback-tabs">
            <button class="feedback-tab active" data-tab="submit">
                <i class="fas fa-pen"></i> Donner un avis
            </button>
            <button class="feedback-tab" data-tab="view">
                <i class="fas fa-list"></i> Voir les retours
            </button>
        </div>
        
        <!-- Tab: Donner un avis -->
        <div class="feedback-tab-content active" id="feedback-submit">
            <form class="feedback-form" id="feedback-form">
                <div class="form-group">
                    <label>Type de retour</label>
                    <div class="feedback-type-selector">
                        <label class="type-option">
                            <input type="radio" name="feedback_type" value="bug">
                            <span class="type-badge type-bug">
                                <i class="fas fa-bug"></i> Bug
                            </span>
                        </label>
                        <label class="type-option">
                            <input type="radio" name="feedback_type" value="suggestion">
                            <span class="type-badge type-suggestion">
                                <i class="fas fa-lightbulb"></i> Suggestion
                            </span>
                        </label>
                        <label class="type-option">
                            <input type="radio" name="feedback_type" value="question">
                            <span class="type-badge type-question">
                                <i class="fas fa-question-circle"></i> Question
                            </span>
                        </label>
                        <label class="type-option">
                            <input type="radio" name="feedback_type" value="praise" checked>
                            <span class="type-badge type-praise">
                                <i class="fas fa-thumbs-up"></i> Bravo
                            </span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Votre note</label>
                    <div class="star-rating" id="star-rating">
                        <i class="fas fa-star" data-rating="1"></i>
                        <i class="fas fa-star" data-rating="2"></i>
                        <i class="fas fa-star" data-rating="3"></i>
                        <i class="fas fa-star" data-rating="4"></i>
                        <i class="fas fa-star" data-rating="5"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="feedback-title">Titre</label>
                    <input type="text" id="feedback-title" name="title" class="form-input" placeholder="Résumez votre retour..." required>
                </div>
                
                <div class="form-group">
                    <label for="feedback-content">Détails</label>
                    <textarea id="feedback-content" name="content" class="form-textarea" rows="5" placeholder="Décrivez votre expérience, le problème rencontré ou votre suggestion..." required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Envoyer
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Tab: Voir les retours -->
        <div class="feedback-tab-content" id="feedback-view">
            <div class="feedback-filters">
                <select id="feedback-filter-type" class="filter-select">
                    <option value="">Tous les types</option>
                    <option value="bug">🐛 Bugs</option>
                    <option value="suggestion">💡 Suggestions</option>
                    <option value="question">❓ Questions</option>
                    <option value="praise">👍 Bravo</option>
                </select>
            </div>
            
            <div class="feedback-list" id="feedback-list"></div>
            
            <div class="feedback-empty" id="feedback-empty" style="display: none;">
                <i class="fas fa-comments"></i>
                <p>Aucun retour pour le moment. Soyez le premier à donner votre avis !</p>
            </div>
        </div>
    </div>
</template>

<!-- Template pour un item feedback -->
<template id="template-feedback-item">
    <div class="feedback-item" data-feedback-id="">
        <div class="feedback-item-header">
            <span class="feedback-type-badge">Type</span>
            <div class="feedback-rating"></div>
            <span class="feedback-date">Date</span>
        </div>
        
        <h4 class="feedback-item-title">Titre</h4>
        <p class="feedback-item-content">Contenu...</p>
        
        <div class="feedback-item-author">
            <i class="fas fa-user"></i>
            <span>Auteur</span>
        </div>
        
        <div class="feedback-item-response" style="display: none;">
            <div class="response-header">
                <i class="fas fa-reply"></i>
                <strong>Réponse de l'équipe</strong>
            </div>
            <p class="response-content">Réponse admin...</p>
        </div>
        
        <div class="feedback-item-status">
            <span class="status-badge">Statut</span>
        </div>
    </div>
</template>

<style>
/* Styles additionnels pour beta */
.beta-info-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    border: 2px solid var(--secondary);
    color: var(--secondary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    z-index: 5;
}

.beta-info-btn:hover {
    background: var(--secondary);
    color: white;
    transform: scale(1.1);
}

.beta-card-header {
    position: relative;
}

.beta-registered-badge {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background: var(--success);
    color: white;
    padding: 8px;
    text-align: center;
    font-weight: 600;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

/* Info popup styles */
.beta-info-popup {
    padding: 10px 0;
}

.info-section {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--gray-200);
}

.info-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.info-section h4 {
    font-size: 0.9rem;
    color: var(--primary);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-section h4 i {
    color: var(--secondary);
}

.info-dates {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.date-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: var(--gray-600);
}

.date-item i {
    color: var(--primary);
    width: 16px;
}

.info-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.info-stat {
    text-align: center;
    padding: 15px;
    background: var(--gray-50);
    border-radius: 10px;
}

.stat-number {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary);
}

.stat-text {
    font-size: 0.8rem;
    color: var(--gray-500);
}

.registration-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    border-radius: 8px;
}

.registration-status.not-registered {
    background: var(--gray-100);
    color: var(--gray-600);
}

.registration-status.not-registered i {
    color: var(--gray-400);
}

.registration-status.registered {
    background: var(--success-light);
    color: var(--success);
}

.registration-status.registered i {
    color: var(--success);
}

.btn-block {
    display: flex;
    width: 100%;
    justify-content: center;
}

/* Success modal */
.registration-success-content {
    text-align: center;
}

.registration-success-content .success-icon {
    width: 80px;
    height: 80px;
    background: var(--success-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.registration-success-content .success-icon i {
    font-size: 2.5rem;
    color: var(--success);
}

.registration-success-content h3 {
    color: var(--primary);
    margin-bottom: 10px;
}

.registration-success-content > p {
    color: var(--gray-600);
    margin-bottom: 25px;
}

.next-steps {
    text-align: left;
    background: var(--gray-50);
    padding: 20px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
}

.next-steps h4 {
    font-size: 1rem;
    color: var(--primary);
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.next-steps ol {
    list-style: none;
    padding: 0;
    margin: 0;
    counter-reset: step;
}

.next-steps li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
    padding-left: 5px;
}

.next-steps li i {
    color: var(--secondary);
    margin-top: 3px;
    width: 18px;
    flex-shrink: 0;
}

.course-access-section {
    margin-top: 20px;
}

.alert-info {
    background: var(--info-light);
    color: #1e40af;
    padding: 15px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}
</style>
