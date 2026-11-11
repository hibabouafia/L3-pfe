<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function reponse(bool $ok, string $msg, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg]);
    exit;
}

// 1) يجب أن يكون مسجلاً الدخول
if (empty($_SESSION['user_id'])) {
    reponse(false, "Non connecté.", 401);
}

// 2) فحص صلاحية الإضافة من قاعدة البيانات
$stmt = $pdo->prepare("SELECT role, can_add FROM UTILISATEUR WHERE id = ? AND is_archived = 0");
$stmt->execute([$_SESSION['user_id']]);
$u = $stmt->fetch();

if (!$u || ($u['role'] !== 'admin' && !$u['can_add'])) {
    reponse(false, "Vous n'avez pas le droit d'ajouter des fichiers.", 403);
}

// 3) التحقق من وجود ملف
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['fichier'])) {
    reponse(false, "Aucun fichier reçu.", 400);
}

$f = $_FILES['fichier'];
if ($f['error'] !== UPLOAD_ERR_OK) {
    reponse(false, "Erreur lors du téléversement.", 400);
}

// 4) الحجم (حتى 10 ميغابايت)
if ($f['size'] > 10 * 1024 * 1024) {
    reponse(false, "Fichier trop volumineux (max 10 Mo).", 400);
}

// 5) الامتداد المسموح
$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
$autorises = ['xlsx', 'xls', 'csv', 'pdf'];
if (!in_array($ext, $autorises, true)) {
    reponse(false, "Type non autorisé (xlsx, xls, csv, pdf).", 400);
}

// 6) الحفظ باسم عشوائي
$nomStocke = bin2hex(random_bytes(16)) . '.' . $ext;
$dest = __DIR__ . '/../uploads/' . $nomStocke;

if (!move_uploaded_file($f['tmp_name'], $dest)) {
    reponse(false, "Impossible d'enregistrer le fichier.", 500);
}

// 7) التسجيل في قاعدة البيانات
$type = trim($_POST['type_document'] ?? '');
$stmt = $pdo->prepare(
    "INSERT INTO FICHIER (user_id, nom_original, nom_stocke, type_document, extension, taille)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$_SESSION['user_id'], $f['name'], $nomStocke, $type, $ext, $f['size']]);

reponse(true, "Fichier ajouté avec succès.");
