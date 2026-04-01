## 3. Definição da Arquitetura do Web Service e Fluxo de Dados

Baseado na análise de requisitos e na pesquisa de soluções, esta seção descreve a arquitetura proposta para o Web Service de identificação de espécies e o fluxo de dados associado, adotando a abordagem híbrida recomendada.

### 3.1. Arquitetura Proposta: Híbrida com API Externa de IA

A arquitetura recomendada utiliza um backend PHP responsável pela orquestração e lógica de negócio, integrando-se a um serviço externo especializado em Visão Computacional (IA) para a tarefa de classificação de imagens.

**Componentes Principais:**

1.  **Cliente (Aplicação Web/Mobile):** Interface utilizada pelo usuário final para enviar a imagem (fora do escopo deste Web Service, mas é quem consome a API).
2.  **Web Server (ex: Nginx, Apache):** Recebe as requisições HTTP e as direciona para a aplicação PHP.
3.  **Aplicação PHP (Backend):**
    *   **Framework PHP (Recomendado: Laravel ou Symfony):** Fornece a estrutura base para roteamento, controllers, services, ORM, etc., facilitando o desenvolvimento, organização e manutenção.
    *   **API Endpoint (`/api/identify`):** Ponto de entrada para as requisições de identificação. Responsável por receber a requisição POST com a imagem e a chave de API.
    *   **Middleware de Autenticação:** Verifica a validade da chave de API fornecida no cabeçalho da requisição.
    *   **Controller de Identificação:** Orquestra o fluxo principal: recebe a imagem, chama os serviços necessários e formata a resposta.
    *   **Serviço de Validação de Imagem:** Realiza validações básicas no arquivo recebido (tamanho, tipo MIME permitido - ex: image/jpeg, image/png).
    *   **Serviço de Armazenamento Temporário:** Salva a imagem recebida em um local temporário (disco local ou preferencialmente um serviço de armazenamento de objetos como AWS S3, Google Cloud Storage) para processamento.
    *   **Cliente da API de IA (PHP):** Classe dedicada a interagir com a API do serviço de IA externo escolhido (ex: Google Cloud Vision AI, AWS Rekognition). Utiliza bibliotecas como GuzzleHttp ou SDKs oficiais para enviar a imagem (ou sua URL) e tratar a resposta.
    *   **Serviço de Lógica de Identificação:** Contém a lógica principal:
        *   Chama o Cliente da API de IA.
        *   Recebe a predição (espécie, confiança).
        *   Compara a confiança com um limiar pré-definido (configurável).
        *   Decide se a identificação foi bem-sucedida ou se requer validação humana.
    *   **Serviço de Banco de Dados (ORM - ex: Eloquent, Doctrine):** Interface para interagir com a base de dados do Carbonífera Biodiversa. Responsável por:
        *   Salvar registros de identificações bem-sucedidas.
        *   Marcar imagens/registros que necessitam de validação por especialista.
        *   Consultar informações sobre espécies.
    *   **Módulo/Tabela de Validação:** Armazena as informações das imagens que aguardam validação por especialistas (referência da imagem, data, possível predição da IA, status).
4.  **Serviço Externo de IA (Visão Computacional):** Plataforma como Google Cloud Vision AI (com AutoML Custom Vision), AWS Rekognition (com Custom Labels), Azure Computer Vision, etc. Responsável por receber a imagem e retornar a classificação da espécie e o nível de confiança. O treinamento de um modelo customizado com imagens da fauna da Região Carbonífera é crucial aqui.
5.  **Banco de Dados (ex: MySQL, PostgreSQL):** Armazena os dados da plataforma Carbonífera Biodiversa, incluindo informações sobre espécies, registros de identificação e a fila de validação.
6.  **Interface de Validação para Especialistas:** Uma interface (provavelmente parte da plataforma Carbonífera Biodiversa) onde especialistas podem revisar as imagens pendentes e confirmar/corrigir a identificação (interage com o Módulo/Tabela de Validação).

### 3.2. Fluxo de Dados Detalhado

1.  **Requisição:** O Cliente envia uma requisição `POST` para `/api/identify`, incluindo a imagem no corpo (multipart/form-data) e uma `Authorization: Bearer <API_KEY>` no cabeçalho.
2.  **Autenticação:** O Middleware de Autenticação valida a `<API_KEY>`.
3.  **Recebimento e Validação:** O Controller de Identificação recebe a requisição. O Serviço de Validação de Imagem verifica o tipo e tamanho do arquivo.
4.  **Armazenamento Temporário:** A imagem validada é salva temporariamente pelo Serviço de Armazenamento.
5.  **Chamada à IA:** O Controller invoca o Serviço de Lógica de Identificação, passando a referência da imagem armazenada.
6.  **Interação com API Externa:** O Serviço de Lógica de Identificação utiliza o Cliente da API de IA para enviar a imagem ao Serviço Externo de IA.
7.  **Resposta da IA:** O Serviço Externo de IA processa a imagem e retorna a espécie prevista e a pontuação de confiança.
8.  **Processamento da Resposta:** O Cliente da API de IA recebe e parseia a resposta. O Serviço de Lógica de Identificação avalia a confiança:
    *   **Confiança Alta (>= Limiar):**
        *   O Serviço de Banco de Dados é chamado para registrar a identificação como bem-sucedida (ID da imagem, espécie identificada, confiança, timestamp).
        *   Uma resposta JSON de sucesso é preparada: `{"status": "success", "species": "Nome da Espécie", "confidence": 0.95}`.
    *   **Confiança Baixa (< Limiar):**
        *   O Serviço de Banco de Dados é chamado para adicionar a imagem à fila de validação (ID da imagem, predição da IA se houver, timestamp, status = 'pending_validation').
        *   Uma resposta JSON indicando pendência é preparada: `{"status": "pending_validation", "message": "Identificação requer análise de especialista."}`.
9.  **Resposta ao Cliente:** O Controller envia a resposta JSON formatada de volta ao Cliente.
10. **(Fluxo Assíncrono/Separado) Validação por Especialista:**
    *   Especialistas acessam a Interface de Validação.
    *   A interface consulta o Módulo/Tabela de Validação por registros pendentes.
    *   O especialista visualiza a imagem, confirma ou corrige a espécie.
    *   A interface chama o Serviço de Banco de Dados para atualizar o registro (status = 'validated', espécie confirmada) e potencialmente mover os dados para a tabela principal de ocorrências.

### 3.3. Considerações Adicionais

*   **Segurança:** Além da chave de API, garantir HTTPS, validação de input, proteção contra upload de arquivos maliciosos.
*   **Configuração:** O limiar de confiança da IA e as credenciais da API externa devem ser configuráveis (ex: via variáveis de ambiente).
*   **Logs:** Implementar logs detalhados para rastrear requisições, respostas da IA e erros.
*   **Escalabilidade:** A arquitetura com serviço externo é inerentemente mais escalável. Se o backend PHP se tornar um gargalo, pode ser escalado horizontalmente (múltiplas instâncias).

