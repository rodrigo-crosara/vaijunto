<?php
/**
 * View: Login (Pura & Limpa)
 */
?>

<div class="flex flex-col items-center justify-center min-h-full px-6 py-12">
    <!-- Header/Logo -->
    <div class="text-center mb-12">
        <div
            class="w-20 h-20 bg-primary rounded-[2.5rem] flex items-center justify-center mx-auto mb-6 shadow-2xl shadow-primary/30">
            <i class="bi bi-car-front-fill text-white text-4xl"></i>
        </div>
        <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">Vai<span class="text-primary">Junto</span></h1>
        <p class="text-gray-500 mt-2 font-medium">Caronas sem atrito para sua rotina.</p>
    </div>

    <!-- Login Card -->
    <div class="w-full max-w-sm">
        <form id="login-form" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">E-mail
                    Corporativo</label>
                <input type="email" name="email" required
                    class="w-full p-4 rounded-3xl border border-gray-100 bg-white shadow-sm focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-gray-700 font-medium"
                    placeholder="voce@empresa.com">
            </div>

            <div class="relative">
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Sua
                    Senha</label>
                <input type="password" name="password" required
                    class="w-full p-4 rounded-3xl border border-gray-100 bg-white shadow-sm focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-gray-700 font-medium"
                    placeholder="••••••••">
            </div>

            <button type="submit"
                class="w-full py-5 bg-primary text-white font-extrabold rounded-3xl shadow-xl shadow-primary/30 hover:scale-[1.02] active:scale-95 transition-all text-lg mt-6">
                Entrar no App
            </button>
        </form>

        <div class="mt-8 text-center text-sm text-gray-400">
            Ainda não tem conta? <a href="#" class="text-primary font-bold hover:underline">Cadastre-se</a>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#login-form').on('submit', function (e) {
            e.preventDefault();
            const btn = $(this).find('button');
            const originalText = btn.html();

            btn.prop('disabled', true).html('<span class="flex items-center justify-center gap-2"><i class="bi bi-arrow-repeat animate-spin"></i> Entrando...</span>');

            $.ajax({
                url: 'api/login.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        Swal.fire({
                            title: 'Ops!',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'Tentar novamente',
                            customClass: { confirmButton: 'bg-primary text-white px-8 py-3 rounded-2xl font-bold' },
                            buttonsStyling: false
                        });
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function () {
                    Swal.fire({ text: 'Erro na conexão.', icon: 'error' });
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });
    });
</script>