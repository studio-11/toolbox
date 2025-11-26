<?php
/**
 * BRIQUE 3 : BETA TESTING
 * =======================
 * Fichier : briques/brick-beta.php
 * 
 * Fonctionnalités :
 * - Liste des outils en beta test
 * - Inscription beta testeurs
 * - Système de feedback structuré
 * - Notation par étoiles
 * - Review Logs popup
 * 
 * Pour modifier cette brique, éditez uniquement ce fichier
 * et le fichier js/brick-beta.js
 */
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
            <p>Inscrivez-vous aux outils en beta, testez-les et partagez vos retours pour améliorer les futures versions.</p>
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
                    <span class="stat-value">0</span>
                    <span class="stat-label">testeurs</span>
                </div>
                <div class="beta-stat">
                    <i class="fas fa-comments"></i>
                    <span class="stat-value">0</span>
                    <span class="stat-label">feedbacks</span>
                </div>
            </div>
        </div>
        
        <div class="beta-card-footer">
            <button class="btn btn-primary beta-register-btn">
                <i class="fas fa-user-plus"></i> S'inscrire
            </button>
            <button class="btn btn-secondary beta-feedback-btn">
                <i class="fas fa-comment-dots"></i> Feedback
            </button>
        </div>
        
        <div class="beta-card-registered" style="display: none;">
            <div class="registered-badge">
                <i class="fas fa-check-circle"></i>
                Vous êtes inscrit !
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
