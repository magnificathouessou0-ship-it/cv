<?php
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function requireLogin() {
    if (!isset($_SESSION['id'])) {
        redirect('connexion.php');
    }
}

function isLoggedIn() {
    return isset($_SESSION['id']);
}

function getUserInfo($bdd, $userId) {
    $req = $bdd->prepare('SELECT * FROM users WHERE id = ?');
    $req->execute([$userId]);
    return $req->fetch(PDO::FETCH_ASSOC);
}
?>