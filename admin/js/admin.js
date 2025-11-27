/**
 * IFEN Toolbox Admin - Main JavaScript
 * =====================================
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // SIDEBAR TOGGLE
    // ============================================
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileToggle = document.getElementById('mobileToggle');
    
    // Desktop toggle (collapse)
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
        
        // Restore state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }
    }
    
    // Mobile toggle
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
        
        // Close on click outside
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992 && 
                !sidebar.contains(e.target) && 
                !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('mobile-open');
            }
        });
    }
    
    // ============================================
    // FLASH MESSAGES AUTO-HIDE
    // ============================================
    const flashAlert = document.getElementById('flashAlert');
    if (flashAlert) {
        setTimeout(() => {
            flashAlert.style.opacity = '0';
            flashAlert.style.transform = 'translateY(-10px)';
            setTimeout(() => flashAlert.remove(), 300);
        }, 5000);
    }
    
    // ============================================
    // MODAL HANDLING
    // ============================================
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };
    
    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
            });
            document.body.style.overflow = '';
        }
    });
    
    // ============================================
    // CONFIRM DELETE
    // ============================================
    window.confirmDelete = function(message, callback) {
        if (confirm(message || 'Êtes-vous sûr de vouloir supprimer cet élément ?')) {
            callback();
        }
    };
    
    // ============================================
    // FORM HELPERS
    // ============================================
    
    // Auto-resize textareas
    document.querySelectorAll('textarea.form-control').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
    
    // Form validation visual feedback
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let hasError = false;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    hasError = true;
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (hasError) {
                e.preventDefault();
            }
        });
    });
    
    // ============================================
    // TABLE SORTING (simple)
    // ============================================
    document.querySelectorAll('.table th[data-sort]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const table = this.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const column = this.cellIndex;
            const isAsc = this.classList.contains('sort-asc');
            
            // Remove sort classes from all headers
            table.querySelectorAll('th').forEach(header => {
                header.classList.remove('sort-asc', 'sort-desc');
            });
            
            // Sort rows
            rows.sort((a, b) => {
                const aVal = a.cells[column].textContent.trim();
                const bVal = b.cells[column].textContent.trim();
                
                if (!isNaN(aVal) && !isNaN(bVal)) {
                    return isAsc ? bVal - aVal : aVal - bVal;
                }
                return isAsc ? bVal.localeCompare(aVal) : aVal.localeCompare(bVal);
            });
            
            // Update DOM
            rows.forEach(row => tbody.appendChild(row));
            this.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
        });
    });
    
    // ============================================
    // SEARCH FILTER (client-side)
    // ============================================
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const query = this.value.toLowerCase();
                const table = document.querySelector('.table tbody');
                
                if (table) {
                    table.querySelectorAll('tr').forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(query) ? '' : 'none';
                    });
                }
            }, 300);
        });
    }
    
    // ============================================
    // TOOLTIPS (simple)
    // ============================================
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.dataset.tooltip;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
            tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
        });
        
        el.addEventListener('mouseleave', function() {
            document.querySelectorAll('.tooltip').forEach(t => t.remove());
        });
    });
    
    // ============================================
    // COPY TO CLIPBOARD
    // ============================================
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copié dans le presse-papiers');
        });
    };
    
    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    window.showToast = function(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Remove after delay
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };
    
    // Add toast styles dynamically
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast {
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 12px 20px;
                background: #333;
                color: white;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                transform: translateY(100px);
                opacity: 0;
                transition: all 0.3s ease;
                z-index: 9999;
            }
            .toast.show {
                transform: translateY(0);
                opacity: 1;
            }
            .toast-success { background: #28a745; }
            .toast-error { background: #dc3545; }
            .toast-info { background: #17a2b8; }
        `;
        document.head.appendChild(style);
    }
    
    // ============================================
    // API HELPER
    // ============================================
    window.api = {
        baseUrl: document.querySelector('meta[name="api-url"]')?.content || 'api/api.php',
        
        async request(action, data = {}) {
            try {
                const formData = new FormData();
                formData.append('action', action);
                
                for (const [key, value] of Object.entries(data)) {
                    formData.append(key, value);
                }
                
                const response = await fetch(this.baseUrl, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                return result;
            } catch (error) {
                console.error('API Error:', error);
                return { success: false, error: error.message };
            }
        },
        
        async get(action, params = {}) {
            const url = new URL(this.baseUrl, window.location.origin);
            url.searchParams.append('action', action);
            
            for (const [key, value] of Object.entries(params)) {
                url.searchParams.append(key, value);
            }
            
            try {
                const response = await fetch(url);
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return { success: false, error: error.message };
            }
        }
    };
    
    // ============================================
    // LOADING STATES
    // ============================================
    window.setLoading = function(element, loading = true) {
        if (loading) {
            element.dataset.originalContent = element.innerHTML;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            element.disabled = true;
        } else {
            element.innerHTML = element.dataset.originalContent;
            element.disabled = false;
        }
    };
    
});
