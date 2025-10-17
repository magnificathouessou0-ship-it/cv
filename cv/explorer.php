<?php
session_start();
require_once('functions.php');
require_once('config.php');
requireLogin();

$user_id = $_SESSION['id'];

// Débloquer un utilisateur
if (isset($_GET['unblock'])) {
    $id_bloque = intval($_GET['unblock']);
    $del = $bdd->prepare("DELETE FROM blocages WHERE id_user=? AND id_bloque=?");
    $del->execute([$user_id, $id_bloque]);
    redirect("explorer.php?tab=blocages");
}

// Bloquer un utilisateur
if (isset($_GET['block'])) {
    $id_bloque = intval($_GET['block']);
    $check = $bdd->prepare("SELECT 1 FROM blocages WHERE id_user=? AND id_bloque=?");
    $check->execute([$user_id, $id_bloque]);
    if ($check->rowCount() == 0) {
        $insert = $bdd->prepare("INSERT INTO blocages (id_user, id_bloque) VALUES (?, ?)");
        $insert->execute([$user_id, $id_bloque]);
    }
    redirect("explorer.php?tab=explorer");
}

// Gestion des demandes d'amis
if (isset($_GET['action']) && isset($_GET['id'])) {
    $ami_id = intval($_GET['id']);
    if ($_GET['action'] === 'accepter') {
        $bdd->prepare("UPDATE demandes_amis SET statut='accepter' WHERE sender_id=? AND receiver_id=?")
            ->execute([$ami_id, $user_id]);
    } elseif ($_GET['action'] === 'refuser') {
        $bdd->prepare("UPDATE demandes_amis SET statut='refuser' WHERE sender_id=? AND receiver_id=?")
            ->execute([$ami_id, $user_id]);
    }
    redirect("explorer.php?tab=invitations");
}

