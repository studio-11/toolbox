<?php
/**
 * BRIQUE 1 : OUTILS DISPONIBLES
 * =============================
 * Fichier : briques/brick-tools.php
 * 
 * Fonctionnalités :
 * - Vue grille / slider horizontal
 * - Filtres (type, catégorie, recherche)
 * - Favoris
 * - Modal détails + tutoriel
 * - Review Logs popup
 * 
 * Pour modifier cette brique, éditez uniquement ce fichier
 * et le fichier js/brick-tools.js
 */
?>

<div class="brick-container brick-tools" id="brick-tools">
    <!-- Header de la brique -->
    <div class="brick-header">
        <div class="brick-title-section">
            <h2 class="brick-title">
                <i class="fas fa-tools"></i>
                Outils Disponibles
            </h2>
            <p class="brick-subtitle">Découvrez et utilisez les outils validés par l'équipe IFEN</p>
        </div>
        
        <div class="brick-actions">
            <!-- Toggle vue grille/slider -->
            <div class="view-toggle">
                <button class="view-btn active" data-view="grid" title="Vue grille">
                    <i class="fas fa-th-large"></i>
                </button>
                <button class="view-btn" data-view="slider" title="Vue slider">
                    <i class="fas fa-arrows-left-right"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="brick-filters">
        <div class="filter-group">
            <label for="filter-type">Type</label>
            <select id="filter-type" class="filter-select">
                <option value="">Tous les types</option>
                <option value="course">🎓 Activité de cours</option>
                <option value="platform">🌐 Plateforme externe</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="filter-category">Catégorie</label>
            <select id="filter-category" class="filter-select">
                <option value="">Toutes les catégories</option>
                <!-- Chargé dynamiquement -->
            </select>
        </div>
        
        <div class="filter-group filter-search">
            <label for="filter-search">Recherche</label>
            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="filter-search" class="filter-input" placeholder="Rechercher un outil...">
            </div>
        </div>
        
        <button class="btn btn-secondary btn-sm filter-reset" id="filter-reset">
            <i class="fas fa-times"></i> Réinitialiser
        </button>
    </div>
    
    <!-- Compteur de résultats -->
    <div class="results-info">
        <span id="results-count">-- outils</span>
        <span class="results-filter-info" id="filter-info"></span>
    </div>
    
    <!-- Conteneur des outils - Vue Grille -->
    <div class="tools-grid" id="tools-grid">
        <div class="loading-state">
            <div class="spinner"></div>
            <p>Chargement des outils...</p>
        </div>
    </div>
    
    <!-- Conteneur des outils - Vue Slider -->
    <div class="tools-slider-container" id="tools-slider-container" style="display: none;">
        <button class="slider-nav slider-prev" id="slider-prev" aria-label="Précédent">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="tools-slider" id="tools-slider">
            <div class="slider-track" id="slider-track">
                <!-- Chargé dynamiquement par JS -->
            </div>
        </div>
        
        <button class="slider-nav slider-next" id="slider-next" aria-label="Suivant">
            <i class="fas fa-chevron-right"></i>
        </button>
        
        <!-- Pagination dots -->
        <div class="slider-dots" id="slider-dots"></div>
    </div>
    
    <!-- État vide -->
    <div class="empty-state" id="tools-empty" style="display: none;">
        <i class="fas fa-search"></i>
        <h3>Aucun outil trouvé</h3>
        <p>Essayez de modifier vos filtres ou effectuez une nouvelle recherche.</p>
        <button class="btn btn-primary" onclick="BrickTools.resetFilters()">
            <i class="fas fa-redo"></i> Réinitialiser les filtres
        </button>
    </div>
</div>

<!-- Template pour une carte outil -->
<template id="template-tool-card">
    <div class="tool-card" data-tool-id="">
        <div class="tool-card-header">
            <div class="tool-icon">
                <i class="fas fa-puzzle-piece"></i>
            </div>
            <div class="tool-badges"></div>
            <button class="tool-favorite" title="Ajouter aux favoris">
                <i class="far fa-heart"></i>
            </button>
        </div>
        
        <div class="tool-card-body">
            <h3 class="tool-name">Nom de l'outil</h3>
            <p class="tool-description">Description courte de l'outil...</p>
            
            <div class="tool-meta">
                <span class="tool-type">
                    <i class="fas fa-tag"></i>
                    <span>Type</span>
                </span>
                <span class="tool-category">
                    <i class="fas fa-folder"></i>
                    <span>Catégorie</span>
                </span>
            </div>
        </div>
        
        <div class="tool-card-footer">
            <div class="tool-stats">
                <span class="stat" title="Installations">
                    <i class="fas fa-download"></i>
                    <span class="stat-value">0</span>
                </span>
                <span class="stat" title="Vues">
                    <i class="fas fa-eye"></i>
                    <span class="stat-value">0</span>
                </span>
            </div>
            <button class="btn btn-primary btn-sm tool-details-btn">
                <i class="fas fa-info-circle"></i> Détails
            </button>
        </div>
    </div>
</template>
