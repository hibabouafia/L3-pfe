<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/db.php';
$stmt = $pdo->prepare("SELECT nom, role, can_add, can_delete FROM UTILISATEUR WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$peutAjouter = ($user['role'] === 'admin' || $user['can_add']);

$stmt = $pdo->prepare("SELECT nom_original, type_document, extension, taille, date_upload
                       FROM FICHIER WHERE user_id = ? ORDER BY date_upload DESC");
$stmt->execute([$_SESSION['user_id']]);
$fichiers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace utilisateur</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 30px auto; padding: 0 15px; }
        #dropzone { border: 2px dashed #800020; border-radius: 12px; padding: 40px; text-align: center;
                    color: #800020; cursor: pointer; margin: 20px 0; }
        #dropzone.hover { background: #f6e9ec; }
        #msg { margin: 10px 0; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #800020; color: #fff; }
    </style>
</head>
<body>
    <h1>Bienvenue, <?= htmlspecialchars($user['nom']) ?></h1>
    <p>
        Droits : lecture : oui |
        ajout : <?= $peutAjouter ? 'oui' : 'non' ?> |
        suppression : <?= ($user['role'] === 'admin' || $user['can_delete']) ? 'oui' : 'non' ?>
    </p>

    <?php if ($peutAjouter): ?>
        <h3>Ajouter un fichier</h3>
        <select id="type_document">
            <option value="Rapport">Rapport</option>
            <option value="Facture">Facture</option>
            <option value="Autre">Autre</option>
        </select>

        <div id="dropzone">Glissez un fichier ici ou cliquez pour choisir<br>(xlsx, xls, csv, pdf - max 10 Mo)</div>
        <input type="file" id="fileInput" accept=".xlsx,.xls,.csv,.pdf" hidden>
        <div id="msg"></div>
    <?php endif; ?>

    <h3>Mes fichiers</h3>
    <table>
        <tr><th>Nom</th><th>Type</th><th>Taille</th><th>Date</th></tr>
        <?php foreach ($fichiers as $f): ?>
            <tr>
                <td><?= htmlspecialchars($f['nom_original']) ?></td>
                <td><?= htmlspecialchars($f['type_document'] ?? '') ?></td>
                <td><?= round($f['taille'] / 1024) ?> Ko</td>
                <td><?= htmlspecialchars($f['date_upload']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$fichiers): ?>
            <tr><td colspan="4">Aucun fichier.</td></tr>
        <?php endif; ?>
    </table>

    <p><a href="../logout.php">Se déconnecter</a></p>

<?php if ($peutAjouter): ?>
<script>
const zone = document.getElementById('dropzone');
const input = document.getElementById('fileInput');
const msg = document.getElementById('msg');

zone.addEventListener('click', () => input.click());
input.addEventListener('change', () => { if (input.files[0]) envoyer(input.files[0]); });

zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('hover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('hover'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('hover');
    if (e.dataTransfer.files[0]) envoyer(e.dataTransfer.files[0]);
});

async function envoyer(fichier) {
    const data = new FormData();
    data.append('fichier', fichier);
    data.append('type_document', document.getElementById('type_document').value);
    msg.style.color = '#333';
    msg.textContent = 'Envoi en cours...';
    try {
        const r = await fetch('upload.php', { method: 'POST', body: data });
        const texte = await r.text();
        let j;
        try {
            j = JSON.parse(texte);
        } catch (e) {
            msg.style.color = '#b00020';
            msg.textContent = 'Réponse invalide : ' + texte.substring(0, 400);
            return;
        }
        msg.style.color = j.ok ? 'green' : '#b00020';
        msg.textContent = j.message;
        if (j.ok) setTimeout(() => location.reload(), 1000);
    } catch (e) {
        msg.style.color = '#b00020';
        msg.textContent = 'Erreur réseau : ' + e.message;
    }
}
</script>
<?php endif; ?>
</body>
</html>
