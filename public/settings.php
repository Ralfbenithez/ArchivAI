<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../templates/header.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Mettre à jour les réglages de l'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $theme = $_POST['theme'];

    $stmt = $conn->prepare("UPDATE utilisateurs SET notifications = ?, theme = ? WHERE id = ?");
    $stmt->execute([$notifications, $theme, $_SESSION['user_id']]);

    header("Location: reglages.php");
    exit;
}

// Récupérer les réglages actuels de l'utilisateur
$stmt = $conn->prepare("SELECT notifications, theme FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$settings = $stmt->fetch();
?>

<body>
  <!-- Header -->
  <?php require_once __DIR__ . '/../templates/header.php'; ?>

  <!-- Sidebar -->
  <?php require_once __DIR__ . '/../templates/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="content-wrapper">
      <h1>Réglages</h1>
      <form method="POST">
        <div class="form-group">
          <label for="notifications">Recevoir des notifications</label>
          <input type="checkbox" id="notifications" name="notifications" <?= $settings['notifications'] ? 'checked' : '' ?>>
        </div>
        <div class="form-group">
          <label for="theme">Thème</label>
          <select id="theme" name="theme">
            <option value="light" <?= $settings['theme'] === 'light' ? 'selected' : '' ?>>Clair</option>
            <option value="dark" <?= $settings['theme'] === 'dark' ? 'selected' : '' ?>>Sombre</option>
          </select>
        </div>
        <button type="submit">Enregistrer</button>
      </form>
    </div>
  </main>
</body>
</html>
