<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../templates/header.php';


session_start();

// Journaliser l'état de la session pour le débogage
error_log('État de la session: ' . print_r($_SESSION, true));

$error = '';
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$response = ['success' => false, 'message' => '', 'redirect' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) ? true : false;

        // Vérifier que les champs ne sont pas vides
        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";
            $response['message'] = $error;
        } else {
            // Utiliser $conn pour la connexion à la base de données
            $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Stocker les informations complètes de l'utilisateur dans la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = $user; // Stocker toutes les informations de l'utilisateur
                
                // Gérer la fonctionnalité "se souvenir de moi"
                if ($remember) {
                    $now = date('Y-m-d H:i:s');
                    $stmt = $conn->prepare("SELECT * FROM remember_tokens WHERE user_id = ? AND expires > ?");
                    $stmt->execute([$user['id'], $now]);
                    $existingToken = $stmt->fetch();
                
                    if ($existingToken) {
                        // Réutiliser l'ancien token
                        $token = $existingToken['token'];
                        $expires = strtotime($existingToken['expires']);
                    } 
                
                    // Définir le cookie dans tous les cas
                    setcookie('remember_token', $token, $expires, '/', '', false, true);
                
                    // Vérifier si la table remember_tokens existe
                    try {
                        // Stocker le jeton dans la base de données
                        $token = bin2hex(random_bytes(32));
                        $expires = time() + 60 * 60 * 24 * 30; // 30 jours
                        $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)");
                        $stmt->execute([$user['id'], $token, date('Y-m-d H:i:s', $expires)]);
                        
                        // Définir un cookie
                        setcookie('remember_token', $token, $expires, '/', '', false, true);
                    } catch (PDOException $e) {
                        // Journaliser l'erreur mais continuer
                        error_log('Erreur avec remember_tokens: ' . $e->getMessage());
                        // Ne pas bloquer la connexion si cette fonctionnalité échoue
                    }
                }
                
                $response['success'] = true;
                $response['message'] = "Connexion réussie!";
                $response['redirect'] = 'dashboard.php';
                
                if ($isAjax) {
                    try {
                        header('Content-Type: application/json');
                        echo json_encode($response);
                    } catch (Exception $e) {
                        error_log('Erreur JSON: ' . $e->getMessage());
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
                    }
                    exit;
                }
            } else {
                $error = "Email ou mot de passe incorrect.";
                $response['message'] = $error;
            }
        }
        
        // Retourner une réponse JSON pour les requêtes AJAX
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    } catch (Exception $e) {
        // Capturer et journaliser toute erreur
        error_log('Erreur lors de la connexion: ' . $e->getMessage());
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ]);
            exit;
        } else {
            $error = "Une erreur s'est produite: " . $e->getMessage();
        }
    }
}    
?>    

<div class="container">
    <div class="form-card">
        <!-- En-tête du formulaire -->
        <div class="form-header">
            <h1>Se connecter</h1>
            <p class="subtitle">Complétez les champs ci-dessous</p>
        </div>

        <!-- Message d'erreur général -->
        <?php if (!empty($error)): ?>
            <div class="error-message" style="display: block; margin-bottom: 1rem;"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message" style="display: block; margin-bottom: 1rem; color: var(--success-color);">
                <?= $success_message ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire de connexion -->
        <form id="loginForm" method="post" novalidate>
            <div class="input-group">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" required
                placeholder="Entrer votre email">
                <div class="error-message"></div>
            </div>

            <div class="input-group">
                <label for="password">Mot de passe</label>
                <div class="password-input">
                    <input type="password" id="password" name="password" required
                    placeholder="Entrer votre mot de passe">
                    <button type="button" class="toggle-password" aria-label="Afficher le mot de passe">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg> 
                    </button>
                </div>
                <div class="error-message"></div>
            </div>
            
            <div class="input-group" style="display: flex; align-items: center; margin-top: -0.5rem;">
                <input type="checkbox" id="remember" name="remember" style="width: auto; margin-right: 0.5rem;">
                <label for="remember" style="margin-bottom: 0;">Se souvenir de moi</label>
            </div>
            
            <div class="form-navigation" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-next">Se connecter</button>
            </div>
        </form>	
    </div>
    
    <div class="home" style="text-align: center; margin-top: 1rem;">
        <a href="/ArchivAI/public/inscription.php">Pas encore de compte ? S'inscrire</a>
    </div>
</div>

<!-- Inclusion du fichier JavaScript externe -->
<script src="/ArchivAI/assets/js/login-handler.js"></script>
<script src="/ArchivAI/assets/js/password-strength.js"></script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>