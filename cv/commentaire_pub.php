<?php
session_start();
require 'config.php';
require_once('functions.php');
requireLogin();

// Vérification de session et paramètre GET
if (!isset($_GET['id'])) {
    redirect("dash1.php");
}

$pub_id = intval($_GET['id']);

// Vérifie que la publication existe
$checkPub = $bdd->prepare("SELECT p.*, u.nom, u.prenom FROM publication p JOIN users u ON p.id_users = u.id WHERE p.id = ?");
$checkPub->execute([$pub_id]);
$publication = $checkPub->fetch();

if (!$publication) {
    redirect("dash1.php");
}

// Ajouter un commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenu'])) {
    $contenu = trim(sanitize($_POST['contenu']));

    if (!empty($contenu)) {
        $insert = $bdd->prepare("INSERT INTO commentaires (contenu, date_commentaire, id_users, id_publication) VALUES (?, NOW(), ?, ?)");
        $insert->execute([$contenu, $_SESSION['id'], $pub_id]);

        // Notification à l'auteur si ce n'est pas le commentateur
        if ($publication['id_users'] != $_SESSION['id']) {
            $pseudo = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
            $message = "$pseudo a commenté votre publication.";

            $notif = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
            $notif->execute([$publication['id_users'], $message]);
        }
    }
    redirect("commentaire_pub.php?id=$pub_id");
}

// Récupérer les commentaires
$stmt = $bdd->prepare("SELECT c.id, c.contenu, c.date_commentaire, c.date_modif, u.nom, u.prenom, u.id as user_id 
                      FROM commentaires c 
                      JOIN users u ON c.id_users = u.id 
                      WHERE c.id_publication = ? 
                      ORDER BY c.date_commentaire DESC");
$stmt->execute([$pub_id]);
$commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Commentaires | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
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
                <li class="nav-item"><a class="nav-link" href="publication_form.php"><i class="fas fa-plus me-1"></i>Nouvelle Publication</a></li>
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
            <!-- Publication originale -->
            <div class="card mb-4 slide-up">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">
                            <i class="fas fa-user me-1"></i>
                            <?= htmlspecialchars($publication['prenom'] . ' ' . $publication['nom']) ?>
                        </span>
                        <small class="text-white-50">
                            <?= date('d/m/Y à H:i', strtotime($publication['date_enregistr'])) ?>
                        </small>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= nl2br(htmlspecialchars($publication['contenu'])) ?></p>
                    
                    <?php if (!empty($publication['image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($publication['image']) ?>" 
                             class="img-fluid rounded mt-2" 
                             alt="Image publication"
                             style="max-height: 300px; object-fit: cover;">
                    <?php endif; ?>
                </div>
            </div>

            <!-- Commentaires -->
            <div class="card slide-up">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2"></i>
                        Commentaires (<?= count($commentaires) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($commentaires) === 0): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comment-slash fa-2x text-muted mb-3"></i>
                            <p class="text-muted">Aucun commentaire pour l'instant.<br>Soyez le premier à commenter !</p>
                        </div>
                    <?php else: ?>
                        <div class="comment-list">
                            <?php foreach ($commentaires as $c): ?>
                                <div class="comment-item border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong class="text-primary">
                                                <i class="fas fa-user me-1"></i>
                                                <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?>
                                            </strong>
                                            <?php if ($c['date_modif']): ?>
                                                <small class="text-muted ms-2">
                                                    <i class="fas fa-edit me-1"></i>modifié
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= date('d/m/Y à H:i', strtotime($c['date_commentaire'])) ?>
                                        </small>
                                    </div>
                                    
                                    <p class="mb-2"><?= nl2br(htmlspecialchars($c['contenu'])) ?></p>

                                    <!-- Actions -->
                                    <?php if ($c['user_id'] == $_SESSION['id']): ?>
                                        <div class="d-flex gap-2">
                                            <a href="modifier_comment.php?id_comment=<?= $c['id'] ?>&id_publication=<?= $pub_id ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit me-1"></i>Modifier
                                            </a>
                                            <a href="confirmer_suppression_commentaire.php?id_comment=<?= $c['id'] ?>&id_publication=<?= $pub_id ?>" 
                                               class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash me-1"></i>Supprimer
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire d'ajout de commentaire -->
                    <form method="post" class="mt-4">
                        <div class="mb-3">
                            <label for="contenu" class="form-label">
                                <i class="fas fa-comment me-1"></i>Ajouter un commentaire
                            </label>
                            <textarea name="contenu" id="contenu" class="form-control" rows="3" 
                                      maxlength="1000" placeholder="Votre commentaire..." required></textarea>
                            <div class="form-text text-end">
                                <span id="charCount">0</span>/1000 caractères
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-1"></i>Publier le commentaire
                        </button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="dash1.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
// Compteur de caractères pour les commentaires
document.getElementById('contenu').addEventListener('input', function() {
    const charCount = this.value.length;
    document.getElementById('charCount').textContent = charCount;
    
    if (charCount > 990) {
        document.getElementById('charCount').classList.add('text-danger');
    } else {
        document.getElementById('charCount').classList.remove('text-danger');
    }
});
</script>
</body>
</html>