-- Création de la base de données Archivai
CREATE DATABASE IF NOT EXISTS archivai;
USE archivai;

-- Utilisez le nom de table "utilisateurs" pour correspondre à votre code PHP
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenoms VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table pour les tokens "remember me"
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);
-- Table pour les documents
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100),
    document_type VARCHAR(100),
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_document DATE,
    ocr_data LONGBLOB,
    ocr_text TEXT,
    keywords VARCHAR(255),
    uploaded_by INT,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    
    FULLTEXT INDEX ft_ocr_text (ocr_text),
    INDEX idx_document_type (document_type),
    INDEX idx_date_document (date_document)
);

-- Table pour les entités extraites automatiquement
CREATE TABLE IF NOT EXISTS document_entities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL,       -- exemple: 'person', 'organization', 'amount'
    entity_value VARCHAR(255) NOT NULL,
    entity_position INT,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    INDEX idx_document_id (document_id),
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_value (entity_value)
);
