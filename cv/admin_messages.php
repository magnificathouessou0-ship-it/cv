<?php
session_start();
require_once('config.php');

// Vérifier si l'utilisateur est admin (ajoute un champ role dans users ou remplace par ton système)
if (!isset($_SESSION['id']) || $_SESSION['email'] !== "admin@monsite.com") {
    die("Accès refusé : vous n'êtes pas administrateur");
}

// Suppression d’un message
if (isset($_GET['delete'])) {
    $id_message = intval($_GET['delete']);
    $delete = $bdd->prepare("DELETE FROM messages_contact WHERE id = ?");
    $delete->execute([$id_message]);
    header("Location: admin_messages.php");
    exit();
}

// Récupération des messages
$stmt = $bdd->query("SELECT m.*, u.nom AS user_nom FROM messages_contact m JOIN users u ON m.id_user = u.id ORDER BY m.date_envoi DESC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages Contact - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1da16eff);
            font-family: Arial, sans-serif;
        }
        .table-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        h3 {
            color: #0072ff;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .btn-delete {
            background-color: #dc3545;
            color: #fff;
        }
        .btn-delete:hover {
            background-color: #a71d2a;
        }
    </style>
</head>
<body class="bg-light">

<div class="table-container">
    <h3 class="text-center">Messages de contact reçus</h3>

    <?php if (count($messages) > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Sujet</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $msg): ?>
                <tr>
                    <td><?= htmlspecialchars($msg['nom']) ?></td>
                    <td><?= htmlspecialchars($msg['email']) ?></td>
                    <td><?= htmlspecialchars($msg['sujet']) ?></td>
                    <td><?= nl2br(htmlspecialchars($msg['message'])) ?></td>
                    <td><?= $msg['date_envoi'] ?></td>
                    <td>
                        <a href="admin_messages.php?delete=<?= $msg['id'] ?>" 
                           class="btn btn-delete btn-sm"
                           onclick="return confirm<?('Supprimer ce message ?');"?>
                           Supprimer
                       </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center text-muted">Aucun message reçu.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>