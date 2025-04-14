<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../templates/header.php';
session_start();

// Récupérer le nombre total de documents
$docCount = $conn->query("SELECT COUNT(*) FROM documents")->fetchColumn();

// Taille totale des fichiers
$totalSizeBytes = $conn->query("SELECT SUM(file_size) FROM documents")->fetchColumn();
$totalSizeReadable = $totalSizeBytes ? round($totalSizeBytes / (1024 ** 2), 2) . ' MB' : '0 MB';

// Nombre de documents partagés (exemple fictif, adapter si tu as une colonne dédiée)
$sharedCount = $conn->query("SELECT COUNT(*) FROM documents WHERE document_type = 'shared'")->fetchColumn();

// Nombre de types de documents différents
$docTypeCount = $conn->query("SELECT COUNT(DISTINCT document_type) FROM documents")->fetchColumn();


// Vérifier si l'utilisateur est connecté (plusieurs méthodes de vérification)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Récupérer les informations utilisateur
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
} else {
    // Si seulement l'ID est disponible, récupérer les informations complètes
    require_once __DIR__ . '/../includes/config.php';
    $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $_SESSION['user'] = $user; // Stocker pour les prochaines requêtes
}

// Si l'utilisateur n'est toujours pas disponible, rediriger
if (!$user) {
    header("Location: login.php");
    exit;
}
?>


<body>
  <!-- Header -->
  <header class="header">
    <div class="logo">
      <!-- Logo (adapter le lien ou utiliser une image réelle) -->
      <img src="/ArchivAI/assets/img/LogoArchivAI.png" alt="Archiv'AI Logo">
      <span>Archiv'AI</span>
    </div>
    <div class="search-container">
      <form action="search.php" method="GET" id="search-form">
        <input type="text" name="query" placeholder="Rechercher...">
      </form>
    
      <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
    </div>
    <div class="user-actions">
      <span class="welcome">Hello, <?= htmlspecialchars($user['nom'] ?? $user['prenom'] ?? 'Utilisateur') ?></span>
      <a href="logout.php" class="logout-button">Déconnexion</a>
    </div>
  </header>

  <!-- Sidebar -->
  <nav class="sidebar">
    <ul class="sidebar-menu">
      <li class="menu-item active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></li>
      <li class="menu-item"><i class="fas fa-folder"></i><span>Documents</span></li>
      <li class="menu-item"><i class="fas fa-user"></i><span>Compte</span></li>
      <li class="menu-item"><i class="fas fa-cog"></i><span>Réglages</span></li>
    </ul>
    <button class="help-button"><i class="fas fa-question-circle"></i><span>Aide</span></button>
  </nav>

  <!-- Main Content -->
  <main class="main-content">
    <div class="background"></div>
    <div class="wave"></div>
    <div class="content-wrapper">
      <h1>Bienvenue sur votre Dashboard</h1>
      <p>Gérez vos documents et consultez vos statistiques.</p>
      <div class="stats">
            <div class="stat-card"><h2>Documents</h2><p><?= $docCount ?></p></div>
            <div class="stat-card"><h2>Espace utilisé</h2><p><?= $totalSizeReadable ?></p></div>
            <div class="stat-card"><h2>Types de Documents</h2><p><?= $docTypeCount ?></p></div>
            <div class="stat-card"><h2>Partagés</h2><p><?= $sharedCount ?></p></div>

      </div>
      <!-- Bouton pour lancer la capture photo -->
      <button id="open-camera-btn" class="primary-button">Prendre une photo</button>
    </div>
    <!-- Bouton d'action flottant (optionnel) -->
    <button class="camera-icon"><i class="fas fa-camera"></i></button>
  </main>

  <!-- Modal de capture photo et OCR -->
  <div id="camera-modal" class="modal">
    <div class="modal-content">
      <span id="close-modal" class="close">&times;</span>
      <h2>Capturez, recadrez et analysez votre document</h2>
      <video id="video" autoplay playsinline></video>
      <canvas id="canvas" style="display:none;"></canvas>
    <div id="crop-container" style="display:none;">
      <img id="crop-image" src="" alt="Recadrer votre photo">
    </div>
    <div id="ocr-result-container" style="display:none;">
      <h3>Texte extrait:</h3>
      <div class="ocr-result-box">
        <pre id="ocr-result-text"></pre>
      </div>
    </div>
    <div class="ocr-progress-container">
      <progress id="ocr-progress" value="0" max="100" style="display:none; width:100%"></progress>
      <p id="ocr-status"></p>
    </div>
       <div class="modal-actions">
        <button id="capture-btn">Capturer</button>
        <button id="crop-btn" style="display:none;">Recadrer</button>
        <button id="recognize-btn" style="display:none;">Extraire le texte</button>
        <button id="upload-btn" style="display:none;">Enregistrer</button>
       </div>
    </div>
</div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
  <script src="/ArchivAI/assets/js/dashboard.js"></script>
</body>
</html>