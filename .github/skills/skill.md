# skill.md

## Nome da Skill
Integração com RubixML para Reconhecimento de Espécies – Carbonífera Biodiversa

---

## Objetivo

Esta skill define o processo técnico para integração da biblioteca RubixML à API do projeto Carbonífera Biodiversa, permitindo o treinamento, inferência e evolução contínua de um modelo de Machine Learning voltado ao reconhecimento de espécies da fauna e flora da Região Carbonífera.

O foco principal é permitir que a API realize classificações automáticas com base em imagens enviadas pelos usuários, utilizando um modelo local treinado com espécies regionais, reduzindo a dependência de serviços externos como Google Cloud Vision.

---

## Contexto

Na fase inicial do projeto foi utilizada a Google Cloud Vision API como prova de conceito para validar o fluxo de envio → análise → retorno.

Entretanto, a Cloud Vision apresenta limitações importantes:

- não é regionalizada
- possui baixa precisão para espécies locais
- depende de serviço externo pago
- reduz a autonomia do sistema

Por isso, foi implementada uma segunda fase utilizando RubixML, permitindo:

- treinamento local
- especialização regional
- redução de custos
- maior controle sobre o modelo
- evolução contínua com novos dados

Atualmente o dataset inicial possui poucas imagens e quatro espécies, o que naturalmente reduz a confiabilidade do modelo. A estratégia é expandir continuamente esse dataset com novas submissões dos usuários e validações dos especialistas.

---

## Stack Utilizada

- PHP 8+
- Composer
- RubixML
- GD Library ou ImageMagick
- MySQL (metadados)
- armazenamento em diretórios locais
- Nginx
- API REST

---

## Etapas da Integração

---

## 1. Instalação da Biblioteca

Já incluída no `composer.json`. Para instalar dentro do container:

```bash
docker exec carbonifera_php composer install
```

---

## 2. Organização do Dataset

As imagens de treino ficam em `src/app/ML/Dataset/`, organizadas por tipo e espécie:

```
app/ML/Dataset/
├── fauna/
│   ├── capivara/        # imagens .jpg/.jpeg/.png
│   ├── graxaim/
│   └── quero-quero/
└── flora/
    ├── babosa_do_campo/
    └── trevo_nativo/    # previsto no mapeamento, ainda sem imagens
```

> **Regra:** cada subpasta corresponde a um rótulo de classe no modelo. O nome da pasta é usado diretamente como label pelo script de treinamento.

Para adicionar uma nova espécie, basta criar a subpasta correspondente (ex.: `fauna/lontra/`) e adicionar as imagens.

---

## 3. Pré-processamento de Imagens

Cada imagem passa pelo seguinte pipeline antes de entrar no modelo:

1. Leitura via `file_get_contents()` + `imagecreatefromstring()` (extensão GD)
2. Fundo branco aplicado ao canvas antes do redimensionamento (suporte a PNG com alpha)
3. Redimensionamento para **16×16 pixels** via `imagecopyresampled()`
4. Conversão para **escala de cinza** (fórmula: `0.299·R + 0.587·G + 0.114·B`)
5. Normalização para intervalo `[0, 1]`
6. Validação de dimensão: o vetor deve ter exatamente **256 features**
7. Resultado: vetor de **256 features** por imagem

O pipeline de transformação do RubixML aplica `ZScaleStandardizer` para padronizar as features durante o treinamento e a inferência.

### Restrições e requisitos

| Item | Regra |
|------|-------|
| Formatos aceitos | `.jpg`, `.jpeg`, `.png` (case-insensitive) |
| Imagens PNG com alpha | Compostas sobre fundo **branco** antes do redimensionamento |
| Imagens corrompidas | Descartadas com aviso — não interrompem o treinamento |
| Dimensão mínima | Qualquer tamanho (redimensionado para 16×16) |
| Canais de cor | RGB ou RGBA — convertidos para grayscale |

> **Atenção crítica:** o pré-processamento na inferência (`RubixMLClassificationService::preprocessImage`) deve ser **idêntico** ao usado no treinamento (`train_model.php::preprocessImage()`). O pipeline foi sincronizado — qualquer alteração futura em um deve ser replicada no outro imediatamente.

### Balanceamento de classes

O dataset deve ter número **equilibrado de imagens por espécie**. Classes com mais amostras tendem a dominar as predições, reduzindo a acurácia nas demais.

| Espécie | Imagens recomendadas |
|---------|---------------------|
| Todas as classes | ~70 imagens (limite operacional atual) |

> Com 70 imagens por classe e resolução 16×16 grayscale, a acurácia será moderada mas funcionalmente válida. O modelo melhora progressivamente à medida que o dataset cresce com novas submissões da plataforma.

---

## 4. Treinamento do Modelo

O script principal é `src/app/ML/train_model.php`. Deve ser executado **dentro do container PHP**:

```bash
# Treinamento padrão (100 épocas, batch 32)
docker exec carbonifera_php php app/ML/train_model.php

# Com parâmetros customizados
docker exec carbonifera_php php app/ML/train_model.php --epochs=200 --batch=64

# Com K-Fold Cross Validation (recomendado para avaliar overfitting)
docker exec carbonifera_php php app/ML/train_model.php --epochs=200 --batch=64 --kfold=5

# Salvar modelo em caminho alternativo
docker exec carbonifera_php php app/ML/train_model.php --output=app/ML/models/v2.rbx
```

### Arquitetura da rede neural

- Camadas: `Dense(128)` → `LeakyReLU` → `Dropout(0.2)` → `Dense(32)` → `LeakyReLU`
- Otimizador: `Adam(lr=0.001)`
- Pipeline: `ZScaleStandardizer` → `MultilayerPerceptron`

### Saídas geradas após o treinamento

| Arquivo | Descrição |
|---------|-----------|
| `models/species_classifier.rbx` | Modelo serializado (RubixML) |
| `models/species_classifier.rbx.gz` | Versão comprimida (gzip, menor I/O) |
| `models/training_report.json` | Métricas: acurácia, Macro F1, matriz de confusão |
| `models/training_report.xlsx` | Relatório em planilha Excel |

---

## 5. Carregamento e Inferência

O serviço `RubixMLClassificationService` carrega o modelo automaticamente na primeira requisição e o mantém em cache estático.

Prioridade de carregamento:
1. `species_classifier.rbx.gz` (se existir)
2. `species_classifier.rbx`

O limiar de confiança é configurado em `src/app/Config/config.php`:

```php
'ai_service' => [
    'confidence_threshold' => 0.7, // 70% — abaixo disso vai para pending_validation
],
```

### Mapeamento de rótulos para nomes exibidos

| Rótulo (dataset) | Nome exibido na resposta |
|-----------------|--------------------------|
| `capivara` | Capivara |
| `graxaim` | Graxaim |
| `quero-quero` | Quero-quero |
| `babosa_do_campo` | Babosa-do-campo |
| `trevo_nativo` | Trevo-nativo |

---

## 6. Expansão Contínua do Dataset

O ciclo de evolução do modelo segue o fluxo:

```
Usuário envia imagem
        ↓
IA classifica (confidence ≥ 0.7?)
   ├── Sim → retorna espécie (status: success)
   └── Não → encaminha para especialista (status: pending_validation)
                ↓
        Especialista valida → species_id atribuído (status: validated)
                ↓
        Imagem validada pode ser adicionada ao Dataset para re-treinamento
```

**Para re-treinar com novas imagens:**

1. Copiar imagens validadas para `app/ML/Dataset/{tipo}/{espécie}/`
2. Re-executar o script de treinamento via Docker
3. O novo modelo substituirá o anterior em `models/`
