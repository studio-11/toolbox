/**
 * IFEN Toolbox - Utilitaires JavaScript
 * ======================================
 */

const ToolboxUtils = {
    
    // ==================== API ====================
    
    /**
     * Appel API générique
     */
    async apiCall(endpoint, method = 'GET', data = null, params = null) {
        let url = `${TOOLBOX_CONFIG.api?.baseUrl || '/ifen_html/toolbox/api/api.php'}?action=${endpoint}`;
        
        if (params) {
            Object.keys(params).forEach(key => {
                url += `&${key}=${encodeURIComponent(params[key])}`;
            });
        }
        
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };
        
        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.error);
            }
            
            return result.data !== undefined ? result.data : result;
            
        } catch (error) {
            console.error(`API Error [${endpoint}]:`, error);
            throw error;
        }
    },
    
    // ==================== DOM ====================
    
    /**
     * Affiche un état de chargement
     */
    showLoading(container) {
        if (!container) return;
        container.innerHTML = `
            <div class="loading-state">
                <div class="spinner"></div>
                <p>Chargement...</p>
            </div>
        `;
    },
    
    /**
     * Affiche un état d'erreur
     */
    showError(container, message = 'Une erreur est survenue') {
        if (!container) return;
        container.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${this.escapeHtml(message)}</p>
                <button class="btn btn-secondary" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Réessayer
                </button>
            </div>
        `;
    },
    
    /**
     * Crée une modal
     */
    createModal(options = {}) {
        const { title = '', size = 'medium', content = '', footer = '' } = options;
        
        // Fermer modal existante
        document.querySelector('.toolbox-modal-overlay')?.remove();
        
        const overlay = document.createElement('div');
        overlay.className = 'toolbox-modal-overlay';
        overlay.innerHTML = `
            <div class="toolbox-modal modal-${size}">
                <div class="modal-header">
                    <h2 class="modal-title">${this.escapeHtml(title)}</h2>
                    <button class="modal-close" data-modal-close>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
            </div>
        `;
        
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
        
        // Animation d'entrée
        requestAnimationFrame(() => overlay.classList.add('active'));
        
        // Fermeture
        overlay.querySelectorAll('[data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal(overlay));
        });
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) this.closeModal(overlay);
        });
        
        // Fermeture avec Escape
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                this.closeModal(overlay);
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
        
        return overlay;
    },
    
    /**
     * Ferme une modal
     */
    closeModal(overlay) {
        if (!overlay) return;
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => overlay.remove(), 300);
    },
    
    /**
     * Affiche une notification toast
     */
    showNotification(message, type = 'info', duration = 4000) {
        let container = document.getElementById('toast-container');
        
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas ${icons[type] || icons.info}"></i>
            <span>${this.escapeHtml(message)}</span>
            <button class="toast-close"><i class="fas fa-times"></i></button>
        `;
        
        container.appendChild(toast);
        
        // Animation d'entrée
        requestAnimationFrame(() => toast.classList.add('show'));
        
        // Fermeture
        const closeToast = () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        };
        
        toast.querySelector('.toast-close').addEventListener('click', closeToast);
        
        if (duration > 0) {
            setTimeout(closeToast, duration);
        }
        
        return toast;
    },
    
    // ==================== FORMATAGE ====================
    
    /**
     * Échappe le HTML
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    /**
     * Tronque un texte
     */
    truncate(text, length = 100) {
        if (!text || text.length <= length) return text || '';
        return text.substring(0, length).trim() + '...';
    },
    
    /**
     * Formate une date
     */
    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    },
    
    /**
     * Formate une date relative
     */
    formatRelativeDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'À l\'instant';
        if (minutes < 60) return `Il y a ${minutes} min`;
        if (hours < 24) return `Il y a ${hours}h`;
        if (days < 7) return `Il y a ${days} jour${days > 1 ? 's' : ''}`;
        if (days < 30) return `Il y a ${Math.floor(days / 7)} semaine${Math.floor(days / 7) > 1 ? 's' : ''}`;
        
        return this.formatDate(dateString);
    },
    
    // ==================== STOCKAGE LOCAL ====================
    
    /**
     * Sauvegarde en localStorage
     */
    saveLocal(key, value) {
        try {
            localStorage.setItem(`toolbox_${key}`, JSON.stringify(value));
        } catch (e) {
            console.warn('localStorage not available');
        }
    },
    
    /**
     * Récupère du localStorage
     */
    getLocal(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(`toolbox_${key}`);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            return defaultValue;
        }
    },
    
    /**
     * Supprime du localStorage
     */
    removeLocal(key) {
        try {
            localStorage.removeItem(`toolbox_${key}`);
        } catch (e) {
            console.warn('localStorage not available');
        }
    },
    
    // ==================== UTILITAIRES ====================
    
    /**
     * Debounce function
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
     * Throttle function
     */
    throttle(func, limit = 300) {
        let inThrottle;
        return function executedFunction(...args) {
            if (!inThrottle) {
                func(...args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    /**
     * Génère un ID unique
     */
    generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    },
    
    /**
     * Copie du texte dans le presse-papier
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showNotification('Copié !', 'success', 2000);
            return true;
        } catch (e) {
            console.error('Copy failed:', e);
            return false;
        }
    }
};

// Exporter
window.ToolboxUtils = ToolboxUtils;
