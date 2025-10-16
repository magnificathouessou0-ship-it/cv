<?php
session_start();
require_once('config.php');
require_once('functions.php'); // Ajouter cette ligne

if (!isset($_SESSION['id']) || !isset($_GET['id'])) {
    redirect("explorer.php");
}

$sender_id = $_SESSION['id'];
$receiver_id = intval($_GET['id']);

// Vérifier si invitation ou amitié déjà existante
$check = $bdd->prepare("SELECT * FROM demandes_amis WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)");
$check->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);

if ($check->rowCount() == 0) {
    $insert = $bdd->prepare("INSERT INTO demandes_amis (sender_id, receiver_id, statut) VALUES (?, ?, 'en_attente')");
    $insert->execute([$sender_id, $receiver_id]);
    
    // Notification au receveur
    $pseudo = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
    $message = "$pseudo vous a envoyé une demande d'ami.";
    
    $notif = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
    $notif->execute([$receiver_id, $message]);
}

redirect("explorer.php?tab=explorer&success=demande_envoyee");
?>