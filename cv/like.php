<?php
session_start();
require_once('config.php');

// Vérification des paramètres obligatoires
if (!isset($_SESSION['id']) ||!isset($_GET['id']) ||!isset($_GET['type'])) {
    header("Location: dash1.php");
    exit();
}

$id_user = $_SESSION['id'];
$id_pub = intval($_GET['id']);
$type = $_GET['type'];

// Vérifier que le type est valide
if (!in_array($type, ['like', 'dislike'])) {
    header("Location: dash1.php?error=invalid_type");
    exit();
}

// Vérifier que l'identifiant de publication est valide
if ($id_pub <= 0) {
    header("Location: dash1.php?error=invalid_id");
    exit();
}

// Vérifier si la publication existe
try {
    $checkPub = $bdd->prepare("SELECT id_users FROM publication WHERE id =?");
    $checkPub->execute([$id_pub]);
    $pub = $checkPub->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header("Location: dash1.php?error=sql_error");
    exit();
}

if (!$pub) {
    header("Location: dash1.php?error=pub_not_found");
    exit();
}

$id_auteur = $pub['id_users'];

try {
    // Vérifier si l'utilisateur a déjà réagi
    $check = $bdd->prepare("SELECT type FROM reactions WHERE id_publication =? AND id_users =?");
    $check->execute([$id_pub, $id_user]);
    $reaction_exist = $check->fetch(PDO::FETCH_ASSOC);

    if ($reaction_exist) {
        if ($reaction_exist['type'] === $type) {
            $delete = $bdd->prepare("DELETE FROM reactions WHERE id_publication =? AND id_users =?");
            $delete->execute([$id_pub, $id_user]);
} else {
            $update = $bdd->prepare("UPDATE reactions SET type =?, create_at = NOW() WHERE id_publication =? AND id_users =?");
            $update->execute([$type, $id_pub, $id_user]);
}
} else {
        $insert = $bdd->prepare("INSERT INTO reactions (id_publication, id_users, type, create_at) VALUES (?,?,?, NOW())");
        $insert->execute([$id_pub, $id_user, $type]);
}

    // Créer une notification si ce n’est pas l’auteur
    if ($id_user!== $id_auteur) {
        $message = ($type === 'like')
? "Votre publication a été likée."
: "Votre publication a été dislikée.";

        $notif = $bdd->prepare("INSERT INTO notifications (id_users, message) VALUES (?,?)");
        $notif->execute([$id_auteur, htmlspecialchars($message)]);
}

} catch (PDOException $e) {
    header("Location: dash1.php?error=db_error");
    exit();
}

header("Location: dash1.php");
exit();
?>
