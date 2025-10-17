<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['id']) || !isset($_GET['id'])) {
    redirect("liste_amis.php");
}

$user_id = $_SESSION['id'];
$demande_id = intval($_GET['id']);

// Refuser la demande
$update = $bdd->prepare("UPDATE demandes_amis SET statut='refuser' WHERE id=? AND receiver_id=?");
$update->execute([$demande_id, $user_id]);

redirect("liste_amis.php?success=ami_refuse");
?>