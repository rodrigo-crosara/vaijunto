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

    document.addEventListener('DOMContentLoaded', () => {
        console.log('VaiJunto: App Lite Lite Ready');
    });
</script>
</body>

</html>