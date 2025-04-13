document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const form = document.getElementById('multiStepForm');  // Récupère le formulaire principal
    const formSteps = document.querySelectorAll('.form-step'); //Récupère toutes les étapes du formulaire
    const stepItems = document.querySelectorAll('.step-item'); // Récupère les éléments d'étapes
    const progress = document.getElementById('progress'); // Barre de progression
    const passwordInput = document.getElementById('password'); // Champ du mot de passe
    const confirmPasswordInput = document.getElementById('confirmPassword'); // Champ de confirmation du mot de passe
    const passwordStrength = document.getElementById('passwordStrength'); // Indicateur de force du mot de passe
    const stepElement = document.querySelector('.step-item');

    let currentStep = 0;  // Indice de l'étape actuelle du formulaire

     // Configuration des niveaux de force du mot de passe avec couleurs et textes
    const passwordStrengthConfig = {
        weak: { color: '#ef4444', text: 'Faible' },
        medium: { color: '#f59e0b', text: 'Moyen' },
        strong: { color: '#22c55e', text: 'Fort' }
    };

    // Initialisation du formulaire
    updateFormVisibility(); // Met à jour l'affichage des étapes
    initializePasswordToggles(); // Initialise la visibilité des mots de passe

    // Gestionnaire des boutons "Suivant"
    document.querySelectorAll('.btn-next').forEach(btn => {
        btn.addEventListener('click', () => {
            if (validateCurrentStep()) { // Vérifie si l'étape actuelle est valide
                currentStep++; // Passe à l'étape suivante
                updateFormVisibility(); // Met à jour l'affichage
            }
        });
    });

    // Gestionnaire des boutons "Précédent"
    document.querySelectorAll('.btn-prev').forEach(btn => {
        btn.addEventListener('click', () => {
            currentStep--; // Retourne à l'étape précédente
            updateFormVisibility(); // Met à jour l'affichage
        });
    });

    // Soumission du formulaire
    form.addEventListener('submit', (e) => {
        e.preventDefault(); // Empêche l'envoi du formulaire par défaut
        if (validateCurrentStep()) { // Vérifie si tous les champs sont valides
           // Affichage d'un message de succès après soumission
            const formCard = document.querySelector('.form-card');
            formCard.innerHTML = `
                <div class="success-message" style="text-align: center; padding: 2rem;">
                    <svg viewBox="0 0 24 24" width="48" height="48" style="margin: 0 auto 1rem; fill: var(--success-color);">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                    </svg>
                    <h2 style="color: var(--success-color); margin-bottom: 1rem;">Inscription réussie !</h2>
                    <p style="color: var(--text-secondary);">Votre compte a été créé avec succès.</p>
                </div>
            `;
        }
    });

    // Validation en temps réel des champs
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', () => {
            validateInput(input); // Vérifie le champ saisi
            if (input.type === 'password') {
                updatePasswordStrength(input); // Met à jour la force du mot de passe
            }
        });
    });

    // Fonctions utilitaires
    // Fonction pour mettre à jour la visibilité des étapes du formulaire
    function updateFormVisibility() {
        formSteps.forEach((step, index) => {
            step.classList.toggle('active', index === currentStep); // Affiche uniquement l'étape active
        });

        stepItems.forEach((item, index) => {
            item.classList.toggle('active', index === currentStep); // Indique l'étape active
            item.classList.toggle('completed', index < currentStep); // Marque les étapes précédentes comme terminées
        });
        // Mise à jour de la barre de progression
        const progressWidth = ((currentStep) / (stepItems.length - 1)) * 100;
        progress.style.width = `${progressWidth}%`;
    }

    // Fonction pour valider les champs de l'étape actuelle
    function validateCurrentStep() {
        const currentFormStep = formSteps[currentStep];
        const inputs = currentFormStep.querySelectorAll('input');
        let isValid = true;

        inputs.forEach(input => {
            if (!validateInput(input)) {
                isValid = false;
            }
        });

        // Validation spécifique pour la confirmation du mot de passe
        if (currentStep === 3) {
            if (confirmPasswordInput.value !== passwordInput.value) {
                showError(confirmPasswordInput, 'Les mots de passe ne correspondent pas');
                isValid = false;
            }
        }

        return isValid;
    }

// Fonction de validation des champs de saisie
    function validateInput(input) {
        const errorElement = input.parentElement.querySelector('.error-message') || 
                           input.parentElement.parentElement.querySelector('.error-message');
        let isValid = true;
        let errorMessage = '';

        // Réinitialisation des classes et messages d'erreur
        input.classList.remove('invalid', 'valid');
        errorElement.style.display = 'none';

        // Validation du champ vide
        if (!input.value.trim()) {
            errorMessage = 'Ce champ est requis';
            isValid = false;
        }
        // Validation spécifique selon le type de champ
        else if (input.id === 'name' && !input.value.match(/^[a-zA-ZÀ-ÿ\s]{2,}$/)) {
            errorMessage = 'Veuillez entrer un nom valide (minimum 2 caractères, lettres uniquement)';
            isValid = false;
        }
        else if (input.id === 'email' && !input.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            errorMessage = 'Veuillez entrer une adresse email valide';
            isValid = false;
        }
        else if (input.id === 'password' && !input.value.match(/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d_@-]{8,}$/)) {
            errorMessage = 'Le mot de passe doit contenir au moins 8 caractères, une lettre et un chiffre';
            isValid = false;
        }

        // Affichage des erreurs ou validation
        if (!isValid) {
            input.classList.add('invalid');
            errorElement.textContent = errorMessage;
            errorElement.style.display = 'block';
        } else {
            input.classList.add('valid');
        }

        return isValid;
    }

     // Fonction de mise à jour de la force du mot de passe
    function updatePasswordStrength(input) {
        if (input.id !== 'password') return;

        const strength = calculatePasswordStrength(input.value);
        const strengthBar = passwordStrength.querySelector('.strength-bar');
        const strengthText = passwordStrength.querySelector('.strength-text');

        passwordStrength.style.display = input.value ? 'block' : 'none';
        
        if (input.value) {
            strengthBar.style.background = passwordStrengthConfig[strength].color;
            strengthBar.style.width = strength === 'weak' ? '33%' : 
                                    strength === 'medium' ? '66%' : '100%';
            strengthText.textContent = passwordStrengthConfig[strength].text;
            strengthText.style.color = passwordStrengthConfig[strength].color;
        }
    }

    function calculatePasswordStrength(password) {
        if (password.length < 8) return 'weak';
        const hasLetters = /[a-zA-Z]/.test(password);
        const hasNumbers = /\d/.test(password);
        const hasSpecialChars = /[!@#$%^&*]/.test(password);
        
        if (hasLetters && hasNumbers && hasSpecialChars && password.length >= 12) return 'strong';
        if ((hasLetters && hasNumbers) || (hasLetters && hasSpecialChars) || (hasNumbers && hasSpecialChars)) return 'medium';
        return 'weak';
    }

    function initializePasswordToggles() {
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
            });
        });
    }

     // Fonction pour afficher une erreur sur un champ
     function showError(input, message) {
        const errorElement = input.parentElement.querySelector('.error-message');
        input.classList.add('invalid');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
});