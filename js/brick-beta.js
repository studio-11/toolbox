/**
 * IFEN Toolbox - Brique "Beta Testing"
 * =====================================
 * Gestion des outils en beta test avec feedback utilisateurs
 */

const BrickBeta = {
    
    // État local
    state: {
        betaTools: [],
        userRegistrations: [], // Outils où l'utilisateur est inscrit
        selectedTool: null
    },
    
    // Éléments DOM
    elements: {
        container: null,
        toolsList: null
    },
    
    // ==================== INITIALISATION ====================
    
    async init(containerId = 'beta-section') {
        this.elements.container = document.getElementById(containerId);
        if (!this.elements.container) {
            console.error('BrickBeta: Container not found');
            return;
        }
        
        this.render();
        this.bindEvents();
        await this.loadBetaTools();
    },
    
    render() {
        this.elements.container.innerHTML = `
            <div class="brick-beta">
                <!-- Header -->
                <div class="brick-header">
                    <div class="brick-title-wrapper">
                        <h2 class="brick-title">
                            <i class="fas fa-flask"></i>
                            Beta Testing
                        </h2>
                        <p class="brick-subtitle">Testez les nouveaux outils et partagez vos retours</p>
                    </div>
                    
                    <div class="beta-legend">
                        <span class="legend-item">
                            <span class="dot dot-active"></span> Test en cours
                        </span>
                        <span class="legend-item">
                            <span class="dot dot-ending"></span> Fin bientôt
                        </span>
                    </div>
                </div>
                
                <!-- Info Banner -->
                <div class="beta-info-banner">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Comment ça marche ?</strong>
                        <p>Inscrivez-vous pour tester un outil, utilisez-le, puis laissez vos commentaires et suggestions pour nous aider à l'améliorer.</p>
                    </div>
                </div>
                
                <!-- Liste des outils en beta -->
                <div class="beta-tools-grid" id="beta-tools-list">
                    <!-- Généré dynamiquement -->
                </div>
            </div>
        `;
        
        this.elements.toolsList = this.elements.container.querySelector('#beta-tools-list');
    },
    
    bindEvents() {
        // Events globaux si nécessaire
    },
    
    // ==================== CHARGEMENT ====================
    
    async loadBetaTools() {
        ToolboxUtils.showLoading(this.elements.toolsList);
        
        try {
            // Charger les outils en beta
            const tools = await ToolboxUtils.apiCall('tools', 'GET', null, {
                status: 'beta'
            });
            this.state.betaTools = tools;
            
            // Charger les inscriptions de l'utilisateur
            try {
                const registrations = await ToolboxUtils.apiCall('user_beta_registrations');
                this.state.userRegistrations = registrations.map(r => r.tool_id);
            } catch (e) {
                this.state.userRegistrations = ToolboxUtils.getLocal('beta_registrations', []);
            }
            
            this.renderBetaTools();
            
        } catch (error) {
            console.error('Error loading beta tools:', error);
            ToolboxUtils.showError(this.elements.toolsList, 'Impossible de charger les outils en beta');
        }
    },
    
    // ==================== RENDU ====================
    
    renderBetaTools() {
        if (this.state.betaTools.length === 0) {
            ToolboxUtils.showEmptyState(
                this.elements.toolsList,
                'fa-flask',
                'Aucun outil en beta actuellement',
                'Revenez bientôt pour découvrir les nouveaux outils à tester !'
            );
            return;
        }
        
        this.elements.toolsList.innerHTML = this.state.betaTools.map((tool, index) => 
            this.createBetaCard(tool, index)
        ).join('');
        
        this.bindToolEvents();
    },
    
    createBetaCard(tool, index) {
        const isRegistered = this.state.userRegistrations.includes(tool.id);
        const daysLeft = this.calculateDaysLeft(tool.beta_end_date);
        const isEnding = daysLeft !== null && daysLeft <= 7;
        
        return `
            <article class="beta-card ${isEnding ? 'ending-soon' : ''}" 
                     data-tool-id="${tool.id}"
                     style="animation-delay: ${index * 0.1}s">
                
                <!-- Status Badge -->
                <div class="beta-status">
                    <span class="beta-badge">
                        <i class="fas fa-flask"></i> BETA
                    </span>
                    ${tool.version ? `<span class="version-badge">v${tool.version}</span>` : ''}
                </div>
                
                <!-- Header avec gradient -->
                <div class="beta-card-header" 
                     style="background: ${tool.gradient || 'var(--gradient-1)'}">
                    <i class="fas ${tool.icon || 'fa-vial'}"></i>
                    
                    ${daysLeft !== null ? `
                        <div class="beta-countdown ${isEnding ? 'ending' : ''}">
                            <i class="fas fa-clock"></i>
                            ${daysLeft > 0 ? `${daysLeft} jour${daysLeft > 1 ? 's' : ''} restant${daysLeft > 1 ? 's' : ''}` : 'Dernier jour !'}
                        </div>
                    ` : ''}
                </div>
                
                <!-- Content -->
                <div class="beta-card-content">
                    <h3 class="beta-card-title">${ToolboxUtils.escapeHtml(tool.name)}</h3>
                    
                    <p class="beta-card-description">
                        ${ToolboxUtils.escapeHtml(ToolboxUtils.truncate(tool.short_description, 150))}
                    </p>
                    
                    <!-- Stats -->
                    <div class="beta-stats">
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span>${tool.testers_count || 0} testeurs</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-comments"></i>
                            <span>${tool.feedback_count || 0} retours</span>
                        </div>
                    </div>
                    
                    <!-- Dates -->
                    <div class="beta-dates">
                        ${tool.beta_start_date ? `
                            <span class="date-item">
                                <i class="fas fa-play"></i> Début: ${ToolboxUtils.formatDate(tool.beta_start_date)}
                            </span>
                        ` : ''}
                        ${tool.beta_end_date ? `
                            <span class="date-item">
                                <i class="fas fa-flag-checkered"></i> Fin: ${ToolboxUtils.formatDate(tool.beta_end_date)}
                            </span>
                        ` : ''}
                    </div>
                    
                    <!-- Actions -->
                    <div class="beta-card-actions">
                        ${isRegistered ? `
                            <button class="btn btn-success btn-registered" disabled>
                                <i class="fas fa-check"></i> Inscrit
                            </button>
                            <button class="btn btn-primary" data-action="feedback">
                                <i class="fas fa-comment"></i> Donner mon avis
                            </button>
                        ` : `
                            <button class="btn btn-primary" data-action="register">
                                <i class="fas fa-user-plus"></i> S'inscrire au test
                            </button>
                        `}
                        <button class="btn btn-secondary" data-action="details">
                            <i class="fas fa-info-circle"></i>
                        </button>
                        <button class="btn btn-secondary" data-action="reviews" title="Review Logs">
                            <i class="fas fa-clipboard-list"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Feedback Preview -->
                ${tool.latest_feedback ? `
                    <div class="beta-feedback-preview">
                        <div class="feedback-header">
                            <i class="fas fa-quote-left"></i>
                            Dernier retour
                        </div>
                        <p>"${ToolboxUtils.escapeHtml(ToolboxUtils.truncate(tool.latest_feedback.content, 100))}"</p>
                        <span class="feedback-author">- ${tool.latest_feedback.user_name || 'Testeur'}</span>
                    </div>
                ` : ''}
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
    
    // ==================== EVENTS ====================
    
    bindToolEvents() {
        this.elements.toolsList.querySelectorAll('.beta-card').forEach(card => {
            const toolId = parseInt(card.dataset.toolId);
            
            // Inscription
            card.querySelector('[data-action="register"]')?.addEventListener('click', () => {
                this.registerForBeta(toolId);
            });
            
            // Feedback
            card.querySelector('[data-action="feedback"]')?.addEventListener('click', () => {
                this.showFeedbackModal(toolId);
            });
            
            // Détails
            card.querySelector('[data-action="details"]')?.addEventListener('click', () => {
                this.showToolDetails(toolId);
            });
            
            // Reviews
            card.querySelector('[data-action="reviews"]')?.addEventListener('click', () => {
                this.showReviewLogs(toolId);
            });
        });
    },
    
    // ==================== ACTIONS ====================
    
    async registerForBeta(toolId) {
        try {
            await ToolboxUtils.apiCall('beta_register', 'POST', { tool_id: toolId });
            
            // Mettre à jour localement
            this.state.userRegistrations.push(toolId);
            ToolboxUtils.saveLocal('beta_registrations', this.state.userRegistrations);
            
            // Mettre à jour le compteur
            const tool = this.state.betaTools.find(t => t.id === toolId);
            if (tool) tool.testers_count = (tool.testers_count || 0) + 1;
            
            this.renderBetaTools();
            ToolboxUtils.showNotification('Inscription au beta test réussie !', 'success');
            
            // Afficher les instructions
            this.showRegistrationSuccess(toolId);
            
        } catch (error) {
            ToolboxUtils.showNotification('Erreur lors de l\'inscription', 'error');
        }
    },
    
    showRegistrationSuccess(toolId) {
        const tool = this.state.betaTools.find(t => t.id === toolId);
        if (!tool) return;
        
        ToolboxUtils.createModal({
            title: 'Inscription confirmée !',
            size: 'small',
            content: `
                <div class="registration-success">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Bienvenue dans le beta test !</h3>
                    <p>Vous êtes maintenant inscrit pour tester <strong>${tool.name}</strong>.</p>
                    
                    <div class="next-steps">
                        <h4>Prochaines étapes :</h4>
                        <ol>
                            <li>Accédez à l'outil et testez-le</li>
                            <li>Notez ce qui fonctionne bien</li>
                            <li>Identifiez les bugs ou améliorations</li>
                            <li>Partagez vos retours via le bouton "Donner mon avis"</li>
                        </ol>
                    </div>
                </div>
            `,
            footer: `
                <button class="btn btn-primary" data-modal-close>
                    <i class="fas fa-rocket"></i> C'est parti !
                </button>
            `
        });
    },
    
    showFeedbackModal(toolId) {
        const tool = this.state.betaTools.find(t => t.id === toolId);
        if (!tool) return;
        
        const modal = ToolboxUtils.createModal({
            title: `Feedback - ${tool.name}`,
            size: 'medium',
            content: `
                <div class="feedback-modal">
                    <!-- Tabs pour voir/donner feedback -->
                    <div class="feedback-tabs">
                        <button class="feedback-tab active" data-tab="give">
                            <i class="fas fa-edit"></i> Donner un avis
                        </button>
                        <button class="feedback-tab" data-tab="view">
                            <i class="fas fa-list"></i> Voir les retours (${tool.feedback_count || 0})
                        </button>
                    </div>
                    
                    <!-- Give Feedback -->
                    <div class="feedback-content active" id="feedback-give">
                        <form id="feedback-form">
                            <div class="form-group">
                                <label>Type de retour</label>
                                <div class="feedback-type-selector">
                                    ${Object.entries(TOOLBOX_CONFIG.feedbackTypes).map(([key, info]) => `
                                        <label class="type-option">
                                            <input type="radio" name="feedback_type" value="${key}" 
                                                   ${key === 'general' ? 'checked' : ''}>
                                            <span class="type-label" style="--type-color: ${info.color}">
                                                <i class="fas ${info.icon}"></i>
                                                ${info.label}
                                            </span>
                                        </label>
                                    `).join('')}
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="feedback-title">Titre (optionnel)</label>
                                <input type="text" id="feedback-title" name="title" 
                                       placeholder="Résumé de votre retour">
                            </div>
                            
                            <div class="form-group">
                                <label for="feedback-content">Votre retour *</label>
                                <textarea id="feedback-content" name="content" rows="5" required
                                          placeholder="Décrivez votre expérience, ce qui fonctionne bien, les problèmes rencontrés..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Note globale</label>
                                <div class="rating-selector">
                                    ${[1, 2, 3, 4, 5].map(n => `
                                        <label class="star-label">
                                            <input type="radio" name="rating" value="${n}">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    `).join('')}
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- View Feedbacks -->
                    <div class="feedback-content" id="feedback-view">
                        <div class="feedbacks-list" id="feedbacks-list">
                            <div class="loading-state">
                                <div class="spinner"></div>
                                <p>Chargement...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            footer: `
                <button class="btn btn-secondary" data-modal-close>Fermer</button>
                <button class="btn btn-primary" data-action="submit-feedback" id="submit-feedback-btn">
                    <i class="fas fa-paper-plane"></i> Envoyer
                </button>
            `
        });
        
        // Tab switching
        modal.querySelectorAll('.feedback-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                modal.querySelectorAll('.feedback-tab').forEach(t => t.classList.remove('active'));
                modal.querySelectorAll('.feedback-content').forEach(c => c.classList.remove('active'));
                
                tab.classList.add('active');
                modal.querySelector(`#feedback-${tab.dataset.tab}`).classList.add('active');
                
                // Toggle submit button
                const submitBtn = modal.querySelector('#submit-feedback-btn');
                submitBtn.style.display = tab.dataset.tab === 'give' ? 'inline-flex' : 'none';
                
                // Load feedbacks if viewing
                if (tab.dataset.tab === 'view') {
                    this.loadToolFeedbacks(toolId, modal.querySelector('#feedbacks-list'));
                }
            });
        });
        
        // Rating stars interaction
        modal.querySelectorAll('.star-label').forEach((label, index) => {
            label.addEventListener('mouseenter', () => {
                modal.querySelectorAll('.star-label').forEach((l, i) => {
                    l.classList.toggle('hover', i <= index);
                });
            });
            
            label.addEventListener('click', () => {
                modal.querySelectorAll('.star-label').forEach((l, i) => {
                    l.classList.toggle('selected', i <= index);
                });
            });
        });
        
        modal.querySelector('.rating-selector').addEventListener('mouseleave', () => {
            modal.querySelectorAll('.star-label').forEach(l => l.classList.remove('hover'));
        });
        
        // Submit feedback
        modal.querySelector('[data-action="submit-feedback"]').addEventListener('click', async () => {
            const form = modal.querySelector('#feedback-form');
            const formData = new FormData(form);
            
            const feedbackData = {
                tool_id: toolId,
                feedback_type: formData.get('feedback_type'),
                title: formData.get('title'),
                content: formData.get('content'),
                rating: formData.get('rating') ? parseInt(formData.get('rating')) : null
            };
            
            if (!feedbackData.content) {
                ToolboxUtils.showNotification('Veuillez décrire votre retour', 'warning');
                return;
            }
            
            await this.submitFeedback(feedbackData, modal);
        });
    },
    
    async loadToolFeedbacks(toolId, container) {
        try {
            const feedbacks = await ToolboxUtils.apiCall('beta_feedbacks', 'GET', null, { tool_id: toolId });
            
            if (feedbacks.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h3>Aucun retour pour le moment</h3>
                        <p>Soyez le premier à partager votre avis !</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = feedbacks.map(fb => this.createFeedbackItem(fb)).join('');
            
        } catch (error) {
            container.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erreur lors du chargement</p>
                </div>
            `;
        }
    },
    
    createFeedbackItem(feedback) {
        const typeInfo = TOOLBOX_CONFIG.feedbackTypes[feedback.feedback_type] || TOOLBOX_CONFIG.feedbackTypes.general;
        
        return `
            <div class="feedback-item">
                <div class="feedback-item-header">
                    <span class="feedback-type-badge" style="background: ${typeInfo.color}20; color: ${typeInfo.color}">
                        <i class="fas ${typeInfo.icon}"></i> ${typeInfo.label}
                    </span>
                    ${feedback.rating ? `
                        <span class="feedback-rating">
                            ${Array(5).fill(0).map((_, i) => 
                                `<i class="fas fa-star ${i < feedback.rating ? 'filled' : ''}"></i>`
                            ).join('')}
                        </span>
                    ` : ''}
                </div>
                
                ${feedback.title ? `<h4 class="feedback-item-title">${ToolboxUtils.escapeHtml(feedback.title)}</h4>` : ''}
                <p class="feedback-item-content">${ToolboxUtils.escapeHtml(feedback.content)}</p>
                
                ${feedback.admin_response ? `
                    <div class="admin-response">
                        <div class="response-header">
                            <i class="fas fa-reply"></i> Réponse de l'équipe
                        </div>
                        <p>${ToolboxUtils.escapeHtml(feedback.admin_response)}</p>
                    </div>
                ` : ''}
                
                <div class="feedback-item-footer">
                    <span class="feedback-author">
                        <i class="fas fa-user"></i> ${feedback.user_name || 'Testeur'}
                    </span>
                    <span class="feedback-date">
                        ${ToolboxUtils.formatRelativeDate(feedback.created_at)}
                    </span>
                    ${feedback.status !== 'new' ? `
                        <span class="feedback-status status-${feedback.status}">
                            ${feedback.status === 'reviewed' ? 'Vu' : 
                              feedback.status === 'in_progress' ? 'En traitement' :
                              feedback.status === 'resolved' ? 'Résolu' : feedback.status}
                        </span>
                    ` : ''}
                </div>
            </div>
        `;
    },
    
    async submitFeedback(data, modal) {
        try {
            await ToolboxUtils.apiCall('beta_feedback', 'POST', data);
            
            ToolboxUtils.closeModal(modal);
            ToolboxUtils.showNotification('Merci pour votre retour !', 'success');
            
            // Mettre à jour le compteur
            const tool = this.state.betaTools.find(t => t.id === data.tool_id);
            if (tool) tool.feedback_count = (tool.feedback_count || 0) + 1;
            
            this.renderBetaTools();
            
        } catch (error) {
            ToolboxUtils.showNotification('Erreur lors de l\'envoi', 'error');
        }
    },
    
    showToolDetails(toolId) {
        const tool = this.state.betaTools.find(t => t.id === toolId);
        if (!tool) return;
        
        const isRegistered = this.state.userRegistrations.includes(toolId);
        
        ToolboxUtils.createModal({
            title: tool.name,
            size: 'large',
            content: `
                <div class="beta-tool-details">
                    <div class="beta-badge-large">
                        <i class="fas fa-flask"></i> BETA ${tool.version || ''}
                    </div>
                    
                    ${tool.screenshot_url ? `
                        <img src="${tool.screenshot_url}" alt="${tool.name}" class="tool-screenshot">
                    ` : ''}
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-info-circle"></i> Description</h3>
                        <p>${tool.long_description || tool.short_description}</p>
                    </div>
                    
                    ${tool.features && tool.features.length > 0 ? `
                        <div class="detail-section">
                            <h3><i class="fas fa-list-check"></i> Fonctionnalités à tester</h3>
                            <ul class="features-list">
                                ${tool.features.map(f => `<li>${ToolboxUtils.escapeHtml(f.feature_text || f)}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}
                    
                    <div class="beta-meta-grid">
                        <div class="meta-item">
                            <span class="meta-label">Période de test</span>
                            <span class="meta-value">
                                ${ToolboxUtils.formatDate(tool.beta_start_date)} - ${ToolboxUtils.formatDate(tool.beta_end_date)}
                            </span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Testeurs inscrits</span>
                            <span class="meta-value">${tool.testers_count || 0}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Retours reçus</span>
                            <span class="meta-value">${tool.feedback_count || 0}</span>
                        </div>
                    </div>
                    
                    ${!isRegistered ? `
                        <div class="register-cta">
                            <p>Intéressé par le test de cet outil ?</p>
                            <button class="btn btn-primary btn-lg" data-action="register-from-modal">
                                <i class="fas fa-user-plus"></i> S'inscrire au beta test
                            </button>
                        </div>
                    ` : `
                        <div class="registered-info">
                            <i class="fas fa-check-circle"></i>
                            <span>Vous êtes inscrit à ce beta test</span>
                        </div>
                    `}
                </div>
            `
        });
        
        // Bind register from modal
        document.querySelector('[data-action="register-from-modal"]')?.addEventListener('click', () => {
            document.querySelector('.toolbox-modal-overlay')?.remove();
            this.registerForBeta(toolId);
        });
    },
    
    async showReviewLogs(toolId) {
        try {
            const reviews = await ToolboxUtils.apiCall('tool_reviews', 'GET', null, { tool_id: toolId });
            const tool = this.state.betaTools.find(t => t.id === toolId);
            
            ToolboxUtils.createModal({
                title: `Review Logs - ${tool?.name || 'Outil'}`,
                size: 'medium',
                content: this.renderReviewLogs(reviews)
            });
            
        } catch (error) {
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
    }
};

// Exporter
window.BrickBeta = BrickBeta;
