<?php
session_start();
require_once('functions.php');
require_once('config.php');
require_once('functions.php');
requireLogin();

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contenu = trim($_POST["contenu"] ?? '');
    $id_user = $_SESSION['id'];

    // Gestion de l'image
    $image = "";
    if (!empty($_FILES['image']['name'])) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_ext)) {
            $image = uniqid('img_'). '.'. $file_ext;
            $upload_dir = __DIR__. "/uploads/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $upload_path = $upload_dir. $image;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $erreur = "Erreur lors du téléchargement de l'image.";
            }
        } else {
            $erreur = "Format d'image non supporté. Formats autorisés: jpg, jpeg, png, gif.";
        }
    }

    // Vérification contenu ou image
    if (empty($contenu) && empty($image)) {
        $erreur = "Veuillez saisir un texte ou ajouter une image.";
    }

    // Insertion dans la BDD
    if (empty($erreur)) {
        $stmt = $bdd->prepare('INSERT INTO publication (contenu, image, id_users) VALUES (?, ?, ?)');
        if ($stmt->execute([$contenu, $image, $id_user])) {
            redirect("liste_pub.php?success=publication_created");
        } else {
            $erreur = "Erreur lors de l'enregistrement.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Publication | TalkSpace</title>
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
                <li class="nav-item"><a class="nav-link active" href="publication_form.php"><i class="fas fa-plus me-1"></i>Nouvelle Publication</a></li>
                <li class="nav-item"><a class="nav-link" href="liste_pub.php"><i class="fas fa-list me-1"></i>Mes Publications</a></li>
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
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card slide-up">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Créer une nouvelle publication</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($erreur)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="publicationForm">
                        <div class="mb-4">
                            <label for="contenu" class="form-label">Que souhaitez-vous partager ?</label>
                            <textarea name="contenu" id="contenu" class="form-control" rows="6" 
                                      placeholder="Partagez vos pensées, idées, ou expériences..." 
                                      maxlength="3000" required></textarea>
                            <div class="form-text text-end">
                                <span id="charCount">0</span>/3000 caractères
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="image" class="form-label">Ajouter une image (optionnel)</label>
                            <input type="file" name="image" id="image" class="form-control" 
                                   accept=".jpg,.jpeg,.png,.gif">
                            <div class="form-text">
                                Formats acceptés: JPG, JPEG, PNG, GIF (max 5MB)
                            </div>
                            <div class="image-preview mt-2"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="dash1.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-paper-plane me-1"></i>Publier
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
// Compteur de caractères
document.getElementById('contenu').addEventListener('input', function() {
    const charCount = this.value.length;
    document.getElementById('charCount').textContent = charCount;
    
    if (charCount > 2990) {
        document.getElementById('charCount').classList.add('text-danger');
    } else {
        document.getElementById('charCount').classList.remove('text-danger');
    }
});

// Preview d'image
document.getElementById('image').addEventListener('change', function(e) {
    const file = this.files[0];
    const preview = document.querySelector('.image-preview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="border rounded p-2">
                    <img src="${e.target.result}" class="img-thumbnail" style="max-height: 200px;">
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
</script>
</body>
</html>