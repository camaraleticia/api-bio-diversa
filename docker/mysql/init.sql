-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS carbonifera_db;
USE carbonifera_db;

-- Tabela de espécies
CREATE TABLE IF NOT EXISTS species (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    scientific_name VARCHAR(255),
    type ENUM('fauna', 'flora') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de identificações
CREATE TABLE IF NOT EXISTS identifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    species_id INT,
    confidence FLOAT,
    status ENUM('success', 'pending_validation', 'validated') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (species_id) REFERENCES species(id)
);

-- Tabela de chaves de API
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir espécies de exemplo
INSERT INTO species (name, scientific_name, type, description) VALUES
('Quero-quero', 'Vanellus chilensis', 'fauna', 'Ave comum no sul do Brasil, conhecida por seu comportamento territorial'),
('Capivara', 'Hydrochoerus hydrochaeris', 'fauna', 'Maior roedor do mundo, comum em áreas úmidas'),
('Graxaim', 'Lycalopex gymnocercus', 'fauna', 'Canídeo nativo da América do Sul'),
('Babosa-do-campo', 'Eryngium horridum', 'flora', 'Planta nativa dos campos sulinos'),
('Trevo-nativo', 'Trifolium riograndense', 'flora', 'Espécie de trevo nativa do sul do Brasil');

-- Inserir uma chave de API para testes
INSERT INTO api_keys (api_key, description) VALUES
('cb2023-test-api-key', 'Chave de API para testes do projeto Carbonífera Biodiversa');
