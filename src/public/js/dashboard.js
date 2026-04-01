// Configuração da API
const API_CONFIG = {
    baseUrl: '/api',
    headers: {
        'Authorization': 'Bearer cb2023-test-api-key',
        'Content-Type': 'application/json'
    }
};

// Elementos do DOM
const pendingContainer = document.getElementById('pending-container');
const loadingPending = document.getElementById('loading-pending');
const validationModal = document.getElementById('validation-modal');
const modalImage = document.getElementById('modal-image');
const speciesSelect = document.getElementById('species-select');
const validationForm = document.getElementById('validation-form');
const identificationIdInput = document.getElementById('identification-id');
const closeModal = document.querySelector('.close-modal');

// Carregar dados ao iniciar a página
document.addEventListener('DOMContentLoaded', async function() {
    await loadPendingIdentifications();
    await loadSpecies();
});

// Função para carregar identificações pendentes
async function loadPendingIdentifications() {
    try {
        const response = await fetch(`${API_CONFIG.baseUrl}/identifications/pending`, {
            method: 'GET',
            headers: API_CONFIG.headers
        });
        
        if (!response.ok) {
            throw new Error(`Erro ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        displayPendingIdentifications(data.pending_identifications);
        
    } catch (error) {
        console.error('Erro ao carregar identificações pendentes:', error);
        pendingContainer.innerHTML = `
            <div class="message error">
                Ocorreu um erro ao carregar as identificações pendentes.
                <br>Detalhes: ${error.message}
            </div>
        `;
    } finally {
        loadingPending.style.display = 'none';
    }
}

// Função para exibir identificações pendentes
function displayPendingIdentifications(identifications) {
    if (!identifications || identifications.length === 0) {
        pendingContainer.innerHTML = '<p>Não há identificações pendentes no momento.</p>';
        return;
    }
    
    let html = '';
    
    identifications.forEach(item => {
        html += `
            <div class="card">
                <img src="${item.image_path}" alt="Imagem para identificação" class="card-image">
                <div class="card-body">
                    <h4 class="card-title">Identificação #${item.id}</h4>
                    <p class="card-text">
                        <strong>Data:</strong> ${formatDate(item.created_at)}<br>
                        <strong>Confiança:</strong> ${(item.confidence * 100).toFixed(2)}%
                    </p>
                </div>
                <div class="card-footer">
                    <button class="btn validate-btn" data-id="${item.id}" data-image="${item.image_path}">
                        Validar
                    </button>
                </div>
            </div>
        `;
    });
    
    pendingContainer.innerHTML = html;
    
    // Adicionar eventos aos botões de validação
    document.querySelectorAll('.validate-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const imagePath = this.getAttribute('data-image');
            openValidationModal(id, imagePath);
        });
    });
}

// Função para carregar espécies
async function loadSpecies() {
    try {
        const response = await fetch(`${API_CONFIG.baseUrl}/species`, {
            method: 'GET',
            headers: API_CONFIG.headers
        });
        
        if (!response.ok) {
            throw new Error(`Erro ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        populateSpeciesSelect(data.species);
        
    } catch (error) {
        console.error('Erro ao carregar espécies:', error);
        speciesSelect.innerHTML = '<option value="">Erro ao carregar espécies</option>';
    }
}

// Função para preencher o select de espécies
function populateSpeciesSelect(species) {
    let html = '<option value="">-- Selecione --</option>';
    
    // Agrupar por tipo (fauna/flora)
    const grouped = species.reduce((acc, item) => {
        if (!acc[item.type]) {
            acc[item.type] = [];
        }
        acc[item.type].push(item);
        return acc;
    }, {});
    
    // Adicionar opções agrupadas
    if (grouped.fauna) {
        html += '<optgroup label="Fauna">';
        grouped.fauna.forEach(item => {
            html += `<option value="${item.id}">${item.name} (${item.scientific_name || 'N/A'})</option>`;
        });
        html += '</optgroup>';
    }
    
    if (grouped.flora) {
        html += '<optgroup label="Flora">';
        grouped.flora.forEach(item => {
            html += `<option value="${item.id}">${item.name} (${item.scientific_name || 'N/A'})</option>`;
        });
        html += '</optgroup>';
    }
    
    speciesSelect.innerHTML = html;
}

// Função para abrir o modal de validação
function openValidationModal(id, imagePath) {
    identificationIdInput.value = id;
    modalImage.src = imagePath;
    validationModal.style.display = 'flex';
}

// Função para fechar o modal
closeModal.addEventListener('click', function() {
    validationModal.style.display = 'none';
});

// Fechar o modal ao clicar fora dele
window.addEventListener('click', function(event) {
    if (event.target === validationModal) {
        validationModal.style.display = 'none';
    }
});

// Processar o formulário de validação
validationForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const identificationId = identificationIdInput.value;
    const speciesId = speciesSelect.value;
    
    if (!speciesId) {
        alert('Por favor, selecione uma espécie.');
        return;
    }
    
    try {
        const response = await fetch(`${API_CONFIG.baseUrl}/identifications/validate`, {
            method: 'POST',
            headers: API_CONFIG.headers,
            body: JSON.stringify({
                identification_id: parseInt(identificationId),
                species_id: parseInt(speciesId)
            })
        });
        
        if (!response.ok) {
            throw new Error(`Erro ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        // Fechar o modal
        validationModal.style.display = 'none';
        
        // Exibir mensagem de sucesso
        alert('Identificação validada com sucesso!');
        
        // Recarregar as identificações pendentes
        loadingPending.style.display = 'block';
        await loadPendingIdentifications();
        
    } catch (error) {
        console.error('Erro ao validar identificação:', error);
        alert(`Erro ao validar identificação: ${error.message}`);
    }
});

// Função para formatar data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR');
}
