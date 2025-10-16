<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

$id_users = $_SESSION['id'];

// Gestion recherche
$search = isset($_GET['search']) ? trim(sanitize($_GET['search'])) : '';

if ($search) {
    $query = $bdd->prepare("SELECT p.*, u.nom FROM publication p 
                           JOIN users u ON p.id_users = u.id 
                           WHERE p.contenu LIKE ? 
                           ORDER BY p.id DESC");
    $query->execute(["%$search%"]);
    $publications = $query->fetchAll();
} else {
    $publications = $bdd->query("SELECT p.*, u.nom FROM publication p 
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
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['prenom']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user-edit me-2"></i>Profil</a></li>
                        <li><a class="dropdown-item" href="contact.php"><i class="fas fa-envelope me-2"></i>Contact</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="deconnexion.php"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- Barre de recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="dash1.php" class="row g-3 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" name="search" placeholder="Rechercher une publication..." 
                               class="form-control" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Rechercher
                        </button>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="publication_form.php" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Nouvelle Publication
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Publications -->
    <div class="row">
        <?php if (empty($publications)): ?>
            <div class="col-12">
                <div class="card text-center py-5">
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune publication trouvée</h5>
                    <p class="text-muted">Soyez le premier à partager quelque chose !</p>
                    <a href="publication_form.php" class="btn btn-primary mt-2">
                        <i class="fas fa-plus me-1"></i>Créer une publication
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($publications as $pub): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><?= htmlspecialchars($pub['nom']) ?></span>
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
                                <form action="signal.php" method="POST" class="mt-2">
                                    <input type="hidden" name="type" value="publication">
                                    <input type="hidden" name="id" value="<?= $pub['id'] ?>">
                                    <button type="submit" class="btn btn-outline-warning btn-sm w-100" 
                                            onclick="return confirm('Signaler cette publication ?')">
                                        <i class="fas fa-flag me-1"></i>Signaler
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>