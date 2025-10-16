// Fonctions utilitaires globales
class AppUtils {
    // Initialisation de l'application
    static init() {
        this.initAnimations();
        this.initNotifications();
        this.initForms();
        this.initPasswordToggle();
    }

    // Animations au chargement
    static initAnimations() {
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in, .slide-up');
            elements.forEach(el => {
                el.style.opacity = '0';
                setTimeout(() => {
                    el.style.transition = 'all 0.6s ease';
                    el.style.opacity = '1';
                    if (el.classList.contains('slide-up')) {
                        el.style.transform = 'translateY(0)';
                    }
                }, 100);
            });
        });
    }

    // Gestion des notifications
    static initNotifications() {
        // Auto-hide les alerts après 5 secondes
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            }, 5000);
        });
    }

    // Validation des formulaires
    static initForms() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="loading"></span> Chargement...';
                }
            });
        });
    }

    // Toggle mot de passe
    static initPasswordToggle() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('.password-toggle')) {
                const toggle = e.target.closest('.password-toggle');
                const input = toggle.closest('.input-group').querySelector('input');
                const icon = toggle.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            }
        });
    }

    // Messages flash
    static showMessage(message, type = 'success') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.container').prepend(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }

    // Confirmation de suppression
    static confirmDelete(message = 'Êtes-vous sûr de vouloir supprimer ?') {
        return confirm(message);
    }

    // Formatage de date
    static formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Upload d'image avec preview
    static initImageUpload() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', function(e) {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = input.parentElement.querySelector('.image-preview');
                        if (preview) {
                            preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail mt-2" style="max-height: 200px;">`;
                        }
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    }
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', function() {
    AppUtils.init();
});

// Gestion des likes/dislikes
class ReactionManager {
    static async like(publicationId) {
        try {
            const response = await fetch(`like.php?id=${publicationId}&type=like`);
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    static async dislike(publicationId) {
        try {
            const response = await fetch(`like.php?id=${publicationId}&type=dislike`);
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }
}

// Gestion de la messagerie en temps réel
class ChatManager {
    constructor(container) {
        this.container = container;
        this.autoScroll = true;
        this.init();
    }

    init() {
        this.scrollToBottom();
        this.setupAutoRefresh();
    }

    scrollToBottom() {
        if (this.autoScroll) {
            this.container.scrollTop = this.container.scrollHeight;
        }
    }

    setupAutoRefresh() {
        // Refresh toutes les 30 secondes
        setInterval(() => {
            this.refreshMessages();
        }, 30000);
    }

    async refreshMessages() {
        // Implémentation du refresh des messages
    }
}

// Export pour utilisation globale
window.AppUtils = AppUtils;
window.ReactionManager = ReactionManager;
window.ChatManager = ChatManager;