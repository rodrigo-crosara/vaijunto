<?php
/**
 * View: Perfil e Configurações (Glass & Lite)
 */
$stmt = $pdo->prepare("SELECT u.*, c.model, c.color, c.plate FROM users u LEFT JOIN cars c ON c.user_id = u.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$avatar = $user['photo_url'] ?: "https://ui-avatars.com/api/?name=" . urlencode($user['name'] ?: 'User') . "&background=random";
$isDriver = (bool) $user['is_driver'];
$msg = $_GET['msg'] ?? '';
?>

<!-- Container Principal -->
<div class="pb-24">

    <!-- Alerta de Onboarding -->
    <?php if ($msg === 'complete_registration' || empty($user['name'])): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-r-xl shadow-sm animate-pulse">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-exclamation-triangle-fill text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700 font-bold">
                        Bem-vindo! Antes de viajar, precisamos te conhecer.
                        <span class="block font-normal mt-1">Adicione uma foto e seu nome para continuar.</span>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Cabeçalho do Perfil -->
    <div class="relative mb-8 text-center">
        <form id="avatar-form" enctype="multipart/form-data">
            <div class="relative inline-block group cursor-pointer"
                onclick="document.getElementById('photo-input').click()">
                <img id="avatar-preview" src="<?= $avatar ?>"
                    class="w-32 h-32 rounded-full border-4 border-white shadow-xl object-cover" alt="Avatar">
                <input type="file" name="photo" id="photo-input" class="hidden" accept="image/*"
                    onchange="uploadPhoto(this)">

                <!-- Overlay de Edição -->
                <div
                    class="absolute inset-0 bg-black/30 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="bi bi-camera-fill text-white text-2xl"></i>
                </div>
                <div
                    class="absolute bottom-0 right-0 bg-primary text-white p-2 rounded-full shadow-lg border-2 border-white">
                    <i class="bi bi-pencil-fill text-xs"></i>
                </div>
            </div>
        </form>
        <h2 class="text-2xl font-black text-gray-900 mt-4"><?= htmlspecialchars($user['name'] ?: 'Visitante') ?></h2>
        <div class="flex items-center justify-center gap-1 text-yellow-500 mt-1">
            <i class="bi bi-star-fill text-sm"></i>
            <span class="font-bold text-gray-600"><?= $user['reputation'] ?></span>
        </div>
    </div>

    <!-- Formulário Principal -->
    <form id="profile-form" class="space-y-6">

        <!-- Dados Pessoais -->
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-50">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-person-badge text-primary"></i> Dados Pessoais
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Nome
                        Completo</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required
                        class="w-full p-4 rounded-2xl bg-gray-50 border-0 focus:ring-2 focus:ring-primary/20 font-medium transition-all"
                        placeholder="Como quer ser chamado?">
                </div>

                <div>
                    <label
                        class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">WhatsApp</label>
                    <input type="text" value="<?= htmlspecialchars($user['phone']) ?>" disabled
                        class="w-full p-4 rounded-2xl bg-gray-100 text-gray-400 font-medium cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Bio
                        Curta</label>
                    <textarea name="bio" rows="2"
                        class="w-full p-4 rounded-2xl bg-gray-50 border-0 focus:ring-2 focus:ring-primary/20 font-medium transition-all"
                        placeholder="Ex: Trabalho no Setor Bancário..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Toggle Motorista -->
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-primary">
                        <i class="bi bi-car-front-fill text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">Sou Motorista</h3>
                        <p class="text-xs text-gray-400">Quero oferecer caronas</p>
                    </div>
                </div>
                <!-- Toggle Switch -->
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="driver-toggle" name="is_driver" value="1" class="sr-only peer"
                        <?= $isDriver ? 'checked' : '' ?> onchange="toggleDriverFields()">
                    <div
                        class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-primary">
                    </div>
                </label>
            </div>

            <!-- Campos do Carro (Expansível) -->
            <div id="driver-fields" class="mt-6 space-y-4 <?= $isDriver ? '' : 'hidden' ?>">
                <hr class="border-gray-100 mb-4">

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Modelo do
                        Carro</label>
                    <input type="text" name="car_model" id="input-model"
                        value="<?= htmlspecialchars($user['model'] ?? '') ?>"
                        class="w-full p-4 rounded-2xl bg-gray-50 border-0 focus:ring-2 focus:ring-primary/20 font-medium transition-all"
                        placeholder="Ex: Gol G5 Prata">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Cor</label>
                        <input type="text" name="car_color" id="input-color"
                            value="<?= htmlspecialchars($user['color'] ?? '') ?>"
                            class="w-full p-4 rounded-2xl bg-gray-50 border-0 focus:ring-2 focus:ring-primary/20 font-medium transition-all"
                            placeholder="Prata">
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Placa</label>
                        <input type="text" name="car_plate" id="input-plate"
                            value="<?= htmlspecialchars($user['plate'] ?? '') ?>"
                            class="w-full p-4 rounded-2xl bg-gray-50 border-0 focus:ring-2 focus:ring-primary/20 font-medium transition-all uppercase"
                            placeholder="ABC-1234">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Chave Pix
                        (Para receber)</label>
                    <input type="text" name="pix_key" value="<?= htmlspecialchars($user['pix_key'] ?? '') ?>"
                        class="w-full p-4 rounded-2xl bg-gray-50 border-0 focus:ring-2 focus:ring-primary/20 font-medium transition-all"
                        placeholder="CPF, Email ou Telefone">
                </div>
            </div>
        </div>

        <!-- Botão Salvar -->
        <button type="submit" id="save-btn"
            class="w-full py-5 bg-gray-900 text-white font-extrabold rounded-3xl shadow-xl hover:bg-black transition-all text-lg fixed bottom-24 left-0 right-0 max-w-lg mx-auto z-40">
            Salvar Alterações
        </button>

        <a href="api/logout.php"
            class="block w-full text-center py-4 text-red-500 font-bold hover:text-red-700 transition-colors">
            Sair da Conta
        </a>

        <!-- Espaço para não cobrir o float button com o de salvar -->
        <div class="h-10"></div>

    </form>
</div>

<script>
    function toggleDriverFields() {
        const isChecked = document.getElementById('driver-toggle').checked;
        const fields = document.getElementById('driver-fields');
        if (isChecked) {
            $(fields).slideDown();
            // Adiciona required
            $('#input-model, #input-plate').prop('required', true);
        } else {
            $(fields).slideUp();
            $('#input-model, #input-plate').prop('required', false);
        }
    }

    async function uploadPhoto(input) {
        if (input.files && input.files[0]) {
            const formData = new FormData();
            formData.append('photo', input.files[0]);

            // Feedback visual imediato (loading)
            const preview = document.getElementById('avatar-preview');
            const originalSrc = preview.src;
            preview.style.opacity = '0.5';

            try {
                const response = await fetch('api/upload_photo.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    // Cache buster para forçar recarregamento da imagem
                    preview.src = result.photo_url + '?t=' + new Date().getTime();
                    Swal.fire({
                        text: 'Foto atualizada!',
                        icon: 'success',
                        toast: true,
                        position: 'top',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert(result.message);
                    preview.src = originalSrc;
                }
            } catch (e) {
                console.error(e);
                preview.src = originalSrc;
            } finally {
                preview.style.opacity = '1';
            }
        }
    }

    $(document).ready(function () {
        // Inicializa estado do toggle
        toggleDriverFields();

        $('#profile-form').on('submit', function (e) {
            e.preventDefault();

            // Validação extra para motorista
            if ($('#driver-toggle').is(':checked')) {
                if ($('#input-model').val() === '' || $('#input-plate').val() === '') {
                    Swal.fire({ title: 'Atenção', text: 'Motoristas precisam preencher os dados do carro.', icon: 'warning' });
                    return;
                }
            }

            const btn = $('#save-btn');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Salvando...');

            $.ajax({
                url: 'api/update_profile.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function (res) {
                    if (res.success) {
                        Swal.fire({
                            title: 'Perfil Salvo!',
                            text: 'Suas informações foram atualizadas.',
                            icon: 'success',
                            confirmButtonText: 'Continuar',
                            customClass: { confirmButton: 'bg-primary text-white px-8 py-3 rounded-2xl font-bold' },
                            buttonsStyling: false
                        }).then(() => {
                            // Se veio do onboarding (msg) ou se agora tem nome/foto, manda pra home
                            <?php if ($msg === 'complete_registration'): ?>
                                window.location.href = 'index.php?page=home';
                            <?php else: ?>
                                // Verifica se está completo para redirecionar ou apenas recarregar
                                window.location.href = 'index.php?page=home';
                            <?php endif; ?>
                        });
                    } else {
                        Swal.fire({ text: res.message, icon: 'error' });
                    }
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });
    });
</script>