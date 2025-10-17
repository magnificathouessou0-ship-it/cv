<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

$user_id = $_SESSION['id'];

// Gestion des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['marquer_lu'])) {
        $notif_id = intval($_POST['notif_id']);
        $stmt = $bdd->prepare("UPDATE notifications SET lu = 1 WHERE id = ? AND id_users = ?");
        $stmt->execute([$notif_id, $user_id]);
    } elseif (isset($_POST['marquer_tout_lu'])) {
        $stmt = $bdd->prepare("UPDATE notifications SET lu = 1 WHERE id_users = ? AND lu = 0");
        $stmt->execute([$user_id]);
    } elseif (isset($_POST['supprimer'])) {
        $notif_id = intval($_POST['notif_id']);
        $stmt = $bdd->prepare("DELETE FROM notifications WHERE id = ? AND id_users = ?");
        $stmt->execute([$notif_id, $user_id]);
    } elseif (isset($_POST['supprimer_tout'])) {
        $stmt = $bdd->prepare("DELETE FROM notifications WHERE id_users = ?");
        $stmt->execute([$user_id]);
    }
    
    // Redirection pour éviter la soumission multiple
    redirect("notifications.php");
}

// Récupération notifications triées par date décroissante
$stmt = $bdd->prepare("SELECT * FROM notifications WHERE id_users = ? ORDER BY date_notif DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter les notifications non lues
$non_lues = array_filter($notifications, function($notif) {
    return !$notif['lu'];
});
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mes Notifications | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <style>
        .notification-item {
            border: none;
            border-left: 4px solid transparent;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .notification-item.unread {
            border-left-color: #007bff;
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,123,255,0.1);
        }
        .notification-item.read {
            background-color: #ffffff;
            border-left-color: #6c757d;
        }
        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .notification-icon.unread {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        .notification-icon.read {
            background: #e9ecef;
            color: #6c757d;
        }
        .notification-icon.message {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .notification-icon.friend {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }
        .notification-icon.system {
            background: linear-gradient(135deg, #6c757d, #495057);
        }
        .notification-actions {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .notification-item:hover .notification-actions {
            opacity: 1;
        }
        .btn-action {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            transform: scale(1.1);
        }
        .empty-state {
            padding: 60px 20px;
        }
        .badge-notification {
            font-size: 0.7rem;
            padding: 3px 8px;
        }
        .notification-message {
            line-height: 1.4;
        }
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .delete-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }
    </style>
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dash1.php">
            <i class="fas fa-users me-2"></i>TalkSpace
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dash1.php"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link active" href="notifications.php"><i class="fas fa-bell me-1"></i>Notifications</a></li>
                <li class="nav-item"><a class="nav-link" href="explorer.php"><i class="fas fa-search me-1"></i>Explorer</a></li>
                <li class="nav-item"><a class="nav-link" href="liste_amis.php"><i class="fas fa-user-friends me-1"></i>Amis</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="profil.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="deconnexion.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="card slide-up">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0"><i class="fas fa-bell me-2"></i>Mes Notifications</h4>
                <?php if (count($notifications) > 0): ?>
                    <small class="text-muted">
                        <?= count($notifications) ?> notification(s) - 
                        <?= count($non_lues) ?> non lue(s)
                    </small>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <?php if (count($non_lues) > 0): ?>
                    <form method="post" class="d-inline">
                        <button type="submit" name="marquer_tout_lu" class="btn btn-success btn-sm">
                            <i class="fas fa-check-double me-1"></i>Tout marquer comme lu
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if (count($notifications) > 0): ?>
                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAllModal">
                        <i class="fas fa-trash me-1"></i>Tout supprimer
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ($notifications): ?>
                <div class="p-3">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?= $notif['lu'] ? 'read' : 'unread' ?> p-3">
                            <div class="d-flex align-items-start">
                                <?php
                                // Déterminer l'icône en fonction du type de notification
                                $icon_class = 'bell';
                                $icon_type = 'system';
                                if (strpos(strtolower($notif['message']), 'message') !== false || strpos(strtolower($notif['message']), 'discuter') !== false) {
                                    $icon_class = 'comments';
                                    $icon_type = 'message';
                                } elseif (strpos(strtolower($notif['message']), 'ami') !== false || strpos(strtolower($notif['message']), 'demande') !== false) {
                                    $icon_class = 'user-friends';
                                    $icon_type = 'friend';
                                }
                                ?>
                                <div class="notification-icon <?= $notif['lu'] ? 'read' : $icon_type ?>">
                                    <i class="fas fa-<?= $icon_class ?>"></i>
                                </div>
                                
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="me-3">
                                            <p class="mb-1 notification-message <?= $notif['lu'] ? 'text-muted' : 'fw-bold' ?>">
                                                <?= htmlspecialchars($notif['message'], ENT_QUOTES | ENT_HTML5) ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('d/m/Y à H:i', strtotime($notif['date_notif'])) ?>
                                            </small>
                                        </div>
                                        
                                        <div class="notification-actions d-flex gap-1">
                                            <?php if (!$notif['lu']): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="notif_id" value="<?= $notif['id'] ?>">
                                                    <button type="submit" name="marquer_lu" class="btn-action btn-success" 
                                                            title="Marquer comme lu">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <button type="button" class="btn-action btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal"
                                                    data-notif-id="<?= $notif['id'] ?>"
                                                    data-notif-message="<?= htmlspecialchars($notif['message']) ?>"
                                                    title="Supprimer">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <?php if (!$notif['lu']): ?>
                                            <span class="badge badge-notification bg-primary">Nouveau</span>
                                        <?php else: ?>
                                            <span class="badge badge-notification bg-secondary">Lu</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($icon_type === 'message'): ?>
                                            <span class="badge badge-notification bg-success">Message</span>
                                        <?php elseif ($icon_type === 'friend'): ?>
                                            <span class="badge badge-notification bg-warning text-dark">Ami</span>
                                        <?php else: ?>
                                            <span class="badge badge-notification bg-info">Système</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state text-center">
                    <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune notification</h5>
                    <p class="text-muted mb-4">Vous serez notifié des nouvelles activités ici.</p>
                    <a href="dash1.php" class="btn btn-primary">
                        <i class="fas fa-home me-1"></i>Retour à l'accueil
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($notifications): ?>
            <div class="card-footer bg-light">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <?= count($non_lues) ?> notification(s) non lue(s)
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            Dernière mise à jour : <?= date('H:i:s') ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Suppression Simple -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="delete-icon">
                    <i class="fas fa-exclamation"></i>
                </div>
                <h5 class="modal-title mb-3">Supprimer la notification</h5>
                <p class="text-muted mb-4" id="notificationMessage">
                    Êtes-vous sûr de vouloir supprimer cette notification ?
                </p>
                <div class="d-flex gap-3 justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <form method="post" id="deleteForm">
                        <input type="hidden" name="notif_id" id="deleteNotifId">
                        <button type="submit" name="supprimer" class="btn btn-danger px-4">
                            <i class="fas fa-trash me-2"></i>Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Suppression Totale -->
<div class="modal fade" id="deleteAllModal" tabindex="-1" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="delete-icon">
                    <i class="fas fa-trash"></i>
                </div>
                <h5 class="modal-title mb-3">Supprimer toutes les notifications</h5>
                <p class="text-muted mb-4">
                    Êtes-vous sûr de vouloir supprimer <strong>toutes vos notifications</strong> ?<br>
                    <small>Cette action est irréversible.</small>
                </p>
                <div class="d-flex gap-3 justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <form method="post">
                        <button type="submit" name="supprimer_tout" class="btn btn-danger px-4">
                            <i class="fas fa-trash me-2"></i>Tout supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Animation d'apparition des notifications
document.addEventListener('DOMContentLoaded', function() {
    const notifications = document.querySelectorAll('.notification-item');
    notifications.forEach((notification, index) => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            notification.style.transition = 'all 0.4s ease';
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, index * 100);
    });
});

// Gestion du modal de suppression
const deleteModal = document.getElementById('deleteModal');
deleteModal.addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const notifId = button.getAttribute('data-notif-id');
    const notifMessage = button.getAttribute('data-notif-message');
    
    document.getElementById('deleteNotifId').value = notifId;
    document.getElementById('notificationMessage').innerHTML = 
        `Êtes-vous sûr de vouloir supprimer la notification :<br><strong>"${notifMessage}"</strong> ?`;
});

// Auto-refresh des notifications toutes les 10 secondes
setInterval(() => {
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector('.card-body');
            if (newContent) {
                document.querySelector('.card-body').innerHTML = newContent.innerHTML;
            }
        })
        .catch(error => console.log('Erreur de rafraîchissement:', error));
}, 10000);
</script>
</body>
</html>