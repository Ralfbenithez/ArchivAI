<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../templates/header.php';
session_start();

// Vérifier l'authentification
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Paramètres de pagination
$resultsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $resultsPerPage;

// Paramètres de recherche
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$docType = isset($_GET['type']) ? $_GET['type'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Préparer la requête SQL de base
$sqlQuery = "SELECT d.id, d.title, d.document_type, d.date_document, d.image_url,
                    d.created_at, d.keywords
             FROM documents d
             WHERE d.user_id = ?";
$params = [$_SESSION['user']['id']];

// Construire les conditions de recherche
$conditions = [];

// Recherche par texte
if (!empty($query)) {
    $conditions[] = "(MATCH(d.ocr_text) AGAINST(? IN BOOLEAN MODE)
                    OR d.title LIKE ?
                    OR d.keywords LIKE ?)";
    $params[] = $query;
    $params[] = '%' . $query . '%';
    $params[] = '%' . $query . '%';
}

// Filtre par type de document
if (!empty($docType)) {
    $conditions[] = "d.document_type = ?";
    $params[] = $docType;
}

// Filtre par plage de dates
if (!empty($dateFrom)) {
    $conditions[] = "d.date_document >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $conditions[] = "d.date_document <= ?";
    $params[] = $dateTo;
}

// Ajouter les conditions à la requête
if (!empty($conditions)) {
    $sqlQuery .= " AND " . implode(" AND ", $conditions);
}

// Ajouter tri et pagination
$sqlQuery .= " ORDER BY d.created_at DESC LIMIT ? OFFSET ?";
$params[] = $resultsPerPage;
$params[] = $offset;

// Exécuter la requête
try {
    $stmt = $conn->prepare($sqlQuery);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compter le nombre total de résultats pour la pagination
    $countQuery = str_replace("SELECT d.id, d.title", "SELECT COUNT(*)", $sqlQuery);
    $countQuery = preg_replace('/ORDER BY.*$/i', '', $countQuery);
    $countQuery = preg_replace('/LIMIT.*$/i', '', $countQuery);

    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($params);
    $totalResults = $countStmt->fetchColumn();

    $totalPages = ceil($totalResults / $resultsPerPage);

} catch (PDOException $e) {
    $error = "Erreur lors de la recherche: " . $e->getMessage();
}

// Liste des types de documents pour le filtre
$types = [];
try {
    $typeStmt = $conn->prepare("SELECT DISTINCT document_type FROM documents WHERE user_id = ?");
    $typeStmt->execute([$_SESSION['user']['id']]);
    while ($row = $typeStmt->fetch(PDO::FETCH_ASSOC)) {
        $types[] = $row['document_type'];
    }
} catch (PDOException $e) {
    // Gérer l'erreur
}
?>
    <style>
      /* Ajouter des styles spécifiques à search.php si nécessaire */
      .search-filters {
        background-color: #fff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      }
      .document-actions a, .document-actions button {
        margin-right: 8px;
        text-decoration: none;
        color: #4285f4;
        font-weight: 500;
      }
    </style>
    <script>
      function deleteDocument(documentId) {
          if (confirm('Voulez-vous vraiment supprimer ce document ?')) {
              fetch('/delete_document.php', {
                  method: 'POST',
                  body: JSON.stringify({ id: documentId })
              }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      location.reload();
                  } else {
                      alert('Erreur lors de la suppression du document.');
                  }
              });
          }
      }

      document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('reset-filters').addEventListener('click', function() {
          document.getElementById('type').value = '';
          document.getElementById('date_from').value = '';
          document.getElementById('date_to').value = '';
          document.getElementById('advanced-search-form').submit();
        });
      });
    </script>
