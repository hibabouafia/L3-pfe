<?php
session_start();

// حماية: المديرة فقط
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Tableau de bord</title>
</head>
<body>
    <h1>Bienvenue, Directrice</h1>
    <p>Vous êtes connectée avec l'identifiant : <?= (int)$_SESSION['user_id'] ?></p>
    <a href="../logout.php">Se déconnecter</a>
</body>
</html>
