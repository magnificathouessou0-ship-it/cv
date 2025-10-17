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
$user_id = $_SESSION['id'];

// Vérifie que la publication existe
$checkPub = $bdd->prepare("SELECT p.*, u.nom, u.prenom FROM publication p JOIN users u ON p.id_users = u.id WHERE p.id = ?");
$checkPub->execute([$pub_id]);
$publication = $checkPub->fetch();

if (!$publication) {
    redirect("dash1.php");
}

// Gestion des actions
if (isset($_GET['action'])) {
    // Gestion des réactions aux commentaires
    if (isset($_GET['reaction_comment']) && isset($_GET['type'])) {
        $comment_id = intval($_GET['reaction_comment']);
        $type = $_GET['type']; // 'like' ou 'dislike'
        
        // Vérifier si l'utilisateur a déjà réagi à ce commentaire
        $checkReaction = $bdd->prepare("SELECT id, type FROM reactions_commentaires WHERE id_commentaire = ? AND id_users = ?");
        $checkReaction->execute([$comment_id, $user_id]);
        $existingReaction = $checkReaction->fetch();
        
        if ($existingReaction) {
            // Si l'utilisateur clique sur le même type de réaction, on la supprime
            if ($existingReaction['type'] === $type) {
                $delete = $bdd->prepare("DELETE FROM reactions_commentaires WHERE id_commentaire = ? AND id_users = ?");
                $delete->execute([$comment_id, $user_id]);
            } else {
                // Si l'utilisateur change de type de réaction, on met à jour
                $update = $bdd->prepare("UPDATE reactions_commentaires SET type = ? WHERE id_commentaire = ? AND id_users = ?");
                $update->execute([$type, $comment_id, $user_id]);
            }
        } else {
            // Nouvelle réaction
            $insert = $bdd->prepare("INSERT INTO reactions_commentaires (id_commentaire, id_users, type) VALUES (?, ?, ?)");
            $insert->execute([$comment_id, $user_id, $type]);
        }
        redirect("commentaire_pub.php?id=$pub_id");
    }
    
    // Gestion des réactions aux RÉPONSES
    if (isset($_GET['reaction_reply']) && isset($_GET['type'])) {
        $reply_id = intval($_GET['reaction_reply']);
        $type = $_GET['type']; // 'like' ou 'dislike'
        
        // Vérifier si l'utilisateur a déjà réagi à cette réponse
        $checkReaction = $bdd->prepare("SELECT id, type FROM reactions_reponses WHERE id_reponse = ? AND id_users = ?");
        $checkReaction->execute([$reply_id, $user_id]);
        $existingReaction = $checkReaction->fetch();
        
        if ($existingReaction) {
            // Si l'utilisateur clique sur le même type de réaction, on la supprime
            if ($existingReaction['type'] === $type) {
                $delete = $bdd->prepare("DELETE FROM reactions_reponses WHERE id_reponse = ? AND id_users = ?");
                $delete->execute([$reply_id, $user_id]);
            } else {
                // Si l'utilisateur change de type de réaction, on met à jour
                $update = $bdd->prepare("UPDATE reactions_reponses SET type = ? WHERE id_reponse = ? AND id_users = ?");
                $update->execute([$type, $reply_id, $user_id]);
            }
        } else {
            // Nouvelle réaction
            $insert = $bdd->prepare("INSERT INTO reactions_reponses (id_reponse, id_users, type) VALUES (?, ?, ?)");
            $insert->execute([$reply_id, $user_id, $type]);
        }
        redirect("commentaire_pub.php?id=$pub_id");
    }
    
    // Suppression de commentaire
    if ($_GET['action'] === 'delete_comment' && isset($_GET['comment_id'])) {
        $comment_id = intval($_GET['comment_id']);
        
        // Vérifier si l'utilisateur peut supprimer (propriétaire du commentaire OU propriétaire de la publication)
        $checkComment = $bdd->prepare("SELECT id_users, id_publication FROM commentaires WHERE id = ?");
        $checkComment->execute([$comment_id]);
        $comment = $checkComment->fetch();
        
        if ($comment && ($comment['id_users'] == $user_id || $publication['id_users'] == $user_id)) {
            $delete = $bdd->prepare("DELETE FROM commentaires WHERE id = ?");
            $delete->execute([$comment_id]);
            
            // Supprimer aussi les réponses associées
            $deleteReplies = $bdd->prepare("DELETE FROM reponses_commentaires WHERE id_commentaire = ?");
            $deleteReplies->execute([$comment_id]);
            
            redirect("commentaire_pub.php?id=$pub_id");
        }
    }
    
    // Suppression de réponse
    if ($_GET['action'] === 'delete_reply' && isset($_GET['reply_id'])) {
        $reply_id = intval($_GET['reply_id']);
        
        // Vérifier si l'utilisateur peut supprimer (propriétaire de la réponse OU propriétaire de la publication OU propriétaire du commentaire parent)
        $checkReply = $bdd->prepare("SELECT r.id_users, c.id_users as comment_author, c.id_publication 
                                   FROM reponses_commentaires r 
                                   JOIN commentaires c ON r.id_commentaire = c.id 
                                   WHERE r.id = ?");
        $checkReply->execute([$reply_id]);
        $reply = $checkReply->fetch();
        
        if ($reply && ($reply['id_users'] == $user_id || $publication['id_users'] == $user_id || $reply['comment_author'] == $user_id)) {
            $delete = $bdd->prepare("DELETE FROM reponses_commentaires WHERE id = ?");
            $delete->execute([$reply_id]);
            
            redirect("commentaire_pub.php?id=$pub_id");
        }
    }
}

// Gestion des réponses aux commentaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Réponse à un commentaire
    if (isset($_POST['reponse_contenu']) && isset($_POST['comment_id'])) {
        $reponse_contenu = trim(sanitize($_POST['reponse_contenu']));
        $comment_id = intval($_POST['comment_id']);
        
        if (!empty($reponse_contenu)) {
            $insert = $bdd->prepare("INSERT INTO reponses_commentaires (contenu, date_reponse, id_users, id_commentaire) VALUES (?, NOW(), ?, ?)");
            $insert->execute([$reponse_contenu, $user_id, $comment_id]);
            
            // Notification à l'auteur du commentaire si ce n'est pas le même utilisateur
            $checkCommentAuthor = $bdd->prepare("SELECT id_users FROM commentaires WHERE id = ?");
            $checkCommentAuthor->execute([$comment_id]);
            $commentAuthor = $checkCommentAuthor->fetch();
            
            if ($commentAuthor && $commentAuthor['id_users'] != $user_id) {
                $pseudo = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
                $message = "$pseudo a répondu à votre commentaire.";
                
                $notif = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
                $notif->execute([$commentAuthor['id_users'], $message]);
            }
        }
        redirect("commentaire_pub.php?id=$pub_id");
    }
    
    // Commentaire principal
    if (isset($_POST['contenu']) && !isset($_POST['comment_id'])) {
        $contenu = trim(sanitize($_POST['contenu']));

        if (!empty($contenu)) {
            $insert = $bdd->prepare("INSERT INTO commentaires (contenu, date_commentaire, id_users, id_publication) VALUES (?, NOW(), ?, ?)");
            $insert->execute([$contenu, $user_id, $pub_id]);

            // Notification à l'auteur si ce n'est pas le commentateur
            if ($publication['id_users'] != $user_id) {
                $pseudo = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
                $message = "$pseudo a commenté votre publication.";

                $notif = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
                $notif->execute([$publication['id_users'], $message]);
            }
        }
        redirect("commentaire_pub.php?id=$pub_id");
    }
}

