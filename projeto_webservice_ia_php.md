# Projeto de Implementação: Web Service de Identificação de Espécies com IA em PHP

**Para:** Aluna Desenvolvedora do TCC
**De:** Manus AI
**Data:** 25 de Maio de 2025

## Introdução

Este documento consolida o plano de projeto para o desenvolvimento do Web Service (API) com Inteligência Artificial (IA) proposto no Trabalho de Conclusão de Curso "Carbonífera Biodiversa: Um Catálogo Vivo para Conservação". O objetivo central é criar uma ferramenta em PHP capaz de receber imagens de animais da Região Carbonífera, identificá-los usando IA e integrar os resultados à plataforma existente, incluindo um fluxo para validação por especialistas quando necessário.

Seguindo as diretrizes solicitadas, este plano detalha os requisitos, explora as opções tecnológicas, define uma arquitetura robusta, fornece um guia de implementação passo a passo com recomendações técnicas específicas para PHP e estabelece um plano de testes abrangente. A estrutura visa orientar a implementação de forma clara e organizada, promovendo as melhores práticas de desenvolvimento.

---



## 1. Análise Detalhada dos Requisitos

Esta seção detalha os requisitos funcionais e não funcionais para o desenvolvimento do Web Service de identificação de espécies com IA, baseado na descrição inicial do projeto.

### 1.1. Requisitos Funcionais

Os requisitos funcionais descrevem as funcionalidades específicas que o Web Service deve oferecer:

*   **RF01 - Recebimento de Imagem:** O Web Service deve disponibilizar um endpoint (API) capaz de receber requisições contendo uma imagem (foto de um animal) enviada pelo usuário. O formato da imagem (JPEG, PNG, etc.) e o tamanho máximo devem ser definidos.
*   **RF02 - Processamento da Imagem:** Após o recebimento, a imagem deve ser processada para adequação ao modelo de IA (ex: redimensionamento, normalização).
*   **RF03 - Identificação por IA:** O núcleo do serviço deve utilizar um modelo de Inteligência Artificial (aprendizado de máquina) treinado para classificar a espécie do animal presente na imagem. O foco principal são as espécies da fauna nativa da Região Carbonífera.
*   **RF04 - Integração com Banco de Dados:** As informações resultantes da identificação (espécie identificada, confiança da predição, dados da imagem, etc.) devem ser armazenadas ou atualizadas na base de dados do projeto Carbonífera Biodiversa.
*   **RF05 - Tratamento de Falhas na Identificação:** Implementar um fluxo para casos onde a IA não consiga identificar a espécie com um nível de confiança mínimo pré-definido. Nestes casos, a imagem e informações associadas devem ser encaminhadas para um processo de validação manual por especialistas vinculados ao projeto.
*   **RF06 - Resposta da API:** O Web Service deve retornar uma resposta estruturada (ex: JSON) para o cliente que fez a requisição. A resposta deve conter a espécie identificada (se aplicável), o nível de confiança, ou um status indicando que a identificação falhou ou está pendente de validação por especialista.

### 1.2. Requisitos Não Funcionais

Os requisitos não funcionais definem critérios de qualidade e restrições do sistema:

*   **RNF01 - Tecnologia Principal:** O desenvolvimento do back-end do Web Service deve ser realizado utilizando a linguagem PHP. É permitido o uso de frameworks PHP (como Laravel, Symfony, etc.) e bibliotecas externas, incluindo aquelas para integração com serviços de IA.
*   **RNF02 - Desempenho:** O tempo de resposta da API, desde o envio da imagem até o recebimento do resultado da identificação, deve ser otimizado para proporcionar uma boa experiência ao usuário. Definir metas de tempo de resposta (ex: < 5 segundos para a maioria das requisições).
*   **RNF03 - Precisão da IA:** O modelo de IA deve atingir um nível de acurácia satisfatório na identificação das espécies alvo. Métricas de avaliação (precisão, recall, F1-score) devem ser definidas e monitoradas. A definição do limiar de confiança para encaminhamento à validação manual é crucial.
*   **RNF04 - Escalabilidade:** A arquitetura do Web Service deve ser planejada para suportar um aumento no volume de requisições e no tamanho da base de dados, conforme o projeto cresce.
*   **RNF05 - Manutenibilidade:** O código-fonte deve seguir boas práticas de desenvolvimento (ex: PSR standards para PHP), ser bem documentado (comentários, documentação da API), modular e testável, facilitando futuras manutenções e evoluções.
*   **RNF06 - Segurança:** Implementar medidas de segurança para proteger o Web Service contra vulnerabilidades comuns, como injeção de SQL, cross-site scripting (XSS), upload de arquivos maliciosos e acesso não autorizado à API (ex: autenticação por token/chave de API).
*   **RNF07 - Integração:** A API deve ser projetada de forma a facilitar a integração com a plataforma Carbonífera Biodiversa existente ou futuras aplicações cliente.
*   **RNF08 - Tratamento de Erros:** O Web Service deve lidar graciosamente com erros (ex: formato de imagem inválido, falhas na comunicação com serviços externos, erros internos) e retornar mensagens de erro claras e úteis.

