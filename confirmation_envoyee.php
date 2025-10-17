<?php
session_start();
if (!isset($_SESSION['email_inscription'])) {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email_inscription'];
$token = $_SESSION['token_confirmation'] ?? '';

// CORRECTION : Utiliser l'URL dynamique
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . "://" . $host . dirname($_SERVER['PHP_SELF']);
$lien_confirmation = $base_url . "/confirmer_email.php?token=" . $token;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation envoyée | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: white; /* CHANGÉ EN BLANC */
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); /* Ombre plus légère */
            border: 1px solid #e0e0e0; /* Bordure légère */
        }
        .card-header {
            border-radius: 15px 15px 0 0 !important;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card text-center">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Inscription réussie !
                    </h4>
                </div>
                <div class="card-body py-5">
                    <i class="fas fa-envelope-open-text fa-5x text-primary mb-4"></i>
                    <h5 class="card-title">Vérifiez votre email</h5>
                    <p class="card-text">
                        Un lien de confirmation a été généré pour l'adresse : <strong><?= htmlspecialchars($email) ?></strong>
                    </p>
                    
                    <div class="alert alert-info mt-4">
                        <h6><i class="fas fa-info-circle me-2"></i>Lien de confirmation :</h6>
                        <p class="mb-2">Cliquez sur le bouton ci-dessous pour confirmer votre compte :</p>
                        <a href="<?= $lien_confirmation ?>" class="btn btn-primary mt-2">
                            <i class="fas fa-link me-2"></i>Confirmer mon compte
                        </a>
                    </div>

                    <!-- AFFICHER LE LIEN COMPLET POUR FACILITER LES TESTS -->
                    <div class="alert alert-light border mt-3">
                        <small>
                            <strong>Lien complet :</strong><br>
                            <code class="text-dark"><?= $lien_confirmation ?></code>
                        </small>
                    </div>
                    
                    <div class="mt-4">
                        <a href="login.php" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Aller à la connexion
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-home me-2"></i>Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Copier le lien dans le presse-papier
function copierLien() {
    const lien = "<?= $lien_confirmation ?>";
    navigator.clipboard.writeText(lien).then(function() {
        alert('Lien copié dans le presse-papier !');
    });
}
</script>
</body>
</html>