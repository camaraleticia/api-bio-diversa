# Informações Necessárias para Produção do Capítulo 4 — Metodologia e Solução Proposta

Este documento reúne as principais informações técnicas e metodológicas necessárias para elaboração consistente do Capítulo 4 do Trabalho de Conclusão de Curso.

---

## 4.1 Caracterização da Pesquisa

- **Natureza da pesquisa:** Aplicada — produção de um artefato tecnológico funcional (API REST com integração de Machine Learning)
- **Tipo:** Pesquisa tecnológica experimental, com desenvolvimento iterativo em duas fases (prova de conceito com serviço externo e implementação local com modelo próprio)
- **Objetivo metodológico:** Desenvolver e avaliar uma solução de software capaz de automatizar o reconhecimento de espécies da fauna e flora regional por meio de análise de imagens, integrando inteligência artificial ao fluxo de validação humana especializada

---

## 4.2 Arquitetura Geral da Solução

### Stack Tecnológica

| Camada | Tecnologia |
|--------|-----------|
| Linguagem backend | PHP 8.4 |
| Linguagem frontend | HTML5, CSS3, JavaScript (Vanilla ES2022 com `async/await`) |
| Arquitetura backend | MVC (Model–View–Controller) sem framework externo |
| Banco de dados | MySQL 8.0 |
| Servidor web | Nginx (stable-alpine) |
| Runtime PHP | PHP-FPM (Alpine) |
| Gerenciador de dependências | Composer |
| Biblioteca de IA | RubixML `^2.5` |
| Exportação de dados | PhpSpreadsheet `^5.1` |
| Autenticação | Bearer Token (API Key estática) |
| Infraestrutura | Docker (3 containers: `carbonifera_php`, `carbonifera_nginx`, `carbonifera_mysql`) |
| Hospedagem | Ambiente local/desenvolvimento via Docker Desktop |

### Fluxo de Funcionamento

1. **Envio da imagem:** O usuário seleciona uma imagem na página inicial (`/`). O frontend faz uma requisição `POST multipart/form-data` para `/api/identify` com o header `Authorization: Bearer cb2023-test-api-key`
2. **Comunicação frontend/API:** A requisição é processada pelo front controller (`public/index.php`), roteada pelo `Router` para `ApiController::identify()`
3. **Validação e armazenamento:** `ImageUploadService` valida MIME type (`image/jpeg`, `image/png`, `image/webp`), extensão (`jpg`/`jpeg`), tamanho máximo (5 MB) e salva o arquivo com nome único (`uniqid('img_', true)`) em `public/uploads/images/`
4. **Processamento pela IA:** `ImageClassificationService` aciona `RubixMLClassificationService`, que carrega o modelo serializado (`species_classifier.rbx` ou `.rbx.gz`), pré-processa a imagem (16×16 grayscale → 256 features) e executa a predição
5. **Retorno da classificação:** A API retorna JSON com `identification_id`, `image_url`, `confidence` e `status`. Se `confidence >= 0.5` (threshold configurável): `status: "success"` com `species`. Se abaixo: `status: "pending_validation"` com `suggestion` (melhor palpite da IA, não confirmado)
6. **Encaminhamento ao especialista:** Identificações com `status: "pending_validation"` ficam disponíveis no dashboard (`/dashboard`), que consome `GET /api/identifications/pending`. O especialista visualiza a imagem, seleciona a espécie correta e submete via `POST /api/identifications/validate`, atualizando o status para `"validated"`

---

## 4.3 Desenvolvimento do Frontend

### Funcionamento do Upload (`main.js`)

- Preview local da imagem via `FileReader.readAsDataURL()` antes do envio
- Envio assíncrono via `fetch()` com `FormData` (campo `image`)
- Leitura da resposta como texto antes do parse JSON para capturar erros de servidor
- Exibição condicional: `status === "success"` → exibe espécie identificada com percentual de confiança; `status === "pending_validation"` → exibe mensagem de pendência, percentual de confiança e sugestão da IA com aviso `(não confirmado)`

