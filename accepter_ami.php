<?php
session_start();
require_once('config.php');
require_once('functions.php'); // Ajouter cette ligne


if (!isset($_SESSION['id']) || !isset($_GET['id'])) {
    redirect("liste_amis.php");
}

$user_id = $_SESSION['id'];
$demande_id = intval($_GET['id']);

// Accepter la demande
$update = $bdd->prepare("UPDATE demandes_amis SET statut='accepter' WHERE id=? AND receiver_id=?");
$update->execute([$demande_id, $user_id]);

// Récupérer les infos de l'expéditeur pour la notification
$info_sender = $bdd->prepare("SELECT sender_id FROM demandes_amis WHERE id = ?");
$info_sender->execute([$demande_id]);
$sender_data = $info_sender->fetch();

if ($sender_data) {
    // Notification à l'expéditeur
    $pseudo = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
    $message = "$pseudo a accepté votre demande d'ami.";
    
    $notif = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
    $notif->execute([$sender_data['sender_id'], $message]);
}

redirect("liste_amis.php?success=ami_accepte");
?>