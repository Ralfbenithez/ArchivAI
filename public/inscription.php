<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../templates/header.php';

// Traitement de la requête AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    $response = ['success' => false, 'message' => '', 'redirect' => ''];
    
    // Récupération et validation des données
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    
    // Validation des données
    if (empty($name)) {
        $response['message'] = "Le nom complet est requis.";
    } elseif (!$email) {
        $response['message'] = "L'adresse email n'est pas valide.";
    } elseif (empty($password)) {
        $response['message'] = "Le mot de passe est requis.";
    } elseif ($password !== $confirm_password) {
        $response['message'] = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $response['message'] = "Cet email est déjà utilisé.";
        } else {
           // Décomposition du nom complet en nom et prénoms
        $nameParts = explode(' ', trim($name)); // trim pour enlever les espaces en trop

        $nom = array_shift($nameParts); // Le premier mot est considéré comme le nom de famille
        $prenoms = implode(' ', $nameParts); // Le reste est considéré comme prénoms

        if (empty($prenoms)) {
        // Si l'utilisateur n'a entré qu'un seul mot, on le considère comme nom
        $prenoms = $nom;
        }

            // Hashage du mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertion dans la base de données
            try {
                $stmt = $conn->prepare("INSERT INTO utilisateurs (name, nom, prenoms, email, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $nom, $prenoms, $email, $hashed_password]);
                
                // Récupération de l'ID de l'utilisateur créé
                $user_id = $conn->lastInsertId();
                
                // Création de session
                $_SESSION['user_id'] = $user_id;
                
                $response['success'] = true;
                $response['message'] = "Compte créé avec succès!";
                $response['redirect'] = "login.php";
            } catch (PDOException $e) {
                $response['message'] = "Erreur lors de l'inscription: " . $e->getMessage();
            }
        }
    }
    
    // Retourner la réponse en JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<div class="container">
    <div class="form-card">
        <!-- En-tête du formulaire -->
        <div class="form-header">
            <h1>Créez votre compte</h1>
            <p class="subtitle">Complétez les étapes ci-dessous</p>
        </div>

        <!-- Barre de progression des étapes -->
        <div class="progress-container">
            <div class="steps-indicator">
                <!-- Étape 1 : Profil (active par défaut) -->
                <div class="step-item active" data-step="1">
                    <div class="step-circle">1</div>
                    <span class="step-text">Profil</span>
                </div>
                <!-- Étape 2 : Contact -->
                <div class="step-item" data-step="2">
                    <div class="step-circle">2</div>
                    <span class="step-text">Contact</span>
                </div>
                <!-- Étape 3 : Sécurité -->
                <div class="step-item" data-step="3">
                    <div class="step-circle">3</div>
                    <span class="step-text">Sécurité</span>
                </div>
                <!-- Étape 4 : Confirmation -->
                <div class="step-item" data-step="4">
                    <div class="step-circle">4</div>
                    <span class="step-text">Confirmation</span>
                </div>
            </div>
            <!-- Barre de progression dynamique -->
            <div class="progress-bar">
                <div class="progress" id="progress"></div>
            </div>
        </div>

        <!-- Message d'erreur général -->
        <div id="general-error" class="error-message" style="display: none; margin-bottom: 1rem;"></div>

        <!-- Formulaire d'inscription multi-étapes -->
        <form id="multiStepForm" novalidate>
            <!-- Étape 1: Profil -->
            <div class="form-step active" id="step1">
                <div class="input-group">
                    <label for="name">Nom complet</label>
                    <input type="text" id="name" name="name" required pattern="^[a-zA-ZÀ-ÿ\s]{2,}$"
                        placeholder="Entrez votre nom complet">
                    <div class="error-message"></div>
                </div>
                <div class="form-navigation">
                    <button type="button" class="btn btn-next">Continuer</button>
                </div>
            </div>

            <!-- Étape 2: Contact -->
            <div class="form-step" id="step2">
                <div class="input-group">
                    <label for="email">Adresse email</label>
                    <input type="email" id="email" name="email" required placeholder="exemple@email.com">
                    <div class="error-message"></div>
                </div>
                <div class="form-navigation">
                    <button type="button" class="btn btn-prev">Retour</button>
                    <button type="button" class="btn btn-next">Continuer</button>
                </div>
            </div>

            <!-- Étape 3: Sécurité -->
            <div class="form-step" id="step3">
                <div class="input-group">
                    <label for="password">Mot de passe</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required
                            pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d_@-]{8,}$" placeholder="Minimum 8 caractères">
                        <button type="button" class="toggle-password" aria-label="Afficher le mot de passe">
                            <svg viewBox="0 0 24 24" width="24" height="24">
                                <path
                                    d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                            </svg>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar"></div>
                        <span class="strength-text"></span>
                    </div>
                    <div class="error-message"></div>
                </div>
                <div class="form-navigation">
                    <button type="button" class="btn btn-prev">Retour</button>
                    <button type="button" class="btn btn-next">Continuer</button>
                </div>
            </div>

            <!-- Étape 4: Confirmation -->
            <div class="form-step" id="step4">
                <div class="input-group">
                    <label for="confirmPassword">Confirmez le mot de passe</label>
                    <div class="password-input">
                        <input type="password" id="confirmPassword" name="confirmPassword" required
                            placeholder="Retapez votre mot de passe">
                        <button type="button" class="toggle-password" aria-label="Afficher le mot de passe">
                            <svg viewBox="0 0 24 24" width="24" height="24">
                                <path
                                    d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                            </svg>
                        </button>
                    </div>
                    <div class="error-message"></div>
                </div>
                <div class="form-navigation">
                    <button type="button" class="btn btn-prev">Retour</button>
                    <button type="submit" class="btn btn-submit">Créer mon compte</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div>
    <a href="/ArchivAI/public/login.php">Déjà un compte ? Se connecter</a>
</div>

<!-- Inclusion du fichier JavaScript externe -->
<script src="/ArchivAI/assets/js/form-handler.js"></script>
<script src="/ArchivAI/assets/js/password-strength.js"></script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>