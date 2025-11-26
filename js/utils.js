/**
 * IFEN Toolbox - Utilitaires Partagés
 * ====================================
 * Fonctions utilitaires communes à toutes les briques
 */

const ToolboxUtils = {
    
    // ==================== API CALLS ====================
    
    /**
     * Appel API générique avec gestion d'erreurs
     */
    async apiCall(action, method = 'GET', data = null, params = {}) {
        const url = new URL(TOOLBOX_CONFIG.api.baseUrl, window.location.origin);
        url.searchParams.append('action', action);
        
        // Ajouter les paramètres supplémentaires
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                url.searchParams.append(key, value);
            }
        });
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        };
        
        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Erreur inconnue');
            }
            
            return result.data;
        } catch (error) {
            console.error('API Error:', error);
            this.showNotification('Erreur de connexion à l\'API', 'error');
            throw error;
        }
    },
    
    // ==================== NOTIFICATIONS ====================
    
    /**
     * Afficher une notification toast
     */
    showNotification(message, type = 'info', duration = 3000) {
        // Supprimer les notifications existantes
        document.querySelectorAll('.toolbox-notification').forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = `toolbox-notification notification-${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        notification.innerHTML = `
            <i class="fas ${icons[type] || icons.info}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Auto-fermeture
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    },
    
    // ==================== MODALS ====================
    
    /**
     * Créer et afficher un modal
     */
    createModal(options = {}) {
        const {
            title = 'Modal',
            content = '',
            size = 'medium', // small, medium, large, full
            closable = true,
            onClose = null,
            footer = null
        } = options;
        
        // Supprimer les modals existants
        document.querySelectorAll('.toolbox-modal-overlay').forEach(m => m.remove());
        
        const modal = document.createElement('div');
        modal.className = 'toolbox-modal-overlay';
        
        const sizeClass = `modal-${size}`;
        
        modal.innerHTML = `
            <div class="toolbox-modal ${sizeClass}">
                <div class="modal-header">
                    <h2 class="modal-title">${title}</h2>
                    ${closable ? `
                        <button class="modal-close" data-modal-close>
                            <i class="fas fa-times"></i>
                        </button>
                    ` : ''}
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
            </div>
        `;
        
        // Event listeners
        if (closable) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target.closest('[data-modal-close]')) {
                    this.closeModal(modal, onClose);
                }
            });
            
            // Fermer avec Escape
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    this.closeModal(modal, onClose);
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);
        }
        
        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';
        
        // Animation d'ouverture
        setTimeout(() => modal.classList.add('open'), 10);
        
        return modal;
    },
    
    /**
     * Fermer un modal
     */
    closeModal(modal, callback = null) {
        modal.classList.remove('open');
        document.body.style.overflow = '';
        
        setTimeout(() => {
            modal.remove();
            if (callback) callback();
        }, 300);
    },
    
    /**
     * Modal de confirmation
     */
    async confirmAction(message, title = 'Confirmer', confirmText = 'Confirmer', cancelText = 'Annuler') {
        return new Promise((resolve) => {
            const modal = this.createModal({
                title: title,
                content: `<p style="font-size: 1rem; color: var(--gray);">${message}</p>`,
                size: 'small',
                footer: `
                    <button class="btn btn-secondary" data-modal-close>${cancelText}</button>
                    <button class="btn btn-primary" data-confirm>${confirmText}</button>
                `,
                onClose: () => resolve(false)
            });
            
            modal.querySelector('[data-confirm]').addEventListener('click', () => {
                this.closeModal(modal);
                resolve(true);
            });
        });
    },
    
    // ==================== FORMATAGE ====================
    
    /**
     * Formater une date
     */
    formatDate(dateString, options = {}) {
        if (!dateString) return '-';
        
        const defaultOptions = {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            ...options
        };
        
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('fr-FR', defaultOptions).format(date);
    },
    
    /**
     * Formater une date relative
     */
    formatRelativeDate(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        
        if (days > 30) return this.formatDate(dateString);
        if (days > 0) return `il y a ${days} jour${days > 1 ? 's' : ''}`;
        if (hours > 0) return `il y a ${hours} heure${hours > 1 ? 's' : ''}`;
        if (minutes > 0) return `il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
        return 'à l\'instant';
    },
    
    /**
     * Formater un nombre
     */
    formatNumber(num) {
        return new Intl.NumberFormat('fr-FR').format(num);
    },
    
    /**
     * Échapper le HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    /**
     * Tronquer un texte
     */
    truncate(text, length = 100) {
        if (!text || text.length <= length) return text;
        return text.substring(0, length).trim() + '...';
    },
    
    // ==================== DOM UTILITIES ====================
    
    /**
     * Afficher un loader dans un élément
     */
    showLoading(element, message = 'Chargement...') {
        element.innerHTML = `
            <div class="loading-state">
                <div class="spinner"></div>
                <p>${message}</p>
            </div>
        `;
    },
    
    /**
     * Afficher un état vide
     */
    showEmptyState(element, icon = 'fa-inbox', title = 'Aucun élément', message = '') {
        element.innerHTML = `
            <div class="empty-state">
                <i class="fas ${icon}"></i>
                <h3>${title}</h3>
                ${message ? `<p>${message}</p>` : ''}
            </div>
        `;
    },
    
    /**
     * Afficher une erreur
     */
    showError(element, message = 'Une erreur est survenue') {
        element.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
                <button class="btn btn-secondary" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Réessayer
                </button>
            </div>
        `;
    },
    
    /**
     * Scroll smooth vers un élément
     */
    scrollTo(elementId, offset = 20) {
        const element = document.getElementById(elementId);
        if (element) {
            const position = element.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top: position, behavior: 'smooth' });
        }
    },
    
    // ==================== DEBOUNCE & THROTTLE ====================
    
    /**
     * Debounce
     */
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * Throttle
     */
    throttle(func, limit = 100) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    // ==================== BADGES & STATUS ====================
    
    /**
     * Créer un badge de statut
     */
    createStatusBadge(status, config = TOOLBOX_CONFIG.toolStatus) {
        const statusInfo = config[status] || { label: status, badge: 'secondary', icon: 'fa-circle' };
        return `<span class="status-badge badge-${statusInfo.badge}">
            <i class="fas ${statusInfo.icon}"></i> ${statusInfo.label}
        </span>`;
    },
    
    /**
     * Créer un badge de priorité
     */
    createPriorityBadge(priority) {
        const info = TOOLBOX_CONFIG.priorities[priority] || TOOLBOX_CONFIG.priorities.medium;
        return `<span class="priority-badge" style="background: ${info.color}20; color: ${info.color};">
            <i class="fas ${info.icon}"></i> ${info.label}
        </span>`;
    },
    
    // ==================== PROGRESS ====================
    
    /**
     * Créer une barre de progression
     */
    createProgressBar(percent, label = '') {
        return `
            <div class="progress-container">
                ${label ? `<span class="progress-label">${label}</span>` : ''}
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${percent}%"></div>
                </div>
                <span class="progress-percent">${percent}%</span>
            </div>
        `;
    },
    
    // ==================== STORAGE ====================
    
    /**
     * Sauvegarder dans localStorage
     */
    saveLocal(key, data) {
        try {
            localStorage.setItem(`toolbox_${key}`, JSON.stringify(data));
            return true;
        } catch (e) {
            console.error('LocalStorage error:', e);
            return false;
        }
    },
    
    /**
     * Récupérer du localStorage
     */
    getLocal(key, defaultValue = null) {
        try {
            const data = localStorage.getItem(`toolbox_${key}`);
            return data ? JSON.parse(data) : defaultValue;
        } catch (e) {
            return defaultValue;
        }
    },
    
    /**
     * Supprimer du localStorage
     */
    removeLocal(key) {
        localStorage.removeItem(`toolbox_${key}`);
    }
};

// Exporter pour utilisation globale
window.ToolboxUtils = ToolboxUtils;