### 1.3. Restrições

*   **R01 - Linguagem:** PHP é a linguagem mandatória para o back-end.
*   **R02 - Escopo da Fauna:** O foco inicial da IA é a fauna da Região Carbonífera.
*   **R03 - Validação Humana:** O sistema deve prever e suportar um fluxo de validação por especialistas.

Esta análise inicial serve como base para as próximas etapas do planejamento, como a pesquisa de tecnologias e a definição da arquitetura.

---



## 2. Pesquisa de Soluções em PHP e Bibliotecas de IA

Esta seção apresenta as opções pesquisadas para implementar a funcionalidade de classificação de imagens utilizando PHP, conforme os requisitos do projeto.

### 2.1. Bibliotecas Nativas de Machine Learning para PHP

Existem bibliotecas que buscam trazer funcionalidades de Machine Learning diretamente para o ecossistema PHP.

*   **PHP-ML (php-ai/php-ml):**
    *   **Descrição:** Uma biblioteca popular que oferece uma variedade de algoritmos de ML, incluindo classificação (SVC, k-NN, Naive Bayes), regressão, clustering e pré-processamento. Possui documentação razoável e exemplos disponíveis.
    *   **Vantagens:** Implementação puramente em PHP, fácil de instalar via Composer, API relativamente simples para algoritmos clássicos de ML.
    *   **Desvantagens:** O suporte a redes neurais profundas (essenciais para classificação de imagens complexa) parece limitado ou menos maduro comparado a bibliotecas Python. Pode exigir mais esforço para treinar modelos complexos de visão computacional e pode ter limitações de desempenho para inferência em tempo real com modelos grandes.
    *   **Link:** [https://php-ml.readthedocs.io/](https://php-ml.readthedocs.io/)

*   **Rubix ML (Rubix/ML):**
    *   **Descrição:** Apresenta-se como uma biblioteca de ML e Deep Learning de alto nível para PHP. Possui uma API amigável, mais de 40 algoritmos, suporte a ETL, pré-processamento e validação cruzada. Oferece também o Rubix Server para colocar modelos em produção.
    *   **Vantagens:** Foco em Deep Learning, o que é mais adequado para classificação de imagens. API parece moderna e bem documentada. Suporte comercial e para produção (Rubix Server).
    *   **Desvantagens:** Pode ter uma curva de aprendizado maior devido à complexidade dos algoritmos de Deep Learning. A comunidade pode ser menor em comparação com ecossistemas de IA mais estabelecidos (Python).
    *   **Link:** [https://rubixml.com/](https://rubixml.com/)

*   **Rindow Neural Networks:**
    *   **Descrição:** Focada especificamente em redes neurais para PHP, com sintaxe inspirada em bibliotecas Python como Keras. Inclui tutoriais para classificação de imagens.
    *   **Vantagens:** Especializada em redes neurais, potencialmente mais poderosa para tarefas de visão computacional dentro do PHP. Sintaxe familiar para quem conhece Keras.
    *   **Desvantagens:** Pode ser menos abrangente que as outras em termos de algoritmos clássicos de ML. A maturidade e o tamanho da comunidade podem ser fatores a considerar.
    *   **Link:** [https://rindow.github.io/neuralnetworks/](https://rindow.github.io/neuralnetworks/)

### 2.2. Integração com Serviços de IA Externos (Via API)

Uma abordagem alternativa e frequentemente mais prática é utilizar serviços de IA especializados em visão computacional, acessíveis via API.

*   **Descrição:** Plataformas como Google Cloud Vision AI, AWS Rekognition, Azure Computer Vision, ou mesmo modelos hospedados (ex: via TensorFlow Serving, PyTorch Serve) oferecem APIs RESTful robustas para classificação de imagens, detecção de objetos, etc. O PHP pode facilmente fazer requisições HTTP para essas APIs.
*   **Vantagens:**
    *   **Modelos Pré-treinados:** Acesso a modelos de última geração, frequentemente pré-treinados em vastos datasets (como ImageNet), que podem ser usados diretamente ou ajustados (fine-tuning).
    *   **Desempenho e Escalabilidade:** Esses serviços são otimizados para alto desempenho e escalabilidade, gerenciados pelo provedor.
    *   **Manutenção:** A complexidade do treinamento e manutenção do modelo de IA fica a cargo do provedor do serviço.
    *   **Facilidade de Integração:** Bibliotecas PHP como Guzzle HTTP facilitam o consumo de APIs REST.
*   **Desvantagens:**
    *   **Custo:** Geralmente são serviços pagos (embora muitos ofereçam níveis gratuitos generosos).
    *   **Dependência Externa:** Cria uma dependência de um serviço de terceiros.
    *   **Latência de Rede:** A comunicação via rede introduz latência adicional comparado a uma biblioteca local.
    *   **Customização:** A customização profunda do modelo pode ser mais limitada do que treinar um modelo próprio do zero.

### 2.3. Recomendação Preliminar

Considerando os requisitos do projeto (foco em PHP, necessidade de boa acurácia na identificação de espécies, fluxo de validação humana) e a complexidade inerente à classificação de imagens de fauna específica:

1.  **Abordagem Híbrida (Recomendada):** Utilizar um **serviço de IA externo via API** (como Google Cloud Vision AI com AutoML para treinar um modelo customizado nas espécies da Região Carbonífera, ou AWS Rekognition Custom Labels) parece a opção mais robusta e eficiente. O PHP seria responsável por: 
    *   Receber o upload da imagem.
    *   Pré-processar minimamente se necessário (ex: verificar formato).
    *   Enviar a imagem para a API do serviço de IA.
    *   Receber e interpretar a resposta (espécie, confiança).
    *   Integrar com o banco de dados do Carbonífera Biodiversa.
    *   Gerenciar o fluxo de envio para validação humana se a confiança for baixa.
2.  **Bibliotecas Nativas PHP:** Se a intenção for manter *todo* o processamento dentro do ambiente PHP e evitar dependências externas/custos, **Rubix ML** parece a opção nativa mais promissora devido ao seu foco em Deep Learning. No entanto, isso exigirá um esforço consideravelmente maior no treinamento, ajuste fino e gerenciamento do modelo de IA, além de potencialmente exigir mais recursos de servidor para a inferência.

Esta pesquisa servirá de base para a definição da arquitetura na próxima etapa.

---



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

---



## 4. Detalhamento das Etapas de Implementação e Recomendações Técnicas

Esta seção fornece um guia passo a passo e recomendações técnicas para a implementação do Web Service em PHP, seguindo a arquitetura definida.

### 4.1. Configuração do Ambiente de Desenvolvimento

1.  **Instalar PHP:** Certifique-se de ter uma versão recente do PHP instalada (>= 7.4 ou 8.x recomendado).
2.  **Instalar Composer:** Gerenciador de dependências para PHP ([https://getcomposer.org/](https://getcomposer.org/)).
3.  **Escolher um Framework (Recomendado):**
    *   **Laravel:** Oferece um ecossistema rico, ORM Eloquent, sistema de filas, facilidades para APIs (Sanctum para autenticação). É uma ótima escolha pela produtividade.
    *   **Symfony:** Mais modular e flexível, excelente para projetos complexos. ORM Doctrine é poderoso.
    *   *Se não usar framework:* Será necessário estruturar o projeto manualmente (roteamento, autoloading, etc.), o que aumenta a complexidade.
4.  **Banco de Dados:** Instalar e configurar um SGBD (MySQL/MariaDB ou PostgreSQL).
5.  **Servidor Web Local:** Configurar Nginx ou Apache para servir a aplicação PHP localmente.
6.  **Controle de Versão:** Utilizar Git desde o início do projeto.

### 4.2. Estrutura do Projeto (Exemplo com Laravel)

```
/app
  /Http
    /Controllers
      ApiController.php       # Controller para a API de identificação
    /Middleware
      AuthenticateApiKey.php  # Middleware para validar a chave de API
  /Services
    ImageValidationService.php # Serviço para validar uploads
    StorageService.php         # Serviço para lidar com armazenamento (local/S3)
    AiApiService.php           # Serviço para interagir com a API de IA externa
    IdentificationLogiService.php # Serviço com a lógica de negócio
  /Models                     # Modelos Eloquent (ex: IdentificationLog, Species)
/config                     # Arquivos de configuração (ex: api_keys.php, ai_service.php)
/database
  /migrations               # Migrations para criar tabelas
/routes
  api.php                   # Definição da rota /api/identify
/storage                    # Diretório para armazenamento (ex: uploads temporários)
.env                        # Variáveis de ambiente (chaves de API, config BD, etc.)
composer.json               # Dependências do projeto
```

### 4.3. Etapas de Implementação

1.  **Inicializar Projeto:** Crie o projeto com o framework escolhido (ex: `composer create-project laravel/laravel nome-do-projeto`).
2.  **Configurar Banco de Dados:** Defina as credenciais no arquivo `.env`.
3.  **Criar Migrations:** Defina as tabelas necessárias (ex: `identifications`, `pending_validations`, `api_keys`) e execute as migrations (`php artisan migrate` no Laravel).
4.  **Implementar Autenticação por API Key:**
    *   Crie a tabela `api_keys`.
    *   Implemente o middleware `AuthenticateApiKey.php` para verificar a chave enviada no cabeçalho `Authorization: Bearer <KEY>` contra a tabela.
    *   Registre o middleware na rota da API.
5.  **Definir Rota da API:** Em `routes/api.php`, defina a rota `POST /identify` apontando para um método no `ApiController`, protegida pelo middleware de autenticação.
    ```php
    // routes/api.php (Laravel)
    use App\Http\Controllers\ApiController;
    use App\Http\Middleware\AuthenticateApiKey;
    
    Route::middleware(AuthenticateApiKey::class)->post("/identify", [ApiController::class, "identify"]);
    ```
6.  **Implementar Controller (`ApiController`):**
    *   Receber a requisição (`Illuminate\Http\Request $request`).
    *   Validar a presença e o tipo do arquivo de imagem (`$request->validate([...])`).
    *   Chamar os serviços necessários (Validação, Armazenamento, Lógica de Identificação).
    *   Retornar a resposta JSON (`response()->json(...)`).
7.  **Implementar Serviço de Validação (`ImageValidationService`):**
    *   Verificar o tipo MIME (`$file->getMimeType()`).
    *   Verificar o tamanho do arquivo (`$file->getSize()`).
8.  **Implementar Serviço de Armazenamento (`StorageService`):**
    *   Receber o objeto de arquivo (`UploadedFile`).
    *   Salvar o arquivo em um local seguro (ex: `storage/app/uploads`) com um nome único.
    *   Retornar o caminho do arquivo salvo.
    *   *Opcional/Avançado:* Integrar com AWS S3 ou Google Cloud Storage para escalabilidade.
9.  **Implementar Cliente da API de IA (`AiApiService`):**
    *   Instalar Guzzle: `composer require guzzlehttp/guzzle`.
    *   Obter credenciais/chave da API do serviço de IA escolhido (Google Vision, AWS Rekognition, etc.) e configurá-las no `.env`.
    *   Criar método para enviar a imagem (arquivo ou URL) para a API externa usando Guzzle.
    *   Tratar a resposta (JSON), extraindo a espécie prevista e a confiança.
    *   Implementar tratamento de erros (falhas na comunicação, erros da API externa).
10. **Implementar Serviço de Lógica (`IdentificationLogiService`):**
    *   Receber o caminho da imagem armazenada.
    *   Chamar o `AiApiService` para obter a predição.
    *   Ler o limiar de confiança do arquivo de configuração ou `.env`.
    *   Aplicar a lógica: se confiança >= limiar, registrar sucesso no BD; senão, registrar para validação pendente.
    *   Interagir com os Models Eloquent para salvar os dados no banco.
    *   Retornar um resultado estruturado para o Controller.
11. **Criar Models Eloquent:** Defina os modelos para as tabelas (`IdentificationLog`, `PendingValidation`, `ApiKey`, etc.) para facilitar a interação com o banco de dados.
12. **Configuração:** Mova configurações sensíveis (chaves de API, limiar de confiança, credenciais BD) para o arquivo `.env`.

### 4.4. Recomendações Técnicas Adicionais

*   **Segurança:**
    *   **HTTPS:** Sempre use HTTPS para a API.
    *   **Validação de Input:** Valide rigorosamente todos os dados recebidos (imagem, parâmetros).
    *   **Upload Seguro:** Não confie no nome do arquivo enviado pelo cliente. Gere nomes únicos. Armazene uploads fora do diretório web público. Valide tipos MIME no servidor.
    *   **Rate Limiting:** Implemente limites de requisições por chave de API para prevenir abuso.
    *   **Tratamento de Erros:** Não exponha detalhes internos em mensagens de erro. Use códigos de status HTTP apropriados.
*   **Qualidade de Código:**
    *   **PSR Standards:** Siga os padrões PSR para PHP ([https://www.php-fig.org/psr/](https://www.php-fig.org/psr/)).
    *   **Injeção de Dependência:** Use a injeção de dependência do framework para gerenciar serviços.
    *   **Código Limpo:** Escreva código claro, comentado e bem organizado.
*   **Logging:** Use o sistema de logging do framework (ex: Monolog no Laravel/Symfony) para registrar eventos importantes, erros e requisições/respostas da API externa.
*   **Documentação da API:** Considere usar ferramentas como Swagger/OpenAPI para documentar a API, facilitando o consumo por outras aplicações.

Seguindo estas etapas e recomendações, a aluna terá um caminho claro para desenvolver um Web Service robusto, seguro e manutenível em PHP.

---



## 5. Estruturação do Plano de Testes e Validação da IA

Este plano descreve as estratégias e casos de teste para garantir a qualidade, funcionalidade e confiabilidade do Web Service de identificação de espécies, com foco especial na integração e validação da Inteligência Artificial.

### 5.1. Objetivos dos Testes

*   Verificar se todos os requisitos funcionais (RF01-RF06) e não funcionais (RNF01-RNF08) foram atendidos.
*   Garantir a correta integração entre o backend PHP e o serviço externo de IA.
*   Validar a precisão do modelo de IA na identificação das espécies alvo.
*   Assegurar que o fluxo de validação por especialistas funcione conforme o esperado.
*   Avaliar o desempenho e a segurança da API.
*   Identificar e corrigir bugs antes da implantação.

### 5.2. Tipos de Testes

1.  **Testes Unitários:** Foco em testar componentes isolados (classes, métodos, serviços) do código PHP. Ex: testar a lógica do `ImageValidationService`, métodos individuais do `IdentificationLogiService` (sem chamadas reais a APIs externas, usando mocks).
2.  **Testes de Integração:** Verificar a interação entre diferentes componentes do backend PHP. Ex: testar se o `ApiController` chama corretamente os serviços e se a interação com o banco de dados (via Models/ORM) funciona.
3.  **Testes de API (Funcionais):** Testar os endpoints da API (`/api/identify`) como um todo, simulando requisições de clientes. Verificar respostas HTTP, estrutura JSON e lógica de negócio ponta a ponta (incluindo a chamada real à API de IA em ambiente de teste/staging).
4.  **Testes de Validação da IA:** Avaliar o desempenho do modelo de IA com um conjunto de dados de teste específico.
5.  **Testes de Aceitação do Usuário (UAT):** Foco na validação do fluxo completo, especialmente o processo de validação manual pelos especialistas.
6.  **Testes de Desempenho:** Medir o tempo de resposta da API sob carga normal e, opcionalmente, sob estresse.
7.  **Testes de Segurança:** Identificar vulnerabilidades na API e na aplicação.

### 5.3. Ambiente de Teste

*   **Desenvolvimento:** Testes unitários e de integração executados localmente pelos desenvolvedores.
*   **Staging (Homologação):** Ambiente similar ao de produção para execução de testes de API, validação da IA, UAT e testes de desempenho/segurança. Deve ter acesso a uma versão de teste do serviço de IA externo e uma cópia (ou estrutura similar) do banco de dados.
*   **Produção:** Monitoramento contínuo após a implantação.

### 5.4. Ferramentas Sugeridas

*   **PHPUnit:** Framework padrão para testes unitários e de integração em PHP.
*   **Postman / Insomnia:** Ferramentas para testes manuais e automatizados de API.
*   **Guzzle Mock Handler (ou similar):** Para simular respostas de APIs externas em testes unitários/integração.
*   **Framework de Testes do Laravel/Symfony:** Utilizar as facilidades de teste integradas (ex: `actingAs`, `postJson`, `assertDatabaseHas` no Laravel).
*   **Ferramentas de Teste de Carga (Opcional):** ApacheBench (ab), k6, JMeter.
*   **Scanners de Segurança (Opcional):** OWASP ZAP, Nikto.

### 5.5. Casos de Teste Principais

**API Funcional e Integração:**

| ID    | Cenário                                       | Passos                                                                                                | Resultado Esperado                                                                                                |
| :---- | :-------------------------------------------- | :---------------------------------------------------------------------------------------------------- | :---------------------------------------------------------------------------------------------------------------- |
| FT01  | Identificação bem-sucedida (Alta Confiança) | Enviar POST /api/identify com API Key válida e imagem de espécie conhecida (espera-se alta confiança). | Resposta HTTP 200 OK. JSON: `{"status": "success", "species": "NomeCorreto", "confidence": >=LIMIAR}`. Registro no BD. |
| FT02  | Identificação pendente (Baixa Confiança)    | Enviar POST /api/identify com API Key válida e imagem de espécie ambígua (espera-se baixa confiança). | Resposta HTTP 200 OK. JSON: `{"status": "pending_validation", ...}`. Registro na tabela de validação pendente. |
| FT03  | Imagem com formato inválido                 | Enviar POST /api/identify com arquivo não-imagem (ex: .txt).                                          | Resposta HTTP 422 Unprocessable Entity ou 400 Bad Request. Mensagem de erro clara sobre formato.                 |
| FT04  | Imagem muito grande                         | Enviar POST /api/identify com imagem excedendo o limite de tamanho configurado.                       | Resposta HTTP 413 Payload Too Large ou 400 Bad Request. Mensagem de erro clara sobre tamanho.                   |
| FT05  | API Key ausente                             | Enviar POST /api/identify sem cabeçalho `Authorization`.                                              | Resposta HTTP 401 Unauthorized.                                                                                   |
| FT06  | API Key inválida                            | Enviar POST /api/identify com `Authorization: Bearer CHAVE_INVALIDA`.                                 | Resposta HTTP 401 Unauthorized.                                                                                   |
| FT07  | Falha na comunicação com API de IA          | Simular (via mock ou configuração) falha na chamada à API externa.                                    | Resposta HTTP 500 Internal Server Error ou 503 Service Unavailable. Mensagem de erro genérica. Log detalhado.   |
| FT08  | Erro interno no Backend PHP                 | Simular exceção não tratada em um serviço.                                                            | Resposta HTTP 500 Internal Server Error. Mensagem de erro genérica. Log detalhado.                                |

**Validação da IA:**

*   **VA01:** Preparar um conjunto de imagens de teste (não usadas no treinamento) com rótulos corretos das espécies da Região Carbonífera.
*   **VA02:** Submeter cada imagem via API.
*   **VA03:** Comparar a `species` retornada pela API com o rótulo real.
*   **VA04:** Calcular métricas: Acurácia Geral, Precisão por Espécie, Recall por Espécie, Matriz de Confusão.
*   **VA05:** Analisar imagens com baixa confiança para entender os desafios do modelo.
*   **VA06:** Verificar se o limiar de confiança separa adequadamente as identificações seguras das duvidosas.

**Validação Manual (UAT):**

*   **UAT01:** Submeter imagens que resultem em `pending_validation`.
*   **UAT02:** Verificar se essas imagens aparecem corretamente na interface dos especialistas.
*   **UAT03:** Simular um especialista validando (confirmando/corrigindo) a espécie.
*   **UAT04:** Verificar se o status da imagem é atualizado no banco de dados e se os dados são movidos/atualizados corretamente após a validação.

**Desempenho:**

*   **PE01:** Medir o tempo médio de resposta da API `/api/identify` com imagens de tamanhos variados sob carga normal (ex: 10 requisições concorrentes).

**Segurança:**

*   **SEC01:** Tentar enviar arquivos executáveis ou com scripts maliciosos disfarçados de imagem.
*   **SEC02:** Verificar se a API Key é transmitida de forma segura (HTTPS).
*   **SEC03:** Testar limites de taxa (Rate Limiting) se implementado.
*   **SEC04:** Executar scanners básicos de vulnerabilidade (ex: ZAP) contra o endpoint em Staging.

### 5.6. Critérios de Aceitação

*   Todos os casos de teste críticos (FT01-FT08, VA01-VA06, UAT01-UAT04) devem passar.
*   Acurácia da IA deve atender a um mínimo definido pelo projeto (ex: > 85% no conjunto de teste).
*   Tempo médio de resposta da API deve estar dentro da meta definida (ex: < 5 segundos).
*   Nenhuma vulnerabilidade de segurança crítica ou alta identificada.

Este plano deve ser revisado e ajustado conforme o desenvolvimento avança.

---


