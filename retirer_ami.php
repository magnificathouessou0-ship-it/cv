<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

if (!isset($_GET['id'])) {
    redirect("liste_amis.php");
}

$user_id = $_SESSION['id'];
$ami_id = intval($_GET['id']);

// Supprimer l'amitié
$delete = $bdd->prepare("DELETE FROM demandes_amis 
                        WHERE statut='accepter' 
                        AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?))");
$delete->execute([$user_id, $ami_id, $ami_id, $user_id]);

redirect("liste_amis.php?success=ami_retire");
?>