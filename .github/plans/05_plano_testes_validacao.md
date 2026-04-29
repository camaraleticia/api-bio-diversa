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

