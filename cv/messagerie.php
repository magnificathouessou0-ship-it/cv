<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

if (!isset($_SESSION['id']) || !isset($_GET['id'])) {
    redirect("liste_amis.php");
}

$user_id = $_SESSION['id'];
$ami_id = intval($_GET['id']);

// Vérifier si amis
$check = $bdd->prepare("SELECT 1 FROM demandes_amis WHERE statut='accepter' AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?))");
$check->execute([$user_id, $ami_id, $ami_id, $user_id]);
if (!$check->fetch()) {
    echo "<div class='alert alert-danger'>Vous n'êtes pas amis avec cet utilisateur.</div>";
    exit();
}

// Récupérer le nom de l'ami
$ami_stmt = $bdd->prepare("SELECT nom, prenom FROM users WHERE id = ?");
$ami_stmt->execute([$ami_id]);
$ami_data = $ami_stmt->fetch(PDO::FETCH_ASSOC);

if (!$ami_data) {
    echo "<div class='alert alert-danger'>Utilisateur introuvable.</div>";
    exit();
}

$ami_nom = $ami_data['prenom'] . " " . $ami_data['nom'];

// Envoi message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $msg = sanitize($_POST['message']);
    $insert = $bdd->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $insert->execute([$user_id, $ami_id, $msg]);
    
    // Redirection pour éviter le re-soumission
    redirect("messagerie.php?id=" . $ami_id);
}

// Récupérer messages
$stmt = $bdd->prepare("SELECT m.id, m.message, m.sender_id, m.receiver_id, m.date_envoie, u.nom AS sender_nom, u.prenom AS sender_prenom 
                       FROM messages m 
                       JOIN users u ON m.sender_id = u.id 
                       WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?) 
                       ORDER BY m.date_envoie ASC");
$stmt->execute([$user_id, $ami_id, $ami_id, $user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marquer les messages comme lus
$update_lus = $bdd->prepare("UPDATE messages SET lu = 1 WHERE receiver_id = ? AND sender_id = ? AND lu = 0");
$update_lus->execute([$user_id, $ami_id]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--bg-white);
            border-radius: 15px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            height: 80vh;
        }
        .chat-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            background: #f8f9fa;
        }
        .chat-input {
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            background: var(--bg-white);
            border-radius: 0 0 15px 15px;
        }
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        .typing-indicator {
            padding: 10px;
            font-style: italic;
            color: var(--text-light);
            text-align: center;
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
                <li class="nav-item"><a class="nav-link" href="liste_amis.php"><i class="fas fa-user-friends me-1"></i>Amis</a></li>
                <li class="nav-item"><a class="nav-link active" href="messagerie.php"><i class="fas fa-comments me-1"></i>Messagerie</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="profil.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="deconnexion.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="chat-container slide-up">
        <div class="chat-header">
            <div class="d-flex align-items-center">
                <a href="liste_amis.php" class="btn btn-sm btn-light me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h6 class="mb-0"><?= htmlspecialchars($ami_nom) ?></h6>
                    <small class="opacity-75">
                        <?php 
                        $last_seen = $bdd->prepare("SELECT MAX(date_envoie) FROM messages WHERE sender_id = ?");
                        $last_seen->execute([$ami_id]);
                        $last_activity = $last_seen->fetchColumn();
                        echo $last_activity ? 'En ligne' : 'Hors ligne';
                        ?>
                    </small>
                </div>
            </div>
            <div class="chat-actions">
                <button class="btn btn-sm btn-light" title="Informations">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <?php if (empty($messages)): ?>
                <div class="text-center text-muted my-5">
                    <i class="fas fa-comments fa-3x mb-3"></i>
                    <p>Aucun message échangé pour le moment.<br>Envoyez le premier message !</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $m): ?>
                    <div class="d-flex mb-3 <?= $m['sender_id'] == $user_id ? 'justify-content-end' : 'justify-content-start' ?>">
                        <div class="msg <?= $m['sender_id'] == $user_id ? 'msg-right' : 'msg-left' ?>">
                            <div class="message-content">
                                <?= nl2br(htmlspecialchars($m['message'])) ?>
                            </div>
                            <div class="message-time text-end">
                                <?= date('H:i', strtotime($m['date_envoie'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form method="POST" class="chat-input">
            <div class="input-group">
                <input type="text" name="message" class="form-control" 
                       placeholder="Tapez votre message..." required
                       maxlength="1000">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="form-text text-end mt-1">
                Appuyez sur Entrée pour envoyer
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
class Messagerie {
    constructor() {
        this.chatMessages = document.getElementById('chatMessages');
        this.autoScroll = true;
        this.init();
    }

    init() {
        this.scrollToBottom();
        this.setupAutoRefresh();
        this.setupEnterToSend();
    }

    scrollToBottom() {
        if (this.autoScroll) {
            this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
        }
    }

    setupAutoRefresh() {
        // Actualiser les messages toutes les 5 secondes
        setInterval(() => {
            this.refreshMessages();
        }, 5000);
    }

    setupEnterToSend() {
        const messageInput = document.querySelector('input[name="message"]');
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.querySelector('form').submit();
            }
        });
    }

    async refreshMessages() {
        try {
            const response = await fetch(`get_messages.php?ami_id=<?= $ami_id ?>`);
            if (response.ok) {
                const newMessages = await response.text();
                // Implémentez la logique de mise à jour des messages
            }
        } catch (error) {
            console.error('Erreur lors du rafraîchissement:', error);
        }
    }
}

// Initialisation de la messagerie
document.addEventListener('DOMContentLoaded', function() {
    new Messagerie();
});

// Auto-scroll quand l'utilisateur est en bas
document.getElementById('chatMessages').addEventListener('scroll', function() {
    const isAtBottom = this.scrollHeight - this.clientHeight <= this.scrollTop + 50;
    this.autoScroll = isAtBottom;
});
</script>
</body>
</html>