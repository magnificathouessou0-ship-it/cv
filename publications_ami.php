<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

if (!isset($_GET['id'])) {
    redirect("liste_amis.php");
}

$user_id = $_SESSION['id'];
$ami_id = intval($_GET['id']);

// Vérifier si amis
$check_ami = $bdd->prepare("SELECT * FROM demandes_amis 
                           WHERE statut='accepter' 
                           AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?))");
$check_ami->execute([$user_id, $ami_id, $ami_id, $user_id]);

if ($check_ami->rowCount() == 0) {
    redirect("liste_amis.php?error=not_friend");
}

// Récupérer les infos de l'ami
$ami_info = $bdd->prepare("SELECT nom, prenom FROM users WHERE id = ?");
$ami_info->execute([$ami_id]);
$ami = $ami_info->fetch(PDO::FETCH_ASSOC);

if (!$ami) {
    redirect("liste_amis.php?error=user_not_found");
}

// Récupérer les publications de l'ami
$publications = $bdd->prepare("SELECT p.*, u.nom, u.prenom 
                              FROM publication p 
                              JOIN users u ON p.id_users = u.id 
                              WHERE p.id_users = ? 
                              ORDER BY p.date_enregistr DESC");
$publications->execute([$ami_id]);
$posts = $publications->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publications de <?= htmlspecialchars($ami['prenom']) ?> | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .publication-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.2s ease;
        }
        .publication-card:hover {
            transform: translateY(-2px);
        }
        .publication-image {
            max-height: 300px;
            object-fit: cover;
            border-radius: 8px;
        }
        .stats-badge {
            font-size: 0.85rem;
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
    <!-- En-tête -->
    <div class="card slide-up mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-newspaper me-2 text-primary"></i>
                        Publications de <?= htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']) ?>
                    </h4>
                    <p class="text-muted mb-0">
                        <?= count($posts) ?> publication<?= count($posts) > 1 ? 's' : '' ?> au total
                    </p>
                </div>
                <a href="profil_ami.php?id=<?= $ami_id ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i>Retour au profil
                </a>
            </div>
        </div>
    </div>

    <!-- Liste des publications -->
    <?php if (empty($posts)): ?>
        <div class="card text-center py-5">
            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucune publication</h5>
            <p class="text-muted"><?= htmlspecialchars($ami['prenom']) ?> n'a pas encore publié de contenu</p>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="card publication-card slide-up">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-circle fa-lg text-primary me-2"></i>
                        <div>
                            <strong><?= htmlspecialchars($post['prenom'] . ' ' . $post['nom']) ?></strong>
                            <br>
                            <small class="text-muted">
                                <?= date('d/m/Y à H:i', strtotime($post['date_enregistr'])) ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Contenu -->
                    <p class="card-text"><?= nl2br(htmlspecialchars($post['contenu'])) ?></p>
                    
                    <!-- Image -->
                    <?php if ($post['image']): ?>
                        <div class="mt-3">
                            <img src="uploads/<?= htmlspecialchars($post['image']) ?>" 
                                 class="publication-image img-fluid w-100" 
                                 alt="Publication image">
                        </div>
                    <?php endif; ?>
                    
                    <!-- Statistiques -->
                    <div class="mt-3">
                        <?php
                        // Compter les commentaires
                        $countCom = $bdd->prepare("SELECT COUNT(*) FROM commentaires WHERE id_publication = ?");
                        $countCom->execute([$post['id']]);
                        $nbCom = $countCom->fetchColumn();

                        // Compter les likes
                        $countLike = $bdd->prepare("SELECT COUNT(*) FROM reactions WHERE id_publication = ? AND type = 'like'");
                        $countLike->execute([$post['id']]);
                        $nbLike = $countLike->fetchColumn();

                        // Compter les dislikes
                        $countDislike = $bdd->prepare("SELECT COUNT(*) FROM reactions WHERE id_publication = ? AND type = 'dislike'");
                        $countDislike->execute([$post['id']]);
                        $nbDislike = $countDislike->fetchColumn();
                        ?>
                        
                        <div class="d-flex gap-3">
                            <span class="stats-badge text-muted">
                                <i class="fas fa-comment me-1"></i><?= $nbCom ?>
                            </span>
                            <span class="stats-badge text-success">
                                <i class="fas fa-thumbs-up me-1"></i><?= $nbLike ?>
                            </span>
                            <span class="stats-badge text-danger">
                                <i class="fas fa-thumbs-down me-1"></i><?= $nbDislike ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>