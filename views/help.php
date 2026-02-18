<?php
/**
 * View: Páginas de Ajuda, Termos e Privacidade
 */

$section = $_GET['section'] ?? 'faq';
?>

<div class="max-w-2xl mx-auto pt-6 px-4 pb-24">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Ajuda & Termos</h1>

    <!-- Tabs -->
    <div class="flex space-x-2 mb-6 bg-gray-100 p-1 rounded-xl">
        <a href="index.php?page=help&section=faq"
            class="flex-1 text-center py-2 text-sm font-bold rounded-lg transition-all <?= $section == 'faq' ? 'bg-white shadow text-primary' : 'text-gray-500' ?>">
            FAQ
        </a>
        <a href="index.php?page=help&section=terms"
            class="flex-1 text-center py-2 text-sm font-bold rounded-lg transition-all <?= $section == 'terms' ? 'bg-white shadow text-primary' : 'text-gray-500' ?>">
            Termos
        </a>
    </div>

    <!-- Content -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
        <?php if ($section == 'faq'): ?>
            <div class="space-y-6">
                <details class="group">
                    <summary class="flex justify-between items-center font-bold cursor-pointer list-none text-gray-800">
                        <span>Como crio uma carona?</span>
                        <span class="transition group-open:rotate-180">
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </summary>
                    <p class="text-gray-600 mt-3 text-sm leading-relaxed group-open:animate-fadeIn">
                        Para oferecer uma carona, você precisa preencher seu perfil com foto e dados do carro. Depois,
                        clique no botão "+" no menu inferior e preencha os detalhes da viagem.
                    </p>
                </details>

                <hr class="border-gray-100">

                <details class="group">
                    <summary class="flex justify-between items-center font-bold cursor-pointer list-none text-gray-800">
                        <span>Como pago pela carona?</span>
                        <span class="transition group-open:rotate-180">
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </summary>
                    <p class="text-gray-600 mt-3 text-sm leading-relaxed group-open:animate-fadeIn">
                        O pagamento é combinado diretamente com o motorista, geralmente via Pix. O app apenas conecta vocês.
                    </p>
                </details>

                <hr class="border-gray-100">

                <details class="group">
                    <summary class="flex justify-between items-center font-bold cursor-pointer list-none text-gray-800">
                        <span>Esqueci meu PIN, e agora?</span>
                        <span class="transition group-open:rotate-180">
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </summary>
                    <p class="text-gray-600 mt-3 text-sm leading-relaxed group-open:animate-fadeIn">
                        Na tela de login, clique em "Esqueci meu PIN" para falar com o suporte e solicitar o reset da sua
                        senha.
                    </p>
                </details>
            </div>

        <?php elseif ($section == 'terms'): ?>
            <div class="prose prose-sm text-gray-600">
                <h3 class="text-gray-900 font-bold mb-2">1. Termos de Uso</h3>
                <p class="mb-4">
                    Ao usar o Carona.online, você concorda que o serviço é apenas uma plataforma de conexão entre motoristas
                    e
                    passageiros. Não nos responsabilizamos por cancelamentos, atrasos ou incidentes durante o trajeto.
                </p>

                <h3 class="text-gray-900 font-bold mb-2">2. Privacidade</h3>
                <p class="mb-4">
                    Seus dados (telefone e foto) são compartilhados apenas com as pessoas com quem você confirma uma viagem.
                    Não vendemos seus dados para terceiros.
                </p>

                <h3 class="text-gray-900 font-bold mb-2">3. Regras de Conduta</h3>
                <p>
                    Respeito é fundamental. Usuários reportados por comportamento inadequado serão banidos permanentemente
                    da plataforma.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contact Support -->
    <div class="mt-8 text-center">
        <p class="text-gray-400 text-xs mb-2">Ainda precisa de ajuda?</p>
        <a href="https://wa.me/5561999999999" target="_blank"
            class="inline-flex items-center gap-2 text-primary font-bold text-sm hover:underline">
            <i class="bi bi-whatsapp"></i> Falar com Suporte
        </a>
    </div>
</div>