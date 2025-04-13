<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../templates/header.php';

$token = $_GET['token'] ?? '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8) {
        $errors[] = "Le mot de passe est trop court.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Vérifie que le token est valide
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $errors[] = "Lien invalide ou expiré.";
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed, $reset['email']]);

        // Supprime le token
        $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

        echo "<p style='color: green;'>Mot de passe réinitialisé. <a href='login.php'>Se connecter</a></p>";
        require_once __DIR__ . '/../templates/footer.php';
        exit;
    }
}
?>

<h2>Réinitialiser le mot de passe</h2>

<?php foreach ($errors as $error): ?>
    <div style="color: red"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<form method="post">
    <label>Nouveau mot de passe</label>
    <input type="password" name="password" id="password" required><br>
    <div id="password-strength"></div><br>

    <label>Confirmer le mot de passe</label>
    <input type="password" name="confirm_password" required><br><br>

    <button type="submit">Réinitialiser</button>
</form>

<script src="/assets/js/password-strength.js"></script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
