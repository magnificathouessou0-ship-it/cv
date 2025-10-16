<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

// Vérifier que l'utilisateur est connecté et qu'une publication/commentaire est ciblé
if (!isset($_SESSION['id']) || !isset($_POST['type']) || !isset($_POST['id'])) {
    redirect("dash1.php");
}

$id_user = $_SESSION['id'];
$type = $_POST['type'];
$id = intval($_POST['id']);

if ($type === 'publication') {
    // Vérifier si la publication existe
    $checkPub = $bdd->prepare("SELECT id_users FROM publication WHERE id = ?");
    $checkPub->execute([$id]);
    $id_auteur = $checkPub->fetchColumn();

    if (!$id_auteur) {
        redirect("dash1.php");
    }

    // Vérifier si l'utilisateur a déjà signalé cette publication
    $check = $bdd->prepare("SELECT 1 FROM signalement_pub WHERE id_pub = ? AND id_user = ?");
    $check->execute([$id, $id_user]);
    
    if ($check->fetch()) {
        redirect("dash1.php?error=already_signaled");
    }

    // Enregistrer le signalement
    $insert = $bdd->prepare("INSERT INTO signalement_pub (id_pub, id_user) VALUES (?, ?)");
    $insert->execute([$id, $id_user]);

    // Compter les signalements
    $count = $bdd->prepare("SELECT COUNT(*) FROM signalement_pub WHERE id_pub = ?");
    $count->execute([$id]);
    $total = $count->fetchColumn();

    // Notification à l'auteur si ce n'est pas le signaleur
    if ($id_auteur != $id_user) {
        $pseudo = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
        $message = "$pseudo a signalé votre publication.";

        $notif = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
        $notif->execute([$id_auteur, $message]);
    }

    // Si 5 signalements ou plus, suppression
    if ($total >= 5) {
        // Supprimer la publication
        $deletePub = $bdd->prepare("DELETE FROM publication WHERE id = ?");
        $deletePub->execute([$id]);

        // Notification à l'auteur
        $messageSuppr = "Votre publication a été supprimée suite à plusieurs signalements.";
        $notifSuppr = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
        $notifSuppr->execute([$id_auteur, $messageSuppr]);
    }

} elseif ($type === 'commentaire') {
    // Logique similaire pour les commentaires
    $checkCom = $bdd->prepare("SELECT id_users FROM commentaires WHERE id = ?");
    $checkCom->execute([$id]);
    $id_auteur = $checkCom->fetchColumn();

    if (!$id_auteur) {
        redirect("dash1.php");
    }

    $check = $bdd->prepare("SELECT 1 FROM signalement_comment WHERE id_comment = ? AND id_user = ?");
    $check->execute([$id, $id_user]);
    
    if ($check->fetch()) {
        redirect("dash1.php?error=already_signaled");
    }

    $insert = $bdd->prepare("INSERT INTO signalement_comment (id_comment, id_user) VALUES (?, ?)");
    $insert->execute([$id, $id_user]);

    $count = $bdd->prepare("SELECT COUNT(*) FROM signalement_comment WHERE id_comment = ?");
    $count->execute([$id]);
    $total = $count->fetchColumn();

    if ($id_auteur != $id_user) {
        $pseudo = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
        $message = "$pseudo a signalé votre commentaire.";

        $notif = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
        $notif->execute([$id_auteur, $message]);
    }

    if ($total >= 5) {
        $deleteCom = $bdd->prepare("DELETE FROM commentaires WHERE id = ?");
        $deleteCom->execute([$id]);

        $messageSuppr = "Votre commentaire a été supprimé suite à plusieurs signalements.";
        $notifSuppr = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
        $notifSuppr->execute([$id_auteur, $messageSuppr]);
    }
}

redirect("dash1.php?success=signalement_effectue");
?>