document.addEventListener("DOMContentLoaded", function() {
  // Gestion de la sidebar et autres interactions habituelles
  const menuItems = document.querySelectorAll('.menu-item');
  menuItems.forEach(item => {
    item.addEventListener('click', function() {
      menuItems.forEach(i => i.classList.remove('active'));
      this.classList.add('active');
    });
  });
  
  document.querySelector('.help-button')?.addEventListener('click', function() {
    alert("Besoin d'aide ? Contactez notre support à support@archivai.com");
  });
  
  document.querySelector('.camera-icon')?.addEventListener('click', function() {
    alert("Fonctionnalité de prise de photo en cours de développement.");
  });

  // --- Fonctionnalité de prise de photo, recadrage et OCR ---
  const openCameraBtn = document.getElementById("open-camera-btn");
  const modal = document.getElementById("camera-modal");
  const closeModal = document.getElementById("close-modal");
  const video = document.getElementById("video");
  const canvas = document.getElementById("canvas");
  const cropContainer = document.getElementById("crop-container");
  const cropImage = document.getElementById("crop-image");
  const captureBtn = document.getElementById("capture-btn");
  const cropBtn = document.getElementById("crop-btn");
  const uploadBtn = document.getElementById("upload-btn");
  const recognizeBtn = document.getElementById("recognize-btn");
  const resultContainer = document.getElementById("ocr-result-container");
  const resultText = document.getElementById("ocr-result-text");
  const progressBar = document.getElementById("ocr-progress");
  const progressStatus = document.getElementById("ocr-status");

  let stream;
  let cropper;
  let ocrResult = "";

  // Ouvrir la modale et démarrer la caméra
  openCameraBtn.addEventListener("click", async () => {
    modal.style.display = "block";
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: true });
      video.srcObject = stream;
    } catch (err) {
      alert("Impossible d'accéder à la caméra : " + err);
    }
  });

  // Fermer la modale
  closeModal.addEventListener("click", () => {
    modal.style.display = "none";
    // Stopper la caméra
    if (stream) {
      stream.getTracks().forEach(track => track.stop());
    }
    // Réinitialiser l'affichage
    video.style.display = "block";
    cropContainer.style.display = "none";
    resultContainer.style.display = "none";
    captureBtn.style.display = "inline-block";
    cropBtn.style.display = "none";
    recognizeBtn.style.display = "none";
    uploadBtn.style.display = "none";
    if (cropper) {
      cropper.destroy();
      cropper = null;
    }
  });

  // Capturer l'image depuis le flux vidéo
  captureBtn.addEventListener("click", () => {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const context = canvas.getContext("2d");
    context.drawImage(video, 0, 0);
    const dataURL = canvas.toDataURL("image/jpeg");
    // Afficher l'image dans la zone de recadrage
    cropImage.src = dataURL;
    video.style.display = "none";
    cropContainer.style.display = "block";
    captureBtn.style.display = "none";
    cropBtn.style.display = "inline-block";

    // Initialiser Cropper.js
    cropper = new Cropper(cropImage, {
      aspectRatio: 16 / 9,
      viewMode: 1
    });
  });

  // Recadrer l'image
  cropBtn.addEventListener("click", () => {
    const croppedCanvas = cropper.getCroppedCanvas();
    // Afficher le résultat du recadrage dans cropImage pour prévisualisation
    cropImage.src = croppedCanvas.toDataURL("image/jpeg");
    cropper.destroy();
    cropper = null;
    cropBtn.style.display = "none";
    recognizeBtn.style.display = "inline-block"; // Afficher le bouton OCR
  });

  // Fonction OCR avec l'API OCR.space
recognizeBtn.addEventListener("click", async () => {
  recognizeBtn.disabled = true;
  progressBar.style.display = "block";
  progressStatus.textContent = "Analyse en cours...";

  const imageBase64 = cropImage.src.replace(/^data:image\/(png|jpeg);base64,/, "");

  try {
    const response = await fetch("https://api.ocr.space/parse/image", {
      method: "POST",
      headers: {
        "apikey": "K82129420788957",
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: new URLSearchParams({
        base64Image: "data:image/jpeg;base64," + imageBase64,
        language: "fre",
        isOverlayRequired: "false"
      })
    });

    const data = await response.json();
    
    if (data.OCRExitCode === 1) {
      ocrResult = data.ParsedResults[0].ParsedText;
      resultText.textContent = ocrResult;
      resultContainer.style.display = "block";
      recognizeBtn.style.display = "none";
      uploadBtn.style.display = "inline-block";
      progressStatus.textContent = "Analyse terminée.";
    } else {
      progressStatus.textContent = "Erreur OCR : " + data.ErrorMessage;
      console.error("OCR API Error:", data);
      recognizeBtn.disabled = false;
    }

  } catch (error) {
    console.error("Erreur API OCR:", error);
    progressStatus.textContent = "Erreur de communication avec l'API.";
    recognizeBtn.disabled = false;
  }
});


  // Envoyer l'image recadrée et le texte OCR au serveur
  uploadBtn.addEventListener("click", () => {
    const finalImageData = cropImage.src;
    
    fetch("/ArchivAI/includes/upload-process.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ 
        imageData: finalImageData,
        ocrText: ocrResult
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert("Image enregistrée et analysée avec succès !");
        closeModal.click(); // Ferme la modale
        // Optionnel: rafraîchir ou rediriger
        // window.location.reload(); 
      } else {
        alert("Erreur lors de l'enregistrement : " + data.error);
      }
    })
    .catch(err => {
      console.error(err);
      alert("Erreur lors de la communication avec le serveur.");
      console.error("OCR API Error:", data);
    });
  });
});