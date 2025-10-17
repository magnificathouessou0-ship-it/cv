// Gestion spécifique des notifications
class NotificationSystem {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.markAllAsRead();
    }

    // Liaison des événements
    bindEvents() {
        // Marquer comme lu
        document.querySelectorAll('.mark-as-read').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.markAsRead(e.target.closest('[data-notification-id]'));
            });
        });

        // Supprimer une notification
        document.querySelectorAll('.delete-notification').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.deleteNotification(e.target.closest('[data-notification-id]'));
            });
        });

        // Supprimer toutes les notifications
        const deleteAllBtn = document.querySelector('.delete-all-notifications');
        if (deleteAllBtn) {
            deleteAllBtn.addEventListener('click', () => {
                this.deleteAllNotifications();
            });
        }

        // Refresh manuel
        const refreshBtn = document.querySelector('.refresh-notifications');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshNotifications();
            });
        }
    }

    // Marquer une notification comme lue
    async markAsRead(notificationElement) {
        const notificationId = notificationElement.dataset.notificationId;
        
        try {
            const response = await fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            });

            if (response.ok) {
                notificationElement.classList.remove('unread');
                notificationElement.classList.add('read');
                this.updateNotificationCount();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    // Supprimer une notification
    async deleteNotification(notificationElement) {
        const notificationId = notificationElement.dataset.notificationId;
        
        try {
            const response = await fetch('delete_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            });

            if (response.ok) {
                notificationElement.style.opacity = '0';
                setTimeout(() => {
                    notificationElement.remove();
                    this.updateNotificationCount();
                }, 300);
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    // Supprimer toutes les notifications
    async deleteAllNotifications() {
        if (!confirm('Voulez-vous vraiment supprimer toutes les notifications ?')) {
            return;
        }

        try {
            const response = await fetch('delete_all_notifications.php', {
                method: 'POST'
            });

            if (response.ok) {
                document.querySelectorAll('[data-notification-id]').forEach(el => {
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 300);
                });
                this.updateNotificationCount();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    // Rafraîchir les notifications
    async refreshNotifications() {
        try {
            const response = await fetch('get_notifications.php');
            if (response.ok) {
                const notifications = await response.json();
                this.renderNotifications(notifications);
                this.updateNotificationCount();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    // Rendu des notifications
    renderNotifications(notifications) {
        const container = document.querySelector('.notifications-container');
        if (!container) return;

        container.innerHTML = notifications.map(notif => `
            <div class="notification-item ${notif.lu ? 'read' : 'unread'}" data-notification-id="${notif.id}">
                <div class="notification-content">
                    <p class="notification-message">${notif.message}</p>
                    <small class="notification-time">${this.formatTime(notif.date_notif)}</small>
                </div>
                <div class="notification-actions">
                    ${!notif.lu ? `
                        <button class="btn btn-sm btn-success mark-as-read" title="Marquer comme lu">
                            <i class="fas fa-check"></i>
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-danger delete-notification" title="Supprimer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `).join('');

        this.bindEvents();
    }

    // Mettre à jour le compteur de notifications
    updateNotificationCount() {
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        const badge = document.querySelector('.notification-badge');
        
        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    // Marquer toutes comme lues
    async markAllAsRead() {
        const markAllBtn = document.querySelector('.mark-all-read');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', async () => {
                try {
                    const response = await fetch('mark_all_notifications_read.php', {
                        method: 'POST'
                    });

                    if (response.ok) {
                        document.querySelectorAll('.notification-item').forEach(item => {
                            item.classList.remove('unread');
                            item.classList.add('read');
                        });
                        this.updateNotificationCount();
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                }
            });
        }
    }

    // Formatage du temps
    formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;

        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'À l\'instant';
        if (minutes < 60) return `Il y a ${minutes} min`;
        if (hours < 24) return `Il y a ${hours} h`;
        if (days < 7) return `Il y a ${days} j`;
        
        return date.toLocaleDateString('fr-FR');
    }
}

// Gestionnaire de notifications en temps réel (WebSocket alternative)
class RealTimeNotifications {
    constructor() {
        this.retryCount = 0;
        this.maxRetries = 5;
        this.init();
    }

    async init() {
        if (typeof io !== 'undefined') {
            this.setupSocketIO();
        } else {
            this.setupPolling();
        }
    }

    // Configuration WebSocket (Socket.io)
    setupSocketIO() {
        this.socket = io();

        this.socket.on('connect', () => {
            console.log('Connecté aux notifications en temps réel');
            this.retryCount = 0;
        });

        this.socket.on('new_notification', (data) => {
            this.handleNewNotification(data);
        });

        this.socket.on('disconnect', () => {
            console.log('Déconnecté des notifications');
            this.attemptReconnect();
        });
    }

    // Configuration du polling (fallback)
    setupPolling() {
        this.pollInterval = setInterval(() => {
            this.checkForNewNotifications();
        }, 15000); // Toutes les 15 secondes
    }

    // Vérifier les nouvelles notifications
    async checkForNewNotifications() {
        try {
            const response = await fetch('check_new_notifications.php');
            if (response.ok) {
                const data = await response.json();
                if (data.has_new) {
                    this.handleNewNotification(data.notification);
                }
            }
        } catch (error) {
            console.error('Erreur de vérification des notifications:', error);
        }
    }

    // Gérer une nouvelle notification
    handleNewNotification(notification) {
        // Afficher une notification toast
        this.showToastNotification(notification);
        
        // Mettre à jour le compteur
        this.incrementNotificationCount();
        
        // Jouer un son
        this.playNotificationSound();
        
        // Actualiser la liste si on est sur la page des notifications
        if (window.location.pathname.includes('notifications.php')) {
            window.notificationSystem.refreshNotifications();
        }
    }

    // Afficher une notification toast
    showToastNotification(notification) {
        const toast = document.createElement('div');
        toast.className = 'notification-toast position-fixed';
        toast.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            background: white;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
            padding: 15px;
            animation: slideInRight 0.3s ease;
        `;

        toast.innerHTML = `
            <div class="d-flex align-items-start">
                <i class="fas fa-bell text-primary me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <h6 class="mb-1">Nouvelle notification</h6>
                    <p class="mb-2 small">${notification.message}</p>
                    <small class="text-muted">${new Date().toLocaleTimeString('fr-FR')}</small>
                </div>
                <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;

        document.body.appendChild(toast);

        // Auto-remove après 5 secondes
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }

    // Incrémenter le compteur de notifications
    incrementNotificationCount() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            const currentCount = parseInt(badge.textContent) || 0;
            badge.textContent = currentCount + 1;
            badge.style.display = 'inline-block';
        }
    }

    // Jouer un son de notification
    playNotificationSound() {
        // Créer un son simple avec Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.1);
            gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.3);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (error) {
            console.log('Audio non supporté');
        }
    }

    // Tentative de reconnexion
    attemptReconnect() {
        if (this.retryCount < this.maxRetries) {
            this.retryCount++;
            setTimeout(() => {
                this.init();
            }, Math.pow(2, this.retryCount) * 1000); // Backoff exponentiel
        }
    }

    // Nettoyage
    destroy() {
        if (this.socket) {
            this.socket.disconnect();
        }
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le système de notifications
    window.notificationSystem = new NotificationSystem();
    
    // Initialiser les notifications en temps réel si l'utilisateur est connecté
    if (document.body.classList.contains('user-logged-in')) {
        window.realTimeNotifications = new RealTimeNotifications();
    }
});

// Styles CSS pour les notifications
const notificationStyles = `
.notification-item {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.notification-item.unread {
    border-left-color: var(--primary-color);
    background-color: rgba(46, 139, 87, 0.05);
}

.notification-item.read {
    opacity: 0.8;
}

.notification-item:hover {
    transform: translateX(5px);
}

.notification-actions {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.notification-item:hover .notification-actions {
    opacity: 1;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.notification-badge.pulse {
    animation: pulse 0.6s ease-in-out;
}
`;

// Ajouter les styles au document
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);

// Export pour utilisation globale
window.NotificationSystem = NotificationSystem;
window.RealTimeNotifications = RealTimeNotifications;