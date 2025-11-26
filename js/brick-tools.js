/**
 * IFEN Toolbox - Brique "Outils Disponibles"
 * ===========================================
 * Gestion de l'affichage des outils avec slider et grille
 */

const BrickTools = {
    
    // État local
    state: {
        tools: [],
        filters: {
            type: 'all',
            category: 'all',
            search: ''
        },
        viewMode: 'grid', // 'grid' ou 'slider'
        sliderPosition: 0
    },
    
    // Éléments DOM
    elements: {
        container: null,
        toolsGrid: null,
        toolsSlider: null,
        filterType: null,
        filterCategory: null,
        filterSearch: null
    },
    
    // ==================== INITIALISATION ====================
    
    /**
     * Initialiser la brique
     */
    async init(containerId = 'tools-section') {
        this.elements.container = document.getElementById(containerId);
        if (!this.elements.container) {
            console.error('BrickTools: Container not found');
            return;
        }
        
        this.render();
        this.bindEvents();
        await this.loadTools();
    },
    
    /**
     * Rendu initial de la brique
     */
    render() {
        this.elements.container.innerHTML = `
            <div class="brick-tools">
                <!-- Header -->
                <div class="brick-header">
                    <div class="brick-title-wrapper">
                        <h2 class="brick-title">
                            <i class="fas fa-toolbox"></i>
                            Outils Disponibles
                        </h2>
                        <p class="brick-subtitle">Modules et fonctionnalités prêts à l'emploi</p>
                    </div>
                    
                    <!-- View Toggle -->
                    <div class="view-toggle">
                        <button class="view-btn ${this.state.viewMode === 'slider' ? 'active' : ''}" 
                                data-view="slider" title="Vue slider">
                            <i class="fas fa-arrows-alt-h"></i>
                        </button>
                        <button class="view-btn ${this.state.viewMode === 'grid' ? 'active' : ''}" 
                                data-view="grid" title="Vue grille">
                            <i class="fas fa-th"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Filtres -->
                <div class="brick-filters">
                    <div class="filter-group">
                        <label for="filter-tools-type">Type</label>
                        <select id="filter-tools-type" class="filter-select">
                            <option value="all">Tous les types</option>
                            <option value="course">🧩 Outil de cours</option>
                            <option value="platform">⚙️ Fonctionnalité plateforme</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filter-tools-category">Catégorie</label>
                        <select id="filter-tools-category" class="filter-select">
                            <option value="all">Toutes les catégories</option>
                        </select>
                    </div>
                    
                    <div class="filter-group filter-search">
                        <label for="filter-tools-search">Recherche</label>
                        <input type="text" id="filter-tools-search" class="filter-input" 
                               placeholder="Rechercher un outil...">
                    </div>
                </div>
                
                <!-- Slider View -->
                <div class="tools-slider-wrapper ${this.state.viewMode === 'slider' ? 'active' : ''}">
                    <button class="slider-nav slider-prev" data-slider-nav="prev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="tools-slider" id="tools-slider">
                        <!-- Cards générées dynamiquement -->
                    </div>
                    <button class="slider-nav slider-next" data-slider-nav="next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <!-- Pagination dots -->
                    <div class="slider-dots" id="slider-dots"></div>
                </div>
                
                <!-- Grid View -->
                <div class="tools-grid-wrapper ${this.state.viewMode === 'grid' ? 'active' : ''}">
                    <div class="tools-grid" id="tools-grid">
                        <!-- Cards générées dynamiquement -->
                    </div>
                </div>
            </div>
        `;
        
        // Récupérer les éléments
        this.elements.toolsGrid = this.elements.container.querySelector('#tools-grid');
        this.elements.toolsSlider = this.elements.container.querySelector('#tools-slider');
        this.elements.filterType = this.elements.container.querySelector('#filter-tools-type');
        this.elements.filterCategory = this.elements.container.querySelector('#filter-tools-category');
        this.elements.filterSearch = this.elements.container.querySelector('#filter-tools-search');
    },
    
    /**
     * Bindre les événements
     */
    bindEvents() {
        // View toggle
        this.elements.container.querySelectorAll('[data-view]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.state.viewMode = btn.dataset.view;
                this.updateViewMode();
            });
        });
        
        // Filtres
        this.elements.filterType.addEventListener('change', () => {
            this.state.filters.type = this.elements.filterType.value;
            this.renderTools();
        });
        
        this.elements.filterCategory.addEventListener('change', () => {
            this.state.filters.category = this.elements.filterCategory.value;
            this.renderTools();
        });
        
        this.elements.filterSearch.addEventListener('input', 
            ToolboxUtils.debounce(() => {
                this.state.filters.search = this.elements.filterSearch.value;
                this.renderTools();
            }, 300)
        );
        
        // Slider navigation
        this.elements.container.querySelectorAll('[data-slider-nav]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.navigateSlider(btn.dataset.sliderNav);
            });
        });
        
        // Touch/swipe support pour le slider
        this.initSliderTouch();
    },
    
    // ==================== CHARGEMENT DES DONNÉES ====================
    
    /**
     * Charger les outils depuis l'API
     */
    async loadTools() {
        ToolboxUtils.showLoading(this.elements.toolsGrid);
        ToolboxUtils.showLoading(this.elements.toolsSlider);
        
        try {
            // Charger les outils disponibles (stable + new)
            const tools = await ToolboxUtils.apiCall('tools', 'GET', null, {
                status: 'available'
            });
            
            this.state.tools = tools;
            this.renderTools();
            
            // Charger les catégories
            await this.loadCategories();
            
        } catch (error) {
            console.error('Error loading tools:', error);
            ToolboxUtils.showError(this.elements.toolsGrid, 'Impossible de charger les outils');
            ToolboxUtils.showError(this.elements.toolsSlider, 'Impossible de charger les outils');
        }
    },
    
    /**
     * Charger les catégories
     */
    async loadCategories() {
        try {
            const categories = await ToolboxUtils.apiCall('categories');
            
            // Peupler le select des catégories
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.name;
                this.elements.filterCategory.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    },
    
    // ==================== RENDU ====================
    
    /**
     * Rendre les outils filtrés
     */
    renderTools() {
        const filteredTools = this.filterTools();
        
        // Rendre dans la grille
        this.renderGrid(filteredTools);
        
        // Rendre dans le slider
        this.renderSlider(filteredTools);
        
        // Mettre à jour les dots
        this.updateSliderDots(filteredTools.length);
    },
    
    /**
     * Filtrer les outils selon les critères
     */
    filterTools() {
        return this.state.tools.filter(tool => {
            // Filtre type
            if (this.state.filters.type !== 'all' && tool.type !== this.state.filters.type) {
                return false;
            }
            
            // Filtre catégorie
            if (this.state.filters.category !== 'all' && 
                tool.category_id != this.state.filters.category) {
                return false;
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
    
    /**
     * Rendre la vue grille
     */
    renderGrid(tools) {
        if (tools.length === 0) {
            ToolboxUtils.showEmptyState(
                this.elements.toolsGrid,
                'fa-tools',
                'Aucun outil trouvé',
                'Essayez de modifier vos filtres'
            );
            return;
        }
        
        this.elements.toolsGrid.innerHTML = tools.map((tool, index) => 
            this.createToolCard(tool, index)
        ).join('');
        
        // Bind events des cards
        this.bindCardEvents(this.elements.toolsGrid);
    },
    
    /**
     * Rendre la vue slider
     */
    renderSlider(tools) {
        if (tools.length === 0) {
            this.elements.toolsSlider.innerHTML = `
                <div class="slider-empty">
                    <i class="fas fa-tools"></i>
                    <p>Aucun outil à afficher</p>
                </div>
            `;
            return;
        }
        
        this.elements.toolsSlider.innerHTML = tools.map((tool, index) => 
            this.createToolCard(tool, index, 'slider')
        ).join('');
        
        // Reset position
        this.state.sliderPosition = 0;
        this.updateSliderPosition();
        
        // Bind events des cards
        this.bindCardEvents(this.elements.toolsSlider);
    },
    
    /**
     * Créer une card d'outil
     */
    createToolCard(tool, index, context = 'grid') {
        const typeLabels = {
            course: 'Outil de cours',
            platform: 'Fonctionnalité plateforme'
        };
        
        const badges = [];
        if (tool.is_hot) badges.push('<span class="badge badge-hot">🔥 Hot</span>');
        if (tool.status === 'new') badges.push('<span class="badge badge-new">Nouveau</span>');
        if (tool.status === 'beta') badges.push('<span class="badge badge-beta">Beta</span>');
        
        const features = tool.features ? tool.features.slice(0, 3) : [];
        
        return `
            <article class="tool-card" data-tool-id="${tool.id}" 
                     style="animation-delay: ${index * 0.1}s">
                <div class="tool-card-header" 
                     style="background: ${tool.gradient || 'var(--gradient-1)'}">
                    <i class="fas ${tool.icon || 'fa-tools'}"></i>
                    ${badges.join('')}
                </div>
                
                <div class="tool-card-content">
                    <h3 class="tool-card-title">${ToolboxUtils.escapeHtml(tool.name)}</h3>
                    
                    <div class="tool-card-meta">
                        <span class="meta-tag">${typeLabels[tool.type] || tool.type}</span>
                        ${tool.category_name ? `<span class="meta-tag">${tool.category_name}</span>` : ''}
                        ${tool.time_to_use ? `<span class="meta-tag">⏱ ${tool.time_to_use}</span>` : ''}
                    </div>
                    
                    <p class="tool-card-description">
                        ${ToolboxUtils.escapeHtml(ToolboxUtils.truncate(tool.short_description, 120))}
                    </p>
                    
                    ${features.length > 0 ? `
                        <ul class="tool-card-bullets">
                            ${features.map(f => `<li>${ToolboxUtils.escapeHtml(f.feature_text || f)}</li>`).join('')}
                        </ul>
                    ` : ''}
                    
                    <div class="tool-card-footer">
                        <button class="btn btn-icon ${tool.is_favorited ? 'btn-primary' : 'btn-secondary'}" 
                                data-action="favorite" title="Favoris">
                            <i class="fas fa-heart"></i>
                        </button>
                        <button class="btn btn-secondary" data-action="details">
                            <i class="fas fa-info-circle"></i> Détails
                        </button>
                        <button class="btn btn-secondary" data-action="reviews" title="Review Logs">
                            <i class="fas fa-clipboard-list"></i>
                        </button>
                        <button class="btn btn-primary" data-action="tutorial">
                            <i class="fas fa-book"></i> Tutoriel
                        </button>
                    </div>
                </div>
            </article>
        `;
    },
    
    /**
     * Bind les événements des cards
     */
    bindCardEvents(container) {
        container.querySelectorAll('.tool-card').forEach(card => {
            const toolId = card.dataset.toolId;
            
            // Favoris
            card.querySelector('[data-action="favorite"]')?.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleFavorite(toolId);
            });
            
            // Détails
            card.querySelector('[data-action="details"]')?.addEventListener('click', (e) => {
                e.stopPropagation();
                this.showToolDetails(toolId);
            });
            
            // Reviews
            card.querySelector('[data-action="reviews"]')?.addEventListener('click', (e) => {
                e.stopPropagation();
                this.showReviewLogs(toolId);
            });
            
            // Tutoriel
            card.querySelector('[data-action="tutorial"]')?.addEventListener('click', (e) => {
                e.stopPropagation();
                this.showTutorial(toolId);
            });
            
            // Click sur la card = détails
            card.addEventListener('click', () => {
                this.showToolDetails(toolId);
            });
        });
    },
    
    // ==================== SLIDER ====================
    
    /**
     * Mettre à jour le mode de vue
     */
    updateViewMode() {
        const sliderWrapper = this.elements.container.querySelector('.tools-slider-wrapper');
        const gridWrapper = this.elements.container.querySelector('.tools-grid-wrapper');
        
        // Update buttons
        this.elements.container.querySelectorAll('[data-view]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === this.state.viewMode);
        });
        
        // Update wrappers
        sliderWrapper.classList.toggle('active', this.state.viewMode === 'slider');
        gridWrapper.classList.toggle('active', this.state.viewMode === 'grid');
    },
    
    /**
     * Naviguer dans le slider
     */
    navigateSlider(direction) {
        const tools = this.filterTools();
        const maxPosition = Math.max(0, tools.length - this.getSlidesToShow());
        
        if (direction === 'next') {
            this.state.sliderPosition = Math.min(this.state.sliderPosition + 1, maxPosition);
        } else {
            this.state.sliderPosition = Math.max(this.state.sliderPosition - 1, 0);
        }
        
        this.updateSliderPosition();
        this.updateSliderDots(tools.length);
    },
    
    /**
     * Mettre à jour la position du slider
     */
    updateSliderPosition() {
        const slider = this.elements.toolsSlider;
        const cardWidth = slider.querySelector('.tool-card')?.offsetWidth || 380;
        const gap = 28; // Gap CSS
        const offset = this.state.sliderPosition * (cardWidth + gap);
        
        slider.style.transform = `translateX(-${offset}px)`;
        
        // Update nav buttons state
        const prevBtn = this.elements.container.querySelector('.slider-prev');
        const nextBtn = this.elements.container.querySelector('.slider-next');
        const tools = this.filterTools();
        const maxPosition = Math.max(0, tools.length - this.getSlidesToShow());
        
        prevBtn.disabled = this.state.sliderPosition === 0;
        nextBtn.disabled = this.state.sliderPosition >= maxPosition;
    },
    
    /**
     * Mettre à jour les dots du slider
     */
    updateSliderDots(totalTools) {
        const dotsContainer = this.elements.container.querySelector('#slider-dots');
        const slidesToShow = this.getSlidesToShow();
        const totalDots = Math.ceil(totalTools / slidesToShow);
        
        if (totalDots <= 1) {
            dotsContainer.innerHTML = '';
            return;
        }
        
        dotsContainer.innerHTML = Array.from({ length: totalDots }, (_, i) => `
            <button class="slider-dot ${i === Math.floor(this.state.sliderPosition / slidesToShow) ? 'active' : ''}" 
                    data-dot="${i}"></button>
        `).join('');
        
        // Bind click events
        dotsContainer.querySelectorAll('.slider-dot').forEach(dot => {
            dot.addEventListener('click', () => {
                this.state.sliderPosition = parseInt(dot.dataset.dot) * slidesToShow;
                this.updateSliderPosition();
                this.updateSliderDots(totalTools);
            });
        });
    },
    
    /**
     * Nombre de slides à afficher selon la taille d'écran
     */
    getSlidesToShow() {
        if (window.innerWidth < 768) return 1;
        if (window.innerWidth < 1024) return 2;
        return 3;
    },
    
    /**
     * Initialiser le support touch pour le slider
     */
    initSliderTouch() {
        const slider = this.elements.toolsSlider;
        let startX, startPos;
        
        slider.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startPos = this.state.sliderPosition;
        });
        
        slider.addEventListener('touchmove', (e) => {
            if (!startX) return;
            const diff = startX - e.touches[0].clientX;
            if (Math.abs(diff) > 50) {
                e.preventDefault();
            }
        });
        
        slider.addEventListener('touchend', (e) => {
            if (!startX) return;
            const diff = startX - e.changedTouches[0].clientX;
            if (diff > 50) {
                this.navigateSlider('next');
            } else if (diff < -50) {
                this.navigateSlider('prev');
            }
            startX = null;
        });
    },
    
    // ==================== ACTIONS ====================
    
    /**
     * Toggle favoris
     */
    async toggleFavorite(toolId) {
        const tool = this.state.tools.find(t => t.id == toolId);
        if (!tool) return;
        
        try {
            const method = tool.is_favorited ? 'DELETE' : 'POST';
            await ToolboxUtils.apiCall('favorite', method, { tool_id: toolId });
            
            tool.is_favorited = !tool.is_favorited;
            this.renderTools();
            
            ToolboxUtils.showNotification(
                tool.is_favorited ? 'Ajouté aux favoris' : 'Retiré des favoris',
                'success'
            );
        } catch (error) {
            console.error('Error toggling favorite:', error);
        }
    },
    
    /**
     * Afficher les détails d'un outil
     */
    async showToolDetails(toolId) {
        try {
            const tool = await ToolboxUtils.apiCall('tool', 'GET', null, { id: toolId });
            
            // Tracker la vue
            ToolboxUtils.apiCall('track', 'POST', { tool_id: toolId, action_type: 'view' });
            
            const modal = ToolboxUtils.createModal({
                title: tool.name,
                size: 'large',
                content: this.renderToolDetailsContent(tool),
                footer: `
                    <button class="btn btn-secondary" data-modal-close>Fermer</button>
                    <button class="btn btn-primary" data-action="install">
                        <i class="fas fa-download"></i> Installer
                    </button>
                `
            });
            
            // Bind install action
            modal.querySelector('[data-action="install"]')?.addEventListener('click', () => {
                this.installTool(toolId);
            });
            
        } catch (error) {
            ToolboxUtils.showNotification('Erreur lors du chargement', 'error');
        }
    },
    
    /**
     * Rendu du contenu détails d'un outil
     */
    renderToolDetailsContent(tool) {
        return `
            ${tool.screenshot_url ? `<img src="${tool.screenshot_url}" alt="${tool.name}" class="tool-screenshot">` : ''}
            
            ${tool.video_url ? `
                <div class="video-container">
                    <iframe src="${tool.video_url}" frameborder="0" allowfullscreen></iframe>
                </div>
            ` : ''}
            
            <div class="tool-detail-section">
                <h3><i class="fas fa-info-circle"></i> Description</h3>
                <p>${tool.long_description || tool.short_description}</p>
            </div>
            
            ${tool.features && tool.features.length > 0 ? `
                <div class="tool-detail-section">
                    <h3><i class="fas fa-check-circle"></i> Points clés</h3>
                    <ul class="features-list">
                        ${tool.features.map(f => `<li>${ToolboxUtils.escapeHtml(f.feature_text || f)}</li>`).join('')}
                    </ul>
                </div>
            ` : ''}
            
            <div class="tool-meta-grid">
                <div class="meta-item">
                    <span class="meta-label">Public cible</span>
                    <span class="meta-value">${tool.audience || '-'}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Temps d'utilisation</span>
                    <span class="meta-value">${tool.time_to_use || '-'}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Difficulté</span>
                    <span class="meta-value">${tool.difficulty || '-'}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Version</span>
                    <span class="meta-value">${tool.version || '1.0.0'}</span>
                </div>
            </div>
        `;
    },
    
    /**
     * Afficher les review logs
     */
    async showReviewLogs(toolId) {
        try {
            const reviews = await ToolboxUtils.apiCall('tool_reviews', 'GET', null, { tool_id: toolId });
            const tool = this.state.tools.find(t => t.id == toolId);
            
            ToolboxUtils.createModal({
                title: `Review Logs - ${tool?.name || 'Outil'}`,
                size: 'medium',
                content: this.renderReviewLogs(reviews)
            });
            
        } catch (error) {
            // Si pas de reviews, afficher un message
            ToolboxUtils.createModal({
                title: 'Review Logs',
                size: 'small',
                content: `
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>Aucune review</h3>
                        <p>Aucune review n'a été effectuée sur cet outil.</p>
                    </div>
                `
            });
        }
    },
    
    /**
     * Rendu des review logs
     */
    renderReviewLogs(reviews) {
        if (!reviews || reviews.length === 0) {
            return `
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Aucune review</h3>
                    <p>Aucune review n'a été effectuée sur cet outil.</p>
                </div>
            `;
        }
        
        return `
            <div class="reviews-list">
                ${reviews.map(review => `
                    <div class="review-item">
                        <div class="review-header">
                            <span class="review-type">
                                <i class="fas ${TOOLBOX_CONFIG.reviewTypes[review.review_type]?.icon || 'fa-clipboard'}"></i>
                                ${TOOLBOX_CONFIG.reviewTypes[review.review_type]?.label || review.review_type}
                            </span>
                            ${ToolboxUtils.createStatusBadge(review.status, {
                                pending: { label: 'En attente', badge: 'warning', icon: 'fa-clock' },
                                approved: { label: 'Approuvé', badge: 'success', icon: 'fa-check' },
                                rejected: { label: 'Rejeté', badge: 'danger', icon: 'fa-times' },
                                needs_changes: { label: 'À modifier', badge: 'info', icon: 'fa-edit' }
                            })}
                            ${ToolboxUtils.createPriorityBadge(review.priority)}
                        </div>
                        <h4 class="review-title">${ToolboxUtils.escapeHtml(review.title)}</h4>
                        <p class="review-content">${ToolboxUtils.escapeHtml(review.content)}</p>
                        <div class="review-footer">
                            <span class="review-author">
                                <i class="fas fa-user"></i> ${review.reviewer_name || 'Admin'}
                            </span>
                            <span class="review-date">
                                <i class="fas fa-calendar"></i> ${ToolboxUtils.formatDate(review.review_date)}
                            </span>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    },
    
    /**
     * Afficher le tutoriel
     */
    async showTutorial(toolId) {
        try {
            const tool = await ToolboxUtils.apiCall('tool', 'GET', null, { id: toolId });
            
            ToolboxUtils.createModal({
                title: `Tutoriel - ${tool.name}`,
                size: 'large',
                content: `
                    ${tool.video_url ? `
                        <div class="video-container">
                            <iframe src="${tool.video_url}" frameborder="0" allowfullscreen></iframe>
                        </div>
                    ` : ''}
                    
                    ${tool.tutorial_text ? `
                        <div class="tutorial-content">
                            ${tool.tutorial_text}
                        </div>
                    ` : ''}
                    
                    ${tool.code_snippet ? `
                        <div class="code-section">
                            <h3><i class="fas fa-code"></i> Code à copier</h3>
                            <pre><code>${ToolboxUtils.escapeHtml(tool.code_snippet)}</code></pre>
                            <button class="btn btn-secondary" onclick="navigator.clipboard.writeText(\`${tool.code_snippet.replace(/`/g, '\\`')}\`); ToolboxUtils.showNotification('Code copié!', 'success');">
                                <i class="fas fa-copy"></i> Copier le code
                            </button>
                        </div>
                    ` : ''}
                    
                    ${!tool.video_url && !tool.tutorial_text && !tool.code_snippet ? `
                        <div class="empty-state">
                            <i class="fas fa-book"></i>
                            <h3>Tutoriel en cours de rédaction</h3>
                            <p>Le tutoriel pour cet outil sera bientôt disponible.</p>
                        </div>
                    ` : ''}
                `
            });
            
        } catch (error) {
            ToolboxUtils.showNotification('Erreur lors du chargement', 'error');
        }
    },
    
    /**
     * Installer un outil (tracker)
     */
    async installTool(toolId) {
        try {
            await ToolboxUtils.apiCall('track', 'POST', {
                tool_id: toolId,
                action_type: 'install'
            });
            
            ToolboxUtils.showNotification('Installation enregistrée !', 'success');
        } catch (error) {
            console.error('Error tracking install:', error);
        }
    }
};

// Exporter
window.BrickTools = BrickTools;
