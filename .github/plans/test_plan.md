## Testes de Fluxo com Espécies de Exemplo

Para testar o Web Service de identificação de espécies, vamos criar imagens de teste simulando as espécies especificadas e verificar o fluxo completo desde o upload até a validação.

### Preparação de Imagens de Teste

Vamos criar arquivos de texto simples que simularão imagens para cada espécie de exemplo:

1. Quero-quero (fauna)
2. Capivara (fauna)
3. Graxaim (fauna)
4. Babosa-do-campo (flora)
5. Trevo-nativo (flora)

Também criaremos uma imagem genérica que deve resultar em baixa confiança e ser enviada para validação manual.

### Casos de Teste

#### Caso 1: Upload e Identificação Automática
- Enviar imagens com nomes que contenham as palavras-chave das espécies
- Verificar se o sistema identifica corretamente com alta confiança
- Confirmar que os registros são salvos no banco de dados

#### Caso 2: Identificação com Baixa Confiança
- Enviar imagem genérica sem palavras-chave
- Verificar se o sistema marca como "pending_validation"
- Confirmar que aparece no dashboard para validação manual

#### Caso 3: Validação Manual
- Acessar o dashboard de validação
- Selecionar uma identificação pendente
- Atribuir uma espécie manualmente
- Verificar se o status é atualizado no banco de dados

### Resultados Esperados

- Todas as espécies de exemplo devem ser corretamente identificadas quando suas palavras-chave estão presentes
- Imagens sem palavras-chave devem ser enviadas para validação manual
- O fluxo de validação manual deve funcionar corretamente
- Todos os registros devem ser salvos no banco de dados
