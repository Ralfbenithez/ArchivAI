<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../templates/header.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mettre à jour les informations de l'utilisateur
    $nom = $_POST['nom'];
    $prenoms = $_POST['prenoms'];
    $date_naissance = $_POST['date_naissance'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE utilisateurs SET nom = ?, prenoms = ?, date_naissance = ?, password = ? WHERE id = ?");
    $stmt->execute([$nom, $prenoms, $date_naissance, $password, $user_id]);

    header("Location: account.php");
    exit;
}
?>

<body>
  <!-- Header -->
  <?php require_once __DIR__ . '/../templates/header.php'; ?>


  <!-- Main Content -->
  <main class="main-content">
    <div class="content-wrapper">
      <h1>Mon Compte</h1>
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="nom">Nom</label>
          <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
        </div>
        <div class="form-group">
          <label for="prenom">Prénoms</label>
          <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user['prenoms']) ?>" required>
        </div>
        <div class="form-group">
          <label for="date_naissance">Date de naissance</label>
          <input type="date" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($user['date_naissance']) ?>">
        </div>
        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
          <label for="photo">Photo de profil</label>
          <input type="file" id="photo" name="photo" accept="image/*">
        </div>
        <button type="submit">Mettre à jour</button>
      </form>
    </div>
  </main>
</body>
</html>
