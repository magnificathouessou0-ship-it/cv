<?php
session_start();
require_once('functions.php');
require_once('config.php');
requireLogin();

// Validation et sécurisation de l'ID
$pub_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$pub_id || $pub_id <= 0) {
    redirect("liste_pub.php");
}

try {
    $stmt = $bdd->prepare("SELECT * FROM publication WHERE id = ? AND id_users = ?");
    $stmt->execute([$pub_id, $_SESSION['id']]);
    $publication = $stmt->fetch();
    
    if (!$publication) {
        redirect("liste_pub.php");
    }
} catch (PDOException $e) {
    error_log("Erreur base de données - modifier_pub.php: " . $e->getMessage());
    $_SESSION['erreur'] = "Une erreur est survenue lors du chargement de la publication.";
    redirect("liste_pub.php");
}

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider'])) {
    $contenu = trim($_POST['contenu'] ?? '');
    $image = $publication['image'];
    
    // Validation du contenu
    if (empty($contenu)) {
        $erreur = "Le contenu de la publication ne peut pas être vide.";
    } elseif (strlen($contenu) > 3000) {
        $erreur = "Le contenu ne peut pas dépasser 3000 caractères.";
    }
    
    // Gestion de l'upload d'image
    if (empty($erreur) && !empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $max_file_size = 5 * 1024 * 1024; // 5MB
        
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validation du fichier
        if (!in_array($file_ext, $allowed_ext)) {
            $erreur = "Format d'image non supporté. Formats acceptés: " . implode(', ', $allowed_ext);
        } elseif ($file_size > $max_file_size) {
            $erreur = "Le fichier est trop volumineux. Taille maximum: 5MB";
        } elseif (!getimagesize($file_tmp)) {
            $erreur = "Le fichier sélectionné n'est pas une image valide.";
        } else {
            // Préparation du répertoire d'upload
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Génération d'un nom de fichier sécurisé
            $new_filename = 'img_' . uniqid() . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            
            // Suppression de l'ancienne image si elle existe
            if (!empty($publication['image'])) {
                $old_image_path = $upload_dir . $publication['image'];
                if (file_exists($old_image_path) && is_file($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            
            // Déplacement du nouveau fichier
            if (move_uploaded_file($file_tmp, $destination)) {
                $image = $new_filename;
            } else {
                $erreur = "Erreur lors du téléchargement de l'image. Veuillez réessayer.";
            }
        }
    } elseif (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => "Le fichier dépasse la taille maximum autorisée par le serveur.",
            UPLOAD_ERR_FORM_SIZE => "Le fichier dépasse la taille maximum autorisée.",
            UPLOAD_ERR_PARTIAL => "Le téléchargement a été interrompu.",
            UPLOAD_ERR_NO_FILE => "Aucun fichier n'a été téléchargé.",
            UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant.",
            UPLOAD_ERR_CANT_WRITE => "Erreur d'écriture sur le disque.",
            UPLOAD_ERR_EXTENSION => "Une extension PHP a arrêté le téléchargement."
        ];
        $erreur = $upload_errors[$_FILES['image']['error']] ?? "Erreur inconnue lors du téléchargement.";
    }
    
    // Mise à jour en base de données
    if (empty($erreur)) {
        try {
            $update_stmt = $bdd->prepare("UPDATE publication SET contenu = ?, image = ?, date_enregistr = NOW() WHERE id = ? AND id_users = ?");
            $update_stmt->execute([$contenu, $image, $pub_id, $_SESSION['id']]);
            
            if ($update_stmt->rowCount() > 0) {
                $_SESSION['success'] = "Publication modifiée avec succès!";
                redirect("liste_pub.php");
            } else {
                $erreur = "Aucune modification n'a été effectuée.";
            }
        } catch (PDOException $e) {
            error_log("Erreur mise à jour publication - modifier_pub.php: " . $e->getMessage());
            $erreur = "Erreur lors de la mise à jour de la publication. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la publication | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .current-image img {
            max-height: 200px;
            object-fit: contain;
        }
        .image-preview img {
            max-height: 150px;
            object-fit: contain;
        }
        .char-count-warning {
            color: #dc3545;
            font-weight: bold;
        }
        .slide-up {
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
                <li class="nav-item"><a class="nav-link active" href="liste_pub.php"><i class="fas fa-list me-1"></i>Mes Publications</a></li>
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
            <div class="card slide-up">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Modifier la publication</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($erreur)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($erreur) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="publicationForm">
                        <div class="mb-4">
                            <label for="contenu" class="form-label fw-semibold">Contenu de la publication <span class="text-danger">*</span></label>
                            <textarea name="contenu" id="contenu" rows="6" class="form-control" 
                                      placeholder="Modifiez le contenu de votre publication..." 
                                      maxlength="3000" required><?= htmlspecialchars($publication['contenu']) ?></textarea>
                            <div class="form-text d-flex justify-content-between">
                                <span>Caractères restants: <span id="charRemaining"><?= 3000 - strlen($publication['contenu']) ?></span></span>
                                <span><span id="charCount"><?= strlen($publication['contenu']) ?></span>/3000</span>
                            </div>
                        </div>

                        <!-- Image actuelle -->
                        <?php if (!empty($publication['image'])): ?>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Image actuelle</label>
                                <div class="current-image border rounded p-3 bg-light">
                                    <img src="uploads/<?= htmlspecialchars($publication['image']) ?>" 
                                         class="img-fluid rounded" 
                                         alt="Image actuelle de la publication"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <div class="image-placeholder text-center" style="display: none;">
                                        <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                        <p class="text-muted">Image non disponible</p>
                                    </div>
                                    <div class="mt-2 text-center">
                                        <small class="text-muted">Image actuelle</small>
                                        <br>
                                        <button type="button" class="btn btn-sm btn-outline-danger mt-1" id="removeImage">
                                            <i class="fas fa-trash me-1"></i>Supprimer cette image
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Nouvelle image -->
                        <div class="mb-4">
                            <label for="image" class="form-label fw-semibold">
                                <?= empty($publication['image']) ? 'Ajouter une image' : 'Remplacer l\'image' ?> 
                                <span class="text-muted">(optionnel)</span>
                            </label>
                            <input type="file" name="image" id="image" class="form-control" 
                                   accept=".jpg,.jpeg,.png,.gif,.webp">
                            <div class="form-text">
                                Formats acceptés: JPG, JPEG, PNG, GIF, WEBP (max 5MB)
                            </div>
                            <div class="image-preview mt-2"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="liste_pub.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour aux publications
                            </a>
                            <div>
                                <button type="button" class="btn btn-outline-danger me-2" onclick="resetForm()">
                                    <i class="fas fa-undo me-1"></i>Réinitialiser
                                </button>
                                <button type="submit" name="valider" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informations sur la publication -->
            <div class="card mt-4 slide-up">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Informations sur la publication</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Date de création:</small>
                            <span class="fw-semibold"><?= date('d/m/Y à H:i', strtotime($publication['date_enregistr'])) ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Dernière modification:</small>
                            <span class="fw-semibold"><?= date('d/m/Y à H:i') ?></span>
                        </div>
                    </div>
                    <?php if (!empty($publication['image'])): ?>
                        <div class="row">
                            <div class="col-12">
                                <small class="text-muted d-block">Nom du fichier image:</small>
                                <span class="fw-semibold"><?= htmlspecialchars($publication['image']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Compteur de caractères
const contenuTextarea = document.getElementById('contenu');
const charCount = document.getElementById('charCount');
const charRemaining = document.getElementById('charRemaining');

function updateCharCount() {
    const length = contenuTextarea.value.length;
    charCount.textContent = length;
    charRemaining.textContent = 3000 - length;
    
    if (length > 2990) {
        charRemaining.classList.add('char-count-warning');
    } else {
        charRemaining.classList.remove('char-count-warning');
    }
}

contenuTextarea.addEventListener('input', updateCharCount);

// Preview de la nouvelle image
document.getElementById('image').addEventListener('change', function(e) {
    const file = this.files[0];
    const preview = document.querySelector('.image-preview');
    
    if (file) {
        // Validation de la taille
        if (file.size > 5 * 1024 * 1024) {
            alert('Le fichier est trop volumineux. Taille maximum: 5MB');
            this.value = '';
            preview.innerHTML = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="border rounded p-3 bg-light">
                    <p class="mb-2 fw-semibold"><i class="fas fa-image me-1"></i>Aperçu de la nouvelle image:</p>
                    <img src="${e.target.result}" class="img-thumbnail d-block mx-auto" alt="Aperçu">
                    <div class="mt-2 text-center">
                        <small class="text-muted">${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</small>
                    </div>
                </div>
            `;
        }
        reader.onerror = function() {
            preview.innerHTML = '<div class="alert alert-warning">Erreur lors de la lecture du fichier</div>';
        }
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
});

// Suppression de l'image actuelle
<?php if (!empty($publication['image'])): ?>
document.getElementById('removeImage').addEventListener('click', function() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette image ? Cette action est irréversible.')) {
        // Créer un champ caché pour indiquer la suppression
        let hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'remove_current_image';
        hiddenInput.value = '1';
        document.getElementById('publicationForm').appendChild(hiddenInput);
        
        // Masquer l'image actuelle
        document.querySelector('.current-image').style.display = 'none';
        
        // Afficher un message
        const preview = document.querySelector('.image-preview');
        preview.innerHTML = '<div class="alert alert-info">L\'image actuelle sera supprimée lors de l\'enregistrement.</div>';
    }
});
<?php endif; ?>

// Réinitialisation du formulaire
function resetForm() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ? Toutes les modifications non enregistrées seront perdues.')) {
        document.getElementById('publicationForm').reset();
        document.querySelector('.image-preview').innerHTML = '';
        updateCharCount();
        
        // Réafficher l'image actuelle si elle était masquée
        const currentImage = document.querySelector('.current-image');
        if (currentImage) {
            currentImage.style.display = 'block';
        }
        
        // Supprimer le champ de suppression d'image s'il existe
        const removeInput = document.querySelector('input[name="remove_current_image"]');
        if (removeInput) {
            removeInput.remove();
        }
    }
}

// Empêcher la soumission du formulaire avec Enter dans le textarea
contenuTextarea.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.ctrlKey) {
        document.getElementById('publicationForm').submit();
    }
});

// Gestion des erreurs d'image
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('error', function() {
            this.style.display = 'none';
            const placeholder = this.nextElementSibling;
            if (placeholder && placeholder.classList.contains('image-placeholder')) {
                placeholder.style.display = 'block';
            }
        });
    });
});
</script>
</body>
</html>