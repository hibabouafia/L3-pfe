<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)($_POST['identifiant'] ?? 0);
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT id, password, role FROM UTILISATEUR WHERE id = ? AND is_archived = 0"
    );
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: user/dashboard.php");
        }
        exit;
    } else {
        $error = "Identifiant ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LOGIN PAGE</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <header class="top-navbar">
        <div class="nav-brand">
            <img src="images/logo.webp" alt="Logo de l'entreprise" class="brand-logo">
            <span class="brand-short">SNTP</span>
            <div class="brand-divider"></div>
            <div class="brand-full">
                Société Nationale<br>
                des Travaux Publics
            </div>
        </div>
        <nav class="nav-links">
            <a href="#" class="nav-link"><i class="fa-solid fa-house"></i> Accueil</a>
            <a href="#" class="nav-link"><i class="fa-solid fa-info-circle"></i> A propos</a>
        </nav>
    </header>

    <main class="page">

        <section class="company-info">
            <div class="burgundy-line"></div>
            <p class="welcome">Bienvenue sur l’espace</p>
            <h1 class="sntp-title">SNTP</h1>
            <p class="description">
                Votre partenaire dans la réalisation<br>
                des grands projets d’infrastructure
            </p>
        </section>

        <section class="login-container">
            <div class="profile-icon"><i class="fa-solid fa-user"></i></div>
            <h2>Se Connecter</h2>
            <p class="subtitle">Accéder à votre compte</p>

            <?php if ($error): ?>
                <p class="error-msg"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <label for="identifiant">Identifiant</label>
                <div class="input-box">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="identifiant" name="identifiant"
                           placeholder="Entrez votre identifiant" required>
                </div>

                <label for="password">Mot de passe</label>
                <div class="input-box">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password"
                           placeholder="Entrez votre mot de passe" required>
                    <i class="fa-solid fa-eye" id="togglePassword"></i>
                </div>

                <button type="submit">
                    <i class="fa-solid fa-door-open"></i> Se Connecter
                </button>
            </form>
        </section>

    </main>

    <footer class="footer">
        <p>&copy; 2026 Société Nationale des Travaux Publics.</p>
    </footer>

    <script>
        const toggle = document.getElementById('togglePassword');
        const pwd = document.getElementById('password');
        toggle.addEventListener('click', () => {
            const show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            toggle.classList.toggle('fa-eye', !show);
            toggle.classList.toggle('fa-eye-slash', show);
        });
    </script>
</body>
</html>
