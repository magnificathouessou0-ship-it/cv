<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['id'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['id'];

$stmt = $bdd->prepare("SELECT id, message, date_notif, lu FROM notifications WHERE id_users = ? ORDER BY date_notif DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($notifications);
?>