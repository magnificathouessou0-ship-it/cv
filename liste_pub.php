<?php
session_start();
require_once('functions.php');
require_once('config.php');
requireLogin();

$id_user = $_SESSION['id'];
$stmt = $bdd->prepare("SELECT * FROM publication WHERE id_users = ? ORDER BY date_enregistr DESC");
$stmt->execute([$id_user]);
$publications = $stmt->fetchAll();

// Gestion des messages de succès
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Publications | TalkSpace</title>
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
                <li class="nav-item"><a class="nav-link" href="publication_form.php"><i class="fas fa-plus me-1"></i>Nouvelle Publication</a></li>
                <li class="nav-item"><a class="nav-link active" href="liste_pub.php"><i class="fas fa-list me-1"></i>Mes Publications</a></li>
                <li class="nav-item"><a class="nav-link" href="explorer.php"><i class="fas fa-search me-1"></i>Explorer</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="profil.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="deconnexion.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="card slide-up">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-newspaper me-2"></i>Mes Publications</h4>
            <a href="publication_form.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Nouvelle Publication
            </a>
        </div>
        <div class="card-body">
            <!-- Messages de statut -->
            <?php if ($success === 'publication_created'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Publication créée avec succès !
                </div>
            <?php elseif ($success === 'publication_updated'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Publication modifiée avec succès !
                </div>
            <?php elseif ($success === 'publication_deleted'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Publication supprimée avec succès !
                </div>
            <?php endif; ?>

            <?php if (empty($publications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune publication</h5>
                    <p class="text-muted">Commencez par créer votre première publication</p>
                    <a href="publication_form.php" class="btn btn-primary mt-2">
                        <i class="fas fa-plus me-1"></i>Créer une publication
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($publications as $pub): ?>
                        <div class="col-12 col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 publication-card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-white-50">
                                            <?= date('d/m/Y', strtotime($pub['date_enregistr'])) ?>
                                        </small>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('H:i', strtotime($pub['date_enregistr'])) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <!-- Contenu texte -->
                                    <div class="flex-grow-1">
                                        <p class="card-text publication-content">
                                            <?= nl2br(htmlspecialchars(substr($pub['contenu'], 0, 150))) ?>
                                            <?php if (strlen($pub['contenu']) > 150): ?>
                                                ... <a href="publication.php?id=<?= $pub['id'] ?>" class="text-primary text-decoration-none">Voir plus</a>
                                            <?php endif; ?>
                                        </p>

                                        <!-- Image -->
                                        <?php if (!empty($pub['image'])): ?>
                                            <div class="publication-image mt-2">
                                                <img src="uploads/<?= htmlspecialchars($pub['image']) ?>" 
                                                     class="img-fluid rounded" 
                                                     alt="Image publication"
                                                     style="max-height: 200px; object-fit: cover;">
                                            </div>
                                        <?php endif; ?>
                                    </div>

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
                                    ?>

                                    <div class="publication-stats mt-3">
                                        <div class="d-flex justify-content-around text-center">
                                            <div>
                                                <small class="text-muted">
                                                    <i class="fas fa-comment"></i><br>
                                                    <?= $nbCom ?>
                                                </small>
                                            </div>
                                            <div>
                                                <small class="text-muted">
                                                    <i class="fas fa-thumbs-up text-success"></i><br>
                                                    <?= $nbLike ?>
                                                </small>
                                            </div>
                                            <div>
                                                <small class="text-muted">
                                                    <i class="fas fa-thumbs-down text-danger"></i><br>
                                                    <?= $nbDislike ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="publication-actions mt-3">
                                        <div class="d-grid gap-2">
                                            <a href="commentaire_pub.php?id=<?= $pub['id'] ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-comments me-1"></i>Commentaires
                                            </a>
                                            <div class="d-flex gap-2">
                                                <a href="edit_pub.php?id=<?= $pub['id'] ?>" 
                                                   class="btn btn-outline-success btn-sm flex-fill">
                                                    <i class="fas fa-edit me-1"></i>Modifier
                                                </a>
                                                <!-- Bouton Supprimer avec Modal -->
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm flex-fill"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal"
                                                        data-publication-id="<?= $pub['id'] ?>"
                                                        data-publication-content="<?= htmlspecialchars(substr($pub['contenu'], 0, 100)) ?>...">
                                                    <i class="fas fa-trash me-1"></i>Supprimer
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistiques globales -->
    <?php if (!empty($publications)): ?>
        <div class="card mt-4 fade-in">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistiques de vos publications</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <i class="fas fa-newspaper fa-2x text-primary mb-2"></i>
                            <h4><?= count($publications) ?></h4>
                            <p class="text-muted mb-0">Publications</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <i class="fas fa-comment fa-2x text-success mb-2"></i>
                            <h4>
                                <?php 
                                $total_com = $bdd->prepare("SELECT COUNT(*) FROM commentaires c JOIN publication p ON c.id_publication = p.id WHERE p.id_users = ?");
                                $total_com->execute([$id_user]);
                                echo $total_com->fetchColumn();
                                ?>
                            </h4>
                            <p class="text-muted mb-0">Commentaires totaux</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <i class="fas fa-thumbs-up fa-2x text-info mb-2"></i>
                            <h4>
                                <?php 
                                $total_likes = $bdd->prepare("SELECT COUNT(*) FROM reactions r JOIN publication p ON r.id_publication = p.id WHERE p.id_users = ? AND r.type = 'like'");
                                $total_likes->execute([$id_user]);
                                echo $total_likes->fetchColumn();
                                ?>
                            </h4>
                            <p class="text-muted mb-0">Likes totaux</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <i class="fas fa-eye fa-2x text-warning mb-2"></i>
                            <h4>
                                <?php 
                                $total_vues = $bdd->prepare("SELECT COUNT(*) FROM publication p LEFT JOIN commentaires c ON p.id = c.id_publication WHERE p.id_users = ?");
                                $total_vues->execute([$id_user]);
                                echo $total_vues->fetchColumn();
                                ?>
                            </h4>
                            <p class="text-muted mb-0">Activité totale</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    <h5>Êtes-vous sûr de vouloir supprimer cette publication ?</h5>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Attention :</strong> Cette action est irréversible. La publication et tous ses commentaires seront définitivement supprimés.
                </div>

                <div class="publication-preview border rounded p-3 bg-light mt-3">
                    <h6>Publication à supprimer :</h6>
                    <p id="publicationContent" class="mb-0 text-muted"></p>
                </div>

                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Cette action ne peut pas être annulée
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <a href="#" id="confirmDelete" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i>Supprimer définitivement
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
// Gestion de la modal de suppression
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteModal');
    
    deleteModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const publicationId = button.getAttribute('data-publication-id');
        const publicationContent = button.getAttribute('data-publication-content');
        
        // Mettre à jour le contenu de la modal
        document.getElementById('publicationContent').textContent = publicationContent;
        
        // Mettre à jour le lien de suppression
        const confirmDelete = document.getElementById('confirmDelete');
        confirmDelete.href = 'trash_pub.php?id=' + publicationId;
    });
});
</script>

<style>
.publication-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.publication-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
.publication-content {
    line-height: 1.5;
    max-height: 120px;
    overflow: hidden;
}
.publication-image {
    border-radius: 8px;
    overflow: hidden;
}
.publication-stats {
    border-top: 1px solid var(--border-color);
    padding-top: 10px;
}
.publication-actions {
    border-top: 1px solid var(--border-color);
    padding-top: 15px;
}
.modal-header {
    border-bottom: none;
}
.publication-preview {
    max-height: 100px;
    overflow-y: auto;
}
</style>
</body>
</html>