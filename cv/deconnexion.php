<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card text-center slide-up">
                <div class="card-header bg-warning">
                    <h4 class="mb-0 text-dark"><i class="fas fa-sign-out-alt me-2"></i>Déconnexion</h4>
                </div>
                <div class="card-body py-5">
                    <i class="fas fa-question-circle fa-4x text-warning mb-4"></i>
                    <h5 class="mb-3">Voulez-vous vraiment vous déconnecter ?</h5>
                    <p class="text-muted">Vous devrez vous reconnecter pour accéder à votre compte.</p>
                    
                    <form action="logount.php" method="post" class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Oui, se déconnecter
                        </button>
                        <a href="dash1.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Annuler
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>