</head>
<body>
  <!-- Header -->
  <header class="header">
        <div class="logo">
            <img src="/ArchivAI/assets/img/LogoArchivAI.png" alt="Archiv'AI Logo">
            <span>Archiv'AI</span>
        </div>

        <div class="search-container">
            <form action="search.php" method="GET" id="search-form">
                 <input type="text" name="query" placeholder="Rechercher..." value="<?= htmlspecialchars($query) ?>">
            </form>
        <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
        </div>

        <div class="user-actions">
           <span class="welcome">Bonjour, <?= htmlspecialchars($_SESSION['user']['nom']) ?></span>
           <a href="logout.php" class="logout-button">Déconnexion</a>
        </div>
  </header>

  <!-- Sidebar -->
  <nav class="sidebar">
    <ul class="sidebar-menu">
      <li class="menu-item"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
      <li class="menu-item active"><a href="search.php"><i class="fas fa-search"></i><span>Recherche</span></a></li>
      <li class="menu-item"><a href="documents.php"><i class="fas fa-folder"></i><span>Documents</span></a></li>
      <li class="menu-item"><a href="account.php"><i class="fas fa-user"></i><span>Compte</span></a></li>
      <li class="menu-item"><a href="settings.php"><i class="fas fa-cog"></i><span>Réglages</span></a></li>
    </ul>
    <button class="help-button"><i class="fas fa-question-circle"></i><span>Aide</span></button>
  </nav>

  <!-- Main Content -->
  <main class="main-content">
    <div class="background"></div>
    <div class="wave"></div>
    <div class="content-wrapper">
      <h1>Recherche de documents</h1>

      <!-- Filtres avancés -->
      <div class="search-filters">
        <form action="search.php" method="GET" id="advanced-search-form">
          <input type="hidden" name="query" value="<?= htmlspecialchars($query) ?>">
          <div class="filter-row">
            <div class="filter-group">
              <label for="type">Type de document</label>
              <select name="type" id="type">
                <option value="<?= htmlspecialchars($type['document_type']) ?>">Tous les types</option>
                <?php foreach ($types as $type): ?>
                <option value="<?= htmlspecialchars($type) ?>" <?= $docType === $type ? 'selected' : '' ?>>
                  <?= ucfirst(htmlspecialchars($type)) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="filter-group">
              <label for="date_from">Du</label>
              <input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="filter-group">
              <label for="date_to">Au</label>
              <input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <div class="filter-actions">
              <button type="submit" class="filter-button">Filtrer</button>
              <button type="button" id="reset-filters" class="reset-button">Réinitialiser</button>
            </div>
          </div>
        </form>
      </div>

      <!-- Résultats de recherche -->
      <div class="search-results" id="search-results">
        <?php if (isset($error)): ?>
          <div class="error-message"><?= $error ?></div>
        <?php elseif (empty($results)): ?>
          <div class="no-results">
            <i class="fas fa-search"></i>
            <h2>Aucun document trouvé</h2>
            <p>Essayez d'autres termes de recherche ou critères de filtrage.</p>
          </div>
        <?php else: ?>
          <div class="results-header">
            <h3><?= $totalResults ?> document(s) trouvé(s)</h3>
          </div>
          <div class="results-grid">
            <?php foreach ($results as $doc): ?>
            <div class="document-card">
              <div class="document-preview">
                <img src="<?= htmlspecialchars($doc['image_url']) ?>" alt="Aperçu du document">
                <span class="document-type"><?= ucfirst(htmlspecialchars($doc['document_type'])) ?></span>
              </div>
              <div class="document-info">
                <h4><?= htmlspecialchars($doc['title']) ?></h4>
                <div class="document-meta">
                  <span class="document-date"><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($doc['date_document'])) ?></span>
                  <span class="document-created"><i class="fas fa-clock"></i> <?= date('d/m/Y', strtotime($doc['created_at'])) ?></span>
                </div>
                <?php if (!empty($doc['keywords'])): ?>
                <div class="document-tags">
                  <?php foreach (explode(', ', $doc['keywords']) as $keyword): ?>
                  <span class="tag"><?= htmlspecialchars($keyword) ?></span>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="document-actions">
                  <a href="view_document.php?id=<?= $doc['id'] ?>" class="view-btn">Voir</a>
                  <a href="download_document.php?id=<?= $doc['id'] ?>" class="download-btn">Télécharger</a>
                  <button class="delete-btn" onclick="deleteDocument(<?= $doc['id'] ?>)">Supprimer</button>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
          <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?query=<?= urlencode($query) ?>&type=<?= urlencode($docType) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&page=<?= $page-1 ?>" class="page-link">&laquo; Précédent</a>
            <?php endif; ?>
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($i = $startPage; $i <= $endPage; $i++): ?>
              <a href="?query=<?= urlencode($query) ?>&type=<?= urlencode($docType) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&page=<?= $i ?>" class="page-link"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?query=<?= urlencode($query) ?>&type=<?= urlencode($docType) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&page=<?= $page+1 ?>" class="page-link">Suivant &raquo;</a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>
</body>
</html>
