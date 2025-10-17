<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

if (!isset($_GET['id'])) {
    redirect("liste_pub.php");
}

$id = intval($_GET['id']);

$req = $bdd->prepare("SELECT * FROM publication WHERE id = ? AND id_users = ?");
$req->execute([$id, $_SESSION['id']]);
$pub = $req->fetch();

if (!$pub) {
    redirect("liste_pub.php");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de suppression | TalkSpace</title>
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
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card slide-up">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation de suppression</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-trash-alt fa-4x text-danger mb-3"></i>
                        <h5>Êtes-vous sûr de vouloir supprimer cette publication ?</h5>
                        <p class="text-muted">Cette action est irréversible. Tous les commentaires et réactions associés seront également supprimés.</p>
                    </div>

                    <!-- Aperçu de la publication -->
                    <div class="publication-preview border rounded p-3 mb-4 bg-light">
                        <p class="mb-2">
                            <?= nl2br(htmlspecialchars(substr($pub['contenu'], 0, 200))) ?>
                            <?php if (strlen($pub['contenu']) > 200): ?>...<?php endif; ?>
                        </p>
                        
                        <?php if (!empty($pub['image'])): ?>
                            <div class="mt-2">
                                <img src="uploads/<?= htmlspecialchars($pub['image']) ?>" 
                                     class="img-fluid rounded" 
                                     alt="Image publication"
                                     style="max-height: 150px;">
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-2 text-muted small">
                            <i class="fas fa-calendar me-1"></i>
                            Publié le <?= date('d/m/Y à H:i', strtotime($pub['date_enregistr'])) ?>
                        </div>
                    </div>

                    <form method="POST" action="confirm_suppression.php" class="d-flex gap-3 justify-content-center">
                        <input type="hidden" name="id" value="<?= $pub['id'] ?>">
                        <button type="submit" name="confirmer" class="btn btn-danger px-4">
                            <i class="fas fa-trash me-1"></i>Oui, supprimer
                        </button>
                        <a href="liste_pub.php" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-times me-1"></i>Annuler
                        </a>
                    </form>
                </div>
            </div>

            <!-- Conséquences de la suppression -->
            <div class="card mt-4 fade-in">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle me-2 text-primary"></i>Ce qui sera supprimé :</h6>
                    <ul class="list-unstyled mb-0">
                        <li><small class="text-muted"><i class="fas fa-check text-danger me-1"></i>La publication</small></li>
                        <li><small class="text-muted"><i class="fas fa-check text-danger me-1"></i>Tous les commentaires associés</small></li>
                        <li><small class="text-muted"><i class="fas fa-check text-danger me-1"></i>Tous les likes/dislikes</small></li>
                        <li><small class="text-muted"><i class="fas fa-check text-danger me-1"></i>L'image associée (si existante)</small></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>