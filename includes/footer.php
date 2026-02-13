<!-- App Specific Scripts -->
<script>
    function shareRide(ride) {
        // Formatar Hora
        const date = new Date(ride.departure_time);
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        const timeFormatted = `${hours}:${minutes}`;

        // Formatar Valor
        const priceFormatted = parseFloat(ride.price).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

        // Formatar Rota/Waypoints
        let routeText = "";
        try {
            const points = JSON.parse(ride.waypoints || '[]');
            if (Array.isArray(points) && points.length > 0) {
                routeText = `%0a🛣️ *Rota:* ${points.join(', ')}`;
            }
        } catch (e) { }

        // Link Direto
        const link = `${window.location.origin}${window.location.pathname}?ride_id=${ride.id}`;

        // Montar Mensagem
        const msg = `🚘 *Vaga Disponível!*%0a` +
            `📍 *De:* ${ride.origin}%0a` +
            `🏁 *Para:* ${ride.destination}%0a` +
            `🕒 *Saída:* ${timeFormatted}%0a` +
            `${routeText}%0a` +
            `💰 *Valor:* ${priceFormatted}%0a%0a` +
            `📲 *Garanta sua vaga:*%0a${link}`;

        window.open(`https://wa.me/?text=${msg}`, '_blank');
    }

    // ===== SISTEMA DE AVALIAÇÃO PÓS-VIAGEM =====
    let pendingRatingData = null;

    async function checkPendingRating() {
        try {
            const res = await fetch('api/check_pending_rating.php');
            const data = await res.json();
            if (data.success && data.pending) {
                pendingRatingData = data;
                showRatingModal(data);
            }
        } catch (e) { console.log('Rating check skipped'); }
    }

    function showRatingModal(data) {
        const roleLabel = data.rated_role === 'driver' ? 'motorista' : 'passageiro';

        Swal.fire({
            html: `
                <div class="text-center py-2">
                    <img src="${data.other_user_photo}" class="w-20 h-20 rounded-full mx-auto mb-4 border-4 border-gray-100 shadow-lg object-cover">
                    <h3 class="text-lg font-black text-gray-900 mb-1">Como foi viajar com ${data.other_user_name.split(' ')[0]}?</h3>
                    <p class="text-xs text-gray-400 mb-1">${data.origin} → ${data.destination} • ${data.date}</p>
                    <p class="text-[10px] text-gray-300 uppercase font-bold tracking-widest mb-6">${roleLabel}</p>
                    
                    <div id="star-rating" class="flex justify-center gap-3 mb-6">
                        ${[1, 2, 3, 4, 5].map(i => `
                            <button type="button" onclick="selectStar(${i})" id="star-${i}" 
                                class="w-12 h-12 rounded-full bg-gray-100 text-gray-300 text-2xl flex items-center justify-center transition-all hover:scale-110 hover:bg-yellow-50 hover:text-yellow-400">
                                <i class="bi bi-star-fill"></i>
                            </button>
                        `).join('')}
                    </div>
                    <input type="hidden" id="rating-score" value="0">
                    
                    <textarea id="rating-comment" rows="2" 
                        class="w-full p-4 rounded-2xl bg-gray-50 border-0 text-sm font-medium resize-none focus:ring-2 focus:ring-primary/20 transition-all"
                        placeholder="Deixe um comentário (opcional)..."></textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '⭐ Enviar Avaliação',
            cancelButtonText: 'Depois',
            customClass: {
                popup: 'rounded-[2.5rem] !p-6',
                confirmButton: 'bg-primary text-white font-bold px-8 py-3 rounded-2xl shadow-lg hover:shadow-primary/40 transition-all',
                cancelButton: 'bg-gray-100 text-gray-400 font-bold px-6 py-3 rounded-2xl ml-2 hover:bg-gray-200'
            },
            buttonsStyling: false,
            allowOutsideClick: false,
            preConfirm: () => {
                const score = document.getElementById('rating-score').value;
                if (score == 0) {
                    Swal.showValidationMessage('Toque nas estrelas para avaliar!');
                    return false;
                }
                return {
                    score: parseInt(score),
                    comment: document.getElementById('rating-comment').value
                };
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch('api/submit_rating.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            booking_id: data.booking_id,
                            score: result.value.score,
                            comment: result.value.comment
                        })
                    });
                    const resp = await res.json();
                    if (resp.success) {
                        Swal.fire({
                            title: 'Obrigado! 🌟',
                            text: `Sua avaliação foi registrada.`,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-[2.5rem]' }
                        });
                    } else {
                        Swal.fire({ text: resp.message, icon: 'error' });
                    }
                } catch (e) {
                    Swal.fire({ text: 'Erro de conexão.', icon: 'error' });
                }
            }
        });
    }

    function selectStar(n) {
        document.getElementById('rating-score').value = n;
        for (let i = 1; i <= 5; i++) {
            const btn = document.getElementById('star-' + i);
            if (i <= n) {
                btn.classList.remove('bg-gray-100', 'text-gray-300');
                btn.classList.add('bg-yellow-400', 'text-white', 'scale-110', 'shadow-lg', 'shadow-yellow-200');
            } else {
                btn.classList.remove('bg-yellow-400', 'text-white', 'scale-110', 'shadow-lg', 'shadow-yellow-200');
                btn.classList.add('bg-gray-100', 'text-gray-300');
            }
        }
    }

    // ===== SISTEMA DE POLLING DE NOTIFICAÇÕES =====
    let knownNotifIds = new Set();
    let firstPoll = true;

    async function pollNotifications() {
        try {
            const res = await fetch('api/check_notifications.php');
            const data = await res.json();

            // Atualizar badge do sino
            const badge = document.getElementById('notif-badge');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count > 9 ? '9+' : data.count;
                    badge.classList.remove('hidden');
                    badge.classList.add('flex');
                } else {
                    badge.classList.add('hidden');
                    badge.classList.remove('flex');
                }
            }

            // Toasts para notificações NOVAS (não no primeiro poll)
            if (!firstPoll && data.notifications) {
                data.notifications.forEach(n => {
                    if (!knownNotifIds.has(n.id)) {
                        // Nova notificação! Toast!
                        const iconMap = {
                            'booking': 'success',
                            'cancel': 'warning',
                            'confirmed': 'success',
                            'payment': 'success',
                            'system': 'info'
                        };
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: iconMap[n.type] || 'info',
                            title: n.message,
                            showConfirmButton: false,
                            timer: 4000,
                            timerProgressBar: true,
                            customClass: { popup: 'rounded-2xl !text-sm' },
                            didOpen: (toast) => {
                                toast.addEventListener('click', () => {
                                    if (n.link_url) window.location.href = n.link_url;
                                });
                            }
                        });
                    }
                });
            }

            // Atualizar IDs conhecidos
            if (data.notifications) {
                data.notifications.forEach(n => knownNotifIds.add(n.id));
            }
            firstPoll = false;

        } catch (e) {
            // Silencioso
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        console.log('VaiJunto: App Ready');
        // Verificar avaliações pendentes ao carregar qualquer página
        checkPendingRating();
        // Polling de notificações: imediato + a cada 15s
        pollNotifications();
        setInterval(pollNotifications, 15000);
    });
</script>
</body>

</html>