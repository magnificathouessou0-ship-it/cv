<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

if (!isset($_SESSION['id']) || !isset($_GET['id_comment'])) {
    redirect("dash1.php");
}

$id_comment = intval($_GET['id_comment']);
$user_id = $_SESSION['id'];

// Vérifier que le commentaire appartient bien à l'utilisateur
$stmt = $bdd->prepare("SELECT c.id, c.contenu, c.id_publication, u.nom, u.prenom 
                      FROM commentaires c 
                      JOIN users u ON c.id_users = u.id 
                      WHERE c.id = ? AND c.id_users = ?");
$stmt->execute([$id_comment, $user_id]);
$comment = $stmt->fetch();

if (!$comment) {
    redirect("dash1.php");
}

$message = "";

// CORRECTION : Vérifier si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $nouveau_contenu = trim($_POST['contenu'] ?? '');
    
    if (empty($nouveau_contenu)) {
        $message = "<div class='alert alert-danger'>Le commentaire ne peut pas être vide.</div>";
    } elseif (strlen($nouveau_contenu) > 1000) {
        $message = "<div class='alert alert-danger'>Le commentaire ne doit pas dépasser 1000 caractères.</div>";
    } else {
        // CORRECTION : Utiliser seulement le contenu sans la date de modification
        $update = $bdd->prepare("UPDATE commentaires SET contenu = ? WHERE id = ?");
        
        if ($update->execute([$nouveau_contenu, $id_comment])) {
            // CORRECTION : Redirection avec succès
            header("Location: commentaire_pub.php?id=" . $comment['id_publication'] . "&success=comment_updated");
            exit();
        } else {
            $message = "<div class='alert alert-danger'>Erreur lors de la modification du commentaire.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Modifier commentaire | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <style>
        .slide-up {
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Modifier votre commentaire</h4>
                </div>
                <div class="card-body">
                    <?= $message ?>
                    
                    <!-- Informations sur le commentaire -->
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                <small class="fw-bold">Commentaire de <?= htmlspecialchars($comment['prenom'] . ' ' . $comment['nom']) ?></small>
                                <br>
                                <small class="text-muted">Ce commentaire sera marqué comme modifié</small>
                            </div>
                        </div>
                    </div>

                    <form method="post">
                        <div class="mb-4">
                            <label for="contenu" class="form-label fw-bold">Votre commentaire</label>
                            <textarea name="contenu" id="contenu" class="form-control" rows="5" maxlength="1000" required><?= htmlspecialchars($comment['contenu']) ?></textarea>
                            <div class="form-text text-end">
                                <span id="charCount"><?= strlen($comment['contenu']) ?></span>/1000 caractères
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="commentaire_pub.php?id=<?= $comment['id_publication'] ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                            <button type="submit" name="modifier" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Conseils d'édition -->
            <div class="card mt-4 fade-in">
                <div class="card-body">
                    <h6><i class="fas fa-lightbulb me-2 text-warning"></i>Conseils pour un bon commentaire</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Soyez respectueux et constructif</small></li>
                        <li><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Relisez-vous avant de publier</small></li>
                        <li><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Évitez les informations personnelles</small></li>
                        <li><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Respectez les autres utilisateurs</small></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Compteur de caractères
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.querySelector('textarea[name="contenu"]');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            
            if (count > 990) {
                charCount.classList.add('text-danger', 'fw-bold');
            } else {
                charCount.classList.remove('text-danger', 'fw-bold');
            }
        });
        
        // Déclencher l'événement input au chargement pour le comptage initial
        textarea.dispatchEvent(new Event('input'));
    }
});

// Empêcher la soumission du formulaire si le texte est vide
document.querySelector('form').addEventListener('submit', function(e) {
    const textarea = document.querySelector('textarea[name="contenu"]');
    if (textarea.value.trim() === '') {
        e.preventDefault();
        alert('Le commentaire ne peut pas être vide.');
        textarea.focus();
    }
});
</script>
</body>
</html>