<?php
require 'config.php';
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

// Vérification de session
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

// Récupération des données POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['imageData'])) {
    echo json_encode(['success' => false, 'error' => 'Aucune donnée reçue']);
    exit;
}

// Extraire la partie base64 de l'image
$imageData = $data['imageData'];
list($type, $imageData) = explode(';', $imageData);
list(, $imageData) = explode(',', $imageData);
$imageData = base64_decode($imageData);

if ($imageData === false) {
    echo json_encode(['success' => false, 'error' => 'Décodage de l\'image impossible']);
    exit;
}

// Récupérer les données OCR
$ocrText = isset($data['ocrText']) ? $data['ocrText'] : '';
$documentType = isset($data['documentType']) ? $data['documentType'] : 'autre';
$documentName = isset($data['documentName']) ? $data['documentName'] : '';

// Fonction pour appeler Gemini AI
function getSmartFilenameFromGemini($ocrText) {
    $apiKey = 'AIzaSyCS37r88doNNQagqgLFhtJq3jYLntFLe74';
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

    $postData = json_encode([
        "contents" => [[
            "parts" => [[
                "text" => "Propose un nom de fichier court et pertinent pour ce document en te basant sur son contenu OCR suivant :\n\n" . $ocrText . "\n\nNom suggéré :"
            ]]
        ]]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return null;

    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return trim($data['candidates'][0]['content']['parts'][0]['text']);
    }

    return null;
}

// Générer un nom intelligent avec Gemini si vide
if (empty($documentName)) {
    $aiName = getSmartFilenameFromGemini($ocrText);
    if ($aiName) {
        $documentName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $aiName);
        $documentName = substr($documentName, 0, 50); // Limiter la longueur
    } else {
        $documentName = 'document_' . date('Ymd_His');
    }
} else {
    $documentName = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $documentName);
}

// Générer un nom unique pour le fichier
$folder = "uploads/" . date('Y') . "/" . date('m') . "/";
if (!is_dir($folder)) {
    mkdir($folder, 0755, true);
}

$baseFilename = $documentName;
$filename = $baseFilename . ".jpg";
$counter = 0;

while (file_exists($folder . $filename)) {
    $counter++;
    $filename = $baseFilename . "_" . $counter . ".jpg";
}

$filePath = $folder . $filename;

// Sauvegarder l'image
if (file_put_contents($filePath, $imageData) === false) {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la sauvegarde de l\'image']);
    $fileSize = filesize($filePath);
    exit;
}

// Sauvegarder le texte OCR dans un fichier texte
$textFilename = pathinfo($filename, PATHINFO_FILENAME) . ".txt";
$textFilePath = $folder . $textFilename;
file_put_contents($textFilePath, $ocrText);

// Extraction de la date
$documentDate = null;
$datePatterns = [
    '/(\d{1,2})[\/\.-](\d{1,2})[\/\.-](20\d{2})/i',
    '/(20\d{2})[\/\.-](\d{1,2})[\/\.-](\d{1,2})/i',
    '/(\d{1,2})\s+(janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre)\s+(20\d{2})/i'
];

foreach ($datePatterns as $pattern) {
    if (preg_match($pattern, $ocrText, $matches)) {
        if (count($matches) >= 4) {
            if (preg_match('/janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre/i', $matches[2])) {
                $months = [
                    'janvier' => '01', 'février' => '02', 'mars' => '03', 'avril' => '04',
                    'mai' => '05', 'juin' => '06', 'juillet' => '07', 'août' => '08',
                    'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'décembre' => '12'
                ];
                $month = strtolower($matches[2]);
                $documentDate = $matches[3] . '-' . $months[$month] . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            } else {
                $documentDate = $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            }
            break;
        } else if (count($matches) >= 3 && is_numeric($matches[1]) && $matches[1] >= 2000) {
            $documentDate = $matches[1] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            break;
        }
    }
}

if (!$documentDate) {
    $documentDate = date('Y-m-d');
}

// Extraction des mots-clés
$keywords = [];

