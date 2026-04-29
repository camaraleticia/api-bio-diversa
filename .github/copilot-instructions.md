# copilot-instructions.md

## Projeto
Carbonífera Biodiversa – API de Reconhecimento de Espécies com IA

---

## Contexto Geral

Este projeto tem como objetivo desenvolver uma API REST em PHP para integração com a plataforma Carbonífera Biodiversa, permitindo que usuários da web enviem imagens de espécies da fauna e flora da Região Carbonífera do Rio Grande do Sul.

A API realiza uma primeira tentativa de reconhecimento automático utilizando Inteligência Artificial (Machine Learning). Quando a confiabilidade da predição for alta, o sistema retorna a espécie reconhecida. Quando a confiabilidade for baixa, a imagem é encaminhada para validação manual por especialistas (biólogos).

O sistema deve permitir crescimento contínuo do dataset para melhoria futura do modelo de IA.

---

## Situação Atual do Projeto

A plataforma Carbonífera Biodiversa já existe e está em produção.

Ela atualmente:

- já cataloga espécies da fauna e flora
- não permite upload de imagens por cidadãos
- recebe registros apenas da equipe técnica

Este projeto adiciona:

- módulo de envio de imagens
- API de reconhecimento
- integração com IA
- fluxo de validação por especialistas

O foco principal é a API e o motor de reconhecimento.

---

## Stack Tecnológica

### Backend

- PHP 8+
- Arquitetura MVC
- API REST
- JSON
- PDO
- MySQL
- Composer
- PSR-12
- Nginx
- armazenamento local de imagens

### Inteligência Artificial

- RubixML (modelo local)

---

## Objetivo Técnico Principal

Implementar uma API robusta, modular e escalável que:

1. receba imagens
2. valide uploads
3. execute reconhecimento com IA
4. determine nível de confiança
5. retorne resultado ao usuário
6. encaminhe casos incertos para especialistas
7. armazene imagens para expansão futura do dataset

---

## Padrões Obrigatórios

### Código

Sempre seguir:

- PSR-12
- responsabilidade única
- baixo acoplamento
- controllers enxutos
- services para regra de negócio
- validação centralizada
- respostas padronizadas em JSON

Evitar:

- lógica de negócio em controllers
- SQL espalhado no código
- código duplicado
- hardcode desnecessário
- mistura de responsabilidades

---

## Estrutura do Projeto

```
api-bio-diversa/
├── docker-compose.yml
├── README.md
├── create_test_images.sh
├── data-base/                            # Modelos de BD e dumps SQL
│   ├── biodiversa-atual.mwb
│   └── dump-carboniferabiodiversa.sql
├── docker/                               # Configuração dos containers
│   ├── mysql/
│   │   └── init.sql
│   ├── nginx/
│   │   └── default.conf
│   └── php/
│       └── Dockerfile
└── src/                                  # Código-fonte da aplicação
    ├── composer.json
    ├── composer.lock
    ├── app/
    │   ├── Config/
    │   │   ├── config.php                # Configurações gerais (DB, paths)
    │   │   └── routes.php                # Definição de rotas (API + web)
    │   ├── Controllers/
    │   │   ├── ApiController.php         # Endpoints REST da API
    │   │   ├── DashboardController.php   # Interface de gestão
    │   │   └── HomeController.php        # Página inicial
    │   ├── Core/                         # Núcleo do framework MVC
    │   │   ├── Autoloader.php
    │   │   ├── Controller.php
    │   │   ├── Database.php              # Abstração PDO
    │   │   ├── Model.php
    │   │   └── Router.php
    │   ├── ML/                           # Motor de Inteligência Artificial
    │   │   ├── DataCollector.php
    │   │   ├── DataPreprocessor.php
    │   │   ├── ModelTrainer.php
    │   │   ├── ModelEvaluator.php
    │   │   ├── classify_image.php
    │   │   ├── train_model.php
    │   │   ├── Dataset/                  # Dados de treino
    │   │   └── models/                   # Modelos treinados serializados
    │   ├── Models/
    │   │   ├── Identification.php        # Entidade de identificação
    │   │   └── Species.php               # Entidade de espécie
    │   ├── Services/
    │   │   ├── ImageUploadService.php    # Validação e armazenamento de uploads
    │   │   ├── ImageClassificationService.php   # Orquestração do reconhecimento
    │   │   └── RubixMLClassificationService.php # Integração com RubixML
    │   └── Views/
    │       ├── dashboard/
    │       │   └── index.php
    │       └── home/
    │           └── index.php
    └── public/                           # Document root (Nginx)
        ├── index.php                     # Front controller
        ├── server_router.php             # Roteador para PHP built-in server
        ├── css/
        │   └── styles.css
        ├── js/
        │   ├── main.js
        │   └── dashboard.js
        ├── img/                          # Imagens estáticas
        └── uploads/
            └── images/                   # Imagens enviadas pelos usuários
```

