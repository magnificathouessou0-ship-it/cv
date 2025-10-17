<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['count' => 0]);
    exit();
}

$user_id = $_SESSION['id'];

// Compter les notifications non lues
$stmt = $bdd->prepare("SELECT COUNT(*) as count FROM notifications WHERE id_users = ? AND lu = 0");
$stmt->execute([$user_id]);
$count = $stmt->fetch()['count'];

// Récupérer la dernière notification
$latest_stmt = $bdd->prepare("SELECT message FROM notifications WHERE id_users = ? ORDER BY date_notif DESC LIMIT 1");
$latest_stmt->execute([$user_id]);
$latest = $latest_stmt->fetch();

header('Content-Type: application/json');
echo json_encode([
    'count' => $count,
    'latest' => $latest
]);
?>