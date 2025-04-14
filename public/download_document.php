<?php
require_once __DIR__ . '/../includes/config.php';
session_start();

// Vérifier l'authentification
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Récupérer l'ID du document
$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupérer les détails du document
try {
    $stmt = $conn->prepare("SELECT file_path, title FROM documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$documentId, $_SESSION['user']['id']]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        throw new Exception("Document non trouvé.");
    }

    // Télécharger le fichier
    $filePath = $document['file_path'];
    $fileName = basename($filePath);

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
