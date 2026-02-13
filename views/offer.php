<?php
/**
 * View: Oferecer Carona (UI Fix & Waypoints)
 * Baseado em: metronic-tailwind-html-demos/dist/html/demo1/account/home/settings-plain.html
 */
?>
<div class="max-w-2xl mx-auto pt-6 px-4 pb-20">
    
    <!-- Card Principal -->
    <div class="card border border-gray-200 shadow-sm rounded-xl bg-white">
        
        <!-- Card Header -->
        <div class="card-header border-b border-gray-200 py-6 px-8">
            <h3 class="card-title text-xl font-bold text-gray-900">Nova Viagem</h3>
        </div>

        <!-- Card Body -->
        <div class="card-body p-8">
            <form id="offer-form" class="form">
                
                <!-- Origem -->
                <div class="flex flex-col mb-7">
                    <label class="form-label font-bold text-gray-800 text-sm mb-2 required">Origem</label>
                    <input type="text" name="origin" class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors" placeholder="Ex: Brazlândia" required>
                </div>

                <!-- Destino -->
                <div class="flex flex-col mb-7">
                    <label class="form-label font-bold text-gray-800 text-sm mb-2 required">Destino</label>
                    <input type="text" name="destination" class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors" placeholder="Ex: Esplanada dos Ministérios" required>
                </div>

                <!-- Pontos de Passagem (Novo) -->
                <div class="flex flex-col mb-7">
                    <label class="form-label font-bold text-gray-800 text-sm mb-2">Por onde você vai passar?</label>
                    <textarea name="waypoints" class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors" rows="2" placeholder="Ex: Rodeador, Estrutural, Rodoviária"></textarea>
                    <span class="text-xs text-gray-400 mt-1">Separe os locais por vírgula.</span>
                </div>

                <!-- Data e Vagas (Lado a Lado) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-7">
                    <div class="flex flex-col">
                        <label class="form-label font-bold text-gray-800 text-sm mb-2 required">Data e Hora</label>
                        <input type="datetime-local" name="departure_time" class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors" required>
                    </div>

                    <div class="flex flex-col">
                        <label class="form-label font-bold text-gray-800 text-sm mb-2 required">Vagas</label>
                        <select name="seats" class="form-select form-select-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors cursor-pointer" required>
                            <option value="1">1 vaga</option>
                            <option value="2">2 vagas</option>
                            <option value="3" selected>3 vagas</option>
                            <option value="4">4 vagas</option>
                        </select>
                    </div>
                </div>

                <!-- Valor -->
                <div class="flex flex-col mb-7">
                    <label class="form-label font-bold text-gray-800 text-sm mb-2 required">Valor (R$)</label>
                    <input type="number" name="price" step="0.50" min="0" class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors" placeholder="Ex: 10.00" required>
                </div>

                <!-- Observações -->
                <div class="flex flex-col mb-7">
                    <label class="form-label font-bold text-gray-800 text-sm mb-2">Observações</label>
                    <input type="text" name="details" class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors" placeholder="Ex: Só aceito Pix, sem foto não levo">
                </div>

                <!-- Separator -->
                <div class="border-t border-gray-100 my-6"></div>

                <!-- Actions -->
                <div class="flex justify-end">
                    <button type="button" onclick="history.back()" class="btn btn-light text-gray-600 hover:bg-gray-100 font-bold py-3 px-6 rounded-lg mr-3">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-submit-offer" class="btn btn-primary font-bold py-3 px-8 rounded-lg shadow-lg shadow-primary/20 hover:shadow-primary/40 transition-shadow">
                        Publicar Carona
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('offer-form');
    const btnParam = document.getElementById('btn-submit-offer');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const originalBtnText = btnParam.innerText;
        btnParam.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Publicando...';
        btnParam.disabled = true;

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('api/create_ride.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            // Tenta processar JSON, se falhar, lança erro
            let result;
            try {
                result = await response.json();
            } catch (jsonError) {
                throw new Error('Erro de comunicação com o servidor (JSON Inválido).');
            }

            if (result.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        text: "Carona publicada com sucesso! Boa viagem.",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ver no Feed",
                        customClass: {
                            confirmButton: "btn btn-primary font-bold px-6 py-2 rounded-lg"
                        }
                    }).then(() => {
                        window.location.href = 'index.php?page=home';
                    });
                } else {
                    // Fallback nativo
                    alert("Carona publicada com sucesso!");
                    window.location.href = 'index.php?page=home';
                }
            } else {
                throw new Error(result.message || 'Erro ao publicar carona.');
            }
        } catch (error) {
            console.error(error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    text: error.message,
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Ok, entendi",
                    customClass: {
                        confirmButton: "btn btn-primary font-bold px-6 py-2 rounded-lg"
                    }
                });
            } else {
                alert(error.message);
            }
            // Destravar botão
            btnParam.innerText = originalBtnText;
            btnParam.disabled = false;
        }
    });
});
</script>