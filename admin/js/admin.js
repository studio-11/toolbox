/**
 * IFEN Toolbox Admin - JavaScript
 * 
 * Utilities et fonctions communes pour l'administration
 */

// Configuration
const ADMIN_CONFIG = {
    apiUrl: '../api.php',
    adminApiUrl: 'api-admin.php'
};

// ==================== UTILITIES ====================

/**
 * Afficher une notification toast
 */
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    
    const icon = type === 'success' ? 'check-circle' : 
                 type === 'danger' ? 'exclamation-circle' :
                 type === 'warning' ? 'exclamation-triangle' : 'info-circle';
    
    notification.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Confirmer une action
 */
async function confirmAction(message, title = 'Confirmer') {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay active';
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">${title}</h3>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-action="cancel">Annuler</button>
                    <button class="btn btn-danger" data-action="confirm">Confirmer</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.addEventListener('click', (e) => {
            if (e.target.dataset.action === 'confirm') {
                resolve(true);
                modal.remove();
            } else if (e.target.dataset.action === 'cancel' || e.target === modal) {
                resolve(false);
                modal.remove();
            }
        });
    });
}

/**
 * Appel API avec gestion d'erreurs
 */
async function apiCall(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Erreur serveur');
        }
        
        return data.data;
    } catch (error) {
        console.error('API Error:', error);
        showNotification(error.message, 'danger');
        throw error;
    }
}

/**
 * Formater une date
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

/**
 * Formater un nombre
 */
function formatNumber(num) {
    return new Intl.NumberFormat('fr-FR').format(num);
}

/**
 * Débounce pour les recherches
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Échapper le HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ==================== MODALS ====================

/**
 * Ouvrir une modal
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

/**
 * Fermer une modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Fermer modal en cliquant sur l'overlay
 */
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// ==================== TABLES ====================

/**
 * Créer un bouton d'action pour table
 */
function createActionButton(icon, title, onClick, className = 'btn-secondary') {
    const btn = document.createElement('button');
    btn.className = `btn ${className} btn-sm btn-icon`;
    btn.title = title;
    btn.innerHTML = `<i class="fas fa-${icon}"></i>`;
    btn.addEventListener('click', onClick);
    return btn;
}

/**
 * Créer une ligne de table vide (empty state)
 */
function createEmptyRow(colspan, message) {
    return `
        <tr>
            <td colspan="${colspan}" class="text-center text-muted">
                <i class="fas fa-inbox fa-3x" style="opacity: 0.3; margin: 20px 0;"></i>
                <p>${message}</p>
            </td>
        </tr>
    `;
}

// ==================== FORMS ====================

/**
 * Désactiver un formulaire pendant l'envoi
 */
function disableForm(form, disable = true) {
    const elements = form.querySelectorAll('input, textarea, select, button');
    elements.forEach(el => {
        el.disabled = disable;
    });
}

/**
 * Réinitialiser un formulaire
 */
function resetForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
    }
}

/**
 * Valider un formulaire
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--danger)';
            isValid = false;
        } else {
            field.style.borderColor = '';
        }
    });
    
    if (!isValid) {
        showNotification('Veuillez remplir tous les champs obligatoires', 'warning');
    }
    
    return isValid;
}

// ==================== UPLOAD ====================

/**
 * Prévisualiser une image
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview || !input.files || !input.files[0]) return;
    
    const reader = new FileReader();
    reader.onload = (e) => {
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

/**
 * Upload de fichier avec progression
 */
async function uploadFile(file, url) {
    const formData = new FormData();
    formData.append('file', file);
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Erreur upload');
        }
        
        return data.data;
    } catch (error) {
        showNotification('Erreur lors de l\'upload: ' + error.message, 'danger');
        throw error;
    }
}

// ==================== SEARCH & FILTER ====================

/**
 * Filtrer une table
 */
function filterTable(tableId, searchTerm, columnIndex = null) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const term = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const cells = columnIndex !== null ? 
            [row.cells[columnIndex]] : 
            Array.from(row.cells);
        
        const text = cells.map(cell => cell.textContent.toLowerCase()).join(' ');
        row.style.display = text.includes(term) ? '' : 'none';
    });
}

// ==================== SORTING ====================

/**
 * Trier une table
 */
function sortTable(tableId, columnIndex, isNumeric = false) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent.trim();
        const bVal = b.cells[columnIndex].textContent.trim();
        
        if (isNumeric) {
            return parseFloat(aVal) - parseFloat(bVal);
        } else {
            return aVal.localeCompare(bVal);
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// ==================== CLIPBOARD ====================

/**
 * Copier du texte dans le presse-papier
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showNotification('Copié dans le presse-papier', 'success');
    } catch (error) {
        showNotification('Erreur de copie', 'danger');
    }
}

// ==================== ANIMATIONS ====================

// Ajouter les animations CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
`;
document.head.appendChild(style);

// ==================== INIT ====================

document.addEventListener('DOMContentLoaded', () => {
    // Fermer les alerts automatiquement
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Gestion logout
    const logoutLinks = document.querySelectorAll('a[href*="logout"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', async (e) => {
            e.preventDefault();
            if (await confirmAction('Voulez-vous vraiment vous déconnecter ?', 'Déconnexion')) {
                window.location.href = link.href;
            }
        });
    });
});

// ==================== EXPORTS ====================

// Exporter les fonctions pour utilisation globale
window.AdminUtils = {
    showNotification,
    confirmAction,
    apiCall,
    formatDate,
    formatNumber,
    debounce,
    escapeHtml,
    openModal,
    closeModal,
    createActionButton,
    createEmptyRow,
    disableForm,
    resetForm,
    validateForm,
    previewImage,
    uploadFile,
    filterTable,
    sortTable,
    copyToClipboard
};