// Récupérer les utilisateurs (sauf soi-même et les bloqués)
$query = $bdd->prepare("SELECT u.* FROM users u 
                       WHERE u.id != ? 
                       AND u.id NOT IN (
                           SELECT id_bloque FROM blocages WHERE id_user = ? 
                           UNION 
                           SELECT id_user FROM blocages WHERE id_bloque = ?
                       )");
$query->execute([$user_id, $user_id, $user_id]);
$users = $query->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les demandes envoyées
$demandes_envoyees = $bdd->prepare("SELECT receiver_id, statut FROM demandes_amis WHERE sender_id = ?");
$demandes_envoyees->execute([$user_id]);
$envoyees = $demandes_envoyees->fetchAll(PDO::FETCH_KEY_PAIR);

// Récupérer les invitations reçues
$demandes_recues = $bdd->prepare("SELECT d.sender_id, d.statut, u.nom, u.prenom, u.email 
                                 FROM demandes_amis d 
                                 JOIN users u ON d.sender_id = u.id 
                                 WHERE d.receiver_id = ? AND d.statut = 'en_attente'");
$demandes_recues->execute([$user_id]);
$invitations = $demandes_recues->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les utilisateurs bloqués
$query_bloques = $bdd->prepare("SELECT b.id_bloque, u.nom, u.prenom, u.email, b.date_blocage 
                               FROM blocages b 
                               JOIN users u ON u.id = b.id_bloque 
                               WHERE b.id_user = ? 
                               ORDER BY b.date_blocage DESC");
$query_bloques->execute([$user_id]);
$bloques = $query_bloques->fetchAll(PDO::FETCH_ASSOC);

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'explorer';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorer | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
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
                <li class="nav-item"><a class="nav-link active" href="explorer.php"><i class="fas fa-search me-1"></i>Explorer</a></li>
                <li class="nav-item"><a class="nav-link" href="liste_amis.php"><i class="fas fa-user-friends me-1"></i>Amis</a></li>
                <li class="nav-item"><a class="nav-link" href="messagerie.php"><i class="fas fa-comments me-1"></i>Messagerie</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="profil.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="deconnexion.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- Navigation par onglets -->
    <div class="card mb-4">
        <div class="card-body">
            <ul class="nav nav-pills justify-content-center mb-0 gap-2">
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'explorer' ? 'active' : '' ?>" href="?tab=explorer">
                        <i class="fas fa-search me-1"></i>Explorer
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'invitations' ? 'active' : '' ?>" href="?tab=invitations">
                        <i class="fas fa-user-clock me-1"></i>Invitations
                        <?php if (count($invitations) > 0): ?>
                            <span class="badge bg-danger ms-1"><?= count($invitations) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'blocages' ? 'active' : '' ?>" href="?tab=blocages">
                        <i class="fas fa-ban me-1"></i>Bloqués
                        <?php if (count($bloques) > 0): ?>
                            <span class="badge bg-secondary ms-1"><?= count($bloques) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <?php if ($tab === 'explorer'): ?>
        <!-- Exploration des utilisateurs -->
        <div class="card slide-up">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-search me-2"></i>Découvrir de nouveaux amis</h4>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>
                    <div class="row">
                        <?php foreach ($users as $u): ?>
                            <div class="col-12 col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 user-card">
                                    <div class="card-body text-center">
                                        <div class="user-avatar mb-3">
                                            <i class="fas fa-user-circle fa-3x text-primary"></i>
                                        </div>
                                        <h6 class="card-title"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></h6>
                                        <p class="card-text text-muted small">
                                            <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($u['email']) ?>
                                        </p>
                                        
                                        <div class="d-flex flex-column gap-2">
                                            <?php if (isset($envoyees[$u['id']])): ?>
                                                <?php if ($envoyees[$u['id']] == 'en_attente'): ?>
                                                    <button class="btn btn-warning btn-sm w-100" disabled>
                                                        <i class="fas fa-clock me-1"></i>En attente
                                                    </button>
                                                <?php elseif ($envoyees[$u['id']] == 'accepter'): ?>
                                                    <button class="btn btn-success btn-sm w-100" disabled>
                                                        <i class="fas fa-check me-1"></i>Ami
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="ajouter_ami.php?id=<?= $u['id'] ?>" class="btn btn-primary btn-sm w-100">
                                                    <i class="fas fa-user-plus me-1"></i>Ajouter
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="?block=<?= $u['id'] ?>&tab=explorer" class="btn btn-outline-danger btn-sm w-100"
                                               onclick="return confirm('Bloquer cet utilisateur ?')">
                                                <i class="fas fa-ban me-1"></i>Bloquer
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun utilisateur à découvrir</h5>
                        <p class="text-muted">Tous les utilisateurs sont déjà vos amis ou bloqués</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($tab === 'invitations'): ?>
        <!-- Invitations reçues -->
        <div class="card slide-up">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-user-clock me-2"></i>Invitations reçues</h4>
            </div>
            <div class="card-body">
                <?php if (count($invitations) > 0): ?>
                    <div class="row">
                        <?php foreach ($invitations as $inv): ?>
                            <div class="col-12 col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-user-circle fa-3x text-warning"></i>
                                        </div>
                                        <h6 class="card-title"><?= htmlspecialchars($inv['prenom'] . ' ' . $inv['nom']) ?></h6>
                                        <p class="card-text text-muted small"><?= htmlspecialchars($inv['email']) ?></p>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="?action=accepter&id=<?= $inv['sender_id'] ?>&tab=invitations" 
                                               class="btn btn-success btn-sm">
                                                <i class="fas fa-check me-1"></i>Accepter
                                            </a>
                                            <a href="?action=refuser&id=<?= $inv['sender_id'] ?>&tab=invitations" 
                                               class="btn btn-danger btn-sm">
                                                <i class="fas fa-times me-1"></i>Refuser
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune invitation reçue</h5>
                        <p class="text-muted">Les demandes d'amis apparaîtront ici</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($tab === 'blocages'): ?>
        <!-- Utilisateurs bloqués -->
        <div class="card slide-up">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-ban me-2"></i>Utilisateurs bloqués</h4>
            </div>
            <div class="card-body">
                <?php if (count($bloques) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($bloques as $b): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle fa-2x text-muted me-3"></i>
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($b['prenom'] . ' ' . $b['nom']) ?></h6>
                                        <p class="mb-1 text-muted small"><?= htmlspecialchars($b['email']) ?></p>
                                        <small class="text-muted">
                                            Bloqué le <?= date('d/m/Y à H:i', strtotime($b['date_blocage'])) ?>
                                        </small>
                                    </div>
                                </div>
                                <a href="?unblock=<?= $b['id_bloque'] ?>&tab=blocages" 
                                   class="btn btn-warning btn-sm"
                                   onclick="return confirm('Débloquer cet utilisateur ?')">
                                    <i class="fas fa-unlock me-1"></i>Débloquer
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-unlock fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun utilisateur bloqué</h5>
                        <p class="text-muted">Les utilisateurs que vous bloquez apparaîtront ici</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="dash1.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Retour à l'accueil
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<style>
.user-card {
    transition: transform 0.2s ease;
}
.user-card:hover {
    transform: translateY(-3px);
}
.user-avatar {
    position: relative;
}
</style>
</body>
</html>