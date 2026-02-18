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
            <!-- Smart Box: Repetir Última (Hidden by default) -->
            <div id="smart-replay-box"
                class="hidden bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <div
                        class="bg-blue-100 text-primary w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                        <i class="bi bi-arrow-repeat text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800">Fazer o trajeto de sempre?</p>
                        <p id="last-route-text" class="text-xs text-gray-500 truncate max-w-[200px]"></p>
                    </div>
                </div>
                <button type="button" onclick="fillWithLastRide()"
                    class="text-xs font-bold bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="bi bi-magic"></i> Preencher
                </button>
            </div>

            <form id="offer-form" class="form">

                <!-- Origem -->
                <div class="flex flex-col mb-7">
                    <label class="form-label font-bold text-gray-800 text-sm mb-2 required">Origem</label>
                    <input type="text" name="origin" id="input-origin"
                        class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors"
                        placeholder="Ex: Brazlândia" required>
                </div>

                <!-- Destino -->
                <div class="flex flex-col mb-7">
                    <label class="form-label font-bold text-gray-800 text-sm mb-2 required">Destino</label>
                    <input type="text" name="destination" id="input-destination"
                        class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors"
                        placeholder="Ex: Esplanada dos Ministérios" required>
                </div>

                <!-- Pontos de Passagem (Novo) -->
                <div class="flex flex-col mb-7">
                    <label class="form-label font-bold text-gray-800 text-sm mb-2">Por onde você vai passar?</label>
                    <textarea name="waypoints" id="input-waypoints"
                        class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors"
                        rows="2" placeholder="Ex: Rodeador, Estrutural, Rodoviária"></textarea>

                    <!-- Chips de Sugestão -->
                    <div class="mt-3">
                        <span class="text-xs text-gray-400 block mb-2">Toque para adicionar:</span>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $commonPoints = ['Estrutural', 'EPTG', 'Eixão', 'Pistão Sul', 'Taguatinga', 'Rodoviária', 'UnB', 'Esplanada', 'SIG', 'SIA'];
                            foreach ($commonPoints as $pt) {
                                echo "<span onclick=\"addWaypoint('$pt')\" class='cursor-pointer bg-gray-100 hover:bg-gray-200 text-gray-600 text-[10px] font-bold px-3 py-1.5 rounded-full transition-all border border-gray-200'>$pt</span>";
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Data e Vagas (Lado a Lado) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-7">
                    <div class="flex flex-col">
                        <label class="form-label font-bold text-gray-800 text-sm mb-2 required">Data e Hora</label>
                        <input type="datetime-local" name="departure_time"
                            class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors"
                            required>
                    </div>

                    <div class="flex flex-col">
                        <label class="form-label font-bold text-gray-800 text-sm mb-2 required">Vagas</label>
                        <select name="seats"
                            class="form-select form-select-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors cursor-pointer"
                            required>
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
                    <input type="number" name="price" step="0.50" min="0"
                        class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors"
                        placeholder="Ex: 10.00" required>
                </div>

                <!-- Observações -->
                <div class="flex flex-col mb-7">
                    <label class="form-label font-bold text-gray-800 text-sm mb-2">Observações</label>
                    <input type="text" name="details"
                        class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors"
                        placeholder="Ex: Só aceito Pix, sem foto não levo">
                </div>

                <!-- Separator -->
                <div class="border-t border-gray-100 my-6"></div>

                <!-- Actions -->
                <div class="flex justify-end">
                    <button type="button" onclick="history.back()"
                        class="btn btn-light text-gray-600 hover:bg-gray-100 font-bold py-3 px-6 rounded-lg mr-3">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-submit-offer"
                        class="btn btn-primary font-bold py-3 px-8 rounded-lg shadow-lg shadow-primary/20 hover:shadow-primary/40 transition-shadow">
                        Publicar Carona
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    let lastRideData = null;

    function addWaypoint(name) {
        const input = document.getElementById('input-waypoints');
        let current = input.value.trim();
        if (current.length > 0 && !current.endsWith(',')) {
            current += ', ';
        }
        input.value = current + name;
    }

    async function checkLastRide() {
        try {
            const res = await fetch('api/get_last_ride_data.php');
            const data = await res.json();
            if (data.success) {
                lastRideData = data.data;
                document.getElementById('smart-replay-box').classList.remove('hidden');
                document.getElementById('last-route-text').innerText = `${lastRideData.origin} ➝ ${lastRideData.destination}`;
            }
        } catch (e) { }
    }

    function fillWithLastRide() {
        if (!lastRideData) return;

        $('input[name="origin"]').val(lastRideData.origin);
        $('input[name="destination"]').val(lastRideData.destination);
        $('input[name="price"]').val(lastRideData.price);
        $('select[name="seats"]').val(lastRideData.seats);
        $('input[name="details"]').val(lastRideData.details);

        // Parse waypoints
        try {
            const pts = JSON.parse(lastRideData.waypoints || '[]');
            if (Array.isArray(pts)) {
                $('textarea[name="waypoints"]').val(pts.join(', '));
            }
        } catch (e) {
            $('textarea[name="waypoints"]').val('');
        }

        // Animação de sucesso
        const btn = document.querySelector('#smart-replay-box button');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Preenchido!';
        btn.classList.replace('bg-primary', 'bg-green-500');
        setTimeout(() => {
            document.getElementById('smart-replay-box').classList.add('hidden');
        }, 1000);
    }

    function suggestDate() {
        const now = new Date();
        const hour = now.getHours();

        // Lógica de Sugestão Inteligente
        let target = new Date();
        if (hour < 12) {
            // Se é de manhã, sugere volta às 18h hoje
            target.setHours(18, 0, 0, 0);
        } else {
            // Se é tarde/noite, sugere ida amanhã às 08h
            target.setDate(target.getDate() + 1);
            target.setHours(8, 0, 0, 0);
        }

        // Formatar para input datetime-local (YYYY-MM-DDTHH:mm)
        // Correção de fuso horário local
        const pad = (n) => n.toString().padStart(2, '0');
        const str = `${target.getFullYear()}-${pad(target.getMonth() + 1)}-${pad(target.getDate())}T${pad(target.getHours())}:${pad(target.getMinutes())}`;

        $('input[name="departure_time"]').val(str);
    }

    document.addEventListener('DOMContentLoaded', () => {
        checkLastRide();
        suggestDate();

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
                    Swal.fire({
                        title: 'Carona Criada! 🚀',
                        text: 'Agora, publique no seu grupo de caronas para lotar rápido.',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: '📢 Publicar no WhatsApp <i class="bi bi-box-arrow-up-right text-[10px]"></i>',
                        cancelButtonText: 'Ir para o Feed',
                        customClass: {
                            confirmButton: 'bg-green-500 text-white font-bold px-6 py-3 rounded-2xl shadow-lg hover:bg-green-600 transition-all',
                            cancelButton: 'bg-gray-100 text-gray-500 font-bold px-6 py-3 rounded-2xl ml-2 hover:bg-gray-200'
                        },
                        buttonsStyling: false
                    }).then((r) => {
                        window.location.href = 'index.php?page=my_rides';
                        if (r.isConfirmed) {
                            const origem = data.origin;
                            const destino = data.destination;
                            const horaRaw = data.departure_time;
                            const hora = new Date(horaRaw).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            const valor = parseFloat(data.price).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            const rota = data.waypoints || 'Via padrão';

                            const link = `${window.location.origin}${window.location.pathname}?ride_id=${result.ride_id}`;
                            const textoZap = `🚗 *Vaga Disponível!*\n\n📍 *De:* ${origem}\n🏁 *Para:* ${destino}\n⏰ *Saída:* ${hora}\n🛣️ *Rota:* ${rota}\n💰 *Valor:* R$ ${valor}\n\n👉 *Garanta sua vaga:* ${link}`;
                            const urlZap = `https://wa.me/?text=${encodeURIComponent(textoZap)}`;

                            window.open(urlZap, '_blank');
                        }
                    });
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