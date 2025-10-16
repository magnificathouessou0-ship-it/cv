<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['id']) || !isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: explorer.php");
    exit();
}

$user_id = $_SESSION['id'];
$sender_id = intval($_GET['id']);
$action = $_GET['action'];

if ($action === 'accepter') {
    $update = $bdd->prepare("UPDATE demandes_amis SET statut='accepter' 
                             WHERE sender_id=? AND receiver_id=? AND statut='en_attente'");
    $update->execute([$sender_id, $user_id]);
} elseif ($action === 'refuser') {
    $update = $bdd->prepare("UPDATE demandes_amis SET statut='refuser' 
                             WHERE sender_id=? AND receiver_id=? AND statut='en_attente'");
    $update->execute([$sender_id, $user_id]);
}

header("Location: explorer.php");
exit();