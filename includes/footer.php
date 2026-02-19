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
        const msg = `🚘 *Carona.online - Vaga Disponível!*%0a` +
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
    <?php if (isset($_SESSION['user_id'])): ?>
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

            }

            // Toasts e Alertas para notificações NOVAS (não no primeiro poll)
            if (!firstPoll && data.notifications) {
                let hasNew = false;
                data.notifications.forEach(n => {
                    if (!knownNotifIds.has(n.id)) {
                        hasNew = true;
                        // Nova notificação! Toast Visual
                        const iconMap = {
                            'booking': 'success',
                            'cancel': 'warning',
                            'confirmed': 'success',
                            'payment': 'success',
                            'system': 'info'
                        };
                        
                        // Toast Visual do App
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

                        // Notificação do Sistema (Nativo)
                        if (Notification.permission === "granted") {
                           try {
                                new Notification("Carona.online", {
                                    body: n.message,
                                    icon: "assets/media/app/icon-192.png",
                                    vibrate: [200, 100, 200],
                                    tag: 'carona-online-' + n.id
                                });
                           } catch(e) {}
                        }
                    }
                });

                // Feedback Físico (Som e Vibração) - Apenas uma vez por lote
                if (hasNew) {
                    // Vibrar
                    if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
                    
                    // Som
                    try {
                        const audio = new Audio('assets/media/notification.mp3');
                        audio.volume = 0.5;
                        audio.play().catch(() => {}); // Falha silenciosa se não houver interação prévia
                    } catch (e) {}
                }
            }

            // Atualizar IDs conhecidos
            if (data.notifications) {
                data.notifications.forEach(n => knownNotifIds.add(n.id));
            }
            firstPoll = false;

        } catch (e) {
            // Silencioso
        }
        } catch (e) {
            // Silencioso
        }
    }
    <?php else: ?>
    function pollNotifications() {} // No-op for guests
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', () => {
        console.log('Carona.online: App Ready');
        // Verificar avaliações pendentes ao carregar qualquer página
        checkPendingRating();
        // Polling de notificações: imediato + a cada 15s
        pollNotifications();
        setInterval(pollNotifications, 15000);

        // Registrar Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('./sw.js')
                    .then(reg => console.log('SW registrado!', reg.scope))
                    .catch(err => console.log('SW falhou:', err));
            });
        }

        // --- MONITORAMENTO DE REDE (OFFLINE/ONLINE) ---
        let offlineToastId = null;

        window.addEventListener('offline', () => {
            document.body.classList.add('is-offline');
            Swal.fire({
                toast: true,
                position: 'top',
                icon: 'warning',
                title: 'Sem conexão. O app está offline.',
                showConfirmButton: false,
                timer: 0,
                didOpen: (toast) => {
                    offlineToastId = toast;
                }
            });
        });

        window.addEventListener('online', () => {
            document.body.classList.remove('is-offline');
            if (Swal.isVisible() && Swal.getIcon()?.classList.contains('swal2-warning')) {
                Swal.close();
            }

            Swal.fire({
                toast: true,
                position: 'top',
                icon: 'success',
                title: 'Conexão restabelecida!',
                showConfirmButton: false,
                timer: 3000
            });

            // Recarregar se estiver em páginas dinâmicas para atualizar dados
            if (window.location.search.includes('page=home') || window.location.search.includes('page=notifications')) {
                setTimeout(() => location.reload(), 1500);
            }
        });

        // Solicitar Permissão de Notificações
        if (Notification.permission === 'default') {
            setTimeout(() => {
                Swal.fire({
                    toast: true,
                    position: 'top',
                    icon: 'info',
                    title: 'Ativar Alertas Sonoros? 🔔',
                    showConfirmButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Sim',
                    cancelButtonText: 'Não',
                    customClass: { popup: 'rounded-2xl !text-sm' }
                }).then((res) => {
                    if (res.isConfirmed) {
                        Notification.requestPermission();
                    }
                });
            }, 5000); // 5s delay
        }

        // Iniciar Tour
        setTimeout(checkFirstVisit, 1500);
        // Tentar mostrar install (se não for tour, mostra direto, se for tour, mostra no final)
        if (localStorage.getItem('tutorial_seen')) {
            setTimeout(showInstallPromotion, 3000);
        }
    });

    // Capturar evento de instalação
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        console.log('PWA: Pronto para instalar');
        showInstallPromotion(); // Se disponível, mostrar
    });

    // ===== ONBOARDING (BOAS-VINDAS) =====
    function checkFirstVisit() {
        if (!localStorage.getItem('tutorial_seen')) {
            const steps = [
                {
                    title: 'Bem-vindo ao Carona.online!',
                    text: 'A forma mais fácil de dividir custos e fazer amigos. 👋',
                    icon: 'info',
                    confirmButtonText: 'Próximo >'
                },
                {
                    title: 'Como funciona?',
                    text: '1. Ache a carona.\n2. Solicite a vaga.\n3. Combine tudo pelo WhatsApp do motorista! 💬',
                    icon: 'question',
                    confirmButtonText: 'Entendi >'
                },
                {
                    title: 'Tudo pronto!',
                    text: 'Instale nosso App para receber notificações. 🚀',
                    icon: 'success',
                    confirmButtonText: 'Começar!'
                }
            ];

            // Queue Swals
            let stepIndex = 0;
            const showStep = () => {
                if (stepIndex >= steps.length) {
                    localStorage.setItem('tutorial_seen', 'true');
                    // Tentar mostrar install promo após o tutorial
                    if (deferredPrompt) showInstallPromotion();
                    return;
                }
                const s = steps[stepIndex];
                Swal.fire({
                    title: s.title,
                    text: s.text,
                    icon: s.icon,
                    confirmButtonText: s.confirmButtonText,
                    customClass: {
                        popup: 'rounded-[2.5rem] !p-6',
                        confirmButton: 'bg-primary text-white font-bold px-8 py-3 rounded-2xl shadow-lg'
                    },
                    allowOutsideClick: false
                }).then(() => {
                    stepIndex++;
                    showStep();
                });
            };
            showStep();
        }
    }

    // ===== PROMOÇÃO DE INSTALAÇÃO (BANNER/TOAST) =====
    function showInstallPromotion() {
        if (localStorage.getItem('install_dismissed')) return;

        // Verificar se está em modo standalone (já instalado)
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
        if (isStandalone) return;

        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

        if (isIOS) {
            // Toast iOS
            Swal.fire({
                toast: true,
                position: 'bottom',
                html: `
                    <div class="flex flex-col items-start gap-2">
                        <span class="font-bold text-sm">📲 Instale o App para melhor experiência!</span>
                        <span class="text-xs">Toque em <span class="font-bold">Compartilhar</span> <i class="bi bi-box-arrow-up"></i> e depois em <span class="font-bold">Adicionar à Tela de Início</span> ➕</span>
                         <button onclick="dismissInstall()" class="text-primary text-xs font-bold mt-1 self-end">Entendi</button>
                    </div>
                `,
                showConfirmButton: false,
                customClass: { popup: 'rounded-t-3xl !pb-8 !px-6 shadow-[0_-10px_40px_rgba(0,0,0,0.1)]' },
                timer: 0 // Persistente
            });
        } else if (deferredPrompt) {
            // Android / Desktop Button
            const btn = document.createElement('div');
            btn.id = 'install-fab';
            btn.className = 'fixed bottom-24 left-1/2 -translate-x-1/2 z-40 animate-bounce cursor-pointer';
            btn.innerHTML = `
                <button onclick="triggerInstall()" class="bg-primary text-white font-bold text-sm px-6 py-3 rounded-full shadow-2xl flex items-center gap-2 border-2 border-white">
                    <i class="bi bi-phone"></i> Instalar App
                </button>
                <div class="text-center mt-1">
                    <button onclick="dismissInstall()" class="text-[10px] text-gray-400 font-bold bg-white/80 px-2 rounded">Não agora</button>
                </div>
            `;
            document.body.appendChild(btn);
        }
    }

    function triggerInstall() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((result) => {
                if (result.outcome === 'accepted') {
                    console.log('User accepted install');
                    dismissInstall();
                }
                deferredPrompt = null;
            });
        }
    }

    window.dismissInstall = function () { // Global scope for HTML onclick
        localStorage.setItem('install_dismissed', 'true');
        // Remove FAB if exists
        const fab = document.getElementById('install-fab');
        if (fab) fab.remove();
        // Close Swal if exists (iOS)
        Swal.close();
    };
</script>
</body>

</html>