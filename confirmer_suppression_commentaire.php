<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

$id_comment = $_GET['id_comment'] ?? null;
$id_publication = $_GET['id_publication'] ?? null;

if (!$id_comment || !$id_publication) {
    redirect("dash1.php");
}

// Vérifie que le commentaire existe et appartient à l'utilisateur
$req = $bdd->prepare("SELECT c.contenu, u.prenom, u.nom 
                     FROM commentaires c 
                     JOIN users u ON c.id_users = u.id 
                     WHERE c.id = ? AND c.id_users = ?");
$req->execute([$id_comment, $_SESSION['id']]);
$comment = $req->fetch();

if (!$comment) {
    redirect("commentaire_pub.php?id=$id_publication");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmation Suppression | TalkSpace</title>
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
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card slide-up">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                        <h5>Supprimer ce commentaire ?</h5>
                        <p class="text-muted">Cette action est irréversible.</p>
                    </div>

                    <!-- Aperçu du commentaire -->
                    <div class="comment-preview border rounded p-3 mb-4 bg-light text-start">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <strong class="text-primary">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($comment['prenom'] . ' ' . $comment['nom']) ?>
                            </strong>
                        </div>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($comment['contenu'])) ?></p>
                    </div>

                    <form method="POST" action="supprimer_comment.php" class="d-flex gap-3 justify-content-center">
                        <input type="hidden" name="id_comment" value="<?= $id_comment ?>">
                        <input type="hidden" name="id_publication" value="<?= $id_publication ?>">
                        <button type="submit" name="confirmer" class="btn btn-danger px-4">
                            <i class="fas fa-trash me-1"></i>Oui, supprimer
                        </button>
                        <a href="commentaire_pub.php?id=<?= $id_publication ?>" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-times me-1"></i>Annuler
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>