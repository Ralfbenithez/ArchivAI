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
    $stmt = $conn->prepare("SELECT * FROM documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$documentId, $_SESSION['user']['id']]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        throw new Exception("Document non trouvé.");
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voir Document</title>
    <link rel="stylesheet" href="/ArchivAI/assets/css/dashboard.css">
</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="logo">
      <img src="/ArchivAI/assets/img/logo.svg" alt="Archiv'AI Logo">
      <span>Archiv'AI</span>
    </div>
    <div class="user-actions">
      <span class="welcome">Bonjour, <?= htmlspecialchars($_SESSION['user']['prenoms']) ?></span>
      <a href="logout.php" class="logout-button">Déconnexion</a>
    </div>
  </header>

  <!-- Sidebar -->
  <nav class="sidebar">
    <ul class="sidebar-menu">
      <li class="menu-item"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
      <li class="menu-item"><a href="search.php"><i class="fas fa-search"></i><span>Recherche</span></a></li>
      <li class="menu-item"><a href="documents.php"><i class="fas fa-folder"></i><span>Documents</span></a></li>
      <li class="menu-item"><a href="account.php"><i class="fas fa-user"></i><span>Compte</span></a></li>
      <li class="menu-item"><a href="settings.php"><i class="fas fa-cog"></i><span>Réglages</span></a></li>
    </ul>
    <button class="help-button"><i class="fas fa-question-circle"></i><span>Aide</span></button>
  </nav>

  <!-- Main Content -->
  <main class="main-content">
    <div class="content-wrapper">
      <h1>Détails du Document</h1>
      <?php if (isset($error)): ?>
        <div class="error-message"><?= $error ?></div>
      <?php else: ?>
        <div class="document-details">
          <div class="document-preview">
            <img src="<?= htmlspecialchars($document['image_url']) ?>" alt="Aperçu du document">
          </div>
          <h2><?= htmlspecialchars($document['title']) ?></h2>
          <p><strong>Type:</strong> <?= htmlspecialchars($document['document_type']) ?></p>
          <p><strong>Date:</strong> <?= date('d/m/Y', strtotime($document['date_document'])) ?></p>
          <p><strong>Téléchargé le:</strong> <?= date('d/m/Y', strtotime($document['created_at'])) ?></p>
          <p><strong>Mots-clés:</strong> <?= htmlspecialchars($document['keywords']) ?></p>
          <div class="document-actions">
            <a href="download_document.php?id=<?= $document['id'] ?>" class="download-btn">Télécharger</a>
            <button class="delete-btn" onclick="deleteDocument(<?= $document['id'] ?>)">Supprimer</button>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    function deleteDocument(documentId) {
        if (confirm('Voulez-vous vraiment supprimer ce document ?')) {
            fetch('/delete_document.php', {
                method: 'POST',
                body: JSON.stringify({ id: documentId })
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'search.php';
                } else {
                    alert('Erreur lors de la suppression du document.');
                }
            });
        }
    }
  </script>
</body>
</html>
