# Documentação do Web Service de Identificação de Espécies com IA

## Visão Geral

Este projeto implementa um Web Service para identificação automática de espécies da fauna e flora da Região Carbonífera, utilizando Inteligência Artificial. O sistema permite o upload de imagens, classificação automática das espécies e, quando necessário, encaminhamento para validação por especialistas.

## Tecnologias Utilizadas

- **Backend**: PHP 8.4 (MVC sem framework)
- **Frontend**: HTML, CSS e JavaScript puro
- **Banco de Dados**: MySQL 8.0
- **Servidor Web**: Nginx
- **Containerização**: Docker
- **Classificação de Imagens**: Simulação de API de IA (preparada para integração com serviços reais)

## Estrutura do Projeto

```
/
├── docker/                  # Configurações Docker
│   ├── mysql/               # Scripts de inicialização do MySQL
│   ├── nginx/               # Configuração do Nginx
│   └── php/                 # Dockerfile do PHP
├── src/                     # Código-fonte da aplicação
│   ├── app/                 # Código PHP da aplicação
│   │   ├── Config/          # Arquivos de configuração
│   │   ├── Controllers/     # Controladores MVC
│   │   ├── Core/            # Classes base do framework MVC
│   │   ├── Models/          # Modelos de dados
│   │   ├── Services/        # Serviços da aplicação
│   │   └── Views/           # Templates de visualização
│   └── public/              # Arquivos públicos
│       ├── css/             # Estilos CSS
│       ├── js/              # Scripts JavaScript
│       ├── uploads/         # Diretório para uploads de imagens
│       └── index.php        # Ponto de entrada da aplicação
├── docker-compose.yml       # Configuração do Docker Compose
├── create_test_images.sh    # Script para criar imagens de teste
├── test_plan.md             # Plano de testes
└── test_results.md          # Resultados dos testes
```

## Instalação e Execução

### Pré-requisitos

- Docker
- Docker Compose

### Passos para Execução

1. Clone o repositório:
   ```bash
   git clone https://github.com/fabio3268/api-bio-diversa
   cd api-bio-diversa
   ```

2. Inicie os containers Docker:
   ```bash
   docker-compose up -d
   ```

3. Acesse a aplicação:
   - Frontend: http://localhost:8080
   - Dashboard: http://localhost:8080/dashboard

## Funcionalidades

### 1. Upload e Identificação de Imagens

- Acesse a página inicial
- Selecione uma imagem de uma espécie da Região Carbonífera
- Clique em "Identificar Espécie"
- O sistema processará a imagem e exibirá o resultado da identificação

### 2. Dashboard de Validação

- Acesse http://localhost:8080/dashboard
- Visualize as identificações pendentes
- Clique em "Validar" para uma identificação específica
- Selecione a espécie correta no modal
- Confirme a validação

## API REST

O sistema disponibiliza os seguintes endpoints REST:

### Autenticação

Todos os endpoints requerem autenticação via API Key no cabeçalho:
```
Authorization: Bearer cb2023-test-api-key
```

### Endpoints

- **POST /api/identify**
  - Envia uma imagem para identificação
  - Parâmetros: `image` (arquivo de imagem)
  - Retorno: Informações sobre a identificação

- **GET /api/species**
  - Lista todas as espécies cadastradas
  - Parâmetros opcionais: `type` (fauna ou flora)

- **GET /api/identifications**
  - Lista todas as identificações
  - Parâmetros opcionais: `status` (success, pending_validation, validated)

- **GET /api/identifications/pending**
  - Lista identificações pendentes de validação

- **POST /api/identifications/validate**
  - Valida uma identificação pendente
  - Parâmetros: `identification_id`, `species_id`

## Espécies de Exemplo

O sistema está pré-configurado com as seguintes espécies:

### Fauna
- Quero-quero (Vanellus chilensis)
- Capivara (Hydrochoerus hydrochaeris)
- Graxaim (Lycalopex gymnocercus)

### Flora
- Babosa-do-campo (Eryngium horridum)
- Trevo-nativo (Trifolium riograndense)

## Integração com IA

O sistema atualmente utiliza uma simulação de API de IA para classificação de imagens. Para integrar com uma API real:

1. Edite o arquivo `src/app/Services/ImageClassificationService.php`
2. Descomente e adapte o método `callGoogleVisionApi()` (ou implemente outro serviço)
3. Atualize as credenciais e configurações no arquivo `src/app/Config/config.php`

## Testes

O sistema foi testado com imagens simuladas para todas as espécies de exemplo. Os resultados dos testes estão disponíveis no arquivo `test_results.md`.

## Notas de Implementação

- O sistema utiliza uma arquitetura MVC manual em PHP, sem dependência de frameworks externos
- A autenticação é feita via API Key para simplificar a implementação
- O upload de imagens é validado quanto ao tipo e tamanho
- O limiar de confiança para validação manual é configurável
