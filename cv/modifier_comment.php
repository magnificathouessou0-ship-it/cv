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

if (isset($_POST['modifier'])) {
    $nouveau_contenu = trim($_POST['contenu'] ?? '');
    
    if (empty($nouveau_contenu)) {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle me-2'></i>Le commentaire ne peut pas être vide.</div>";
    } elseif (strlen($nouveau_contenu) > 1000) {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle me-2'></i>Le commentaire ne doit pas dépasser 1000 caractères.</div>";
    } else {
        try {
            $update = $bdd->prepare("UPDATE commentaires SET contenu = ?, date_modif = NOW() WHERE id = ? AND id_users = ?");
            if ($update->execute([$nouveau_contenu, $id_comment, $user_id])) {
                $_SESSION['success_message'] = "Commentaire modifié avec succès";
                redirect("commentaire_pub.php?id=" . $comment['id_publication'] . "&success=comment_updated");
            }
        } catch (Exception $e) {
            error_log("Erreur modification commentaire: " . $e->getMessage());
            $message = "<div class='alert alert-danger'><i class='fas fa-times-circle me-2'></i>Erreur lors de la modification.</div>";
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
        .char-counter {
            transition: all 0.3s ease;
        }
        .btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
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
                <div class="card-header">
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

                    <form method="post" id="editForm">
                        <div class="mb-4">
                            <label for="contenu" class="form-label fw-semibold">
                                <i class="fas fa-comment me-1"></i>Votre commentaire
                            </label>
                            <textarea name="contenu" id="contenu" class="form-control" rows="5" maxlength="1000" required 
                                      placeholder="Exprimez-vous de manière respectueuse et constructive..."><?= htmlspecialchars($comment['contenu']) ?></textarea>
                            <div class="form-text d-flex justify-content-between align-items-center mt-2">
                                <div>
                                    <span class="char-counter" id="charCount"><?= strlen($comment['contenu']) ?></span>/1000 caractères
                                    <span id="charWarning" class="text-warning d-none ms-2">
                                        <i class="fas fa-exclamation-triangle"></i> Approche de la limite
                                    </span>
                                </div>
                                <span class="badge bg-secondary" id="remainingBadge"><?= 1000 - strlen($comment['contenu']) ?> restants</span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="commentaire_pub.php?id=<?= $comment['id_publication'] ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annuler
                            </a>
                            <div>
                                <button type="button" id="resetBtn" class="btn btn-outline-warning me-2">
                                    <i class="fas fa-undo me-1"></i>Réinitialiser
                                </button>
                                <button type="submit" name="modifier" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-1"></i>Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Conseils d'édition -->
            <div class="card mt-4 fade-in">
                <div class="card-body">
                    <h6><i class="fas fa-lightbulb me-2 text-warning"></i>Conseils pour un bon commentaire</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-1"><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Soyez respectueux et constructif</small></li>
                        <li class="mb-1"><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Relisez-vous avant de publier</small></li>
                        <li class="mb-1"><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Évitez les informations personnelles</small></li>
                        <li class="mb-1"><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Respectez les autres utilisateurs</small></li>
                        <li class="mb-0"><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Restez pertinent par rapport au sujet</small></li>
                    </ul>
                </div>
            </div>

            <!-- Section statistiques -->
            <div class="card mt-4 fade-in">
                <div class="card-body">
                    <h6><i class="fas fa-chart-bar me-2 text-primary"></i>Statistiques du commentaire</h6>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border rounded p-2 bg-light">
                                <div class="fw-bold text-primary fs-5" id="wordCount"><?= str_word_count($comment['contenu']) ?></div>
                                <small class="text-muted">Mots</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 bg-light">
                                <div class="fw-bold text-info fs-5" id="charCountStat"><?= strlen($comment['contenu']) ?></div>
                                <small class="text-muted">Caractères</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2 bg-light">
                                <div class="fw-bold text-success fs-5"><?= round(strlen($comment['contenu']) / 1000 * 100) ?>%</div>
                                <small class="text-muted">Utilisation</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section historique -->
            <div class="card mt-4 fade-in">
                <div class="card-body">
                    <h6><i class="fas fa-history me-2 text-info"></i>À propos de la modification</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-1"><small class="text-muted"><i class="fas fa-clock me-1"></i>Le commentaire sera marqué "modifié"</small></li>
                        <li class="mb-1"><small class="text-muted"><i class="fas fa-eye me-1"></i>Visible par tous les utilisateurs</small></li>
                        <li class="mb-0"><small class="text-muted"><i class="fas fa-sync me-1"></i>Mise à jour instantanée après enregistrement</small></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Compteur de caractères amélioré
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('contenu');
    const charCount = document.getElementById('charCount');
    const charWarning = document.getElementById('charWarning');
    const wordCount = document.getElementById('wordCount');
    const charCountStat = document.getElementById('charCountStat');
    const remainingBadge = document.getElementById('remainingBadge');
    const resetBtn = document.getElementById('resetBtn');
    const submitBtn = document.getElementById('submitBtn');
    const originalContent = textarea.value;

    function updateCounters() {
        const content = textarea.value;
        const length = content.length;
        const remaining = 1000 - length;
        
        // Mettre à jour le compteur de caractères
        charCount.textContent = length;
        charCountStat.textContent = length;
        remainingBadge.textContent = remaining + ' restants';
        
        // Mettre à jour le compteur de mots
        const words = content.trim() ? content.trim().split(/\s+/).length : 0;
        wordCount.textContent = words;
        
        // Gérer les avertissements et couleurs
        if (length > 950) {
            charCount.classList.add('text-danger', 'fw-bold');
            charWarning.classList.remove('d-none');
            remainingBadge.classList.remove('bg-warning', 'bg-secondary');
            remainingBadge.classList.add('bg-danger');
            submitBtn.classList.remove('btn-primary');
            submitBtn.classList.add('btn-danger');
        } else if (length > 800) {
            charCount.classList.remove('text-danger');
            charCount.classList.add('text-warning', 'fw-bold');
            charWarning.classList.remove('d-none');
            remainingBadge.classList.remove('bg-secondary', 'bg-danger');
            remainingBadge.classList.add('bg-warning');
            submitBtn.classList.remove('btn-danger');
            submitBtn.classList.add('btn-primary');
        } else {
            charCount.classList.remove('text-danger', 'text-warning', 'fw-bold');
            charWarning.classList.add('d-none');
            remainingBadge.classList.remove('bg-warning', 'bg-danger');
            remainingBadge.classList.add('bg-secondary');
            submitBtn.classList.remove('btn-danger');
            submitBtn.classList.add('btn-primary');
        }
        
        // Désactiver le bouton si vide ou trop long
        submitBtn.disabled = length === 0 || length > 1000;
    }

    // Événement de saisie
    textarea.addEventListener('input', updateCounters);
    
    // Bouton de réinitialisation
    resetBtn.addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir réinitialiser le commentaire ? Toutes les modifications seront perdues.')) {
            textarea.value = originalContent;
            updateCounters();
            textarea.focus();
        }
    });

    // Prévention de double soumission
    document.getElementById('editForm').addEventListener('submit', function(e) {
        if (textarea.value.length > 1000) {
            e.preventDefault();
            alert('Le commentaire dépasse la limite de 1000 caractères.');
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Modification en cours...';
    });

    // Focus automatique et sélection de tout le texte
    textarea.focus();
    textarea.setSelectionRange(0, textarea.value.length);

    // Initialisation
    updateCounters();

    // Gestion des touches
    textarea.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            document.getElementById('editForm').submit();
        }
    });
});
</script>
</body>
</html>