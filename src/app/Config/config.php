<?php
/**
 * Arquivo de configuração da aplicação
 */

return [
    // Configurações do banco de dados
    'database' => [
        'host' => 'carbonifera_mysql', // antes mysql
        'database' => 'carbonifera_db',
        'username' => 'carbonifera_user',
        'password' => 'carbonifera_password',
    ],
    
    // Configurações da API de IA
    'ai_service' => [
        // Usaremos uma API externa para classificação de imagens
        'api_url' => 'https://api.example.com/v1/classify', // Será substituído pela API real
        'api_key' => 'your-api-key', // Será substituído pela chave real
        'confidence_threshold' => 0.5, // Limiar de confiança para validação manual
    ],
    
    // Configurações de upload de imagens
    'upload' => [
        'directory' => ROOT_DIR . '/public/uploads/images',
      //  'directory' => ROOT_DIR . '/uploads/images',
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
    ],
    
    // Configurações gerais da aplicação
    'app' => [
        'name' => 'Carbonífera Biodiversa',
        'debug' => true, // Definir como false em produção
    ],
];
