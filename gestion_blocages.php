<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Débloquer un utilisateur
if (isset($_GET['unblock'])) {
    $id_bloque = intval($_GET['unblock']);
    $del = $bdd->prepare("DELETE FROM blocages WHERE id_user=? AND id_bloque=?");
    $del->execute([$user_id, $id_bloque]);
    header("Location: gestion_blocages.php");
    exit();
}

// Récupérer la liste des utilisateurs bloqués
$query = $bdd->prepare("
    SELECT b.id_bloque, u.nom, u.email, b.date_blocage
    FROM blocages b
    JOIN users u ON u.id = b.id_bloque
    WHERE b.id_user = ?
    ORDER BY b.date_blocage DESC
");
$query->execute([$user_id]);
$bloques = $query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des blocages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h3>Utilisateurs bloqués</h3>

    <?php if (count($bloques) > 0): ?>
        <ul class="list-group">
            <?php foreach ($bloques as $b): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <?= htmlspecialchars($b['nom']) ?> (<?= htmlspecialchars($b['email']) ?>)<br>
                        <small class="text-muted">Bloqué depuis le <?= $b['date_blocage'] ?></small>
                    </span>
                    <a href="?unblock=<?= $b['id_bloque'] ?>" class="btn btn-warning btn-sm">Débloquer</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucun utilisateur bloqué.</p>
    <?php endif; ?>

    <a href="explorer.php" class="btn btn-secondary btn-sm mt-3">Retour</a>
</div>
</body>
</html>