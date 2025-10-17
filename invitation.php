<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Récupérer invitations reçues
$stmt = $bdd->prepare("SELECT d.id AS demande_id, u.nom, u.email FROM demandes_amis d JOIN users u ON d.sender_id = u.id WHERE d.receiver_id = ? AND (d.statut = 'en_attente' OR d.statut IS NULL) ORDER BY d.date_envoi DESC");
$stmt->execute([$user_id]);
$invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Accepter
if (isset($_GET['accepter'])) {
    $id = intval($_GET['accepter']);
    $update = $bdd->prepare(" UPDATE demandes_amis  SET statut='accepter'  WHERE id=? AND receiver_id=?");
    $update->execute([$id, $user_id]);
    header("Location: invitation.php");
    exit();
}

// Refuser
if (isset($_GET['refuser'])) {
    $id = intval($_GET['refuser']);
    $update = $bdd->prepare(" UPDATE demandes_amis SET statut='refuser' WHERE id=? AND receiver_id=?");
    $update->execute([$id, $user_id]);
    header("Location: invitation.php");
    exit();
}

// Supprimer toutes
if (isset($_GET['tout_supprimer'])) {
    $del = $bdd->prepare(" DELETE FROM demandes_amis WHERE receiver_id=? AND (statut='en_attente' OR statut IS NULL)");
    $del->execute([$user_id]);
    header("Location: invitation.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitations reçues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Invitations reçues</h2>

    <?php if (count($invitations) > 0): ?>
        <a href="?tout_supprimer=1" class="btn btn-danger btn-sm mb-3">Supprimer toutes</a>
        <ul class="list-group">
            <?php foreach ($invitations as $inv): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars($inv['nom']) ?> (<?= htmlspecialchars($inv['email']) ?>)</span>
                    <div>
                        <a href="?accepter=<?= $inv['demande_id'] ?>" class="btn btn-success btn-sm">Accepter</a>
                        <a href="?refuser=<?= $inv['demande_id'] ?>" class="btn btn-secondary btn-sm">Refuser</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucune invitation reçue.</p>
    <?php endif; ?>
</div>
</body>
</html>