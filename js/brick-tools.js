/**
 * IFEN Toolbox - Brique "Outils Disponibles"
 * ===========================================
 * Version mise à jour avec public cible et difficulté d'utilisation
 */

const BrickTools = {
    
    // État local
    state: {
        tools: [],
        categories: [],
        filters: {
            type: '',
            category: '',
            audience: '',
            search: ''
        },
        viewMode: 'grid',
        sliderPosition: 0,
        favorites: []
    },
    
    // Éléments DOM
    elements: {
        container: null,
        toolsGrid: null,
        toolsSlider: null,
        filterType: null,
        filterCategory: null,
        filterAudience: null,
        filterSearch: null
    },
    
    // ==================== INITIALISATION ====================
    
    async init(containerId = 'tools-section') {
        this.elements.container = document.getElementById(containerId);
        if (!this.elements.container) {
            console.error('BrickTools: Container not found');
            return;
        }
        
        this.elements.toolsGrid = this.elements.container.querySelector('#tools-grid');
        this.elements.toolsSlider = this.elements.container.querySelector('#slider-track');
        this.elements.filterType = this.elements.container.querySelector('#filter-type');
        this.elements.filterCategory = this.elements.container.querySelector('#filter-category');
        this.elements.filterAudience = this.elements.container.querySelector('#filter-audience');
        this.elements.filterSearch = this.elements.container.querySelector('#filter-search');
        
        this.bindEvents();
        await this.loadTools();
        await this.loadCategories();
    },
    
    bindEvents() {
        // View toggle
        this.elements.container.querySelectorAll('[data-view]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.state.viewMode = btn.dataset.view;
                this.updateViewMode();
            });
        });
        
        // Filtres
        if (this.elements.filterType) {
            this.elements.filterType.addEventListener('change', () => {
                this.state.filters.type = this.elements.filterType.value;
                this.renderTools();
            });
        }
        
        if (this.elements.filterCategory) {
            this.elements.filterCategory.addEventListener('change', () => {
                this.state.filters.category = this.elements.filterCategory.value;
                this.renderTools();
            });
        }
        
        if (this.elements.filterAudience) {
            this.elements.filterAudience.addEventListener('change', () => {
                this.state.filters.audience = this.elements.filterAudience.value;
                this.renderTools();
            });
        }
        
        if (this.elements.filterSearch) {
            this.elements.filterSearch.addEventListener('input', ToolboxUtils.debounce(() => {
                this.state.filters.search = this.elements.filterSearch.value;
                this.renderTools();
            }, 300));
        }
        
        // Reset filtres
        const resetBtn = this.elements.container.querySelector('#filter-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => this.resetFilters());
        }
        
        // Slider navigation
        const prevBtn = this.elements.container.querySelector('#slider-prev');
        const nextBtn = this.elements.container.querySelector('#slider-next');
        if (prevBtn) prevBtn.addEventListener('click', () => this.navigateSlider('prev'));
        if (nextBtn) nextBtn.addEventListener('click', () => this.navigateSlider('next'));
        
        // Events délégués pour les cartes
        if (this.elements.toolsGrid) {
            this.elements.toolsGrid.addEventListener('click', (e) => {
                const card = e.target.closest('.tool-card');
                if (!card) return;
                
                const toolId = parseInt(card.dataset.toolId);
                
                if (e.target.closest('.tool-favorite')) {
                    e.stopPropagation();
                    this.toggleFavorite(toolId);
                    return;
                }
                
                if (e.target.closest('.tool-details-btn')) {
                    e.stopPropagation();
                    this.showToolDetails(toolId);
                    return;
                }
                
                // Clic sur la carte = détails
                this.showToolDetails(toolId);
            });
        }
    },
    
    // ==================== CHARGEMENT ====================
    
    async loadTools() {
        if (this.elements.toolsGrid) {
            ToolboxUtils.showLoading(this.elements.toolsGrid);
        }
        
        try {
            const tools = await ToolboxUtils.apiCall('tools', 'GET', null, { status: 'available' });
            this.state.tools = tools || [];
            
            // Charger les favoris
            try {
                const favorites = await ToolboxUtils.apiCall('user_favorites');
                this.state.favorites = Array.isArray(favorites) ? favorites : [];
            } catch (e) {
                this.state.favorites = ToolboxUtils.getLocal('favorites', []);
            }
            
            this.renderTools();
            this.updateResultsCount();
            
        } catch (error) {
            console.error('Error loading tools:', error);
            if (this.elements.toolsGrid) {
                ToolboxUtils.showError(this.elements.toolsGrid, 'Impossible de charger les outils');
            }
        }
    },
    
    async loadCategories() {
        try {
            const categories = await ToolboxUtils.apiCall('categories');
            this.state.categories = categories || [];
            
            if (this.elements.filterCategory && categories) {
                categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.name;
                    this.elements.filterCategory.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    },
    
    // ==================== RENDU ====================
    
    renderTools() {
        const filtered = this.filterTools();
        
        this.updateResultsCount(filtered.length);
        
        // Rendu grille
        if (this.elements.toolsGrid) {
            if (filtered.length === 0) {
                const emptyState = this.elements.container.querySelector('#tools-empty');
                if (emptyState) emptyState.style.display = 'flex';
                this.elements.toolsGrid.innerHTML = '';
            } else {
                const emptyState = this.elements.container.querySelector('#tools-empty');
                if (emptyState) emptyState.style.display = 'none';
                this.elements.toolsGrid.innerHTML = filtered.map((tool, index) => 
                    this.createToolCard(tool, index)
                ).join('');
            }
        }
        
        // Rendu slider
        if (this.elements.toolsSlider) {
            this.elements.toolsSlider.innerHTML = filtered.map((tool, index) => 
                this.createToolCard(tool, index)
            ).join('');
            this.state.sliderPosition = 0;
            this.updateSliderPosition();
        }
    },
    
    filterTools() {
        return this.state.tools.filter(tool => {
            // Filtre type
            if (this.state.filters.type && tool.type !== this.state.filters.type) {
                return false;
            }
            
            // Filtre catégorie
            if (this.state.filters.category && tool.category_id != this.state.filters.category) {
                return false;
            }
            
            // Filtre audience (public cible)
            if (this.state.filters.audience) {
                const audiences = this.parseAudience(tool.target_audience);
                if (!audiences.includes(this.state.filters.audience)) {
                    return false;
                }
            }
            
            // Filtre recherche
            if (this.state.filters.search) {
                const search = this.state.filters.search.toLowerCase();
                const text = `${tool.name} ${tool.short_description}`.toLowerCase();
                if (!text.includes(search)) {
                    return false;
                }
            }
            
            return true;
        });
    },
    
    parseAudience(audience) {
        if (!audience) return [];
        if (Array.isArray(audience)) return audience;
        try {
            return JSON.parse(audience);
        } catch (e) {
            return [];
        }
    },
    
    createToolCard(tool, index) {
        const isFavorite = this.state.favorites.includes(tool.id);
        const audiences = this.parseAudience(tool.target_audience);
        const difficultyInfo = TOOLBOX_CONFIG.difficultyLevels[tool.difficulty] || TOOLBOX_CONFIG.difficultyLevels.medium;
        
        // Badges
        let badges = '';
        if (tool.is_hot) badges += '<span class="badge badge-hot">🔥 Hot</span>';
        if (tool.status === 'new') badges += '<span class="badge badge-new">Nouveau</span>';
        if (tool.status === 'beta') badges += '<span class="badge badge-beta">Beta</span>';
        
        // Badges public cible
        let audienceBadges = '';
        audiences.forEach(aud => {
            const audInfo = TOOLBOX_CONFIG.targetAudiences[aud];
            if (audInfo) {
                audienceBadges += `
                    <span class="audience-badge audience-${aud}" style="background: ${audInfo.bg}; color: ${audInfo.color}">
                        <i class="fas ${audInfo.icon}"></i> ${audInfo.label}
                    </span>
                `;
            }
        });
        
        return `
            <article class="tool-card" data-tool-id="${tool.id}" style="animation-delay: ${index * 0.05}s">
                <div class="tool-card-header" style="background: ${tool.gradient || 'var(--gradient-1)'}">
                    <div class="tool-icon">
                        <i class="fas ${tool.icon || 'fa-puzzle-piece'}"></i>
                    </div>
                    <div class="tool-badges">${badges}</div>
                    <button class="tool-favorite ${isFavorite ? 'active' : ''}" title="${isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
                
                <div class="tool-card-body">
                    <h3 class="tool-name">${ToolboxUtils.escapeHtml(tool.name)}</h3>
                    <p class="tool-description">${ToolboxUtils.escapeHtml(ToolboxUtils.truncate(tool.short_description, 100))}</p>
                    
                    <div class="tool-meta">
                        <span class="tool-type">
                            <i class="fas fa-tag"></i>
                            <span>${tool.type === 'course' ? 'Activité de cours' : 'Plateforme externe'}</span>
                        </span>
                        ${tool.category_name ? `
                            <span class="tool-category">
                                <i class="fas fa-folder"></i>
                                <span>${tool.category_name}</span>
                            </span>
                        ` : ''}
                    </div>
                    
                    ${audienceBadges ? `
                        <div class="tool-audience">
                            ${audienceBadges}
                        </div>
                    ` : ''}
                    
                    ${tool.difficulty ? `
                        <div class="tool-difficulty">
                            <span class="difficulty-label">Difficulté d'utilisation:</span>
                            <span class="difficulty-value difficulty-${tool.difficulty}" style="background: ${difficultyInfo.bg}; color: ${difficultyInfo.color}">
                                ${difficultyInfo.label}
                            </span>
                        </div>
                    ` : ''}
                </div>
                
                <div class="tool-card-footer">
                    <div class="tool-stats">
                        <span class="stat" title="Installations">
                            <i class="fas fa-download"></i>
                            <span class="stat-value">${tool.installations_count || 0}</span>
                        </span>
                        <span class="stat" title="Vues">
                            <i class="fas fa-eye"></i>
                            <span class="stat-value">${tool.views_count || 0}</span>
                        </span>
                    </div>
                    <button class="btn btn-primary btn-sm tool-details-btn">
                        <i class="fas fa-info-circle"></i> Détails
                    </button>
                </div>
            </article>
        `;
    },
    
    updateResultsCount(count) {
        const resultsEl = this.elements.container.querySelector('#results-count');
        if (resultsEl) {
            const total = count !== undefined ? count : this.state.tools.length;
            resultsEl.textContent = `${total} outil${total > 1 ? 's' : ''}`;
        }
    },
    
    // ==================== VUE TOGGLE ====================
    
    updateViewMode() {
        const gridWrapper = this.elements.container.querySelector('.tools-grid');
        const sliderContainer = this.elements.container.querySelector('#tools-slider-container');
        
        this.elements.container.querySelectorAll('[data-view]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === this.state.viewMode);
        });
        
        if (gridWrapper) gridWrapper.style.display = this.state.viewMode === 'grid' ? 'grid' : 'none';
        if (sliderContainer) sliderContainer.style.display = this.state.viewMode === 'slider' ? 'block' : 'none';
    },
    
    // ==================== SLIDER ====================
    
    navigateSlider(direction) {
        const filtered = this.filterTools();
        const slidesToShow = this.getSlidesToShow();
        const maxPosition = Math.max(0, filtered.length - slidesToShow);
        
        if (direction === 'next') {
            this.state.sliderPosition = Math.min(this.state.sliderPosition + 1, maxPosition);
        } else {
            this.state.sliderPosition = Math.max(this.state.sliderPosition - 1, 0);
        }
        
        this.updateSliderPosition();
    },
    
    updateSliderPosition() {
        if (!this.elements.toolsSlider) return;
        
        const cardWidth = 350;
        const gap = 28;
        const offset = this.state.sliderPosition * (cardWidth + gap);
        
        this.elements.toolsSlider.style.transform = `translateX(-${offset}px)`;
        
        const prevBtn = this.elements.container.querySelector('#slider-prev');
        const nextBtn = this.elements.container.querySelector('#slider-next');
        const filtered = this.filterTools();
        const maxPosition = Math.max(0, filtered.length - this.getSlidesToShow());
        
        if (prevBtn) prevBtn.disabled = this.state.sliderPosition === 0;
        if (nextBtn) nextBtn.disabled = this.state.sliderPosition >= maxPosition;
    },
    
    getSlidesToShow() {
        if (window.innerWidth < 768) return 1;
        if (window.innerWidth < 1024) return 2;
        return 3;
    },
    
    // ==================== ACTIONS ====================
    
    resetFilters() {
        this.state.filters = { type: '', category: '', audience: '', search: '' };
        
        if (this.elements.filterType) this.elements.filterType.value = '';
        if (this.elements.filterCategory) this.elements.filterCategory.value = '';
        if (this.elements.filterAudience) this.elements.filterAudience.value = '';
        if (this.elements.filterSearch) this.elements.filterSearch.value = '';
        
        this.renderTools();
    },
    
    async toggleFavorite(toolId) {
        const isFavorite = this.state.favorites.includes(toolId);
        
        try {
            const method = isFavorite ? 'DELETE' : 'POST';
            await ToolboxUtils.apiCall('favorite', method, { tool_id: toolId });
            
            if (isFavorite) {
                this.state.favorites = this.state.favorites.filter(id => id !== toolId);
            } else {
                this.state.favorites.push(toolId);
            }
            
            ToolboxUtils.saveLocal('favorites', this.state.favorites);
            this.renderTools();
            
            ToolboxUtils.showNotification(
                isFavorite ? 'Retiré des favoris' : 'Ajouté aux favoris',
                'success'
            );
        } catch (error) {
            console.error('Error toggling favorite:', error);
        }
    },
    
    async showToolDetails(toolId) {
        try {
            const tool = await ToolboxUtils.apiCall('tool', 'GET', null, { id: toolId });
            
            // Tracker la vue
            ToolboxUtils.apiCall('track', 'POST', { tool_id: toolId, action_type: 'view' }).catch(() => {});
            
            const audiences = this.parseAudience(tool.target_audience);
            const difficultyInfo = TOOLBOX_CONFIG.difficultyLevels[tool.difficulty] || null;
            
            // Badges public cible
            let audienceBadges = '';
            audiences.forEach(aud => {
                const audInfo = TOOLBOX_CONFIG.targetAudiences[aud];
                if (audInfo) {
                    audienceBadges += `
                        <span class="audience-badge audience-${aud}" style="background: ${audInfo.bg}; color: ${audInfo.color}">
                            <i class="fas ${audInfo.icon}"></i> ${audInfo.label}
                        </span>
                    `;
                }
            });
            
            const content = `
                ${tool.screenshot_url ? `<img src="${tool.screenshot_url}" alt="${tool.name}" class="tool-screenshot">` : ''}
                
                <div class="tool-detail-section">
                    <h3><i class="fas fa-info-circle"></i> Description</h3>
                    <p>${ToolboxUtils.escapeHtml(tool.long_description || tool.short_description)}</p>
                </div>
                
                ${tool.features && tool.features.length > 0 ? `
                    <div class="tool-detail-section">
                        <h3><i class="fas fa-check-circle"></i> Fonctionnalités</h3>
                        <ul class="features-list">
                            ${tool.features.map(f => `<li>${ToolboxUtils.escapeHtml(typeof f === 'string' ? f : f.feature_text)}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
                
                <div class="tool-meta-grid">
                    ${audienceBadges ? `
                        <div class="meta-item">
                            <span class="meta-label">Public cible</span>
                            <div class="meta-value">${audienceBadges}</div>
                        </div>
                    ` : ''}
                    ${difficultyInfo ? `
                        <div class="meta-item">
                            <span class="meta-label">Difficulté d'utilisation</span>
                            <span class="meta-value">
                                <span class="difficulty-value difficulty-${tool.difficulty}" style="background: ${difficultyInfo.bg}; color: ${difficultyInfo.color}">
                                    ${difficultyInfo.label}
                                </span>
                            </span>
                        </div>
                    ` : ''}
                    <div class="meta-item">
                        <span class="meta-label">Version</span>
                        <span class="meta-value">${tool.version || '1.0.0'}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Installations</span>
                        <span class="meta-value">${tool.installations_count || 0}</span>
                    </div>
                </div>
            `;
            
            ToolboxUtils.createModal({
                title: tool.name,
                size: 'large',
                content: content,
                footer: `
                    <button class="btn btn-secondary" data-modal-close>Fermer</button>
                    ${tool.tutorial_url ? `
                        <a href="${tool.tutorial_url}" class="btn btn-primary" target="_blank">
                            <i class="fas fa-book"></i> Voir le tutoriel
                        </a>
                    ` : ''}
                `
            });
            
        } catch (error) {
            ToolboxUtils.showNotification('Erreur lors du chargement', 'error');
        }
    }
};

// Exporter
window.BrickTools = BrickTools;
