<?php
/**
 * BRIQUE 2 : IDÉES & VOTES
 * ========================
 * Fichier : briques/brick-ideas.php
 * 
 * Fonctionnalités :
 * - Formulaire de proposition d'idées
 * - Système de votes
 * - Passage en programmation avec workflow
 * - Vue Kanban par phase
 * 
 * Pour modifier cette brique, éditez uniquement ce fichier
 * et le fichier js/brick-ideas.js
 */
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
                    <option value="course">🎓 Nouvelle activité de cours</option>
                    <option value="platform">🌐 Nouvelle plateforme</option>
                    <option value="improvement">⚡ Amélioration existante</option>
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
            </div>
        </div>
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
            <button class="btn btn-xs btn-secondary" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
        </div>
    </div>
</template>