$typeKeywords = [
    'facture' => ['facture', 'paiement', 'montant', 'total', 'ht', 'ttc', 'tva'],
    'contrat' => ['contrat', 'accord', 'convention', 'signature', 'parties', 'engagement'],
    'releve' => ['relevé', 'compte', 'bancaire', 'solde', 'opération', 'transaction'],
    'note' => ['note', 'mémo', 'information', 'rappel'],
    'autre' => ['document', 'information']
];

if (isset($typeKeywords[$documentType])) {
    $keywords = array_merge($keywords, $typeKeywords[$documentType]);
}

// Extraction des entités
$entities = [];

preg_match_all('/(\d+[\s,.]?\d*)\s*(?:€|EUR|euro|euros)/i', $ocrText, $amountMatches);
foreach ($amountMatches[1] as $amount) {
    $cleanAmount = preg_replace('/[^\d,.]/', '', $amount);
    $entities[] = ['type' => 'amount', 'value' => $cleanAmount];
    $keywords[] = 'montant:' . $cleanAmount;
}

preg_match_all('/(?:Ref(?:erence)?|N°|Num(?:ero)?)[^\w\d]*(\w\d[\w\d-]{2,})/i', $ocrText, $refMatches);
foreach ($refMatches[1] as $ref) {
    $entities[] = ['type' => 'reference', 'value' => $ref];
    $keywords[] = 'ref:' . $ref;
}

preg_match_all('/([A-Z][A-Z\s]{2,}(?:\s[A-Z][a-z]+)*)|([A-Z][a-z]+(?:\s[A-Z][a-z]+)+\s(?:SA|SARL|SAS|EURL|Inc|LLC))\b/i', $ocrText, $orgMatches);
foreach ($orgMatches[0] as $org) {
    if (strlen($org) > 3) {
        $entities[] = ['type' => 'organization', 'value' => trim($org)];
        $keywords[] = 'org:' . trim($org);
    }
}

$keywords = array_unique($keywords);
$keywords = array_slice($keywords, 0, 10);
$keywordsString = implode(', ', $keywords);

$ocrResult = [
    'extracted_text' => $ocrText,
    'document_type' => $documentType,
    'summary_name' => $documentName,
    'document_date' => $documentDate,
    'keywords' => $keywordsString,
    'entities' => $entities
];

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO documents (
        uploaded_by,
        file_path,
        mime_type,
        document_type,
        upload_date,
        date_document,
        ocr_data,
        ocr_text,
        keywords,
        title
    ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)");

    $userId = $_SESSION['user']['id'];
    $ocrJson = json_encode($ocrResult);

    $stmt->execute([
        $userId,
        $filePath,
        'image/jpeg',
        $documentType,
        $documentDate,
        $ocrJson,
        $ocrText,
        $keywordsString,
        $documentName
    ]);

    $documentId = $conn->lastInsertId();

    if (!empty($entities)) {
        $entityStmt = $conn->prepare("INSERT INTO document_entities (
            document_id,
            entity_type,
            entity_value
        ) VALUES (?, ?, ?)");

        foreach ($entities as $entity) {
            $entityStmt->execute([
                $documentId,
                $entity['type'],
                $entity['value']
            ]);
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'documentId' => $documentId,
        'imagePath' => $filePath,
        'textPath' => $textFilePath,
        'documentName' => $documentName,
        'documentDate' => $documentDate,
        'keywords' => $keywords,
        'entities' => $entities,
        'message' => 'Document traité et enregistré avec succès'
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    if (file_exists($filePath)) unlink($filePath);
    if (file_exists($textFilePath)) unlink($textFilePath);

    echo json_encode(['success' => false, 'error' => 'Erreur base de données: ' . $e->getMessage()]);
}

// file_size soit bien rempli lors de l’upload
$stmt = $conn->prepare("INSERT INTO documents (
    title, file_path, mime_type, document_type, file_size, upload_date, date_document, ocr_data, ocr_text, keywords, uploaded_by)
    VALUES (:title, :file_path, :mime_type, :document_type, :file_size, NOW(), :date_document, :ocr_data, :ocr_text, :keywords, :uploaded_by)");
    $stmt->bindParam(':file_size', $fileSize, PDO::PARAM_INT);
?>
