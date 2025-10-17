<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

$id = $_GET['id'] ?? null;
if (!$id) {
    redirect("dash1.php");
}

// Requête avec jointure pour récupérer le nom de l'utilisateur
$stmt = $bdd->prepare("
    SELECT p.*, u.nom, u.prenom 
    FROM publication p 
    JOIN users u ON p.id_users = u.id 
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pub = $stmt->fetch();

if (!$pub) {
    redirect("dash1.php");
}

// Récupérer les commentaires
$comments_stmt = $bdd->prepare("
    SELECT c.*, u.nom, u.prenom 
    FROM commentaires c 
    JOIN users u ON c.id_users = u.id 
    WHERE c.id_publication = ? 
    ORDER BY c.date_commentaire DESC
");
$comments_stmt->execute([$id]);
$comments = $comments_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pub['prenom'] . ' ' . $pub['nom']) ?> | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
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
                <li class="nav-item"><a class="nav-link" href="liste_pub.php"><i class="fas fa-list me-1"></i>Mes Publications</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="profil.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="deconnexion.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <!-- Publication -->
            <div class="card slide-up">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>
                                <?= htmlspecialchars($pub['prenom'] . ' ' . $pub['nom']) ?>
                            </h5>
                        </div>
                        <small class="text-white-50">
                            <?= date('d/m/Y à H:i', strtotime($pub['date_enregistr'])) ?>
                        </small>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= nl2br(htmlspecialchars($pub['contenu'])) ?></p>
                    
                    <?php if (!empty($pub['image'])): ?>
                        <div class="text-center mt-3">
                            <img src="uploads/<?= htmlspecialchars($pub['image']) ?>" 
                                 class="img-fluid rounded" 
                                 alt="Image publication"
                                 style="max-height: 500px; object-fit: contain;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Commentaires -->
            <div class="card mt-4 fade-in">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2"></i>
                        Commentaires (<?= count($comments) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($comments)): ?>
                        <p class="text-muted text-center py-3">Aucun commentaire pour le moment.</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-item border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong class="text-primary">
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($comment['prenom'] . ' ' . $comment['nom']) ?>
                                    </strong>
                                    <small class="text-muted">
                                        <?= date('d/m/Y à H:i', strtotime($comment['date_commentaire'])) ?>
                                    </small>
                                </div>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($comment['contenu'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="dash1.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i>Retour à l'accueil
                </a>
                <a href="commentaire_pub.php?id=<?= $id ?>" class="btn btn-primary ms-2">
                    <i class="fas fa-comment me-1"></i>Ajouter un commentaire
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>