## Endpoints da API

**Autenticação:** Todos os endpoints exigem o header `Authorization: Bearer {api-key}`.

| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/api/identify` | Envia imagem para identificação por IA |
| GET | `/api/species` | Lista espécies cadastradas |
| GET | `/api/identifications` | Lista todas as identificações |
| GET | `/api/identifications/pending` | Lista identificações pendentes de validação |
| POST | `/api/identifications/validate` | Valida manualmente uma identificação |

---

### `POST /api/identify`

- **Body:** `multipart/form-data` — campo `image` (arquivo)
- **Resposta (alta confiança):**
  ```json
  {
    "identification_id": 1,
    "image_url": "/uploads/images/img_xxx.jpeg",
    "confidence": 0.92,
    "status": "success",
    "species": "Hydrochoerus hydrochaeris"
  }
  ```
- **Resposta (baixa confiança):**
  ```json
  {
    "identification_id": 2,
    "image_url": "/uploads/images/img_yyy.jpeg",
    "confidence": 0.41,
    "status": "pending_validation",
    "message": "Identificação requer análise de especialista"
  }
  ```

---

### `GET /api/species`

- **Query param opcional:** `type` — filtra por tipo (`fauna` / `flora`)
- **Resposta:**
  ```json
  {
    "species": [...]
  }
  ```

---

### `GET /api/identifications`

- **Query params opcionais:**
    - `status` — filtra por status (`success`, `pending_validation`, `validated`)
    - `formato=excel` — exporta os dados como arquivo `.xlsx` (download)
- **Resposta (JSON):**
  ```json
  {
    "identifications": [...]
  }
  ```
- **Resposta (Excel):** download do arquivo `identificacoes.xlsx`

---

### `GET /api/identifications/pending`

- **Resposta:**
  ```json
  {
    "pending_identifications": [...]
  }
  ```

---

### `POST /api/identifications/validate`

- **Body:** `application/json`
  ```json
  {
    "identification_id": 2,
    "species_id": 5
  }
  ```
- **Resposta (sucesso):**
  ```json
  {
    "message": "Identificação validada com sucesso"
  }
  ```
- **Resposta (erro):**
  ```json
  {
    "error": "Erro ao validar identificação"
  }
  ```

---

## Status possíveis de uma identificação

| Status | Descrição |
|--------|-----------|
| `success` | Identificada automaticamente com alta confiança pela IA |
| `pending_validation` | Confiança insuficiente — aguarda validação de especialista |
| `validated` | Validada manualmente por um especialista |

---

## Espécies no Dataset Atual

| Tipo | Rótulo (pasta) | Nome exibido |
|------|---------------|--------------|
| fauna | `capivara` | Capivara |
| fauna | `graxaim` | Graxaim |
| fauna | `quero-quero` | Quero-quero |
| flora | `babosa_do_campo` | Babosa-do-campo |
| flora | `trevo_nativo` | Trevo-nativo *(previsto, sem imagens ainda)* |

> Mapeamento definido em `RubixMLClassificationService::$speciesMapping`.  
> Limiar de confiança: **0.7** (configurável em `app/Config/config.php`).

---

## Comandos Docker

O ambiente roda em Docker com três containers: `carbonifera_php`, `carbonifera_nginx` e `carbonifera_mysql`.  
O diretório `src/` é montado em `/var/www/html` no container PHP.

| Ação | Comando |
|------|---------|
| Subir ambiente | `docker compose up -d` |
| Derrubar ambiente | `docker compose down` |
| Treinar modelo (padrão) | `docker exec carbonifera_php php app/ML/train_model.php` |
| Treinar com opções | `docker exec carbonifera_php php app/ML/train_model.php --epochs=200 --batch=64 --kfold=5` |
| Instalar dependências | `docker exec carbonifera_php composer install` |
| Acessar container PHP | `docker exec -it carbonifera_php bash` |
| Logs do PHP | `docker logs carbonifera_php` |
| Logs do Nginx | `docker logs carbonifera_nginx` |

### Parâmetros do script de treinamento

| Parâmetro | Padrão | Descrição |
|-----------|--------|-----------|
| `--epochs=N` | `100` | Número de épocas |
| `--batch=N` | `32` | Tamanho do batch |
| `--kfold=N` | desativado | Ativa K-Fold Cross Validation com N folds |
| `--output=path` | `app/ML/models/species_classifier.rbx` | Caminho de saída do modelo |

