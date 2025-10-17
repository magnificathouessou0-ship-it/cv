<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect("liste_amis.php");
}

$ami_id = intval($_GET['id']);
$user_id = $_SESSION['id'];

// Vérifier que c'est bien un ami
$check_ami = $bdd->prepare("
    SELECT id FROM demandes_amis 
    WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
    AND statut = 'accepter'
");
$check_ami->execute([$user_id, $ami_id, $ami_id, $user_id]);

if (!$check_ami->fetch()) {
    $_SESSION['error_message'] = "Vous n'êtes pas ami avec cette personne.";
    redirect("liste_amis.php");
}

// Récupérer les infos de l'ami
$stmt = $bdd->prepare("SELECT nom, prenom, email, tel, sexe, date_naissance, adresse FROM users WHERE id = ?");
$stmt->execute([$ami_id]);
$ami = $stmt->fetch();

if (!$ami) {
    $_SESSION['error_message'] = "Profil non trouvé.";
    redirect("liste_amis.php");
}

// Récupérer les amis de l'ami
$amis_ami_stmt = $bdd->prepare("
    SELECT u.id, u.nom, u.prenom, u.email, u.sexe 
    FROM users u 
    INNER JOIN demandes_amis da ON (u.id = da.sender_id OR u.id = da.receiver_id) 
    WHERE (da.sender_id = ? OR da.receiver_id = ?) 
    AND da.statut = 'accepter' 
    AND u.id != ?
    LIMIT 6
");
$amis_ami_stmt->execute([$ami_id, $ami_id, $ami_id]);
$amis_ami = $amis_ami_stmt->fetchAll(PDO::FETCH_ASSOC);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_ami'])) {
    $delete_ami = $bdd->prepare("
        DELETE FROM demandes_amis 
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
        AND statut = 'accepter'
    ");
    if ($delete_ami->execute([$user_id, $ami_id, $ami_id, $user_id])) {
        $_SESSION['success_message'] = "Ami supprimé avec succès";
        redirect("liste_amis.php");
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Profil de <?= htmlspecialchars($ami['prenom']) ?> | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <style>
        .ami-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(45deg, #007bff, #6610f2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2rem;
        }
        .ami-card {
            transition: transform 0.2s ease;
        }
        .ami-card:hover {
            transform: translateY(-2px);
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
                <li class="nav-item"><a class="nav-link" href="publication_form.php"><i class="fas fa-plus me-1"></i>Nouvelle Publication</a></li>
                <li class="nav-item"><a class="nav-link" href="liste_pub.php"><i class="fas fa-list me-1"></i>Mes Publications</a></li>
                <li class="nav-item"><a class="nav-link" href="explorer.php"><i class="fas fa-search me-1"></i>Explorer</a></li>
                <li class="nav-item"><a class="nav-link" href="liste_amis.php"><i class="fas fa-user-friends me-1"></i>Amis</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="profil.php"><i class="fas fa-user me-1"></i>Mon Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="deconnexion.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <!-- Colonne principale - Profil de l'ami -->
        <div class="col-12 col-lg-8">
            <div class="card slide-up">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        Profil de <?= htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']) ?>
                    </h4>
                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalSupprimerAmi">
                        <i class="fas fa-user-times me-1"></i>Supprimer l'ami
                    </button>
                </div>
                <div class="card-body">
                    <!-- En-tête du profil -->
                    <div class="row align-items-center mb-4">
                        <div class="col-auto">
                            <div class="ami-avatar">
                                <?= strtoupper(substr($ami['prenom'], 0, 1) . substr($ami['nom'], 0, 1)) ?>
                            </div>
                        </div>
                        <div class="col">
                            <h5 class="mb-1"><?= htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']) ?></h5>
                            <p class="text-muted mb-0">
                                <i class="fas fa-<?= $ami['sexe'] == 'Masculin' ? 'mars' : 'venus' ?> me-1"></i>
                                <?= htmlspecialchars($ami['sexe']) ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-envelope me-1"></i>
                                <?= htmlspecialchars($ami['email']) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Informations de l'ami -->
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nom</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($ami['nom']) ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Prénom</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($ami['prenom']) ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($ami['email']) ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Téléphone</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($ami['tel']) ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date de naissance</label>
                            <input type="date" class="form-control" value="<?= htmlspecialchars($ami['date_naissance']) ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sexe</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($ami['sexe']) ?>" readonly>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Adresse</label>
                            <textarea class="form-control" rows="3" readonly><?= htmlspecialchars($ami['adresse']) ?></textarea>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="liste_amis.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-arrow-left me-2"></i>Retour aux amis
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="dash1.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques de l'ami -->
            <div class="card mt-4 fade-in">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistiques de <?= htmlspecialchars($ami['prenom']) ?></h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <i class="fas fa-newspaper fa-2x text-primary mb-2"></i>
                                <h4>
                                    <?php 
                                    $pub_count = $bdd->prepare("SELECT COUNT(*) FROM publication WHERE id_users = ?");
                                    $pub_count->execute([$ami_id]);
                                    echo $pub_count->fetchColumn();
                                    ?>
                                </h4>
                                <p class="text-muted mb-0">Publications</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <i class="fas fa-comments fa-2x text-success mb-2"></i>
                                <h4>
                                    <?php 
                                    $com_count = $bdd->prepare("SELECT COUNT(*) FROM commentaires WHERE id_users = ?");
                                    $com_count->execute([$ami_id]);
                                    echo $com_count->fetchColumn();
                                    ?>
                                </h4>
                                <p class="text-muted mb-0">Commentaires</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <i class="fas fa-user-friends fa-2x text-warning mb-2"></i>
                                <h4>
                                    <?php 
                                    $amis_count = $bdd->prepare("SELECT COUNT(*) FROM demandes_amis WHERE (sender_id = ? OR receiver_id = ?) AND statut = 'accepter'");
                                    $amis_count->execute([$ami_id, $ami_id]);
                                    echo $amis_count->fetchColumn();
                                    ?>
                                </h4>
                                <p class="text-muted mb-0">Amis</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne latérale - Amis de l'ami -->
        <div class="col-12 col-lg-4">
            <div class="card slide-up">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-friends me-2"></i>
                        Amis de <?= htmlspecialchars($ami['prenom']) ?> (<?= count($amis_ami) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($amis_ami): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($amis_ami as $ami_ami): ?>
                                <div class="list-group-item d-flex align-items-center p-3 ami-card">
                                    <div class="ami-avatar me-3" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                        <?= strtoupper(substr($ami_ami['prenom'], 0, 1) . substr($ami_ami['nom'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($ami_ami['prenom'] . ' ' . $ami_ami['nom']) ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-<?= $ami_ami['sexe'] == 'Masculin' ? 'mars' : 'venus' ?> me-1"></i>
                                            <?= htmlspecialchars($ami_ami['sexe']) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($amis_ami) == 6): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">... et d'autres amis</small>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-friends fa-2x text-muted mb-3"></i>
                            <h6 class="text-muted">Aucun ami</h6>
                            <p class="text-muted small"><?= htmlspecialchars($ami['prenom']) ?> n'a pas encore d'amis</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card mt-4 fade-in">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="liste_amis.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Mes amis
                        </a>
                        <a href="explorer.php" class="btn btn-outline-success">
                            <i class="fas fa-search me-2"></i>Trouver des amis
                        </a>
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalSupprimerAmi">
                            <i class="fas fa-user-times me-2"></i>Supprimer cet ami
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Supprimer un ami -->
<div class="modal fade" id="modalSupprimerAmi" tabindex="-1" aria-labelledby="modalSupprimerAmiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalSupprimerAmiLabel">
                    <i class="fas fa-user-times me-2"></i>Supprimer un ami
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-exclamation-triangle text-warning fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">Confirmer la suppression</h6>
                        <p class="mb-0">Êtes-vous sûr de vouloir supprimer <strong><?= htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']) ?></strong> de votre liste d'amis ?</p>
                    </div>
                </div>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <small>Cette action est irréversible. Vous devrez renvoyer une demande d'amitié pour redevenir amis.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <form method="post">
                    <button type="submit" name="supprimer_ami" class="btn btn-danger">
                        <i class="fas fa-user-times me-1"></i>Supprimer l'ami
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Empêcher la soumission multiple
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Traitement...';
            }
        });
    });
});
</script>
</body>
</html>