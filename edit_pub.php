<?php
session_start();
require_once('functions.php');
require_once('config.php');
requireLogin();

$pub_id = $_GET['id'] ?? null;
if (!$pub_id || !is_numeric($pub_id)) {
    redirect("liste_pub.php");
}

try {
    $pub = $bdd->prepare("SELECT * FROM publication WHERE id = ? AND id_users = ?");
    $pub->execute([$pub_id, $_SESSION['id']]);
    $data = $pub->fetch();
} catch (PDOException $e) {
    die("Erreur: ". $e->getMessage());
}

if (!$data) {
    redirect("liste_pub.php");
}

$erreur = "";
$success = "";

if (isset($_POST['valider'])) {
    $contenu = trim($_POST['contenu'] ?? '');
    $image = $data['image']; // image existante par défaut
    
    // CORRECTION : Validation du contenu
    if (empty($contenu)) {
        $erreur = "Le contenu ne peut pas être vide.";
    } elseif (strlen($contenu) > 3000) {
        $erreur = "Le contenu ne doit pas dépasser 3000 caractères.";
    } else {
        // Upload d'une nouvelle image si fournie
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed_ext)) {
                // Vérifier la taille du fichier (max 5MB)
                if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                    $erreur = "L'image ne doit pas dépasser 5MB.";
                } else {
                    // Supprimer l'ancienne image si elle existe et si ce n'est pas l'image par défaut
                    $old_path = __DIR__ . "/uploads/" . $data['image'];
                    if (!empty($data['image']) && file_exists($old_path) && $data['image'] !== 'default.jpg') {
                        unlink($old_path);
                    }

                    $new_filename = uniqid('img_') . '.' . $ext;
                    $upload_dir = __DIR__ . "/uploads/";
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                        $image = $new_filename;
                    } else {
                        $erreur = "Erreur lors du téléchargement de l'image.";
                    }
                }
            } else {
                $erreur = "Format d'image non supporté (jpg, jpeg, png, gif uniquement).";
            }
        }

        // CORRECTION : Mise à jour seulement si pas d'erreur
        if (empty($erreur)) {
            try {
                // CORRECTION : Ne pas mettre à jour date_enregistr pour garder la date originale
                $update = $bdd->prepare("UPDATE publication SET contenu = ?, image = ? WHERE id = ?");
                
                if ($update->execute([$contenu, $image, $pub_id])) {
                    // CORRECTION : Message de succès et redirection
                    $_SESSION['success_message'] = "Publication modifiée avec succès!";
                    redirect("liste_pub.php");
                } else {
                    $erreur = "Erreur lors de la mise à jour de la publication.";
                }
            } catch (PDOException $e) {
                $erreur = "Erreur lors de la mise à jour: " . $e->getMessage();
            }
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
        .current-image {
            background-color: #f8f9fa;
            border: 2px dashed #dee2e6;
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
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success_message'] ?>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="contenu" class="form-label fw-bold">Contenu de la publication <span class="text-danger">*</span></label>
                            <textarea name="contenu" id="contenu" rows="6" class="form-control" 
                                      placeholder="Modifiez le contenu de votre publication..." 
                                      maxlength="3000" required><?= htmlspecialchars($data['contenu']) ?></textarea>
                            <div class="form-text text-end">
                                <span id="charCount"><?= strlen($data['contenu']) ?></span>/3000 caractères
                            </div>
                        </div>

                        <!-- Image actuelle -->
                        <?php if (!empty($data['image'])): ?>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Image actuelle</label>
                                <div class="current-image rounded p-3 text-center">
                                    <img src="uploads/<?= htmlspecialchars($data['image']) ?>" 
                                         class="img-fluid rounded" 
                                         alt="Image actuelle"
                                         style="max-height: 200px;">
                                    <div class="mt-2">
                                        <small class="text-muted">Image actuelle - sera remplacée si vous uploadez une nouvelle image</small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Nouvelle image -->
                        <div class="mb-4">
                            <label for="image" class="form-label fw-bold">
                                <?= empty($data['image']) ? 'Ajouter une image' : 'Changer l\'image' ?> (optionnel)
                            </label>
                            <input type="file" name="image" id="image" class="form-control" 
                                   accept=".jpg,.jpeg,.png,.gif">
                            <div class="form-text">
                                Formats acceptés: JPG, JPEG, PNG, GIF (max 5MB)
                            </div>
                            <div class="image-preview mt-2"></div>
                        </div>

                        <!-- Option pour supprimer l'image actuelle -->
                        <?php if (!empty($data['image'])): ?>
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="supprimer_image" id="supprimer_image" value="1">
                                <label class="form-check-label text-danger" for="supprimer_image">
                                    <i class="fas fa-trash me-1"></i>Supprimer l'image actuelle
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="liste_pub.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annuler
                            </a>
                            <button type="submit" name="valider" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informations sur la publication -->
            <div class="card mt-4 fade-in">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle me-2 text-primary"></i>Informations sur la publication</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Date de création:</small>
                            <p class="mb-2 fw-bold"><?= date('d/m/Y à H:i', strtotime($data['date_enregistr'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Dernière modification:</small>
                            <p class="mb-0 fw-bold">Maintenant</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Compteur de caractères
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('contenu');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            
            if (count > 2990) {
                charCount.classList.add('text-danger', 'fw-bold');
            } else {
                charCount.classList.remove('text-danger', 'fw-bold');
            }
        });
    }

    // Preview de la nouvelle image
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = this.files[0];
            const preview = document.querySelector('.image-preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <div class="border rounded p-2 bg-light">
                            <p class="mb-2"><small class="fw-bold">Aperçu de la nouvelle image:</small></p>
                            <img src="${e.target.result}" class="img-thumbnail" style="max-height: 150px;">
                            <div class="mt-2">
                                <small class="text-muted">${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</small>
                            </div>
                        </div>
                    `;
                }
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });
    }

    // Gestion de la suppression d'image
    const supprimerCheckbox = document.getElementById('supprimer_image');
    if (supprimerCheckbox) {
        supprimerCheckbox.addEventListener('change', function() {
            const imageInput = document.getElementById('image');
            if (this.checked) {
                imageInput.disabled = true;
                imageInput.value = '';
                document.querySelector('.image-preview').innerHTML = '';
            } else {
                imageInput.disabled = false;
            }
        });
    }
});
</script>
</body>
</html>