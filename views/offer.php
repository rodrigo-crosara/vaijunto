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
                class="hidden bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-center gap-3 w-full">
                    <div class="bg-blue-100 text-primary w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                        <i class="bi bi-arrow-repeat text-xl"></i>
                    </div>
                    <div class="flex-grow overflow-hidden">
                        <p class="text-sm font-bold text-gray-800">Fazer o trajeto de sempre?</p>
                        <p id="last-route-text" class="text-xs text-gray-500 truncate w-full"></p>
                    </div>
                </div>
                <button type="button" onclick="fillWithLastRide()"
                    class="w-full sm:w-auto text-sm font-bold bg-primary text-white px-5 py-3 rounded-xl hover:bg-blue-600 transition-colors flex items-center justify-center gap-2 shrink-0 shadow-md">
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
                    <!-- Toggle de Repetição -->
                    <div class="col-span-1 mb-4 sm:mb-0">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input cursor-pointer" type="checkbox" id="chk-repeat"
                                onchange="toggleRepeat()">
                            <label class="form-check-label text-sm font-bold text-gray-700" for="chk-repeat">Repetir na
                                semana?</label>
                        </div>
                    </div>

                    <!-- Single Date Input -->
                    <div id="single-date-container" class="flex flex-col">
                        <label class="form-label font-bold text-gray-800 text-sm mb-2 required">Data e Hora</label>
                        <input type="datetime-local" name="departure_time"
                            class="form-control form-control-solid rounded-lg p-3 bg-gray-50 border-gray-200 focus:bg-white transition-colors"
                            required>
                    </div>

                    <!-- Repeat Days Container (Hidden by Default) -->
                    <div id="repeat-days-container" class="hidden flex flex-col">
                        <label class="form-label font-bold text-gray-800 text-sm mb-2 required">Selecione os dias (Próx.
                            2 sem)</label>
                        <div class="flex gap-2 flex-wrap mb-3">
                            <?php
                            $days = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'];
                            foreach ($days as $idx => $day): ?>
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="repeat_days[]" value="<?= $idx + 1 ?>"
                                        class="peer sr-only">
                                    <span
                                        class="inline-block px-3 py-2 bg-gray-100 peer-checked:bg-primary peer-checked:text-white rounded-lg text-xs font-bold transition-all border border-gray-200 peer-checked:border-primary"><?= $day ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <label class="text-xs font-bold text-gray-500 mb-1">Horário (Fixo)</label>
                        <input type="time" name="repeat_time"
                            class="form-control form-control-solid rounded-lg p-2 bg-gray-50 border-gray-200">
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
                        placeholder="Ex: Sem foto não levo">
                </div>

                <!-- Separator -->
                <div class="border-t border-gray-100 my-6"></div>

                <!-- Actions -->
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-4">
                    <button type="button" onclick="history.back()"
                        class="w-full sm:w-auto bg-gray-100 text-gray-600 hover:bg-gray-200 font-bold py-4 px-6 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-submit-offer"
                        class="w-full sm:w-auto bg-primary text-white font-extrabold py-4 px-8 rounded-xl shadow-xl shadow-primary/30 hover:bg-blue-700 transition-all flex justify-center items-center text-lg">
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
        // Check for session storage repeat data first
        const repeatData = sessionStorage.getItem('repeat_ride_data');
        if (repeatData) {
            lastRideData = JSON.parse(repeatData);
            fillWithLastRide();
            sessionStorage.removeItem('repeat_ride_data'); // Clear after use
            return;
        }

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

        // Parse waypoints - handle both string and array formats
        let pts = [];
        if (typeof lastRideData.waypoints === 'string') {
            try {
                pts = JSON.parse(lastRideData.waypoints);
            } catch (e) {
                // Might be a plain string comma separated?
                if (lastRideData.waypoints.includes('[')) {
                    pts = [];
                } else {
                    pts = lastRideData.waypoints.split(',').map(s => s.trim());
                }
            }
        } else if (Array.isArray(lastRideData.waypoints)) {
            pts = lastRideData.waypoints;
        }

        if (Array.isArray(pts)) {
            $('textarea[name="waypoints"]').val(pts.join(', '));
        }

        // Animação de sucesso
        const btn = document.querySelector('#smart-replay-box button');
        if (btn) {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Preenchido!';
            btn.classList.replace('bg-primary', 'bg-green-500');
            setTimeout(() => {
                document.getElementById('smart-replay-box').classList.add('hidden');
            }, 1000);
        }
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
        // Default time for repetition fields
        $('input[name="repeat_time"]').val(`${pad(target.getHours())}:${pad(target.getMinutes())}`);
    }

    function toggleRepeat() {
        const isRepeat = document.getElementById('chk-repeat').checked;
        const singleDateDiv = document.getElementById('single-date-container');
        const repeatDiv = document.getElementById('repeat-days-container');

        if (isRepeat) {
            singleDateDiv.classList.add('hidden');
            singleDateDiv.querySelector('input').removeAttribute('required');
            repeatDiv.classList.remove('hidden');
        } else {
            singleDateDiv.classList.remove('hidden');
            singleDateDiv.querySelector('input').setAttribute('required', 'true');
            repeatDiv.classList.add('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        checkLastRide();
        suggestDate();

        const form = document.getElementById('offer-form');
        const btnParam = document.getElementById('btn-submit-offer');

        // Global Loading State for buttons
        $('form').on('submit', function () {
            const btn = $(this).find('button[type="submit"]');
            if (btn.length && !btn.prop('disabled')) {
                btn.data('original-text', btn.html());
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Processando...');
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Loading state handled globally, but we might need specific handling here if we preventDefault
            // Since we preventDefault, the global handler might fire but we control the flow.
            // Let's ensure the button is locked.
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
                    // Captura origem, destino e rota do formulário para deixar a mensagem rica
                    const origem = $('input[name="origin"]').val() || 'Origem';
                    const destino = $('input[name="destination"]').val() || 'Destino';
                    const rotaRaw = $('textarea[name="waypoints"]').val() || '';
                    const rotaFormatada = rotaRaw && rotaRaw.trim() !== '' ? rotaRaw : 'Via padrão';

                    // A cereja do bolo: URL super curta!
                    const linkStr = `${window.location.origin}/${result.ride_id}`;

                    const texto = `🚗 *Carona Online - Nova Carona!*\n\n📍 De: ${origem}\n🏁 Para: ${destino}\n🛣️ Rota: ${rotaFormatada}\n\n👉 *Reserve aqui:* ${linkStr}`;
                    const waLink = `https://wa.me/?text=${encodeURIComponent(texto)}`;

                    Swal.fire({
                        title: 'Carona Criada! 🚀',
                        text: 'Deseja divulgar sua vaga no grupo do WhatsApp agora?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: '<i class="bi bi-whatsapp"></i> Divulgar no Grupo',
                        cancelButtonText: 'Agora Não',
                        customClass: {
                            confirmButton: 'bg-green-500 text-white font-bold px-6 py-3 rounded-2xl shadow-lg hover:scale-105 transition-all',
                            cancelButton: 'bg-gray-100 text-gray-500 font-bold px-6 py-3 rounded-2xl ml-2'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Abre o WhatsApp em nova aba
                            window.open(waLink, '_blank');
                        }
                        // Redireciona a aba original para o painel do motorista
                        window.location.href = 'index.php?page=my_rides';
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
                btnParam.innerHTML = btnParam.getAttribute('data-original-text') || 'Publicar Carona';
                btnParam.disabled = false;
            }
        });
    });
</script>