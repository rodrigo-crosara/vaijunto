<?php
if (empty($_SESSION['is_admin'])) {
    header("Location: index.php?page=home");
    exit;
}
?>

<div class="max-w-4xl mx-auto pt-6 px-4 pb-24">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-black text-gray-900 leading-tight">Painel de Controle</h1>
            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Moderação & Estatísticas</p>
        </div>
        <div class="bg-gray-900 text-white px-4 py-2 rounded-2xl text-[10px] font-bold uppercase tracking-widest">
            Admin Mode
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
            <span class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Usuários</span>
            <span id="stat-users" class="text-2xl font-black text-gray-900">...</span>
        </div>
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
            <span class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Caronas Hoje</span>
            <span id="stat-rides" class="text-2xl font-black text-primary">...</span>
        </div>
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
            <span class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Reservas</span>
            <span id="stat-bookings" class="text-2xl font-black text-gray-900">...</span>
        </div>
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
            <span class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Giro Estimado</span>
            <span id="stat-revenue" class="text-2xl font-black text-green-500">...</span>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8 mb-10">
        <!-- Novos Usuários -->
        <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-gray-100">
            <h3 class="text-sm font-black text-gray-900 mb-6 uppercase tracking-tight">Novos Usuários</h3>
            <div id="list-users" class="space-y-4">
                <!-- JS Fill -->
                <div class="animate-pulse flex gap-4">
                    <div class="w-10 h-10 bg-gray-100 rounded-full"></div>
                    <div class="flex-1 space-y-2 py-1">
                        <div class="h-2 bg-gray-100 rounded w-3/4"></div>
                        <div class="h-2 bg-gray-100 rounded w-1/2"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimas Caronas -->
        <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-gray-100">
            <h3 class="text-sm font-black text-gray-900 mb-6 uppercase tracking-tight">Caronas Recentes</h3>
            <div id="list-rides" class="space-y-4">
                <!-- JS Fill -->
                <div class="animate-pulse space-y-4">
                    <div class="h-12 bg-gray-50 rounded-2xl"></div>
                    <div class="h-12 bg-gray-50 rounded-2xl"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manutenção do Sistema -->
    <div>
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 ml-1">Manutenção do Sistema 🧹</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Faxina -->
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
                <div class="w-10 h-10 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center mb-4">
                    <i class="bi bi-eraser-fill text-xl"></i>
                </div>
                <h4 class="text-sm font-bold text-gray-900 mb-1">Limpeza de Órfãos</h4>
                <p class="text-[10px] text-gray-400 mb-4 leading-relaxed">Remove fotos que não estão vinculadas a nenhum usuário ou carro.</p>
                <button onclick="runMaintenance('cleanup_files', 'Deseja iniciar a faxina de arquivos?')" 
                    class="w-full bg-blue-50 text-blue-600 py-2.5 rounded-xl font-bold text-[10px] uppercase hover:bg-blue-600 hover:text-white transition-all">
                    Executar Faxina
                </button>
            </div>

            <!-- Broadcast -->
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
                <div class="w-10 h-10 bg-orange-50 text-orange-500 rounded-xl flex items-center justify-center mb-4">
                    <i class="bi bi-megaphone-fill text-xl"></i>
                </div>
                <h4 class="text-sm font-bold text-gray-900 mb-1">Alerta Geral</h4>
                <p class="text-[10px] text-gray-400 mb-4 leading-relaxed">Envia uma notificação para todos os usuários do sistema.</p>
                <button onclick="sendBroadcast()" 
                    class="w-full bg-orange-50 text-orange-600 py-2.5 rounded-xl font-bold text-[10px] uppercase hover:bg-orange-600 hover:text-white transition-all">
                    Escrever Mensagem
                </button>
            </div>

            <!-- Purge -->
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
                <div class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center mb-4">
                    <i class="bi bi-trash-fill text-xl"></i>
                </div>
                <h4 class="text-sm font-bold text-gray-900 mb-1">Expurgo de Histórico</h4>
                <p class="text-[10px] text-gray-400 mb-4 leading-relaxed">Apaga caronas canceladas ou concluídas há mais de 6 meses.</p>
                <button onclick="runMaintenance('purge_old', 'Deseja apagar registros com mais de 6 meses?')" 
                    class="w-full bg-red-50 text-red-600 py-2.5 rounded-xl font-bold text-[10px] uppercase hover:bg-red-600 hover:text-white transition-all">
                    Limpar Antigos
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    async function loadStats() {
        try {
            const res = await fetch('api/admin_stats.php');
            const data = await res.json();

            if (data.success) {
                // KPIs
                document.getElementById('stat-users').textContent = data.stats.total_users;
                document.getElementById('stat-rides').textContent = data.stats.rides_today;
                document.getElementById('stat-bookings').textContent = data.stats.total_bookings;
                document.getElementById('stat-revenue').textContent = data.stats.revenue.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

                // Usuários
                const userList = document.getElementById('list-users');
                userList.innerHTML = data.recent_users.map(u => `
                    <div class="flex items-center justify-between group">
                        <div class="flex items-center gap-3">
                            <img src="${u.photo_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(u.name || u.phone)}" class="w-10 h-10 rounded-full object-cover">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-gray-800">${u.name || 'Sem Nome'}</span>
                                <span class="text-[10px] text-gray-400">${u.phone}</span>
                            </div>
                        </div>
                        <button onclick="banUser(${u.id})" class="opacity-0 group-hover:opacity-100 transition-all text-[10px] font-bold text-red-400 hover:text-red-600 uppercase">Banir</button>
                    </div>
                `).join('');

                // Caronas
                const rideList = document.getElementById('list-rides');
                rideList.innerHTML = data.recent_rides.map(r => `
                    <div class="bg-gray-50 rounded-2xl p-4 flex items-center justify-between border border-transparent hover:border-gray-200 transition-all">
                        <div class="min-w-0 pr-4">
                            <div class="flex items-center gap-2 text-[10px] font-bold text-gray-900 truncate mb-1">
                                <span>${r.origin_text}</span>
                                <i class="bi bi-arrow-right text-gray-300"></i>
                                <span>${r.destination_text}</span>
                            </div>
                            <span class="text-[10px] text-gray-400 font-medium">${r.driver_name} • ${new Date(r.departure_time).toLocaleDateString('pt-BR')}</span>
                        </div>
                        <button onclick="deleteRide(${r.id})" class="text-gray-300 hover:text-red-500 transition-colors">
                            <i class="bi bi-trash3 text-sm"></i>
                        </button>
                    </div>
                `).join('');
            }
        } catch (e) {
            console.error('Erro ao carregar dashboard admin', e);
        }
    }

    async function adminAction(apiUrl, data, confirmText = "Esta ação não pode ser desfeita.") {
        const confirm = await Swal.fire({
            title: 'Confirmar Ação?',
            text: confirmText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, executar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'bg-primary text-white font-bold px-8 py-3 rounded-2xl shadow-lg',
                cancelButton: 'bg-gray-100 text-gray-400 font-bold px-6 py-3 rounded-2xl ml-2'
            },
            buttonsStyling: false
        });

        if (confirm.isConfirmed) {
            Swal.fire({
                title: 'Processando...',
                html: 'Aguarde um momento',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const res = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire({ text: result.message, icon: 'success', customClass: { popup: 'rounded-[2rem]' } }).then(() => loadStats());
                } else {
                    Swal.fire({ text: result.message, icon: 'error' });
                }
            } catch (err) {
                Swal.fire({ text: 'Erro na conexão.', icon: 'error' });
            }
        }
    }

    function banUser(userId) {
        adminAction('api/admin_actions.php', { action: 'ban_user', user_id: userId });
    }

    function deleteRide(rideId) {
        adminAction('api/admin_actions.php', { action: 'delete_ride', ride_id: rideId });
    }

    function runMaintenance(action, confirmText) {
        adminAction('api/admin_maintenance.php', { action: action }, confirmText);
    }

    async function sendBroadcast() {
        const { value: text } = await Swal.fire({
            title: 'Enviar Alerta Geral 📢',
            input: 'textarea',
            inputLabel: 'Esta mensagem aparecerá no sino de todos os usuários.',
            inputPlaceholder: 'Digite o alerta aqui...',
            inputAttributes: { 'aria-label': 'Digite o alerta aqui' },
            showCancelButton: true,
            confirmButtonText: 'Enviar para Todos',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'rounded-[2.5rem]',
                confirmButton: 'bg-orange-500 text-white font-bold px-8 py-3 rounded-2xl shadow-lg',
                cancelButton: 'bg-gray-100 text-gray-400 font-bold px-6 py-3 rounded-2xl ml-2'
            },
            buttonsStyling: false
        });

        if (text) {
            adminAction('api/admin_maintenance.php', { action: 'broadcast', message: text }, "Enviar agora?");
        }
    }

    document.addEventListener('DOMContentLoaded', loadStats);
</script>