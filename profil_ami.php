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

// Vérifier si l'utilisateur est bien ami avec cette personne
$check_ami = $bdd->prepare("SELECT * FROM demandes_amis 
                           WHERE statut='accepter' 
                           AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?))");
$check_ami->execute([$user_id, $ami_id, $ami_id, $user_id]);

if ($check_ami->rowCount() == 0) {
    redirect("liste_amis.php?error=not_friend");
}

// Récupérer les infos de l'ami
$ami_info = $bdd->prepare("SELECT id, nom, prenom, email, tel, sexe, date_naissance, adresse 
                          FROM users WHERE id = ?");
$ami_info->execute([$ami_id]);
$ami = $ami_info->fetch(PDO::FETCH_ASSOC);

if (!$ami) {
    redirect("liste_amis.php?error=user_not_found");
}

// Récupérer les statistiques de l'ami
$pub_count = $bdd->prepare("SELECT COUNT(*) FROM publication WHERE id_users = ?");
$pub_count->execute([$ami_id]);
$nb_publications = $pub_count->fetchColumn();

$amis_count = $bdd->prepare("SELECT COUNT(*) FROM demandes_amis 
                            WHERE statut='accepter' 
                            AND (sender_id=? OR receiver_id=?)");
$amis_count->execute([$ami_id, $ami_id]);
$nb_amis = $amis_count->fetchColumn();

// Calculer l'âge
$age = '';
if ($ami['date_naissance']) {
    $dateNaissance = new DateTime($ami['date_naissance']);
    $aujourdhui = new DateTime();
    $age = $dateNaissance->diff($aujourdhui)->y;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de <?= htmlspecialchars($ami['prenom']) ?> | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: white;
            font-size: 3rem;
            border: 5px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .action-btn {
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .info-item {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-item:last-child {
            border-bottom: none;
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
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <!-- En-tête du profil -->
            <div class="card slide-up mb-4">
                <div class="card-body text-center py-4">
                    <div class="profile-avatar mb-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="card-title mb-2"><?= htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']) ?></h2>
                    <p class="text-muted mb-4">Membre TalkSpace</p>
                    
                    <!-- Actions principales -->
                    <div class="row g-3 justify-content-center">
                        <div class="col-auto">
                            <a href="messagerie.php?id=<?= $ami_id ?>" class="btn btn-primary action-btn">
                                <i class="fas fa-comment-dots"></i>
                                Discuter
                            </a>
                        </div>
                        <div class="col-auto">
                            <a href="publications_ami.php?id=<?= $ami_id ?>" class="btn btn-info action-btn text-white">
                                <i class="fas fa-newspaper"></i>
                                Voir ses publications
                            </a>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-danger action-btn" 
                                    data-bs-toggle="modal" data-bs-target="#retirerAmiModal">
                                <i class="fas fa-user-times"></i>
                                Retirer des amis
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Informations personnelles -->
                <div class="col-md-6 mb-4">
                    <div class="card slide-up h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations personnelles</h5>
                        </div>
                        <div class="card-body">
                            <div class="info-item">
                                <strong><i class="fas fa-user me-2 text-primary"></i>Nom complet:</strong><br>
                                <?= htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']) ?>
                            </div>
                            <div class="info-item">
                                <strong><i class="fas fa-envelope me-2 text-primary"></i>Email:</strong><br>
                                <?= htmlspecialchars($ami['email']) ?>
                            </div>
                            <?php if ($ami['tel']): ?>
                            <div class="info-item">
                                <strong><i class="fas fa-phone me-2 text-primary"></i>Téléphone:</strong><br>
                                <?= htmlspecialchars($ami['tel']) ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($ami['sexe']): ?>
                            <div class="info-item">
                                <strong><i class="fas fa-venus-mars me-2 text-primary"></i>Sexe:</strong><br>
                                <?= htmlspecialchars($ami['sexe']) ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($age): ?>
                            <div class="info-item">
                                <strong><i class="fas fa-birthday-cake me-2 text-primary"></i>Âge:</strong><br>
                                <?= $age ?> ans
                            </div>
                            <?php endif; ?>
                            <?php if ($ami['adresse']): ?>
                            <div class="info-item">
                                <strong><i class="fas fa-map-marker-alt me-2 text-primary"></i>Adresse:</strong><br>
                                <?= htmlspecialchars($ami['adresse']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="col-md-6 mb-4">
                    <div class="card slide-up h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistiques</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="stat-card p-3">
                                        <i class="fas fa-newspaper fa-2x text-primary mb-2"></i>
                                        <h4 class="mb-1"><?= $nb_publications ?></h4>
                                        <small class="text-muted">Publications</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stat-card p-3">
                                        <i class="fas fa-user-friends fa-2x text-success mb-2"></i>
                                        <h4 class="mb-1"><?= $nb_amis ?></h4>
                                        <small class="text-muted">Amis</small>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    Membre depuis 
                                    <?php 
                                    $date_inscription = $bdd->prepare("SELECT date_inscription FROM users WHERE id = ?");
                                    $date_inscription->execute([$ami_id]);
                                    $inscription = $date_inscription->fetchColumn();
                                    echo $inscription ? date('d/m/Y', strtotime($inscription)) : 'Date inconnue';
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Retirer Ami -->
<div class="modal fade" id="retirerAmiModal" tabindex="-1" aria-labelledby="retirerAmiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                </div>
                
                <h5 class="modal-title mb-3">Retirer un ami</h5>
                
                <div class="alert alert-warning mb-4">
                    <strong>Attention</strong> - Cette action est irréversible
                </div>
                
                <p class="text-muted mb-4">
                    Êtes-vous sûr de vouloir retirer <strong><?= htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']) ?></strong> de votre liste d'amis ?
                </p>

                <div class="d-flex gap-3 justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <a href="retirer_ami.php?id=<?= $ami_id ?>&confirm=yes" class="btn btn-danger px-4">
                        <i class="fas fa-user-times me-2"></i>Oui, retirer
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>