### Validações no frontend

- Verificação de arquivo selecionado antes do envio
- Tratamento de erro HTTP (`!response.ok`)
- Captura de JSON inválido na resposta

### Tela para especialistas (`dashboard.js`)

- Carrega automaticamente identificações pendentes via `GET /api/identifications/pending`
- Carrega lista de espécies via `GET /api/species` e agrupa por tipo (Fauna / Flora) em `<optgroup>`
- Exibe cards com imagem, data e percentual de confiança para cada identificação pendente
- Modal de validação: especialista seleciona a espécie correta de um `<select>` e confirma; a requisição `POST /api/identifications/validate` envia `identification_id` e `species_id` em JSON
- Após validação bem-sucedida, recarrega automaticamente a lista de pendentes

### Tecnologias do frontend

- JavaScript puro (sem frameworks)
- Fetch API para requisições assíncronas
- CSS customizado (`public/css/styles.css`)

---

## 4.4 Desenvolvimento da API

### Endpoints implementados

| Método | Rota | Controller::Método | Descrição |
|--------|------|--------------------|-----------|
| POST | `/api/identify` | `ApiController::identify` | Recebe imagem e retorna classificação |
| GET | `/api/species` | `ApiController::listSpecies` | Lista espécies (filtro: `?type=fauna\|flora`) |
| GET | `/api/identifications` | `ApiController::listIdentifications` | Lista identificações (filtros: `?status=`, `?formato=excel`) |
| GET | `/api/identifications/pending` | `ApiController::listPendingIdentifications` | Lista identificações pendentes |
| POST | `/api/identifications/validate` | `ApiController::validateIdentification` | Valida manualmente uma identificação |

**Autenticação:** todos os endpoints verificam `Authorization: Bearer {api-key}` via `ApiController::checkApiKey()`. A chave válida em desenvolvimento é `cb2023-test-api-key`, definida no banco de dados (`api_keys`) e comparada no controller.

### Estrutura JSON de resposta — `POST /api/identify`

**Alta confiança (`confidence >= threshold`):**
```json
{
  "identification_id": 1,
  "image_url": "/uploads/images/img_xxx.jpeg",
  "confidence": 0.92,
  "status": "success",
  "species": "Capivara"
}
```

**Baixa confiança:**
```json
{
  "identification_id": 2,
  "image_url": "/uploads/images/img_yyy.jpeg",
  "confidence": 0.41,
  "status": "pending_validation",
  "message": "Identificação requer análise de especialista",
  "suggestion": "Graxaim"
}
```

### Organização MVC

**Controllers** (`app/Controllers/`) — responsabilidade de receber requisição, delegar e retornar resposta JSON:
- `ApiController` — todos os endpoints REST
- `DashboardController` — renderiza view do painel de especialistas
- `HomeController` — renderiza view da página inicial

**Services** (`app/Services/`) — regras de negócio isoladas dos controllers:
- `ImageUploadService` — validação de MIME type, extensão, tamanho e persistência de arquivo
- `ImageClassificationService` — orquestração da classificação (delega para `RubixMLClassificationService`)
- `RubixMLClassificationService` — carregamento do modelo, pré-processamento da imagem, inferência e mapeamento de rótulos

**Models** (`app/Models/`) — acesso ao banco de dados via PDO:
- `Identification` — CRUD da tabela `identifications`; métodos: `findByStatus()`, `findPending()`, `updateStatus()`
- `Species` — consultas na tabela `species`; métodos: `findByType()`, `findByName()`
- `Model` (base) — métodos genéricos: `findAll()`, `findById()`, `create()`, `update()`, `delete()`

