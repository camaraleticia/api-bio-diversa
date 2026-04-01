// =============================
// Configuração da API
// =============================
const API_CONFIG = {
    baseUrl: "http://localhost:8080/api",
    headers: {
        Authorization: "Bearer cb2023-test-api-key"
    }
};

// =============================
// Elementos do DOM
// =============================
const uploadForm = document.getElementById("upload-form");
const imageInput = document.getElementById("image");
const imagePreview = document.getElementById("image-preview");
const resultSection = document.querySelector(".result-section");
const resultContainer = document.getElementById("result-container");

// =============================
// Preview da imagem
// =============================
imageInput.addEventListener("change", function () {
    const file = this.files[0];

    if (!file) return;

    const reader = new FileReader();

    reader.onload = function (e) {
        imagePreview.src = e.target.result;
        imagePreview.style.display = "block";
    };

    reader.readAsDataURL(file);
});

// =============================
// Mostrar mensagens
// =============================
function showMessage(message, type = "info") {
    const messageElement = document.createElement("div");
    messageElement.className = `message ${type}`;
    messageElement.textContent = message;

    resultContainer.innerHTML = "";
    resultContainer.appendChild(messageElement);

    resultSection.style.display = "block";
}

// =============================
// Mostrar resultado da API
// =============================
function displayResult(data) {

    let resultHTML = "";

    if (data.status === "success") {

        resultHTML = `
            <div class="result-card">
                <h4>Identificação bem-sucedida!</h4>
                <p>Espécie identificada: <strong>${data.species}</strong></p>
                <p>Confiança: 
                    <span class="confidence success">
                        ${(data.confidence * 100).toFixed(2)}%
                    </span>
                </p>

                <div class="result-image">
                    <img src="${data.image_url}" 
                    alt="Imagem enviada"
                    style="max-width:100%;max-height:300px;">
                </div>
            </div>
        `;

    } else if (data.status === "pending_validation") {

        resultHTML = `
            <div class="result-card">
                <h4>Identificação pendente</h4>
                <p>${data.message}</p>

                <p>Confiança:
                    <span class="confidence pending">
                        ${(data.confidence * 100).toFixed(2)}%
                    </span>
                </p>

                <p>A imagem será analisada por especialistas.</p>

                <div class="result-image">
                    <img src="${data.image_url}" 
                    alt="Imagem enviada"
                    style="max-width:100%;max-height:300px;">
                </div>
            </div>
        `;

    } else {

        resultHTML = `
            <div class="message error">
                Ocorreu um erro ao processar a identificação.
            </div>
        `;
    }

    resultContainer.innerHTML = resultHTML;
    resultSection.style.display = "block";
}

// =============================
// Enviar imagem para API
// =============================
uploadForm.addEventListener("submit", async function (e) {

    e.preventDefault();

    const file = imageInput.files[0];

    if (!file) {
        showMessage("Por favor selecione uma imagem.", "error");
        return;
    }

    const formData = new FormData();
    formData.append("image", file);

    console.log("Imagem enviada:", file);

    resultContainer.innerHTML =
        '<div class="loading">Processando imagem...</div>';

    resultSection.style.display = "block";

    try {

        const response = await fetch(`${API_CONFIG.baseUrl}/identify`, {
            method: "POST",
            headers: {
                Authorization: API_CONFIG.headers.Authorization
            },
            body: formData
        });

        // Ler resposta como texto primeiro
        const responseText = await response.text();

        console.log("Resposta da API:", responseText);

        let data;

        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            throw new Error("Resposta da API não é JSON válido");
        }

        if (!response.ok) {
            throw new Error(data.error || "Erro na API");
        }

        displayResult(data);

    } catch (error) {

        console.error("Erro ao identificar imagem:", error);

        resultContainer.innerHTML = `
            <div class="message error">
                Ocorreu um erro ao processar a imagem.
                <br>
                <strong>${error.message}</strong>
            </div>
        `;
    }
});