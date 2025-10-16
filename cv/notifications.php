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
        if ($stmt->execute([$notif_id, $user_id])) {
            $_SESSION['success_message'] = "Notification marquée comme lue";
        }
    } elseif (isset($_POST['supprimer'])) {
        $notif_id = intval($_POST['notif_id']);
        $stmt = $bdd->prepare("DELETE FROM notifications WHERE id = ? AND id_users = ?");
        if ($stmt->execute([$notif_id, $user_id])) {
            $_SESSION['success_message'] = "Notification supprimée avec succès";
        }
    } elseif (isset($_POST['supprimer_tout'])) {
        $stmt = $bdd->prepare("DELETE FROM notifications WHERE id_users = ?");
        if ($stmt->execute([$user_id])) {
            $_SESSION['success_message'] = "Toutes les notifications ont été supprimées";
        }
    } elseif (isset($_POST['marquer_tout_lu'])) {
        $stmt = $bdd->prepare("UPDATE notifications SET lu = 1 WHERE id_users = ? AND lu = 0");
        if ($stmt->execute([$user_id])) {
            $_SESSION['success_message'] = "Toutes les notifications marquées comme lues";
        }
    }
    
    // Redirection pour éviter la soumission multiple
    header("Location: notifications.php");
    exit();
}

// Récupération de TOUTES les notifications triées par date décroissante
try {
    $stmt = $bdd->prepare("SELECT * FROM notifications WHERE id_users = ? ORDER BY date_notif DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur récupération notifications: " . $e->getMessage());
    $notifications = [];
}

// Compter les notifications non lues
$non_lues = 0;
foreach ($notifications as $notif) {
    if (!$notif['lu']) {
        $non_lues++;
    }
}
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
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .icon-ami { background: #007bff; color: white; }
        .icon-like { background: #28a745; color: white; }
        .icon-comment { background: #ffc107; color: black; }
        .icon-publish { background: #6f42c1; color: white; }
        .icon-signal { background: #dc3545; color: white; }
        .icon-default { background: #6c757d; color: white; }
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
    <!-- Messages de succès -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="card slide-up">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-bell me-2"></i>Mes Notifications</h4>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($non_lues > 0): ?>
                    <span class="badge bg-danger"><?= $non_lues ?> non lue(s)</span>
                <?php endif; ?>
                
                <?php if (count($notifications) > 0): ?>
                    <?php if ($non_lues > 0): ?>
                        <form method="post" class="d-inline">
                            <button type="submit" name="marquer_tout_lu" class="btn btn-success btn-sm">
                                <i class="fas fa-check-double me-1"></i>Tout marquer comme lu
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalSupprimerTout">
                        <i class="fas fa-trash me-1"></i>Tout supprimer
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (count($notifications) > 0): ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notif): 
                        // Déterminer l'icône en fonction du contenu du message
                        $message = strtolower($notif['message'] ?? '');
                        $icon_class = 'icon-default';
                        $icon = 'fas fa-bell';
                        
                        if (strpos($message, 'ami') !== false || strpos($message, 'demande') !== false) {
                            $icon_class = 'icon-ami';
                            $icon = 'fas fa-user-friends';
                        } elseif (strpos($message, 'like') !== false) {
                            $icon_class = 'icon-like';
                            $icon = 'fas fa-thumbs-up';
                        } elseif (strpos($message, 'comment') !== false || strpos($message, 'commentaire') !== false) {
                            $icon_class = 'icon-comment';
                            $icon = 'fas fa-comment';
                        } elseif (strpos($message, 'publier') !== false || strpos($message, 'publication') !== false) {
                            $icon_class = 'icon-publish';
                            $icon = 'fas fa-newspaper';
                        } elseif (strpos($message, 'signaler') !== false || strpos($message, 'signal') !== false) {
                            $icon_class = 'icon-signal';
                            $icon = 'fas fa-flag';
                        }
                    ?>
                        <div class="list-group-item <?= $notif['lu'] ? 'bg-light' : 'fw-bold border-start border-primary border-3' ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1 d-flex align-items-start">
                                    <div class="notification-icon <?= $icon_class ?>">
                                        <i class="<?= $icon ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="mb-2">
                                            <span><?= htmlspecialchars($notif['message'] ?? '', ENT_QUOTES | ENT_HTML5) ?></span>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('d/m/Y à H:i', strtotime($notif['date_notif'])) ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 ms-3">
                                    <?php if (!$notif['lu']): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="notif_id" value="<?= $notif['id'] ?>">
                                            <button type="submit" name="marquer_lu" class="btn btn-success btn-sm" 
                                                    title="Marquer comme lu">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="btn btn-outline-success btn-sm" title="Déjà lue">
                                            <i class="fas fa-check-double"></i>
                                        </span>
                                    <?php endif; ?>

                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="notif_id" value="<?= $notif['id'] ?>">
                                        <button type="submit" name="supprimer" class="btn btn-danger btn-sm" 
                                                title="Supprimer"
                                                onclick="return confirm('Voulez-vous vraiment supprimer cette notification ?');">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune notification</h5>
                    <p class="text-muted">Vous serez notifié des nouvelles activités ici.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="dash1.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Retour au tableau de bord
        </a>
    </div>
</div>

<!-- Modal Supprimer toutes les notifications -->
<div class="modal fade" id="modalSupprimerTout" tabindex="-1" aria-labelledby="modalSupprimerToutLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalSupprimerToutLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Supprimer toutes les notifications
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-trash-alt text-danger fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">Supprimer toutes les notifications ?</h6>
                        <p class="text-muted mb-0">
                            <span class="fw-bold"><?= count($notifications) ?></span> notification(s) seront définitivement supprimées.
                        </p>
                    </div>
                </div>
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <small><strong>Attention :</strong> Cette action supprimera toutes vos notifications de manière permanente et ne peut pas être annulée.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <form method="post">
                    <button type="submit" name="supprimer_tout" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i>Tout supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Empêcher la soumission multiple pour tous les formulaires SAUF ceux avec confirmation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Ne pas empêcher les formulaires avec confirmation
            const hasConfirmation = this.querySelector('button[onclick*="confirm"]');
            if (hasConfirmation) {
                return true;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Traitement...';
                
                // Réactiver le bouton après 3 secondes au cas où
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || submitBtn.innerHTML;
                    }
                }, 3000);
            }
        });
    });

    // Sauvegarder le texte original des boutons
    document.querySelectorAll('form button[type="submit"]').forEach(btn => {
        btn.setAttribute('data-original-text', btn.innerHTML);
    });

    // Auto-fermeture des alerts après 5 secondes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.isConnected) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
});
</script>
</body>
</html>