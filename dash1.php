<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

$id_users = $_SESSION['id'];

// Gestion recherche
$search = isset($_GET['search']) ? trim(sanitize($_GET['search'])) : '';

if ($search) {
    $query = $bdd->prepare("SELECT p.*, u.nom, u.prenom FROM publication p 
                           JOIN users u ON p.id_users = u.id 
                           WHERE p.contenu LIKE ? 
                           ORDER BY p.id DESC");
    $query->execute(["%$search%"]);
    $publications = $query->fetchAll();
} else {
    $publications = $bdd->query("SELECT p.*, u.nom, u.prenom FROM publication p 
                                JOIN users u ON p.id_users = u.id 
                                ORDER BY p.date_enregistr DESC")->fetchAll();
}

// Récupération des réactions de l'utilisateur connecté
$reactions_user = $bdd->prepare("SELECT id_publication, type FROM reactions WHERE id_users = ?");
$reactions_user->execute([$id_users]);
$user_reactions_raw = $reactions_user->fetchAll();

// Conversion en tableau associatif
$user_reactions = [];
foreach ($user_reactions_raw as $reaction) {
    $user_reactions[$reaction['id_publication']] = $reaction['type'];
}

// Récupérer le nombre de notifications non lues
$notif_count = $bdd->prepare("SELECT COUNT(*) FROM notifications WHERE id_users = ? AND lu = 0");
$notif_count->execute([$id_users]);
$nb_notifications = $notif_count->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .signal-modal {
            border-radius: 15px;
        }

        .signal-header {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
            text-align: center;
        }

        .signal-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .reason-item {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .reason-item:hover {
            border-color: #ff6b35;
            background-color: #fff9f5;
        }

        .reason-item.selected {
            border-color: #ff6b35;
            background-color: #fff0e6;
        }

        .reason-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .reason-desc {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 0;
        }

        .signal-btn {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .signal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
        }

        .signal-btn:disabled {
            background: #6c757d;
            transform: none;
            box-shadow: none;
        }

        .confirm-modal {
            border-radius: 15px;
        }

        .confirm-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 25px;
            text-align: center;
        }

        .confirm-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .confirm-btn {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        /* CORRECTIONS SPÉCIFIQUES POUR LA BARRE DE RECHERCHE MOBILE */

        /* CONTAINER PRINCIPAL - CORRECTION DÉFINITIVE */
        .container {
            padding-left: 15px;
            padding-right: 15px;
            max-width: 100%;
            margin: 0 auto;
        }

        /* CARD DE RECHERCHE - CORRECTION DÉFINITIVE */
        .search-card {
            margin: 0;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
        }

        .search-card .card-body {
            padding: 15px;
        }

        /* FORMULAIRE DE RECHERCHE - CORRECTION DÉFINITIVE */
        .search-form {
            margin: 0;
            width: 100%;
        }

        .search-form .row {
            margin-left: 0;
            margin-right: 0;
            align-items: stretch;
        }

        .search-form .col-12,
        .search-form .col-md-8,
        .search-form .col-md-4 {
            padding-left: 0;
            padding-right: 0;
        }

        /* BARRE DE RECHERCHE PRINCIPALE - CORRECTION DÉFINITIVE */
        .search-main-area {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }

        .search-row {
            display: flex;
            align-items: stretch;
            gap: 8px;
            width: 100%;
        }

        .search-input-group {
            display: flex;
            align-items: stretch;
            flex: 1;
            min-width: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .search-input {
            border: none !important;
            border-radius: 0 !important;
            flex: 1;
            padding: 12px 15px;
            font-size: 16px;
            background: white;
            min-width: 120px;
        }

        .search-input:focus {
            outline: none;
            box-shadow: none;
            background: white;
        }

        .search-btn {
            border: none !important;
            border-radius: 0 !important;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 80px;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
        }

        .reset-btn {
            border-radius: 8px !important;
            padding: 12px 15px;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            flex-shrink: 0;
            border: 1px solid #dee2e6;
            background: white;
            color: #6c757d;
            min-width: 100px;
        }

        .new-post-btn {
            border-radius: 8px !important;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .new-post-btn:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            color: white;
        }

        /* CORRECTIONS MOBILE SPÉCIFIQUES - CORRECTION DÉFINITIVE */
        @media (max-width: 768px) {
            .container {
                padding-left: 12px;
                padding-right: 12px;
            }

            .search-card {
                margin-bottom: 1rem;
            }

            .search-card .card-body {
                padding: 12px;
            }

            .search-main-area {
                gap: 8px;
            }

            .search-row {
                flex-direction: row;
                gap: 8px;
            }

            .search-input-group {
                flex: 1;
            }

            .search-input {
                padding: 14px 12px;
                font-size: 16px;
            }

            .search-btn {
                padding: 14px 15px;
                font-size: 14px;
                min-width: 70px;
            }

            .reset-btn {
                padding: 14px 12px;
                font-size: 14px;
                min-width: 90px;
            }

            .new-post-btn {
                padding: 14px 15px;
                font-size: 14px;
                margin-top: 8px;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }

            .search-main-area {
                gap: 10px;
            }

            .search-row {
                flex-direction: column;
                gap: 8px;
            }

            .search-input-group {
                width: 100%;
            }

            .search-input {
                width: 100%;
                padding: 12px;
            }

            .search-btn {
                width: auto;
                padding: 12px 15px;
            }

            .reset-btn {
                width: 100%;
                padding: 12px;
            }

            .new-post-btn {
                margin-top: 0;
            }
        }

        @media (max-width: 375px) {
            .container {
                padding-left: 8px;
                padding-right: 8px;
            }

            .search-card .card-body {
                padding: 10px;
            }

            .search-input {
                padding: 10px;
                font-size: 14px;
            }

            .search-btn {
                padding: 10px 12px;
                font-size: 13px;
                min-width: 60px;
            }

            .search-btn i,
            .reset-btn i {
                margin-right: 4px;
            }

            .reset-btn {
                padding: 10px;
                font-size: 13px;
            }

            .new-post-btn {
                padding: 10px 12px;
                font-size: 13px;
            }
        }

        /* STYLES EXISTANTS POUR LE RESTE DE LA PAGE */
        .navbar-nav .nav-item {
            margin: 2px 0;
        }

        .navbar-nav .nav-link {
            padding: 8px 12px !important;
            border-radius: 5px;
            margin: 1px 0;
        }

        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.1rem;
            }

            .navbar-nav {
                padding: 10px 0;
            }

            .navbar-nav .nav-link {
                padding: 10px 15px !important;
                margin: 2px 0;
            }

            .dropdown-menu {
                margin-top: 5px;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .card-body {
                padding: 15px;
            }

            .card-header {
                padding: 12px 15px;
            }

            .col-12 {
                padding-left: 5px;
                padding-right: 5px;
            }

            .mb-4 {
                margin-bottom: 1rem !important;
            }

            .d-flex.flex-wrap.gap-2 {
                gap: 8px !important;
            }

            .btn-sm {
                padding: 6px 10px;
                font-size: 0.8rem;
            }

            .card-text {
                margin-bottom: 12px;
                line-height: 1.4;
            }

            .mt-2 {
                margin-top: 8px !important;
            }

            .mt-3 {
                margin-top: 12px !important;
            }
        }

        @media (max-width: 576px) {
            .navbar-collapse {
                padding: 10px 0;
            }

            .navbar-nav.ms-auto {
                margin-top: 10px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                padding-top: 10px;
            }

            .modal-dialog {
                margin: 10px;
            }

            .modal-content {
                border-radius: 10px;
            }

            .signal-header,
            .confirm-header {
                padding: 15px;
            }

            .signal-icon {
                font-size: 2.5rem;
            }

            .confirm-icon {
                font-size: 3rem;
            }
        }

        .card {
            margin-bottom: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .card-body {
            padding: 20px;
        }

        .card-header {
            padding: 15px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .badge {
            margin-right: 5px;
            margin-bottom: 5px;
            padding: 6px 10px;
        }

        .d-flex.flex-wrap.gap-2 {
            gap: 10px !important;
        }

        .btn.flex-fill {
            min-width: 80px;
        }

        .card-text {
            line-height: 1.5;
            margin-bottom: 15px;
            word-wrap: break-word;
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translate(25%, -25%);
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
                    <li class="nav-item"><a class="nav-link active" href="dash1.php"><i class="fas fa-home me-1"></i>Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="publication_form.php"><i class="fas fa-plus me-1"></i>Nouvelle Publication</a></li>
                    <li class="nav-item"><a class="nav-link" href="liste_pub.php"><i class="fas fa-list me-1"></i>Mes Publications</a></li>
                    <li class="nav-item"><a class="nav-link" href="explorer.php"><i class="fas fa-search me-1"></i>Explorer</a></li>
                    <li class="nav-item"><a class="nav-link" href="liste_amis.php"><i class="fas fa-user-friends me-1"></i>Amis</a></li>
                    <li class="nav-item"><a class="nav-link" href="messagerie.php"><i class="fas fa-comments me-1"></i>Messagerie</a></li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="notifications.php" id="notificationsDropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($nb_notifications > 0): ?>
                                <span class="notification-badge"><?= $nb_notifications ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <span class="d-none d-sm-inline"><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user-edit me-2"></i>Profil</a></li>
                            <li><a class="dropdown-item" href="contact.php"><i class="fas fa-envelope me-2"></i>Contact</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="deconnexion.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Barre de recherche CORRIGÉE DÉFINITIVEMENT -->
        <div class="card search-card mb-4">
            <div class="card-body">
                <form method="GET" action="dash1.php" class="search-form">
                    <div class="search-main-area">
                        <div class="search-row">
                            <div class="search-input-group">
                                <input type="text" name="search" placeholder="Rechercher une publication..."
                                    class="form-control search-input" value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn search-btn">
                                    <i class="fas fa-search me-1"></i>Rechercher
                                </button>
                            </div>
                            <!-- BOUTON RÉINITIALISER -->
                            <?php if ($search): ?>
                                <a href="dash1.php" class="btn reset-btn">
                                    <i class="fas fa-times me-1"></i>Réinitialiser
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="search-row">
                            <a href="publication_form.php" class="btn new-post-btn">
                                <i class="fas fa-plus me-1"></i>Nouvelle Publication
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Le reste du code des publications reste identique -->
        <div class="row">
            <?php if (empty($publications)): ?>
                <div class="col-12">
                    <div class="card text-center py-5">
                        <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune publication trouvée</h5>
                        <p class="text-muted"><?= $search ? 'Aucun résultat pour votre recherche.' : 'Soyez le premier à partager quelque chose !' ?></p>
                        <?php if ($search): ?>
                            <a href="dash1.php" class="btn btn-primary mt-2">
                                <i class="fas fa-undo me-1"></i>Réinitialiser la recherche
                            </a>
                        <?php else: ?>
                            <a href="publication_form.php" class="btn btn-primary mt-2">
                                <i class="fas fa-plus me-1"></i>Créer une publication
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($publications as $pub): ?>
                    <div class="col-12 col-sm-6 col-lg-4 mb-4">
                        <div class="card h-100 fade-in">

                            <div class="card-header d-flex justify-content-between align-items-center">
                                <?php if (!empty($publication['photo_profil'])): ?>
                                    <img src="<?= htmlspecialchars($publication['photo_profil']) ?>"
                                        alt="Photo de <?= htmlspecialchars($publication['prenom']) ?>"
                                        class="user-photo-sm me-2">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-2x text-muted me-2"></i>
                                <?php endif; ?>
                                <small class="text-white-50"><?= date('d/m/Y H:i', strtotime($pub['date_enregistr'])) ?></small>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <!-- Contenu -->
                                <p class="card-text flex-grow-1">
                                    <?= nl2br(htmlspecialchars(substr($pub['contenu'], 0, 200))) ?>
                                    <?php if (strlen($pub['contenu']) > 200): ?>
                                        ... <a href="publication.php?id=<?= $pub['id'] ?>" class="text-primary text-decoration-none">Voir plus</a>
                                    <?php endif; ?>
                                </p>

                                <!-- Image -->
                                <?php if ($pub['image']): ?>
                                    <img src="uploads/<?= htmlspecialchars($pub['image']) ?>"
                                        class="img-fluid rounded mt-2"
                                        alt="Image publication"
                                        style="max-height: 200px; object-fit: cover;">
                                <?php endif; ?>

                                <!-- Statistiques -->
                                <?php
                                $countCom = $bdd->prepare("SELECT COUNT(*) FROM commentaires WHERE id_publication = ?");
                                $countCom->execute([$pub['id']]);
                                $nbCom = $countCom->fetchColumn();

                                $countLike = $bdd->prepare("SELECT COUNT(*) FROM reactions WHERE id_publication = ? AND type = 'like'");
                                $countLike->execute([$pub['id']]);
                                $nbLike = $countLike->fetchColumn();

                                $countDislike = $bdd->prepare("SELECT COUNT(*) FROM reactions WHERE id_publication = ? AND type = 'dislike'");
                                $countDislike->execute([$pub['id']]);
                                $nbDislike = $countDislike->fetchColumn();

                                $userReactionType = $user_reactions[$pub['id']] ?? null;
                                ?>

                                <div class="mt-2">
                                    <span class="badge bg-secondary me-1"><?= $nbCom ?> <i class="fas fa-comment"></i></span>
                                    <span class="badge bg-success me-1"><?= $nbLike ?> <i class="fas fa-thumbs-up"></i></span>
                                    <span class="badge bg-danger"><?= $nbDislike ?> <i class="fas fa-thumbs-down"></i></span>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <a href="commentaire_pub.php?id=<?= $pub['id'] ?>"
                                        class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fas fa-comment me-1"></i>Commenter
                                    </a>
                                    <a href="like.php?id=<?= $pub['id'] ?>&type=like"
                                        class="btn btn-sm flex-fill <?= $userReactionType === 'like' ? 'btn-success' : 'btn-outline-success' ?>">
                                        <i class="fas fa-thumbs-up me-1"></i>Like
                                    </a>
                                    <a href="like.php?id=<?= $pub['id'] ?>&type=dislike"
                                        class="btn btn-sm flex-fill <?= $userReactionType === 'dislike' ? 'btn-danger' : 'btn-outline-danger' ?>">
                                        <i class="fas fa-thumbs-down me-1"></i>Dislike
                                    </a>
                                </div>

                                <!-- Signalement -->
                                <?php if ($pub['id_users'] != $_SESSION['id']): ?>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-outline-warning btn-sm w-100"
                                            data-bs-toggle="modal"
                                            data-bs-target="#signalModal"
                                            data-publication-id="<?= $pub['id'] ?>"
                                            data-author-name="<?= htmlspecialchars($pub['prenom'] . ' ' . $pub['nom']) ?>">
                                            <i class="fas fa-flag me-1"></i>Signaler
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Les modals restent identiques -->
    <!-- Modal de Signalement -->
    <div class="modal fade" id="signalModal" tabindex="-1" aria-labelledby="signalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content signal-modal">
                <!-- En-tête du modal -->
                <div class="signal-header">
                    <div class="signal-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h5 class="modal-title mb-2">Signaler un contenu</h5>
                    <p class="mb-0 opacity-75">Aidez-nous à garder TalkSpace sécurisé</p>
                </div>

                <!-- Corps du modal -->
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-4">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        <small>Vous êtes sur le point de signaler une publication de <strong id="authorName"></strong></small>
                    </div>

                    <form id="signalForm">
                        <input type="hidden" name="type" value="publication">
                        <input type="hidden" name="id" id="publicationId">

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-3">Pourquoi signalez-vous cette publication ?</label>

                            <div class="reason-item" onclick="selectReason(this, 'spam')">
                                <div class="reason-title">
                                    <i class="fas fa-ban text-danger me-2"></i>Spam ou publicité
                                </div>
                                <div class="reason-desc">
                                    Contenu promotionnel non sollicité ou répétitif
                                </div>
                                <input type="radio" name="raison" value="spam" style="display: none;">
                            </div>

                            <div class="reason-item" onclick="selectReason(this, 'inappropriate')">
                                <div class="reason-title">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>Contenu inapproprié
                                </div>
                                <div class="reason-desc">
                                    Langage offensant, harcèlement ou contenu violent
                                </div>
                                <input type="radio" name="raison" value="inappropriate" style="display: none;">
                            </div>

                            <div class="reason-item" onclick="selectReason(this, 'false_info')">
                                <div class="reason-title">
                                    <i class="fas fa-times-circle text-info me-2"></i>Fausse information
                                </div>
                                <div class="reason-desc">
                                    Information trompeuse ou mensongère
                                </div>
                                <input type="radio" name="raison" value="false_info" style="display: none;">
                            </div>

                            <div class="reason-item" onclick="selectReason(this, 'other')">
                                <div class="reason-title">
                                    <i class="fas fa-ellipsis-h text-secondary me-2"></i>Autre raison
                                </div>
                                <div class="reason-desc">
                                    Autre problème nécessitant une modération
                                </div>
                                <input type="radio" name="raison" value="other" style="display: none;">
                            </div>
                        </div>

                        <div class="mb-3" id="commentSection" style="display: none;">
                            <label for="comment" class="form-label fw-bold text-dark">Détails supplémentaires (optionnel)</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3"
                                placeholder="Décrivez brièvement le problème..."></textarea>
                        </div>

                        <div class="d-flex gap-3 justify-content-center mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Annuler
                            </button>
                            <button type="button" class="btn signal-btn px-4" id="submitSignal" disabled onclick="showConfirmationModal()">
                                <i class="fas fa-paper-plane me-2"></i>Confirmer le signalement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmation -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content confirm-modal">
                <!-- En-tête du modal -->
                <div class="confirm-header">
                    <div class="confirm-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4 class="modal-title mb-2">Confirmer le signalement</h4>
                    <p class="mb-0 opacity-75">Votre action va être envoyée</p>
                </div>

                <!-- Corps du modal -->
                <div class="modal-body p-4 text-center">
                    <div class="mb-4">
                        <p class="text-muted mb-3">
                            Êtes-vous sûr de vouloir signaler cette publication ?
                        </p>
                        <div class="alert alert-info border">
                            <i class="fas fa-shield-alt me-2"></i>
                            <small>Votre signalement sera examiné par notre équipe de modération</small>
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-center">
                        <button type="button" class="btn btn-outline-secondary confirm-btn px-4" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-2"></i>Revenir
                        </button>
                        <button type="button" class="btn btn-success confirm-btn px-4" onclick="submitSignalForm()">
                            <i class="fas fa-check me-2"></i>Oui, signaler
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Variables globales
        let currentSignalData = {};

        // Gestion du modal de signalement
        const signalModal = document.getElementById('signalModal');
        signalModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const publicationId = button.getAttribute('data-publication-id');
            const authorName = button.getAttribute('data-author-name');

            document.getElementById('publicationId').value = publicationId;
            document.getElementById('authorName').textContent = authorName;

            // Réinitialiser le formulaire
            document.querySelectorAll('.reason-item').forEach(item => {
                item.classList.remove('selected');
            });
            document.getElementById('commentSection').style.display = 'none';
            document.getElementById('submitSignal').disabled = true;
            document.getElementById('comment').value = '';
        });

        // Sélection de la raison
        function selectReason(element, reason) {
            // Désélectionner toutes les options
            document.querySelectorAll('.reason-item').forEach(item => {
                item.classList.remove('selected');
            });

            // Sélectionner l'option cliquée
            element.classList.add('selected');

            // Cocher le radio correspondant
            const radio = element.querySelector('input[type="radio"]');
            radio.checked = true;

            // Activer le bouton de soumission
            document.getElementById('submitSignal').disabled = false;

            // Afficher la section commentaire
            document.getElementById('commentSection').style.display = 'block';
        }

        // Afficher le modal de confirmation
        function showConfirmationModal() {
            // Récupérer les données du formulaire
            const formData = new FormData(document.getElementById('signalForm'));
            currentSignalData = {
                type: formData.get('type'),
                id: formData.get('id'),
                raison: formData.get('raison'),
                comment: formData.get('comment') || ''
            };

            // Afficher le modal de confirmation
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            confirmModal.show();
        }

        // Soumettre le formulaire de signalement
        function submitSignalForm() {
            // Créer un formulaire dynamique
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'signal.php';

            // Ajouter les champs
            for (const [key, value] of Object.entries(currentSignalData)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }

            // Soumettre le formulaire
            document.body.appendChild(form);
            form.submit();
        }

        // Fermer le modal de signalement quand on ouvre la confirmation
        document.getElementById('confirmModal').addEventListener('show.bs.modal', function() {
            bootstrap.Modal.getInstance(signalModal).hide();
        });
    </script>
</body>

</html>