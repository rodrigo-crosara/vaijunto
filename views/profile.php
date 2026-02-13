<?php
/**
 * View: Perfil e Configurações (Glass & Lite)
 */
$stmt = $pdo->prepare("SELECT u.*, c.model, c.color, c.plate, c.photo_url as car_photo FROM users u LEFT JOIN cars c ON c.user_id = u.id WHERE u.id = ?");
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
    <form id="profile-form" class="space-y-6" enctype="multipart/form-data">

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

                <!-- WhatsApp (Editável com trava) -->
                <div>
                    <label
                        class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">WhatsApp</label>
                    <div class="relative">
                        <input type="text" name="phone" id="input-phone" value="<?= htmlspecialchars($user['phone']) ?>"
                            readonly
                            class="w-full p-4 pr-14 rounded-2xl bg-gray-100 text-gray-500 font-medium cursor-not-allowed transition-all"
                            placeholder="(61) 99999-9999">
                        <button type="button" onclick="enablePhoneEdit()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center hover:bg-primary/10 hover:text-primary transition-all"
                            id="btn-edit-phone" title="Editar número">
                            <i class="bi bi-pencil-fill text-xs"></i>
                        </button>
                    </div>
                    <p id="phone-warning" class="hidden text-[10px] text-amber-600 font-bold mt-1 ml-1">
                        <i class="bi bi-exclamation-triangle-fill"></i> Alterar o número muda seu login!
                    </p>
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

                <!-- Aviso de Privacidade -->
                <div class="bg-blue-50 text-blue-800 border border-blue-100 rounded-xl p-4 mb-2 flex gap-3 items-start">
                    <i class="bi bi-shield-lock-fill text-xl text-blue-500 shrink-0 mt-0.5"></i>
                    <p class="text-xs leading-relaxed">
                        <b>🔒 Segurança Garantida:</b> Sua placa e a foto do carro ficam ocultas no feed público. Elas só aparecem para o passageiro após confirmada a reserva.
                    </p>
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

                <!-- Foto do Carro -->
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Foto do
                        Carro</label>
                    <p class="text-[10px] text-gray-400 mb-3 ml-1">Ajuda passageiros a identificar seu veículo na rua.
                    </p>
                    <div class="flex items-center gap-4">
                        <?php
                        $carPhoto = $user['car_photo'] ?? '';
                        $carPhotoSrc = $carPhoto ?: '';
                        ?>
                        <?php if ($carPhotoSrc): ?>
                            <img id="car-photo-preview" src="<?= htmlspecialchars($carPhotoSrc) ?>"
                                class="w-20 h-20 rounded-2xl object-cover border-2 border-gray-100 shadow-sm">
                        <?php else: ?>
                            <div id="car-photo-preview"
                                class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center text-gray-300 border-2 border-dashed border-gray-200">
                                <i class="bi bi-car-front text-2xl"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <input type="file" name="car_photo" id="car-photo-input" class="hidden" accept="image/*"
                                onchange="previewCarPhoto(this)">
                            <button type="button" onclick="document.getElementById('car-photo-input').click()"
                                class="bg-gray-50 text-gray-600 px-4 py-2.5 rounded-xl font-bold text-xs hover:bg-gray-100 transition-colors flex items-center gap-2">
                                <i class="bi bi-camera"></i> <?= $carPhotoSrc ? 'Trocar' : 'Adicionar' ?> Foto
                            </button>
                        </div>
                    </div>
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

        <div class="h-10"></div>

    </form>

    <!-- ======================== -->
    <!-- ÚLTIMAS AVALIAÇÕES       -->
    <!-- ======================== -->
    <?php
    try {
        $stmtRatings = $pdo->prepare("
            SELECT rt.score, rt.comment, rt.created_at, u.name as reviewer_name, u.photo_url as reviewer_photo
            FROM ratings rt
            JOIN users u ON rt.reviewer_id = u.id
            WHERE rt.rated_user_id = ?
            ORDER BY rt.created_at DESC
            LIMIT 5
        ");
        $stmtRatings->execute([$_SESSION['user_id']]);
        $ratings = $stmtRatings->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $ratings = [];
    }
    ?>

    <?php if (!empty($ratings)): ?>
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-50 mt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
                <i class="bi bi-star-half text-yellow-400"></i> Últimas Avaliações
            </h3>

            <div class="space-y-4">
                <?php foreach ($ratings as $rt):
                    $reviewerAvatar = $rt['reviewer_photo'] ?: "https://ui-avatars.com/api/?name=" . urlencode($rt['reviewer_name']) . "&background=random&size=64";
                    $dateFormatted = date('d/m/Y', strtotime($rt['created_at']));
                    ?>
                    <div class="flex gap-3 items-start">
                        <img src="<?= htmlspecialchars($reviewerAvatar) ?>" alt="R"
                            class="w-10 h-10 rounded-full border border-gray-100 object-cover shrink-0 mt-0.5">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span
                                    class="text-sm font-bold text-gray-800"><?= htmlspecialchars(explode(' ', $rt['reviewer_name'])[0]) ?></span>
                                <span class="text-[10px] text-gray-300 font-bold"><?= $dateFormatted ?></span>
                            </div>
                            <div class="flex gap-0.5 mb-1.5">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i
                                        class="bi bi-star-fill text-xs <?= $i <= $rt['score'] ? 'text-yellow-400' : 'text-gray-200' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if (!empty($rt['comment'])): ?>
                                <p class="text-xs text-gray-500 leading-relaxed">"<?= htmlspecialchars($rt['comment']) ?>"</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ======================== -->
    <!-- ZONA DE PERIGO           -->
    <!-- ======================== -->
    <div class="mt-8 bg-red-50/50 p-6 rounded-[2rem] border border-red-100">
        <h3 class="text-sm font-bold text-red-400 mb-2 flex items-center gap-2">
            <i class="bi bi-exclamation-octagon-fill"></i> Zona de Perigo
        </h3>
        <p class="text-xs text-red-300 mb-4">Ações irreversíveis. Prossiga com cuidado.</p>
        <button type="button" onclick="excluirConta()"
            class="w-full py-3.5 bg-white text-red-500 border border-red-200 font-bold rounded-2xl text-sm hover:bg-red-500 hover:text-white transition-all flex items-center justify-center gap-2">
            <i class="bi bi-trash3"></i> Excluir minha conta permanentemente
        </button>
    </div>
</div>

<script>
    function toggleDriverFields() {
        const isChecked = document.getElementById('driver-toggle').checked;
        const fields = document.getElementById('driver-fields');
        if (isChecked) {
            $(fields).slideDown();
            $('#input-model, #input-plate').prop('required', true);
        } else {
            $(fields).slideUp();
            $('#input-model, #input-plate').prop('required', false);
        }
    }

    function enablePhoneEdit() {
        const input = document.getElementById('input-phone');
        input.removeAttribute('readonly');
        input.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        input.classList.add('bg-white', 'text-gray-900', 'ring-2', 'ring-amber-300');
        input.focus();
        document.getElementById('btn-edit-phone').classList.add('hidden');
        document.getElementById('phone-warning').classList.remove('hidden');
    }

    function previewCarPhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const preview = document.getElementById('car-photo-preview');
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    // Replace div placeholder with img
                    const img = document.createElement('img');
                    img.id = 'car-photo-preview';
                    img.src = e.target.result;
                    img.className = 'w-20 h-20 rounded-2xl object-cover border-2 border-gray-100 shadow-sm';
                    preview.replaceWith(img);
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    async function uploadPhoto(input) {
        if (input.files && input.files[0]) {
            const formData = new FormData();
            formData.append('photo', input.files[0]);

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

    function excluirConta() {
        Swal.fire({
            title: 'Excluir conta?',
            html: '<p class="text-gray-500 text-sm">Isso apagará <b>todo seu histórico</b>, reputação, avaliações e dados.<br><span class="text-red-500 font-bold">Não tem volta.</span></p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, quero excluir',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'bg-red-500 text-white font-bold px-6 py-3 rounded-2xl shadow-lg hover:bg-red-600 transition-all',
                cancelButton: 'bg-gray-100 text-gray-500 font-bold px-6 py-3 rounded-2xl ml-2'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Segunda confirmação: digitar DELETAR
                Swal.fire({
                    title: 'Confirmação Final',
                    html: '<p class="text-gray-500 text-sm mb-4">Para confirmar, digite <b class="text-red-500">DELETAR</b> no campo abaixo:</p>',
                    input: 'text',
                    inputPlaceholder: 'DELETAR',
                    inputAttributes: { autocapitalize: 'characters', spellcheck: 'false' },
                    showCancelButton: true,
                    confirmButtonText: '🗑️ Excluir Permanentemente',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        confirmButton: 'bg-red-500 text-white font-bold px-6 py-3 rounded-2xl shadow-lg',
                        cancelButton: 'bg-gray-100 text-gray-500 font-bold px-6 py-3 rounded-2xl ml-2',
                        input: 'rounded-2xl font-bold text-center uppercase'
                    },
                    buttonsStyling: false,
                    preConfirm: (value) => {
                        if (value !== 'DELETAR') {
                            Swal.showValidationMessage('Digite DELETAR corretamente');
                            return false;
                        }
                        return value;
                    }
                }).then(async (result2) => {
                    if (result2.isConfirmed) {
                        try {
                            const res = await fetch('api/delete_account.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ confirmation: 'DELETAR' })
                            });
                            const data = await res.json();
                            if (data.success) {
                                Swal.fire({
                                    title: 'Conta excluída',
                                    text: 'Seus dados foram removidos. Até mais.',
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 2500
                                }).then(() => {
                                    window.location.href = 'index.php';
                                });
                            } else {
                                Swal.fire({ text: data.message, icon: 'error' });
                            }
                        } catch (e) {
                            Swal.fire({ text: 'Erro de conexão.', icon: 'error' });
                        }
                    }
                });
            }
        });
    }

    $(document).ready(function () {
        toggleDriverFields();

        // Armazenar telefone original ao carregar a página
        const originalPhone = $('#input-phone').val();

        $('#profile-form').on('submit', async function (e) {
            e.preventDefault();
            const self = this;

            if ($('#driver-toggle').is(':checked')) {
                if ($('#input-model').val() === '' || $('#input-plate').val() === '') {
                    Swal.fire({ title: 'Atenção', text: 'Motoristas precisam preencher os dados do carro.', icon: 'warning' });
                    return;
                }
            }

            // Trava de segurança: confirmar troca de telefone ANTES de enviar
            const currentPhone = $('#input-phone').val();
            if (currentPhone !== originalPhone) {
                const confirm = await Swal.fire({
                    title: 'Confirme seu novo número',
                    html: `<p class="text-gray-600 text-sm">Seu login mudará para:<br><b class="text-xl text-primary mt-2 block">${currentPhone}</b></p><p class="text-red-500 text-xs font-bold mt-3"><i class="bi bi-exclamation-triangle-fill"></i> Se estiver errado, você perderá o acesso à conta!</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Está correto, mudar',
                    cancelButtonText: 'Revisar',
                    customClass: {
                        popup: 'rounded-[2.5rem]',
                        confirmButton: 'bg-primary text-white font-bold px-6 py-3 rounded-2xl shadow-lg',
                        cancelButton: 'bg-gray-100 text-gray-500 font-bold px-6 py-3 rounded-2xl ml-2'
                    },
                    buttonsStyling: false,
                    allowOutsideClick: false
                });

                if (!confirm.isConfirmed) {
                    // Foca no campo de telefone para revisão
                    $('#input-phone').focus().select();
                    return;
                }
            }

            const btn = $('#save-btn');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Salvando...');

            // Usar FormData para suportar upload de arquivo
            const formData = new FormData(self);

            $.ajax({
                url: 'api/update_profile.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.success) {
                        if (res.phone_changed) {
                            Swal.fire({
                                title: 'Número Alterado! 📱',
                                html: `<p class="text-gray-600 text-sm">Seu login agora é:<br><b class="text-lg text-primary">${res.new_phone}</b></p><p class="text-red-500 text-xs font-bold mt-3">Anote para não perder o acesso!</p>`,
                                icon: 'warning',
                                confirmButtonText: 'Entendi',
                                customClass: {
                                    confirmButton: 'bg-primary text-white px-8 py-3 rounded-2xl font-bold',
                                    popup: 'rounded-[2.5rem]'
                                },
                                buttonsStyling: false
                            }).then(() => {
                                window.location.href = 'index.php?page=profile';
                            });
                        } else {
                            Swal.fire({
                                title: 'Perfil Salvo!',
                                text: 'Suas informações foram atualizadas.',
                                icon: 'success',
                                confirmButtonText: 'Continuar',
                                customClass: { confirmButton: 'bg-primary text-white px-8 py-3 rounded-2xl font-bold' },
                                buttonsStyling: false
                            }).then(() => {
                                <?php if ($msg === 'complete_registration'): ?>
                                    window.location.href = 'index.php?page=home';
                                <?php else: ?>
                                    window.location.href = 'index.php?page=home';
                                <?php endif; ?>
                            });
                        }
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