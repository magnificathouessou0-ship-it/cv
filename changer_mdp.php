<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$erreur = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ancien = $_POST['ancien'] ?? '';
    $nouveau = $_POST['nouveau'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if (empty($ancien) || empty($nouveau) || empty($confirmation)) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif ($nouveau !== $confirmation) {
        $erreur = "Le nouveau mot de passe et la confirmation ne correspondent pas.";
    } else {
        $id_user = $_SESSION['id'];

        // Vérifier mot de passe actuel
        $stmt = $bdd->prepare("SELECT mdp FROM users WHERE id = ?");
        $stmt->execute([$id_user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $erreur = "Utilisateur introuvable.";
        } else {
            $hash_enregistre = $row['mdp'];

            // Si ancien hash SHA1
            $valide = (strlen($hash_enregistre) === 40 && sha1($ancien) === $hash_enregistre)
                || password_verify($ancien, $hash_enregistre);

            if (!$valide) {
                $erreur = "Ancien mot de passe incorrect.";
            } else {
                // Hacher en bcrypt
                $nouveau_hash = password_hash($nouveau, PASSWORD_DEFAULT);

                $update = $bdd->prepare("UPDATE users SET mdp = ? WHERE id = ?");
                $update->execute([$nouveau_hash, $id_user]);

                $success = "Mot de passe changé avec succès.";
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
    <title>Changer le mot de passe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:500px;">
    <div class="card p-4 shadow-sm">
        <h3 class="mb-3 text-center">Changer mon mot de passe</h3>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($erreur): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="ancien" class="form-label">Ancien mot de passe</label>
                <input type="password" name="ancien" id="ancien" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="nouveau" class="form-label">Nouveau mot de passe</label>
                <input type="password" name="nouveau" id="nouveau" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="confirmation" class="form-label">Confirmer le mot de passe</label>
                <input type="password" name="confirmation" id="confirmation" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Changer</button>
        </form>
    </div>
</div>
</body>
</html>