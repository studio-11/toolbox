/**
 * IFEN Toolbox - Brique "Beta Testing"
 * =====================================
 * Version mise à jour avec lien Moodle et infos détaillées
 */

const BrickBeta = {
    
    // État local
    state: {
        betaTools: [],
        userRegistrations: [],
        selectedTool: null
    },
    
    // Éléments DOM
    elements: {
        container: null,
        toolsGrid: null
    },
    
    // ==================== INITIALISATION ====================
    
    async init(containerId = 'beta-section') {
        this.elements.container = document.getElementById(containerId);
        if (!this.elements.container) {
            console.error('BrickBeta: Container not found');
            return;
        }
        
        this.elements.toolsGrid = this.elements.container.querySelector('#beta-tools-grid');
        this.bindEvents();
        await this.loadBetaTools();
    },
    
    bindEvents() {
        // Events délégués pour les cartes
        if (this.elements.toolsGrid) {
            this.elements.toolsGrid.addEventListener('click', (e) => {
                const card = e.target.closest('.beta-card');
                if (!card) return;
                
                const toolId = parseInt(card.dataset.toolId);
                
                // Bouton info
                if (e.target.closest('.beta-info-btn')) {
                    e.stopPropagation();
                    this.showInfoPopup(toolId);
                    return;
                }
                
                // Bouton inscription
                if (e.target.closest('.beta-register-btn')) {
                    e.stopPropagation();
                    this.registerForBeta(toolId);
                    return;
                }
                
                // Bouton feedback
                if (e.target.closest('.beta-feedback-btn')) {
                    e.stopPropagation();
                    this.showFeedbackModal(toolId);
                    return;
                }
                
                // Lien cours
                if (e.target.closest('.beta-course-link')) {
                    // Laisser le lien s'ouvrir normalement
                    return;
                }
            });
        }
    },
    
    // ==================== CHARGEMENT ====================
    
    async loadBetaTools() {
        ToolboxUtils.showLoading(this.elements.toolsGrid);
        
        try {
            // Charger les outils en beta
            const tools = await ToolboxUtils.apiCall('tools', 'GET', null, { status: 'beta' });
            this.state.betaTools = tools || [];
            
            // Charger les inscriptions de l'utilisateur
            try {
                const registrations = await ToolboxUtils.apiCall('user_beta_registrations');
                this.state.userRegistrations = Array.isArray(registrations) ? registrations : [];
            } catch (e) {
                this.state.userRegistrations = ToolboxUtils.getLocal('beta_registrations', []);
            }
            
            this.renderBetaTools();
            
        } catch (error) {
            console.error('Error loading beta tools:', error);
            ToolboxUtils.showError(this.elements.toolsGrid, 'Impossible de charger les outils en beta');
        }
    },
    
    // ==================== RENDU ====================
    
    renderBetaTools() {
        if (!this.state.betaTools || this.state.betaTools.length === 0) {
            const emptyState = this.elements.container.querySelector('#beta-empty');
            if (emptyState) emptyState.style.display = 'flex';
            this.elements.toolsGrid.innerHTML = '';
            return;
        }
        
        const emptyState = this.elements.container.querySelector('#beta-empty');
        if (emptyState) emptyState.style.display = 'none';
        
        this.elements.toolsGrid.innerHTML = this.state.betaTools.map((tool, index) => 
            this.createBetaCard(tool, index)
        ).join('');
    },
    
    createBetaCard(tool, index) {
        const isRegistered = this.state.userRegistrations.includes(tool.id);
        const daysLeft = this.calculateDaysLeft(tool.beta_end_date);
        const isEnding = daysLeft !== null && daysLeft <= 7;
        const hasCourseLink = tool.beta_course_id && tool.beta_course_id > 0;
        const courseUrl = hasCourseLink ? TOOLBOX_CONFIG.moodleCourseUrl + tool.beta_course_id : '#';
        
        return `
            <article class="beta-card ${isEnding ? 'ending-soon' : ''}" 
                     data-tool-id="${tool.id}"
                     style="animation-delay: ${index * 0.1}s">
                
                ${isRegistered ? `
                    <div class="beta-registered-badge">
                        <i class="fas fa-check-circle"></i>
                        Vous êtes inscrit !
                    </div>
                ` : ''}
                
                <div class="beta-card-header" style="background: ${tool.gradient || 'var(--gradient-1)'}">
                    <div class="beta-icon">
                        <i class="fas ${tool.icon || 'fa-flask'}"></i>
                    </div>
                    <div class="beta-status">
                        <span class="beta-badge">BETA</span>
                        ${tool.version ? `<span class="beta-version">v${tool.version}</span>` : ''}
                    </div>
                    <button class="beta-info-btn" title="Informations détaillées">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
                
                <div class="beta-card-body">
                    <h3 class="beta-name">${ToolboxUtils.escapeHtml(tool.name)}</h3>
                    <p class="beta-description">${ToolboxUtils.escapeHtml(ToolboxUtils.truncate(tool.short_description, 120))}</p>
                    
                    ${daysLeft !== null ? `
                        <div class="beta-countdown ${isEnding ? 'ending' : ''}">
                            <i class="fas fa-clock"></i>
                            <span class="countdown-text">
                                ${daysLeft > 0 ? `${daysLeft} jour${daysLeft > 1 ? 's' : ''} restant${daysLeft > 1 ? 's' : ''}` : 'Dernier jour !'}
                            </span>
                        </div>
                    ` : ''}
                    
                    <div class="beta-stats">
                        <div class="beta-stat">
                            <i class="fas fa-users"></i>
                            <span class="stat-value testers-count">${tool.testers_count || 0}</span>
                            <span class="stat-label">testeurs</span>
                        </div>
                        <div class="beta-stat">
                            <i class="fas fa-comments"></i>
                            <span class="stat-value feedback-count">${tool.feedback_count || 0}</span>
                            <span class="stat-label">retours</span>
                        </div>
                    </div>
                </div>
                
                <div class="beta-card-footer">
                    ${isRegistered ? `
                        <button class="btn btn-secondary beta-feedback-btn">
                            <i class="fas fa-comment-dots"></i> Feedback
                        </button>
                        ${hasCourseLink ? `
                            <a href="${courseUrl}" class="btn btn-accent beta-course-link" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Accéder à l'outil
                            </a>
                        ` : ''}
                    ` : `
                        <button class="btn btn-primary beta-register-btn">
                            <i class="fas fa-user-plus"></i> S'inscrire
                        </button>
                    `}
                </div>
            </article>
        `;
    },
    
    calculateDaysLeft(endDate) {
        if (!endDate) return null;
        const end = new Date(endDate);
        const now = new Date();
        const diff = Math.ceil((end - now) / (1000 * 60 * 60 * 24));
        return diff >= 0 ? diff : 0;
    },
    
    // ==================== POPUP INFO DÉTAILLÉE ====================
    
    showInfoPopup(toolId) {
        const tool = this.state.betaTools.find(t => t.id === toolId);
        if (!tool) return;
        
        const isRegistered = this.state.userRegistrations.includes(toolId);
        const hasCourseLink = tool.beta_course_id && tool.beta_course_id > 0;
        const courseUrl = hasCourseLink ? TOOLBOX_CONFIG.moodleCourseUrl + tool.beta_course_id : '#';
        
        const content = `
            <div class="beta-info-popup">
                <div class="info-section">
                    <h4><i class="fas fa-align-left"></i> Description</h4>
                    <p class="info-description">${ToolboxUtils.escapeHtml(tool.long_description || tool.short_description || 'Aucune description disponible.')}</p>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-calendar-alt"></i> Période de test</h4>
                    <div class="info-dates">
                        <span class="date-item">
                            <i class="fas fa-play"></i> Début: <strong>${tool.beta_start_date ? ToolboxUtils.formatDate(tool.beta_start_date) : 'Non défini'}</strong>
                        </span>
                        <span class="date-item">
                            <i class="fas fa-flag-checkered"></i> Fin: <strong>${tool.beta_end_date ? ToolboxUtils.formatDate(tool.beta_end_date) : 'Non définie'}</strong>
                        </span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-chart-bar"></i> Statistiques</h4>
                    <div class="info-stats">
                        <div class="info-stat">
                            <span class="stat-number">${tool.testers_count || 0}</span>
                            <span class="stat-text">Testeurs inscrits</span>
                        </div>
                        <div class="info-stat">
                            <span class="stat-number">${tool.feedback_count || 0}</span>
                            <span class="stat-text">Retours reçus</span>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-user-check"></i> Votre inscription</h4>
                    ${isRegistered ? `
                        <div class="registration-status registered">
                            <i class="fas fa-check-circle"></i>
                            <span>Vous êtes inscrit à ce beta test</span>
                        </div>
                    ` : `
                        <div class="registration-status not-registered">
                            <i class="fas fa-times-circle"></i>
                            <span>Vous n'êtes pas inscrit à ce beta test</span>
                        </div>
                    `}
                </div>
                
                ${isRegistered && hasCourseLink ? `
                    <div class="info-section">
                        <h4><i class="fas fa-graduation-cap"></i> Accès au test</h4>
                        <a href="${courseUrl}" class="btn btn-accent btn-block" target="_blank">
                            <i class="fas fa-external-link-alt"></i> Accédez à l'outil et testez-le
                        </a>
                    </div>
                ` : ''}
            </div>
        `;
        
        ToolboxUtils.createModal({
            title: tool.name,
            size: 'medium',
            content: content,
            footer: `
                <button class="btn btn-secondary" data-modal-close>Fermer</button>
                ${!isRegistered ? `
                    <button class="btn btn-primary" id="btn-register-from-popup" data-tool-id="${toolId}">
                        <i class="fas fa-user-plus"></i> S'inscrire
                    </button>
                ` : ''}
            `
        });
        
        // Bind du bouton inscription depuis le popup
        const registerBtn = document.getElementById('btn-register-from-popup');
        if (registerBtn) {
            registerBtn.addEventListener('click', () => {
                document.querySelector('.toolbox-modal-overlay')?.remove();
                this.registerForBeta(toolId);
            });
        }
    },
    
    // ==================== INSCRIPTION ====================
    
    async registerForBeta(toolId) {
        const tool = this.state.betaTools.find(t => t.id === toolId);
        if (!tool) return;
        
        if (this.state.userRegistrations.includes(toolId)) {
            ToolboxUtils.showNotification('Vous êtes déjà inscrit à ce beta test', 'info');
            return;
        }
        
        try {
            await ToolboxUtils.apiCall('beta_register', 'POST', { tool_id: toolId });
            
            // Mettre à jour localement
            this.state.userRegistrations.push(toolId);
            ToolboxUtils.saveLocal('beta_registrations', this.state.userRegistrations);
            
            // Mettre à jour le compteur
            if (tool) tool.testers_count = (tool.testers_count || 0) + 1;
            
            // Afficher le modal de succès
            this.showRegistrationSuccess(tool);
            
            // Re-render
            this.renderBetaTools();
            
        } catch (error) {
            ToolboxUtils.showNotification('Erreur lors de l\'inscription', 'error');
        }
    },
    
    showRegistrationSuccess(tool) {
        const hasCourseLink = tool.beta_course_id && tool.beta_course_id > 0;
        const courseUrl = hasCourseLink ? TOOLBOX_CONFIG.moodleCourseUrl + tool.beta_course_id : '#';
        
        const content = `
            <div class="registration-success-content">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h3>Inscription confirmée !</h3>
                <p>Vous êtes maintenant inscrit au beta test de <strong>${ToolboxUtils.escapeHtml(tool.name)}</strong>.</p>
                
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
                
                ${hasCourseLink ? `
                    <div class="course-access-section">
                        <a href="${courseUrl}" class="btn btn-accent btn-lg btn-block" target="_blank">
                            <i class="fas fa-rocket"></i> Accédez à l'outil et testez-le
                        </a>
                    </div>
                ` : `
                    <div class="no-course-section">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            L'accès à l'outil sera disponible prochainement. Vous recevrez une notification.
                        </div>
                    </div>
                `}
            </div>
        `;
        
        ToolboxUtils.createModal({
            title: 'Bienvenue dans le beta test !',
            size: 'medium',
            content: content,
            footer: `
                <button class="btn btn-primary" data-modal-close>
                    <i class="fas fa-thumbs-up"></i> C'est compris !
                </button>
            `
        });
    },
    
    // ==================== FEEDBACK ====================
    
    showFeedbackModal(toolId) {
        const tool = this.state.betaTools.find(t => t.id === toolId);
        if (!tool) return;
        
        this.state.selectedTool = tool;
        
        const content = `
            <div class="feedback-modal-content">
                <div class="feedback-tabs">
                    <button class="feedback-tab active" data-tab="submit">
                        <i class="fas fa-pen"></i> Donner un avis
                    </button>
                    <button class="feedback-tab" data-tab="view">
                        <i class="fas fa-list"></i> Voir les retours (${tool.feedback_count || 0})
                    </button>
                </div>
                
                <div class="feedback-tab-content active" id="feedback-submit">
                    <form id="feedback-form">
                        <div class="form-group">
                            <label>Type de retour</label>
                            <div class="feedback-type-selector">
                                ${Object.entries(TOOLBOX_CONFIG.feedbackTypes).map(([key, info]) => `
                                    <label class="type-option">
                                        <input type="radio" name="feedback_type" value="${key}" ${key === 'praise' ? 'checked' : ''}>
                                        <span class="type-badge" style="--type-color: ${info.color}">
                                            <i class="fas ${info.icon}"></i> ${info.label}
                                        </span>
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Votre note</label>
                            <div class="star-rating" id="star-rating">
                                ${[1,2,3,4,5].map(n => `<i class="fas fa-star" data-rating="${n}"></i>`).join('')}
                            </div>
                            <input type="hidden" name="rating" id="rating-value" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="feedback-title">Titre</label>
                            <input type="text" id="feedback-title" name="title" class="form-input" 
                                   placeholder="Résumez votre retour..." required>
                        </div>
                        
                        <div class="form-group">
                            <label for="feedback-content">Détails</label>
                            <textarea id="feedback-content" name="content" class="form-textarea" rows="5" 
                                      placeholder="Décrivez votre expérience..." required></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Envoyer
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="feedback-tab-content" id="feedback-view">
                    <div class="feedback-list" id="feedback-list">
                        <div class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement...</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const modal = ToolboxUtils.createModal({
            title: `Feedback - ${tool.name}`,
            size: 'medium',
            content: content
        });
        
        // Bind tabs
        modal.querySelectorAll('.feedback-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                modal.querySelectorAll('.feedback-tab').forEach(t => t.classList.remove('active'));
                modal.querySelectorAll('.feedback-tab-content').forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(`feedback-${tab.dataset.tab}`).classList.add('active');
                
                if (tab.dataset.tab === 'view') {
                    this.loadFeedbacks(toolId);
                }
            });
        });
        
        // Bind star rating
        const stars = modal.querySelectorAll('.star-rating i');
        const ratingInput = modal.querySelector('#rating-value');
        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                const rating = index + 1;
                ratingInput.value = rating;
                stars.forEach((s, i) => {
                    s.classList.toggle('active', i < rating);
                });
            });
            star.addEventListener('mouseenter', () => {
                stars.forEach((s, i) => {
                    s.classList.toggle('hover', i <= index);
                });
            });
        });
        modal.querySelector('.star-rating').addEventListener('mouseleave', () => {
            stars.forEach(s => s.classList.remove('hover'));
        });
        
        // Bind form submit
        modal.querySelector('#feedback-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.submitFeedback(toolId, modal);
        });
    },
    
    async loadFeedbacks(toolId) {
        const container = document.getElementById('feedback-list');
        if (!container) return;
        
        try {
            const feedbacks = await ToolboxUtils.apiCall('beta_feedbacks', 'GET', null, { tool_id: toolId });
            
            if (!feedbacks || feedbacks.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <p>Aucun retour pour le moment. Soyez le premier !</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = feedbacks.map(fb => {
                const typeInfo = TOOLBOX_CONFIG.feedbackTypes[fb.feedback_type] || TOOLBOX_CONFIG.feedbackTypes.general;
                return `
                    <div class="feedback-item">
                        <div class="feedback-item-header">
                            <span class="feedback-type-badge" style="background: ${typeInfo.color}20; color: ${typeInfo.color}">
                                <i class="fas ${typeInfo.icon}"></i> ${typeInfo.label}
                            </span>
                            ${fb.rating ? `
                                <div class="feedback-rating">
                                    ${[1,2,3,4,5].map(n => `<i class="fas fa-star ${n <= fb.rating ? 'active' : ''}"></i>`).join('')}
                                </div>
                            ` : ''}
                            <span class="feedback-date">${ToolboxUtils.formatRelativeDate(fb.created_at)}</span>
                        </div>
                        ${fb.title ? `<h4 class="feedback-item-title">${ToolboxUtils.escapeHtml(fb.title)}</h4>` : ''}
                        <p class="feedback-item-content">${ToolboxUtils.escapeHtml(fb.content)}</p>
                        <div class="feedback-item-author">
                            <i class="fas fa-user"></i>
                            <span>${fb.user_name || 'Testeur anonyme'}</span>
                        </div>
                    </div>
                `;
            }).join('');
            
        } catch (error) {
            container.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erreur lors du chargement</p>
                </div>
            `;
        }
    },
    
    async submitFeedback(toolId, modal) {
        const form = modal.querySelector('#feedback-form');
        const formData = new FormData(form);
        
        const data = {
            tool_id: toolId,
            feedback_type: formData.get('feedback_type'),
            title: formData.get('title'),
            content: formData.get('content'),
            rating: parseInt(formData.get('rating')) || null
        };
        
        if (!data.content) {
            ToolboxUtils.showNotification('Veuillez décrire votre retour', 'warning');
            return;
        }
        
        try {
            await ToolboxUtils.apiCall('beta_feedback', 'POST', data);
            
            ToolboxUtils.closeModal(modal);
            ToolboxUtils.showNotification('Merci pour votre retour !', 'success');
            
            // Mettre à jour le compteur
            const tool = this.state.betaTools.find(t => t.id === toolId);
            if (tool) tool.feedback_count = (tool.feedback_count || 0) + 1;
            
            this.renderBetaTools();
            
        } catch (error) {
            ToolboxUtils.showNotification('Erreur lors de l\'envoi', 'error');
        }
    }
};

// Exporter
window.BrickBeta = BrickBeta;