**Core** (`app/Core/`):
- `Router` — roteamento por método HTTP e URI
- `Database` — singleton PDO com conexão MySQL
- `Controller` — métodos base `render()` e `json()`
- `Autoloader` — PSR-4 para `App\` mapeado em `app/`

### Banco de dados

Três tabelas definidas em `docker/mysql/init.sql`:

- **`species`** — `id`, `name`, `scientific_name`, `type` (ENUM fauna/flora), `description`, `created_at`
- **`identifications`** — `id`, `image_path`, `species_id` (FK → species), `confidence` (FLOAT), `status` (ENUM: success/pending_validation/validated), `created_at`, `updated_at`
- **`api_keys`** — `id`, `api_key`, `description`, `active`, `created_at`

### Armazenamento de imagens

Imagens salvas em `public/uploads/images/` com nome gerado por `uniqid('img_', true)` preservando a extensão original. Tamanho máximo: 5 MB. Formatos aceitos: JPEG, PNG, WebP.

---

## 4.5 Primeira Fase — Google Cloud Vision

- **Objetivo:** Validar o fluxo completo de envio de imagem → análise → retorno de resultado antes de investir no desenvolvimento do modelo local
- **Resultados obtidos:** O fluxo técnico foi validado — upload, requisição à API externa, leitura do score de confiança, registro no banco de dados e retorno ao frontend funcionaram corretamente
- **Limitações encontradas:**
  - API não especializada em fauna e flora regional do Rio Grande do Sul
  - Reconhecimento impreciso para espécies locais, especialmente Graxaim e Quero-quero
  - Dependência de serviço externo pago com custo por requisição
  - Necessidade de envio de imagens a servidor terceiro — limitação de privacidade e autonomia
  - Latência de rede em cada classificação
- **Motivos da substituição:** Necessidade de especialização regional, redução de custos, autonomia total sobre o modelo e possibilidade de evolução contínua com o dataset gerado pelos próprios usuários da plataforma

---

## 4.6 Segunda Fase — RubixML

- **Algoritmo utilizado:** `MultilayerPerceptron` (rede neural feedforward multicamadas) com pipeline `ZScaleStandardizer → MultilayerPerceptron`

- **Arquitetura da rede:**
  ```
  Dense(128) → LeakyReLU → Dropout(0.2) → Dense(32) → LeakyReLU
  ```
  Otimizador: `Adam(lr=0.001)`

- **Processo de treinamento:**
  1. Leitura das imagens por `glob()` das pastas do dataset
  2. Pré-processamento de cada imagem
  3. Divisão estratificada 80% treino / 20% teste
  4. Treinamento com `MultilayerPerceptron::train()`
  5. Avaliação: acurácia, Macro F1, métricas por classe e matriz de confusão
  6. Serialização do modelo em `.rbx` e `.rbx.gz` (gzip)
  7. Geração de relatório `training_report.json` e `training_report.xlsx`

  Parâmetros disponíveis no script de treinamento:

  | Parâmetro | Padrão | Descrição |
  |-----------|--------|-----------|
  | `--epochs=N` | `100` | Número de épocas de treinamento |
  | `--batch=N` | `32` | Tamanho do mini-batch |
  | `--kfold=N` | desativado | Ativa K-Fold Cross Validation com N folds |
  | `--output=path` | `app/ML/models/species_classifier.rbx` | Caminho de saída do modelo |
  | `--grayscale` | não definido (RGB) | Quando informado, converte imagens para escala de cinza (256 features); quando omitido, utiliza canais RGB completos (768 features) |

  Parâmetros recomendados com dataset atual em escala de cinza:
  ```bash
  docker exec carbonifera_php php app/ML/train_model.php --epochs=300 --batch=16 --kfold=5 --grayscale
  ```

  Parâmetros recomendados com dataset atual em RGB (cores):
  ```bash
  docker exec carbonifera_php php app/ML/train_model.php --epochs=300 --batch=16 --kfold=5
  ```

- **Quantidade de imagens:** 70 imagens por espécie (210 total para 3 espécies ativas no dataset)

- **Quantidade de espécies no dataset de treinamento:** 3 (Capivara, Graxaim, Quero-quero)

- **Espécies mapeadas no serviço de inferência:** 5 (incluindo Babosa-do-campo e Trevo-nativo, preparadas para inclusão futura)

- **Estrutura do dataset:**
  ```
  app/ML/Dataset/
  ├── fauna/
  │   ├── capivara/        (70 imagens .jpg/.jpeg)
  │   ├── graxaim/         (70 imagens .jpg/.jpeg)
  │   └── quero-quero/     (70 imagens .jpg/.jpeg)
  └── flora/
      └── babosa_do_campo/ (sem imagens de treino ainda)
  ```

- **Pré-processamento** (idêntico em treinamento e inferência, modo detectado automaticamente via `training_report.json`):
  1. Leitura do arquivo via `file_get_contents()` + `imagecreatefromstring()` (extensão GD)
  2. Criação de canvas `imagecreatetruecolor(16, 16)` com fundo branco (suporte a PNG com transparência)
  3. Redimensionamento para 16×16 pixels via `imagecopyresampled()`
  4. **Modo escala de cinza** (`--grayscale`): luminância ponderada `gray = (0.299·R + 0.587·G + 0.114·B) / 255` → vetor de **256 features**
  5. **Modo RGB** (padrão, sem `--grayscale`): canais separados `R/255`, `G/255`, `B/255` por pixel → vetor de **768 features**; dropout aumentado de 0.2 para 0.4 para compensar maior dimensionalidade com dataset pequeno
  6. Validação: `count($vec) !== esperado` → descarte da amostra com log de aviso
  7. O modo utilizado no treinamento é registrado em `training_report.json` (`color_mode`: `"grayscale"` ou `"rgb"`, `features_per_sample`: `256` ou `768`). Na inferência, `RubixMLClassificationService` lê esse arquivo e sincroniza automaticamente o pré-processamento — sem necessidade de configuração manual

- **Salvamento do modelo:**
  - Formato primário: `app/ML/models/species_classifier.rbx` (RubixML `PersistentModel` + `Filesystem`)
  - Formato comprimido: `app/ML/models/species_classifier.rbx.gz` (gzip nível 9)
  - Na inferência, o arquivo `.gz` tem prioridade; se existir, é descomprimido para arquivo temporário em `sys_get_temp_dir()` e carregado pelo mecanismo oficial do RubixML
  - O modelo é mantido em cache estático (`self::$cachedModel`) para evitar recarregamento a cada requisição

- **Critério de confiança:**
  - Threshold configurável em `src/app/Config/config.php` → `ai_service.confidence_threshold`
  - Valor atual: `0.5` (50%)
  - `confidence >= threshold` → `status: "success"`, espécie retornada em `species`
  - `confidence < threshold` → `status: "pending_validation"`, melhor palpite retornado em `suggestion`

---

## 4.7 Fluxo de Validação Humana

### Área dos especialistas (`/dashboard`)

- Interface web acessada em `/dashboard`, renderizada por `DashboardController`
- Ao carregar, consome automaticamente `GET /api/identifications/pending` e `GET /api/species`
- Exibe cards com: imagem enviada, número da identificação, data/hora e percentual de confiança da IA

### Processo de validação

1. Especialista visualiza a imagem e o palpite da IA
2. Clica em "Validar" → abre modal com a imagem em tamanho maior
3. Seleciona a espécie correta em um `<select>` agrupado por fauna/flora (inclui nome científico)
4. Confirma → requisição `POST /api/identifications/validate` com body:
   ```json
   { "identification_id": 2, "species_id": 5 }
   ```
5. `Identification::updateStatus()` atualiza: `status = 'validated'`, `species_id = {id selecionado}`
6. Dashboard recarrega automaticamente a lista de pendentes

### Atualização do dataset

Imagens validadas são armazenadas em `public/uploads/images/` com identificação rastreável no banco. Para incorporá-las ao treinamento:
1. Copiar o arquivo para `app/ML/Dataset/{tipo}/{espécie}/`
2. Re-executar o script de treinamento via Docker
3. O novo modelo substitui o anterior em `app/ML/models/`

---

## 4.8 Infraestrutura

| Item | Detalhe |
|------|---------|
| Sistema operacional (dev) | Windows com Docker Desktop (filesystem VirtioFS) |
| Orquestração de containers | Docker Compose (3 serviços) |
| Servidor web | Nginx stable-alpine — porta 8080:80 — document root `/var/www/html/public` |
| Runtime PHP | PHP 8.4-fpm-alpine com extensões: `pdo`, `pdo_mysql`, `mysqli`, `gd`, `zip`, `mbstring`, `exif`, `pcntl` |
| Banco de dados | MySQL 8.0 — porta 3306:3306 — volume persistente `mysql_data` |
| Memória PHP (CLI) | 512 MB (`/usr/local/etc/php/conf.d/zz-custom.ini`) |
| Tempo de execução CLI | Ilimitado (para scripts de treinamento) |
| Roteamento Nginx | `try_files $uri $uri/ /index.php?$query_string` — todas as rotas passam pelo front controller |
| Comunicação Nginx → PHP | FastCGI na porta 9000 (`fastcgi_pass php:9000`) |
| Estrutura do servidor | Volume `./src` montado em `/var/www/html`; `./src/public/uploads/images` montado adicionalmente para acesso direto pelo Nginx |

---

## 4.9 Resultados e Limitações

### Funcionalidades implementadas e testadas

- Upload e validação de imagem (tipo MIME, extensão, tamanho)
- Classificação automática com retorno de espécie e percentual de confiança
- Retorno de sugestão não confirmada quando confiança abaixo do threshold
- Registro de toda identificação no banco de dados
- Dashboard de validação para especialistas com modal de seleção de espécie
- Exportação de identificações em planilha `.xlsx`
- Filtro de identificações por status via query param
- Relatório de treinamento em JSON e Excel com métricas por classe e matriz de confusão
- Compressão gzip do modelo serializado com verificação de integridade SHA256

### Limitações do dataset

- 70 imagens por espécie — volume reduzido para treinamento de rede neural
- Todas as imagens são da mesma região geográfica, podendo não representar variações de indivíduos
- Resolução de entrada 16×16 pixels descarta textura e detalhes finos das espécies
- Acurácia estimada entre 50–75% com o dataset atual, insuficiente para uso em produção com alta confiança

### Problemas encontrados e soluções aplicadas

| Problema | Causa identificada | Solução aplicada |
|----------|-------------------|-----------------|
| Leitura de apenas 20 imagens por espécie (mesmo com 70 arquivos nas pastas) | Bug de `DirectoryIterator` com filesystem VirtioFS do Docker Desktop no Windows (truncava listagem em ~20 entradas) | Substituído por `glob()` que usa chamadas de sistema diferentes e retorna todos os arquivos corretamente |
| Vetores de zeros inseridos silenciosamente no dataset (dados corrompidos) | `imagecreatetruecolor()` podia retornar `false` sem verificação | Adicionada verificação de retorno com `imagedestroy($image)` e `return null` no caminho de falha |
| Falhas de leitura de imagem sem diagnóstico | Operador `@` suprimia erros em `file_get_contents()` | Substituído por captura explícita com log `[AVISO]` e contador de falhas por espécie |
| Desbalanceamento de classes (graxaim com ~110 amostras vs ~70 nas demais) | Arquivos `_invertida` gerados por augmentation manual incluídos no dataset | Arquivos `_invertida` removidos; todas as classes niveladas em 70 imagens |
| PNG com canal alpha gerando pixels escuros inconsistentes | Canvas criado sem fundo definido — transparência composta sobre preto | Canvas iniciado com fundo branco via `imagefilledrectangle()` antes do `imagecopyresampled()` |

### Observação importante

O texto acadêmico deve refletir exatamente a implementação real da aplicação. O modelo utilizado é `MultilayerPerceptron` da biblioteca RubixML — **não** uma CNN (Convolutional Neural Network) nem Deep Learning no sentido convencional. As features de entrada são pixels em escala de cinza em baixa resolução (16×16), sem convoluções ou extração automática de características visuais.
