// Gestion des animations et interactions utilisateur
class Animations {
    constructor() {
        this.initScrollAnimations();
        this.initHoverEffects();
        this.initLoadingStates();
        this.initTooltips();
    }

    // Animations au défilement
    initScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);

        // Observer les éléments avec la classe animate-on-scroll
        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });
    }

    // Effets de survol
    initHoverEffects() {
        // Cards hover effects
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
                card.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            });
        });

        // Button hover effects
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'translateY(-2px)';
            });

            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'translateY(0)';
            });
        });
    }

    // États de chargement
    initLoadingStates() {
        // Gestion du chargement des formulaires
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !form.classList.contains('no-loading')) {
                    this.showButtonLoading(submitBtn);
                }
            });
        });
    }

    // Affichage du chargement sur les boutons
    showButtonLoading(button) {
        const originalText = button.innerHTML;
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
            Chargement...
        `;
        button.disabled = true;

        // Réinitialiser après 10 secondes maximum (sécurité)
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 10000);
    }

    // Tooltips Bootstrap
    initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Animation de notification
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show notification-toast`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        `;
        
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    // Confirmation modale personnalisée
    confirmAction(message, confirmText = 'Confirmer', cancelText = 'Annuler') {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Confirmation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${cancelText}</button>
                            <button type="button" class="btn btn-primary" id="confirmBtn">${confirmText}</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

            modal.querySelector('#confirmBtn').addEventListener('click', () => {
                modalInstance.hide();
                resolve(true);
            });

            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
                resolve(false);
            });
        });
    }
}

// Gestionnaire de formulaires avancé
class FormHandler {
    constructor() {
        this.initFormValidation();
        this.initCharacterCounters();
        this.initFileUploads();
    }

    // Validation des formulaires
    initFormValidation() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    this.showFormErrors(form);
                }
            });
        });
    }

    // Validation d'un formulaire
    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');

        inputs.forEach(input => {
            if (!input.value.trim()) {
                this.markInvalid(input, 'Ce champ est obligatoire');
                isValid = false;
            } else {
                this.markValid(input);
            }

            // Validation email
            if (input.type === 'email' && input.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(input.value)) {
                    this.markInvalid(input, 'Format d\'email invalide');
                    isValid = false;
                }
            }

            // Validation mot de passe
            if (input.type === 'password' && input.value) {
                if (input.value.length < 8) {
                    this.markInvalid(input, 'Le mot de passe doit contenir au moins 8 caractères');
                    isValid = false;
                }
            }
        });

        return isValid;
    }

    // Marquer un champ comme invalide
    markInvalid(input, message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');

        // Ajouter le message d'erreur
        let feedback = input.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    }

    // Marquer un champ comme valide
    markValid(input) {
        input.classList.add('is-valid');
        input.classList.remove('is-invalid');
    }

    // Afficher les erreurs du formulaire
    showFormErrors(form) {
        const firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) {
            firstInvalid.focus();
        }
    }

    // Compteurs de caractères
    initCharacterCounters() {
        document.querySelectorAll('textarea[maxlength]').forEach(textarea => {
            const maxLength = textarea.getAttribute('maxlength');
            const counter = document.createElement('div');
            counter.className = 'form-text text-end character-counter';
            counter.innerHTML = `<span class="char-count">0</span>/${maxLength} caractères`;
            
            textarea.parentNode.appendChild(counter);

            textarea.addEventListener('input', () => {
                const count = textarea.value.length;
                counter.querySelector('.char-count').textContent = count;
                
                if (count > maxLength * 0.9) {
                    counter.classList.add('text-warning');
                } else {
                    counter.classList.remove('text-warning');
                }

                if (count > maxLength) {
                    counter.classList.add('text-danger');
                } else {
                    counter.classList.remove('text-danger');
                }
            });

            // Initial count
            textarea.dispatchEvent(new Event('input'));
        });
    }

    // Gestion des uploads de fichiers
    initFileUploads() {
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.validateFile(input, file);
                }
            });
        });
    }

    // Validation des fichiers
    validateFile(input, file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];

        if (file.size > maxSize) {
            this.markInvalid(input, 'Le fichier est trop volumineux (max 5MB)');
            input.value = '';
            return false;
        }

        if (!allowedTypes.includes(file.type)) {
            this.markInvalid(input, 'Type de fichier non autorisé (JPG, PNG, GIF uniquement)');
            input.value = '';
            return false;
        }

        this.markValid(input);
        return true;
    }
}

// Gestionnaire de notifications en temps réel
class NotificationManager {
    constructor() {
        this.notificationCount = 0;
        this.initPolling();
        this.initSound();
    }

    // Polling pour les nouvelles notifications
    initPolling() {
        setInterval(() => {
            this.checkNewNotifications();
        }, 30000); // Vérifier toutes les 30 secondes
    }

    // Vérifier les nouvelles notifications
    async checkNewNotifications() {
        try {
            const response = await fetch('check_notifications.php');
            if (response.ok) {
                const data = await response.json();
                if (data.count > this.notificationCount) {
                    this.showNewNotification(data);
                    this.notificationCount = data.count;
                }
            }
        } catch (error) {
            console.error('Erreur de vérification des notifications:', error);
        }
    }

    // Afficher une nouvelle notification
    showNewNotification(data) {
        // Mettre à jour le compteur dans la navbar
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = data.count;
        }

        // Afficher une notification toast
        if (data.latest) {
            const animations = new Animations();
            animations.showNotification(data.latest.message, 'info');
            this.playNotificationSound();
        }
    }

    // Son de notification
    initSound() {
        this.notificationSound = new Audio('assets/sounds/notification.mp3');
    }

    playNotificationSound() {
        if (this.notificationSound) {
            this.notificationSound.play().catch(e => console.log('Son de notification ignoré'));
        }
    }
}

// Initialisation globale
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les gestionnaires
    window.appAnimations = new Animations();
    window.formHandler = new FormHandler();
    window.notificationManager = new NotificationManager();

    // Ajouter les styles d'animation
    const style = document.createElement('style');
    style.textContent = `
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .animate-on-scroll.animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .notification-toast {
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .spinner-border {
            width: 1rem;
            height: 1rem;
        }
        
        .character-counter.text-warning {
            font-weight: bold;
        }
        
        .character-counter.text-danger {
            font-weight: bold;
            color: #dc3545 !important;
        }
    `;
    document.head.appendChild(style);
});

// Export pour utilisation globale
window.Animations = Animations;
window.FormHandler = FormHandler;
window.NotificationManager = NotificationManager;