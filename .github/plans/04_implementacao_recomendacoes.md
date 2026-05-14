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
    
    Route::middleware(AuthenticateApiKey::class)->post('/identify', [ApiController::class, 'identify']);
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

