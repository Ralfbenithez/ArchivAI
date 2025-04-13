document.addEventListener('DOMContentLoaded', function() {
    // Référence au formulaire de connexion
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        // Initialisation des toggles de mot de passe
        initializePasswordToggles();
        
        // Gestion des erreurs de validation en temps réel
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => validateInput(input));
        });
        
        // Soumission du formulaire en AJAX
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Supprimer les messages d'erreur existants
            document.querySelectorAll('.error-message').forEach(el => {
                if (el.style.display === 'block') {
                    el.style.display = 'none';
                }
            });
            
            // Valider les champs avant envoi
            if (validateForm()) {
                // Créer l'objet FormData
                const formData = new FormData(loginForm);
                
                // Modifier le bouton pour indiquer le chargement
                const submitBtn = loginForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = "Connexion...";
                submitBtn.disabled = true;
                
                // Envoi de la requête AJAX
                fetch('/ArchivAI/public/login.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(response => {
                    // Récupérer la réponse texte brute pour déboguer
                    return response.text().then(text => {
                        console.log("Réponse brute:", text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error("Erreur d'analyse JSON:", e);
                            throw new Error("La réponse n'est pas un JSON valide: " + text.substring(0, 100));
                        }
                    });
                })
                .then(data => {       
                    if (data.success) {
                        // Afficher un message de succès
                        const formCard = document.querySelector('.form-card');
                        formCard.innerHTML = `
                            <div class="success-message" style="text-align: center; padding: 2rem;">
                                <svg viewBox="0 0 24 24" width="48" height="48" style="margin: 0 auto 1rem; fill: var(--success-color);">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                </svg>
                                <h2 style="color: var(--success-color); margin-bottom: 1rem;">Connexion réussie !</h2>
                                <p style="color: var(--text-secondary);">Redirection en cours...</p>
                            </div>
                        `;
                        
                        // Redirection après un court délai
                        if (data.redirect) {
                            console.log("Redirection vers:", data.redirect);
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1500);
                        }
                    } else {
                        // Afficher le message d'erreur
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'error-message';
                        errorDiv.style.display = 'block';
                        errorDiv.style.marginBottom = '1rem';
                        errorDiv.textContent = data.message || "Une erreur est survenue";
                        
                        // Insérer le message d'erreur au début du formulaire
                        const formHeader = document.querySelector('.form-header');
                        formHeader.insertAdjacentElement('afterend', errorDiv);
                        
                        // Rétablir le bouton
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Erreur détaillée:', error);
                    
                    // Créer et afficher un message d'erreur
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.style.display = 'block';
                    errorDiv.style.marginBottom = '1rem';
                    errorDiv.textContent = "Une erreur est survenue lors de la communication avec le serveur: " + error.message;
                    
                    // Insérer le message d'erreur
                    const formHeader = document.querySelector('.form-header');
                    formHeader.insertAdjacentElement('afterend', errorDiv);
                    
                    // Rétablir le bouton
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
            }
        });
    }
    
    // Fonction pour initialiser les toggles de mot de passe
    function initializePasswordToggles() {
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });
    }
    
    // Fonction pour valider un champ de saisie
    function validateInput(input) {
        const errorElement = input.parentElement.querySelector('.error-message') || 
                          input.parentElement.parentElement.querySelector('.error-message');
        let isValid = true;
        
        // Réinitialiser l'affichage des erreurs
        input.classList.remove('invalid', 'valid');
        if (errorElement) errorElement.style.display = 'none';
        
        // Validation de base
        if (input.hasAttribute('required') && !input.value.trim()) {
            isValid = false;
            if (errorElement) {
                errorElement.textContent = 'Ce champ est requis';
                errorElement.style.display = 'block';
            }
        } else if (input.type === 'email' && !isValidEmail(input.value)) {
            isValid = false;
            if (errorElement) {
                errorElement.textContent = 'Veuillez entrer une adresse email valide';
                errorElement.style.display = 'block';
            }
        }
        
        // Appliquer les classes CSS appropriées
        input.classList.add(isValid ? 'valid' : 'invalid');
        
        return isValid;
    }
    
    // Fonction pour valider tout le formulaire
    function validateForm() {
        let isValid = true;
        
        // Valider chaque champ requis
        loginForm.querySelectorAll('input[required]').forEach(input => {
            if (!validateInput(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    // Fonction pour valider un email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});