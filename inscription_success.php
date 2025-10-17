<?php
session_start();
if (!isset($_SESSION['inscription_success'])) {
    header('Location: inscription.php');
    exit();
}

$message = $_SESSION['inscription_success'];
unset($_SESSION['inscription_success']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription réussie | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card text-center">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Inscription réussie
                    </h4>
                </div>
                <div class="card-body py-5">
                    <i class="fas fa-envelope-open-text fa-5x text-primary mb-4"></i>
                    <h5 class="card-title">Vérifiez votre email</h5>
                    <p class="card-text"><?= $message ?></p>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Astuce :</strong> Vérifiez également votre dossier de spam si vous ne voyez pas l'email.
                    </div>
                    <a href="login.php" class="btn btn-primary mt-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                    </a>
                    <a href="inscription.php" class="btn btn-outline-secondary mt-3 ms-2">
                        <i class="fas fa-home me-2"></i>Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>