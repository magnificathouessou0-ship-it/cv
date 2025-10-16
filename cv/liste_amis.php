<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

$user_id = $_SESSION['id'];

// Récupérer les amis (statut accepté)
$query = $bdd->prepare("SELECT u.id, u.nom, u.prenom, u.email, u.tel 
                       FROM demandes_amis d 
                       JOIN users u ON (u.id = d.sender_id OR u.id = d.receiver_id) 
                       WHERE d.statut='accepter' AND (d.sender_id=? OR d.receiver_id=?) AND u.id != ? 
                       ORDER BY u.nom, u.prenom");
$query->execute([$user_id, $user_id, $user_id]);
$amis = $query->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les demandes d'amis en attente
$demandes_attente = $bdd->prepare("SELECT d.id, u.nom, u.prenom, u.email 
                                  FROM demandes_amis d 
                                  JOIN users u ON d.sender_id = u.id 
                                  WHERE d.receiver_id = ? AND d.statut = 'en_attente'");
$demandes_attente->execute([$user_id]);
$invitations = $demandes_attente->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Amis | TalkSpace</title>
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
                <li class="nav-item"><a class="nav-link" href="explorer.php"><i class="fas fa-search me-1"></i>Explorer</a></li>
                <li class="nav-item"><a class="nav-link active" href="liste_amis.php"><i class="fas fa-user-friends me-1"></i>Amis</a></li>
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
    <!-- Invitations en attente -->
    <?php if (count($invitations) > 0): ?>
        <div class="card mb-4 slide-up">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-user-clock me-2"></i>Demandes d'amis en attente</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($invitations as $invitation): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-user-circle fa-3x text-primary"></i>
                                    </div>
                                    <h6 class="card-title"><?= htmlspecialchars($invitation['prenom'] . ' ' . $invitation['nom']) ?></h6>
                                    <p class="card-text text-muted small"><?= htmlspecialchars($invitation['email']) ?></p>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="accepter_ami.php?id=<?= $invitation['id'] ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-check me-1"></i>Accepter
                                        </a>
                                        <a href="refuser_ami.php?id=<?= $invitation['id'] ?>" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times me-1"></i>Refuser
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Liste des amis -->
    <div class="card slide-up">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-user-friends me-2"></i>Mes Amis (<?= count($amis) ?>)</h4>
            <a href="explorer.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Ajouter des amis
            </a>
        </div>
        <div class="card-body">
            <?php if (count($amis) > 0): ?>
                <div class="row">
                    <?php foreach ($amis as $ami): ?>
                        <div class="col-12 col-sm-6 col-lg-4 mb-3">
                            <div class="card h-100 friend-card">
                                <div class="card-body text-center">
                                    <div class="friend-avatar mb-3">
                                        <i class="fas fa-user-circle fa-4x text-primary"></i>
                                    </div>
                                    <h6 class="card-title"><?= htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']) ?></h6>
                                    <p class="card-text text-muted small">
                                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($ami['email']) ?>
                                        <?php if ($ami['tel']): ?>
                                            <br><i class="fas fa-phone me-1"></i><?= htmlspecialchars($ami['tel']) ?>
                                        <?php endif; ?>
                                    </p>
                                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                                        <a href="messagerie.php?id=<?= $ami['id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-comment me-1"></i>Message
                                        </a>
                                        <a href="profil_ami.php?id=<?= $ami['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Voir profil
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Vous n'avez pas encore d'amis</h5>
                    <p class="text-muted">Ajoutez des amis pour commencer à discuter et partager</p>
                    <a href="explorer.php" class="btn btn-primary mt-2">
                        <i class="fas fa-search me-1"></i>Explorer les utilisateurs
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card text-center fade-in">
                <div class="card-body">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <h3><?= count($amis) ?></h3>
                    <p class="text-muted mb-0">Amis</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center fade-in">
                <div class="card-body">
                    <i class="fas fa-user-clock fa-2x text-warning mb-2"></i>
                    <h3><?= count($invitations) ?></h3>
                    <p class="text-muted mb-0">Demandes en attente</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center fade-in">
                <div class="card-body">
                    <i class="fas fa-comments fa-2x text-success mb-2"></i>
                    <h3>
                        <?php 
                        $msg_count = $bdd->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? OR receiver_id = ?");
                        $msg_count->execute([$user_id, $user_id]);
                        echo $msg_count->fetchColumn();
                        ?>
                    </h3>
                    <p class="text-muted mb-0">Messages échangés</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<style>
.friend-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.friend-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}
.friend-avatar {
    position: relative;
}
.friend-avatar::after {
    content: '';
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 12px;
    height: 12px;
    background-color: #28a745;
    border: 2px solid white;
    border-radius: 50%;
}
</style>
</body>
</html>