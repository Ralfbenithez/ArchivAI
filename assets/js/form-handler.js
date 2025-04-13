document.addEventListener('DOMContentLoaded', function() {
    // Référence au formulaire
    const form = document.getElementById('multiStepForm');
    const generalError = document.getElementById('general-error');
    
    // Soumission du formulaire en AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Vérifier si l'étape est valide avant d'envoyer
        if (validateCurrentStep()) {
            // Création de l'objet FormData pour envoyer les données
            const formData = new FormData();
            formData.append('name', document.getElementById('name').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('password', document.getElementById('password').value);
            formData.append('confirmPassword', document.getElementById('confirmPassword').value);
            
            // Afficher un indicateur de chargement
            const submitBtn = document.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = "Traitement en cours...";
            submitBtn.disabled = true;
            
            // Envoi de la requête AJAX
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Affichage du message de succès
                    const formCard = document.querySelector('.form-card');
                    formCard.innerHTML = `
                        <div class="success-message" style="text-align: center; padding: 2rem;">
                            <svg viewBox="0 0 24 24" width="48" height="48" style="margin: 0 auto 1rem; fill: var(--success-color);">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                            </svg>
                            <h2 style="color: var(--success-color); margin-bottom: 1rem;">Inscription réussie !</h2>
                            <p style="color: var(--text-secondary);">${data.message}</p>
                        </div>
                    `;
                    
                    // Redirection après un court délai
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
                    }
                } else {
                    // Afficher le message d'erreur
                    generalError.textContent = data.message;
                    generalError.style.display = 'block';
                    
                    // Rétablir le bouton
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    
                    // Faire défiler jusqu'au message d'erreur
                    generalError.scrollIntoView({ behavior: 'smooth' });
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                generalError.textContent = "Une erreur est survenue lors de la communication avec le serveur";
                generalError.style.display = 'block';
                
                // Rétablir le bouton
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }
    });
    
    // Fonction de validation de l'étape actuelle
    // Cette fonction peut être appelée par votre script password-strength.js existant
    window.validateCurrentStep = function() {
        // Implémenter ici si nécessaire, sinon utiliser celle de password-strength.js
        // On pourrait aussi fusionner les deux scripts
        return true;
    };
});