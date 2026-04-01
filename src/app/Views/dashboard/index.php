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
        <h1><i class="fas fa-leaf"></i> Carbonífera Biodiversa</h1>
        <nav>
            <ul>
                <li><a href="/"><i class="fas fa-home"></i> Início</a></li>
                <li><a href="/dashboard" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="container">
    <section class="dashboard-header">
        <div class="dashboard-title">
            <h2><i class="fas fa-clipboard-list"></i> Dashboard de Validação</h2>
            <p>Analise e valide as identificações pendentes que não puderam ser classificadas com alta confiança pela IA.</p>
        </div>
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Validadas</h3>
                    <p id="validated-count">Carregando...</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Pendentes</h3>
                    <p id="pending-count">Carregando...</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="stat-info">
                    <h3>Automáticas</h3>
                    <p id="auto-count">Carregando...</p>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-filters">
        <h3><i class="fas fa-filter"></i> Filtros</h3>
        <div class="filters-container">
            <div class="filter-group">
                <label for="filter-type">Tipo:</label>
                <select id="filter-type">
                    <option value="all">Todos</option>
                    <option value="fauna">Fauna</option>
                    <option value="flora">Flora</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-date">Data:</label>
                <select id="filter-date">
                    <option value="all">Todas</option>
                    <option value="today">Hoje</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mês</option>
                </select>
            </div>
            <button id="apply-filters" class="btn btn-secondary">
                <i class="fas fa-search"></i> Aplicar Filtros
            </button>
        </div>
    </section>

    <section class="pending-identifications">
        <h3><i class="fas fa-hourglass-half"></i> Identificações Pendentes</h3>
        <div class="loading" id="loading-pending">
            <i class="fas fa-spinner fa-spin"></i> Carregando identificações pendentes...
        </div>
        <div id="pending-container" class="cards-container">
            <!-- Identificações pendentes serão exibidas aqui via JavaScript -->
        </div>
        <div id="no-pending" class="no-results" style="display: none;">
            <i class="fas fa-check-circle"></i>
            <p>Não há identificações pendentes no momento.</p>
            <p class="sub-message">Todas as imagens foram classificadas com sucesso!</p>
        </div>
    </section>

    <section class="recent-validations">
        <h3><i class="fas fa-history"></i> Validações Recentes</h3>
        <div class="loading" id="loading-recent">
            <i class="fas fa-spinner fa-spin"></i> Carregando validações recentes...
        </div>
        <div id="recent-container" class="table-container">
            <table class="data-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagem</th>
                    <th>Espécie</th>
                    <th>Tipo</th>
                    <th>Data</th>
                    <th>Validado por</th>
                </tr>
                </thead>
                <tbody id="recent-validations-body">
                <!-- Validações recentes serão exibidas aqui via JavaScript -->
                </tbody>
            </table>
        </div>
        <div id="no-recent" class="no-results" style="display: none;">
            <i class="fas fa-info-circle"></i>
            <p>Não há validações recentes para exibir.</p>
        </div>
    </section>

    <!-- Modal de Validação -->
    <section class="validation-modal" id="validation-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-check-double"></i> Validar Identificação</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="image-container">
                    <img id="modal-image" src="" alt="Imagem para validação">
                    <div class="image-metadata">
                        <p><strong>ID:</strong> <span id="modal-id"></span></p>
                        <p><strong>Data de envio:</strong> <span id="modal-date"></span></p>
                        <p><strong>Confiança da IA:</strong> <span id="modal-confidence"></span></p>
                    </div>
                </div>
                <div class="form-container">
                    <form id="validation-form">
                        <input type="hidden" id="identification-id" name="identification_id">
                        <div class="form-group">
                            <label for="species-select">Selecione a espécie:</label>
                            <select id="species-select" name="species_id" required>
                                <option value="">-- Selecione --</option>
                                <!-- Opções serão carregadas via JavaScript -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="validation-notes">Observações (opcional):</label>
                            <textarea id="validation-notes" name="notes" rows="3" placeholder="Adicione observações sobre esta identificação..."></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" id="cancel-validation">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Confirmar Validação
                            </button>
                        </div>
                    </form>
                </div>
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
                <p><i class="fas fa-envelope"></i> carbonifera.biodiversa@exemplo.com</p>
                <p><i class="fas fa-map-marker-alt"></i> IFSul - Câmpus Charqueadas</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Carbonífera Biodiversa - Todos os direitos reservados</p>
            <p>Desenvolvido como Trabalho de Conclusão de Curso - Técnico em Informática</p>
        </div>
    </div>
</footer>

<script src="/js/dashboard.js"></script>
<script>
    // Script adicional para melhorar a experiência do usuário
    document.addEventListener('DOMContentLoaded', function() {
        // Manipulação do modal
        const cancelButton = document.getElementById('cancel-validation');
        if (cancelButton) {
            cancelButton.addEventListener('click', function() {
                document.getElementById('validation-modal').style.display = 'none';
            });
        }

        // Simulação de contadores para demonstração
        setTimeout(() => {
            document.getElementById('validated-count').textContent = '24';
            document.getElementById('pending-count').textContent = '7';
            document.getElementById('auto-count').textContent = '156';
        }, 1000);
    });
</script>
</body>
</html>
