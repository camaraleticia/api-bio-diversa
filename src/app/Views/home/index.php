<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<header>
    <div class="container">
        <h1>       <img src="/img/biodiversidade.png"
           alt="Biodiversidade da Região Carbonífera"
           class="logo">
            
        Carbonífera Biodiversa</h1>
        <nav>
            <ul>
                <li><a href="/" class="active"><i class="fas fa-home"></i> Início</a></li>
                <li><a href="/dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="container">
    <section class="hero">
        <div class="hero-content">
            <h2>Identificação de Espécies com IA</h2>
            <p>Envie uma foto de um animal ou planta da Região Carbonífera e nossa Inteligência Artificial irá identificá-lo automaticamente.</p>
            <p class="subtitle">Contribua para o mapeamento da biodiversidade local!</p>
        </div>

    </section>

    <section class="species-examples">
        <h3>Espécies Catalogadas</h3>
        <div class="species-grid">
            <div class="species-card">
                <div class="species-image">
                    <img src="/img/quero-quero.jpg" alt="Quero-quero" onerror="this.src='/img/placeholder.jpg'; this.onerror=null;">
                </div>
                <div class="species-info">
                    <h4>Quero-quero</h4>
                    <p class="scientific-name">Vanellus chilensis</p>
                    <span class="species-type fauna">Fauna</span>
                </div>
            </div>
            <div class="species-card">
                <div class="species-image">
                    <img src="/img/capivara.jpeg" alt="Capivara" onerror="this.src='/img/placeholder.jpg'; this.onerror=null;">
                </div>
                <div class="species-info">
                    <h4>Capivara</h4>
                    <p class="scientific-name">Hydrochoerus hydrochaeris</p>
                    <span class="species-type fauna">Fauna</span>
                </div>
            </div>
            <div class="species-card">
                <div class="species-image">
                    <img src="/img/veado.jpg" alt="Veado" onerror="this.src='/img/placeholder.jpg'; this.onerror=null;">
                </div>
                <div class="species-info">
                    <h4>Veado Campeiro</h4>
                    <p class="scientific-name">Ozotoceros bezoarticus</p>
                    <span class="species-type fauna">Fauna</span>
                </div>
            </div>
            <div class="species-card">
                <div class="species-image">
                    <img src="/img/babosa-do-campo.jpeg" alt="Babosa-do-campo" onerror="this.src='/img/placeholder.jpg'; this.onerror=null;">
                </div>
                <div class="species-info">
                    <h4>Babosa-do-campo</h4>
                    <p class="scientific-name">Eryngium horridum</p>
                    <span class="species-type flora">Flora</span>
                </div>
            </div>
        </div>
    </section>

    <section class="upload-section">
            <h3><i class="fas fa-camera"></i> Enviar Imagem para Identificação</h3>
            <form id="upload-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="image">Selecione uma imagem:</label>
                    <div class="file-input-container">
                        <input type="file" id="image" name="image" accept="image/jpeg, image/png, image/webp" required>
                        <div class="file-input-button">
                            <i class="fas fa-upload"></i> Escolher arquivo
                        </div>
                        <div class="file-input-name">Nenhum arquivo selecionado</div>
                    </div>
                </div>
                <div class="preview-container">
                    <img id="image-preview" src="#" alt="Prévia da imagem" style="display: none; max-width: 100%;">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Identificar Espécie
                </button>
            </form>
        </section>

        <section class="result-section" style="display: none;">
            <h3><i class="fas fa-clipboard-check"></i> Resultado da Identificação</h3>
            <div id="result-container"></div>
        </section>

    <section class="about-section">
        <h3>Sobre o Projeto</h3>
        <div class="about-content">
            <div class="about-text">
                <p>O projeto <strong>Carbonífera Biodiversa</strong> é uma iniciativa desenvolvida por estudantes do Curso Técnico em Informática do IFSul – Câmpus Charqueadas, que visa catalogar e preservar a biodiversidade da Região Carbonífera.</p>
                <p>Esta plataforma utiliza Inteligência Artificial para identificar automaticamente espécies da fauna e flora locais a partir de imagens enviadas pelos usuários, contribuindo para o mapeamento e conservação da biodiversidade regional.</p>
                <p>Participe enviando suas fotos e ajude a construir um catálogo vivo da biodiversidade da nossa região!</p>
            </div>
            <div class="about-image">
                <img src="/img/ifsul-logo.png" alt="IFSul - Câmpus Charqueadas" width="350" height="100" onerror="this.src='/img/placeholder.jpg'; this.onerror=null;">
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-logo">
                <h3><i class="fas fa-leaf"></i> Carbonífera Biodiversa</h3>
                <p>Um Catálogo Vivo para Conservação</p>
            </div>
            <div class="footer-links">
                <h4>Links Úteis</h4>
                <ul>
                    <li><a href="/">Início</a></li>
                    <li><a href="/dashboard">Dashboard</a></li>
                    <li><a href="https://www.ifsul.edu.br/charqueadas/" target="_blank">IFSul Charqueadas</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h4>Contato</h4>
                <p><i class="fas fa-envelope"></i> leticiacamara.ch030@academico.ifsul.edu</p>
                <p><i class="fas fa-map-marker-alt"></i> IFSul - Câmpus Charqueadas</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Carbonífera Biodiversa - Todos os direitos reservados</p>
            <p>Desenvolvido como Trabalho de Conclusão de Curso - Tecnólogo em Sistemas para Internet</p>
        </div>
    </div>
</footer>

<script src="/js/main.js"></script>
<script>
    // Script adicional para melhorar a experiência do usuário
    document.addEventListener('DOMContentLoaded', function() {
        // Melhorar a experiência do input de arquivo
        const fileInput = document.getElementById('image');
        const fileInputName = document.querySelector('.file-input-name');

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                fileInputName.textContent = this.files[0].name;
            } else {
                fileInputName.textContent = 'Nenhum arquivo selecionado';
            }
        });
    });
</script>
</body>
</html>