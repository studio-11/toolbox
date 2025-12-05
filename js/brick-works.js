/**
 * IFEN Toolbox - Brique "Travaux / Statut Plateforme"
 * ===================================================
 */

const BrickWorks = {
    
    // État local
    state: {
        platformStatus: null,
        maintenanceSchedule: []
    },
    
    // ==================== INITIALISATION ====================
    
    async init() {
        await this.loadPlatformStatus();
        this.bindEvents();
    },
    
    bindEvents() {
        // Clic sur la stat "Travaux" pour ouvrir le détail
        const worksStat = document.querySelector('[data-stat="works"]');
        if (worksStat) {
            worksStat.addEventListener('click', () => this.showStatusModal());
        }
    },
    
    // ==================== CHARGEMENT ====================
    
    async loadPlatformStatus() {
        try {
            const status = await ToolboxUtils.apiCall('platform_status');
            this.state.platformStatus = status;
            this.updateStatusBadge();
        } catch (error) {
            console.warn('Platform status not available');
        }
    },
    
    // ==================== RENDU ====================
    
    updateStatusBadge() {
        const statusEl = document.getElementById('platform-status-badge');
        if (!statusEl || !this.state.platformStatus) return;
        
        const status = this.state.platformStatus;
        const statusInfo = TOOLBOX_CONFIG.platformStatuses[status.status] || TOOLBOX_CONFIG.platformStatuses.operational;
        
        statusEl.innerHTML = `
            <i class="fas ${statusInfo.icon}" style="color: ${statusInfo.color}"></i>
            <span>${statusInfo.label}</span>
        `;
        statusEl.style.display = 'flex';
    },
    
    showStatusModal() {
        const status = this.state.platformStatus;
        const statusInfo = status ? 
            (TOOLBOX_CONFIG.platformStatuses[status.status] || TOOLBOX_CONFIG.platformStatuses.operational) :
            TOOLBOX_CONFIG.platformStatuses.operational;
        
        const content = `
            <div class="platform-status-detail">
                <div class="status-header" style="background: ${statusInfo.color}20; border-color: ${statusInfo.color}">
                    <i class="fas ${statusInfo.icon}" style="color: ${statusInfo.color}"></i>
                    <div class="status-info">
                        <span class="status-label">${statusInfo.label}</span>
                        ${status?.message ? `<p class="status-message">${ToolboxUtils.escapeHtml(status.message)}</p>` : ''}
                    </div>
                </div>
                
                ${status?.scheduled_maintenance ? `
                    <div class="maintenance-section">
                        <h4><i class="fas fa-calendar-alt"></i> Maintenance programmée</h4>
                        <div class="maintenance-info">
                            <p><strong>Date:</strong> ${ToolboxUtils.formatDate(status.scheduled_maintenance.date)}</p>
                            <p><strong>Durée estimée:</strong> ${status.scheduled_maintenance.duration || 'Non définie'}</p>
                            ${status.scheduled_maintenance.description ? `<p>${ToolboxUtils.escapeHtml(status.scheduled_maintenance.description)}</p>` : ''}
                        </div>
                    </div>
                ` : ''}
                
                <div class="status-services">
                    <h4><i class="fas fa-server"></i> Services</h4>
                    <ul class="services-list">
                        <li class="service-item operational">
                            <i class="fas fa-check-circle"></i>
                            <span>LearningSphere (Moodle)</span>
                        </li>
                        <li class="service-item operational">
                            <i class="fas fa-check-circle"></i>
                            <span>Authentification IAM</span>
                        </li>
                        <li class="service-item operational">
                            <i class="fas fa-check-circle"></i>
                            <span>Toolbox</span>
                        </li>
                    </ul>
                </div>
                
                <div class="status-footer">
                    <p class="last-update">
                        <i class="fas fa-sync-alt"></i>
                        Dernière mise à jour: ${status?.updated_at ? ToolboxUtils.formatRelativeDate(status.updated_at) : 'Inconnue'}
                    </p>
                </div>
            </div>
        `;
        
        ToolboxUtils.createModal({
            title: 'Statut de la plateforme',
            size: 'medium',
            content: content,
            footer: `
                <button class="btn btn-secondary" data-modal-close>Fermer</button>
            `
        });
    }
};

// Exporter
window.BrickWorks = BrickWorks;