// Récupérer les commentaires avec leurs réactions
$stmt = $bdd->prepare("
    SELECT c.id, c.contenu, c.date_commentaire, c.date_modif, u.nom, u.prenom, u.id as user_id,
           (SELECT COUNT(*) FROM reactions_commentaires rc WHERE rc.id_commentaire = c.id AND rc.type = 'like') as likes,
           (SELECT COUNT(*) FROM reactions_commentaires rc WHERE rc.id_commentaire = c.id AND rc.type = 'dislike') as dislikes,
           (SELECT type FROM reactions_commentaires rc WHERE rc.id_commentaire = c.id AND rc.id_users = ?) as user_reaction
    FROM commentaires c 
    JOIN users u ON c.id_users = u.id 
    WHERE c.id_publication = ? 
    ORDER BY c.date_commentaire DESC
");
$stmt->execute([$user_id, $pub_id]);
$commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les réponses pour chaque commentaire avec leurs réactions
$reponses_par_commentaire = [];
foreach ($commentaires as $commentaire) {
    $stmt_reponses = $bdd->prepare("
        SELECT r.*, u.nom, u.prenom, u.id as user_id,
               (SELECT COUNT(*) FROM reactions_reponses rr WHERE rr.id_reponse = r.id AND rr.type = 'like') as likes,
               (SELECT COUNT(*) FROM reactions_reponses rr WHERE rr.id_reponse = r.id AND rr.type = 'dislike') as dislikes,
               (SELECT type FROM reactions_reponses rr WHERE rr.id_reponse = r.id AND rr.id_users = ?) as user_reaction
        FROM reponses_commentaires r 
        JOIN users u ON r.id_users = u.id 
        WHERE r.id_commentaire = ? 
        ORDER BY r.date_reponse ASC
    ");
    $stmt_reponses->execute([$user_id, $commentaire['id']]);
    $reponses_par_commentaire[$commentaire['id']] = $stmt_reponses->fetchAll(PDO::FETCH_ASSOC);
}
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
    <style>
        .comment-item {
            transition: all 0.3s ease;
        }
        .comment-item:hover {
            background-color: #f8f9fa;
        }
        .reaction-btn.active {
            transform: scale(1.1);
            font-weight: bold;
        }
        .replies-container {
            margin-left: 2rem;
            border-left: 3px solid #e9ecef;
            padding-left: 1rem;
        }
        .reply-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 8px;
        }
        .show-replies-btn {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .show-replies-btn:hover {
            text-decoration: underline;
        }
        .delete-comment-btn {
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        .delete-comment-btn:hover {
            opacity: 1;
        }
        .reply-form {
            margin-top: 10px;
        }
        .reaction-count {
            min-width: 20px;
            text-align: center;
            display: inline-block;
        }
        .reply-actions {
            margin-top: 8px;
        }
        .delete-reply-btn {
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        .delete-reply-btn:hover {
            opacity: 1;
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
                                <div class="comment-item border-bottom pb-3 mb-3" id="comment-<?= $c['id'] ?>">
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
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="text-muted">
                                                <?= date('d/m/Y à H:i', strtotime($c['date_commentaire'])) ?>
                                            </small>
                                            <!-- Bouton de suppression pour le propriétaire de la publication -->
                                            <?php if ($publication['id_users'] == $user_id || $c['user_id'] == $user_id): ?>
                                                <button type="button" class="btn btn-sm delete-comment-btn text-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteCommentModal"
                                                        data-comment-id="<?= $c['id'] ?>"
                                                        data-comment-author="<?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?>"
                                                        title="Supprimer le commentaire">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <p class="mb-2"><?= nl2br(htmlspecialchars($c['contenu'])) ?></p>

                                    <!-- Réactions aux commentaires -->
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <div class="d-flex align-items-center gap-1">
                                            <a href="?id=<?= $pub_id ?>&action=reaction_comment&reaction_comment=<?= $c['id'] ?>&type=like" 
                                               class="btn btn-sm reaction-btn <?= $c['user_reaction'] === 'like' ? 'active btn-success' : 'btn-outline-success' ?>">
                                                <i class="fas fa-thumbs-up me-1"></i>
                                                <span class="reaction-count"><?= $c['likes'] ?></span>
                                            </a>
                                            <a href="?id=<?= $pub_id ?>&action=reaction_comment&reaction_comment=<?= $c['id'] ?>&type=dislike" 
                                               class="btn btn-sm reaction-btn <?= $c['user_reaction'] === 'dislike' ? 'active btn-danger' : 'btn-outline-danger' ?>">
                                                <i class="fas fa-thumbs-down me-1"></i>
                                                <span class="reaction-count"><?= $c['dislikes'] ?></span>
                                            </a>
                                        </div>
                                        
                                        <!-- Bouton pour répondre -->
                                        <button type="button" class="btn btn-sm btn-outline-primary show-reply-form" 
                                                data-comment-id="<?= $c['id'] ?>">
                                            <i class="fas fa-reply me-1"></i>Répondre
                                        </button>
                                        
                                        <!-- Afficher/Masquer les réponses -->
                                        <?php if (!empty($reponses_par_commentaire[$c['id']])): ?>
                                            <button type="button" class="btn btn-sm show-replies-btn" 
                                                    data-comment-id="<?= $c['id'] ?>">
                                                <i class="fas fa-comments me-1"></i>
                                                Voir les réponses (<?= count($reponses_par_commentaire[$c['id']]) ?>)
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Formulaire de réponse -->
                                    <div class="reply-form" id="reply-form-<?= $c['id'] ?>" style="display: none;">
                                        <form method="post" class="mt-2">
                                            <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                            <div class="input-group">
                                                <input type="text" name="reponse_contenu" class="form-control form-control-sm" 
                                                       placeholder="Votre réponse..." maxlength="500" required>
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Réponses aux commentaires -->
                                    <?php if (!empty($reponses_par_commentaire[$c['id']])): ?>
                                        <div class="replies-container mt-3" id="replies-<?= $c['id'] ?>" style="display: none;">
                                            <h6 class="text-muted mb-2"><small>Réponses :</small></h6>
                                            <?php foreach ($reponses_par_commentaire[$c['id']] as $reponse): ?>
                                                <div class="reply-item">
                                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                                        <strong class="text-info">
                                                            <i class="fas fa-user me-1"></i>
                                                            <?= htmlspecialchars($reponse['prenom'] . ' ' . $reponse['nom']) ?>
                                                        </strong>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <small class="text-muted">
                                                                <?= date('d/m/Y H:i', strtotime($reponse['date_reponse'])) ?>
                                                            </small>
                                                            <!-- Bouton de suppression pour la réponse -->
                                                            <?php if ($publication['id_users'] == $user_id || $c['user_id'] == $user_id || $reponse['user_id'] == $user_id): ?>
                                                                <button type="button" class="btn btn-sm delete-reply-btn text-danger"
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteReplyModal"
                                                                        data-reply-id="<?= $reponse['id'] ?>"
                                                                        data-reply-author="<?= htmlspecialchars($reponse['prenom'] . ' ' . $reponse['nom']) ?>"
                                                                        title="Supprimer la réponse">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <p class="mb-2 small"><?= nl2br(htmlspecialchars($reponse['contenu'])) ?></p>
                                                    
                                                    <!-- Réactions aux RÉPONSES -->
                                                    <div class="reply-actions d-flex align-items-center gap-2">
                                                        <div class="d-flex align-items-center gap-1">
                                                            <a href="?id=<?= $pub_id ?>&action=reaction_reply&reaction_reply=<?= $reponse['id'] ?>&type=like" 
                                                               class="btn btn-sm reaction-btn <?= $reponse['user_reaction'] === 'like' ? 'active btn-success' : 'btn-outline-success' ?>">
                                                                <i class="fas fa-thumbs-up me-1"></i>
                                                                <span class="reaction-count"><?= $reponse['likes'] ?></span>
                                                            </a>
                                                            <a href="?id=<?= $pub_id ?>&action=reaction_reply&reaction_reply=<?= $reponse['id'] ?>&type=dislike" 
                                                               class="btn btn-sm reaction-btn <?= $reponse['user_reaction'] === 'dislike' ? 'active btn-danger' : 'btn-outline-danger' ?>">
                                                                <i class="fas fa-thumbs-down me-1"></i>
                                                                <span class="reaction-count"><?= $reponse['dislikes'] ?></span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire d'ajout de commentaire principal -->
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

<!-- Modal de confirmation de suppression de commentaire -->
<div class="modal fade" id="deleteCommentModal" tabindex="-1" aria-labelledby="deleteCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCommentModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Supprimer le commentaire
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le commentaire de <strong id="commentAuthorName"></strong> ?</p>
                <p class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    Cette action est irréversible. Toutes les réponses associées seront également supprimées.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <a href="#" id="confirmDeleteCommentBtn" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i>Supprimer le commentaire
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression de réponse -->
<div class="modal fade" id="deleteReplyModal" tabindex="-1" aria-labelledby="deleteReplyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteReplyModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Supprimer la réponse
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer la réponse de <strong id="replyAuthorName"></strong> ?</p>
                <p class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    Cette action est irréversible.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <a href="#" id="confirmDeleteReplyBtn" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i>Supprimer la réponse
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

// Gestion des formulaires de réponse
document.querySelectorAll('.show-reply-form').forEach(button => {
    button.addEventListener('click', function() {
        const commentId = this.getAttribute('data-comment-id');
        const replyForm = document.getElementById('reply-form-' + commentId);
        
        // Masquer tous les autres formulaires de réponse
        document.querySelectorAll('.reply-form').forEach(form => {
            if (form.id !== 'reply-form-' + commentId) {
                form.style.display = 'none';
            }
        });
        
        // Afficher/masquer le formulaire actuel
        replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
    });
});

// Gestion de l'affichage des réponses
document.querySelectorAll('.show-replies-btn').forEach(button => {
    button.addEventListener('click', function() {
        const commentId = this.getAttribute('data-comment-id');
        const repliesContainer = document.getElementById('replies-' + commentId);
        
        if (repliesContainer.style.display === 'none' || repliesContainer.style.display === '') {
            repliesContainer.style.display = 'block';
            this.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Masquer les réponses';
        } else {
            repliesContainer.style.display = 'none';
            this.innerHTML = '<i class="fas fa-comments me-1"></i>Voir les réponses (' + 
                repliesContainer.querySelectorAll('.reply-item').length + ')';
        }
    });
});

// Gestion du modal de suppression de commentaire
const deleteCommentModal = document.getElementById('deleteCommentModal');
if (deleteCommentModal) {
    deleteCommentModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const commentId = button.getAttribute('data-comment-id');
        const commentAuthor = button.getAttribute('data-comment-author');
        
        document.getElementById('commentAuthorName').textContent = commentAuthor;
        
        const confirmDeleteBtn = document.getElementById('confirmDeleteCommentBtn');
        confirmDeleteBtn.href = `?id=<?= $pub_id ?>&action=delete_comment&comment_id=${commentId}`;
    });
}

// Gestion du modal de suppression de réponse
const deleteReplyModal = document.getElementById('deleteReplyModal');
if (deleteReplyModal) {
    deleteReplyModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const replyId = button.getAttribute('data-reply-id');
        const replyAuthor = button.getAttribute('data-reply-author');
        
        document.getElementById('replyAuthorName').textContent = replyAuthor;
        
        const confirmDeleteBtn = document.getElementById('confirmDeleteReplyBtn');
        confirmDeleteBtn.href = `?id=<?= $pub_id ?>&action=delete_reply&reply_id=${replyId}`;
    });
}
</script>
</body>
</html>