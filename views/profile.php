<?php
/**
 * View: Perfil do Usuário (Edição + Upload + Pix)
 */

require_once 'config/db.php';

// Mensagem de Onboarding
$onboardingMsg = $_GET['msg'] ?? '';

// Buscar dados do usuário e do carro
$user = [];
$car = [];

if (isset($_SESSION['user_id'])) {
    try {
        // Dados User
        $stmt = $pdo->prepare("SELECT name, phone, bio, photo_url, reputation, pix_key, is_driver FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Dados Carro
        $stmtCar = $pdo->prepare("SELECT * FROM cars WHERE user_id = ? LIMIT 1");
        $stmtCar->execute([$_SESSION['user_id']]);
        $car = $stmtCar->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $user = null;
    }
}

$userName = $user['name'] ?? '';
$userBio = $user['bio'] ?? '';
$userPix = $user['pix_key'] ?? '';
$isDriver = (!empty($user['is_driver']) && $user['is_driver'] == 1);
$carModel = $car['model'] ?? '';
$carColor = $car['color'] ?? '';
$carPlate = $car['plate'] ?? '';
?>

<div class="max-w-xl mx-auto pt-6 px-4 pb-20">

    <?php if ($onboardingMsg === 'complete_profile'): ?>
        <div
            class="alert alert-warning flex items-center p-4 mb-6 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800">
            <i class="bi bi-shield-exclamation text-2xl mr-3"></i>
            <div>
                <h4 class="font-bold">Perfil Incompleto</h4>
                <p class="text-sm">Para sua segurança e dos outros, adicione uma foto real e seu nome para acessar as
                    caronas.</p>
            </div>
        </div>
    <?php elseif ($onboardingMsg === 'driver_only'): ?>
        <div
            class="alert alert-primary flex items-center p-4 mb-6 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 animate-bounce-short">
            <i class="bi bi-info-circle-fill text-2xl mr-3"></i>
            <div>
                <h4 class="font-bold">Acesso Restrito</h4>
                <p class="text-sm">Ative a opção <b>"Sou Motorista"</b> abaixo para começar a oferecer caronas.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header com Avatar Interativo -->
    <div class="flex flex-col items-center mb-8">
        <!-- Input de Arquivo Oculto -->
        <input type="file" id="avatar-upload" class="hidden" accept="image/*">

        <div class="symbol symbol-100px symbol-circle mb-4 border-4 border-white shadow-lg relative cursor-pointer group"
            onclick="document.getElementById('avatar-upload').click()">
            <img src="<?= $user['photo_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($userName ?: 'User') . '&background=009ef7&color=fff&size=128' ?>"
                alt="Avatar" id="profile-avatar-img" class="group-hover:opacity-75 transition-opacity">

            <!-- Overlay de Hover -->
            <div
                class="absolute inset-0 flex items-center justify-center bg-black/30 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                <i class="bi bi-camera-fill text-white text-2xl"></i>
            </div>

            <!-- Badge de Edição -->
            <div
                class="absolute bottom-0 right-0 bg-primary text-white p-1.5 rounded-full border-2 border-white shadow-sm">
                <i class="bi bi-pencil-fill text-xs"></i>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($userName ?: 'Olá, Visitante') ?></h2>
        <span class="text-gray-500"><?= htmlspecialchars($user['phone'] ?? '') ?></span>
    </div>

    <form id="profile-form" class="flex flex-col gap-6">

        <!-- Card: Dados Pessoais -->
        <div class="card border border-gray-200 shadow-sm rounded-xl bg-white">
            <div class="card-header px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-800">Dados Pessoais</h3>
            </div>
            <div class="card-body p-6">
                <!-- Nome -->
                <div class="flex flex-col mb-5">
                    <label class="form-label font-bold text-gray-700 text-sm mb-2 required">Nome Completo</label>
                    <input type="text" name="name" class="form-control form-control-solid rounded-lg p-3"
                        value="<?= htmlspecialchars($userName) ?>" placeholder="Ex: Ricardo Moraes" required>
                </div>

                <!-- Bio -->
                <div class="flex flex-col">
                    <label class="form-label font-bold text-gray-700 text-sm mb-2">Bio / Nota</label>
                    <textarea name="bio" class="form-control form-control-solid rounded-lg p-3" rows="2"
                        placeholder="Ex: Moro na Asa Norte e trabalho na Asa Sul."><?= htmlspecialchars($userBio) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Card: Sou Motorista -->
        <div class="card border border-gray-200 shadow-sm rounded-xl bg-white">
            <div class="card-header px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="bi bi-car-front-fill text-primary"></i> Sou Motorista
                </h3>

                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input
                        class="form-check-input h-6 w-12 cursor-pointer <?= ($onboardingMsg === 'driver_only') ? 'animate-pulse ring-2 ring-primary ring-offset-2' : '' ?>"
                        type="checkbox" id="driver-switch" name="is_driver" value="1" <?= $isDriver ? 'checked' : '' ?> />
                </div>
            </div>

            <div id="car-fields" class="card-body p-6 <?= $isDriver ? '' : 'hidden' ?>">
                <div class="grid grid-cols-2 gap-4">
                    <!-- Modelo -->
                    <div class="flex flex-col col-span-2 sm:col-span-1">
                        <label class="form-label font-bold text-gray-700 text-sm mb-2 required">Modelo</label>
                        <input type="text" name="car_model" class="form-control form-control-solid rounded-lg p-3"
                            value="<?= htmlspecialchars($carModel) ?>" placeholder="Ex: Gol G6">
                    </div>

                    <!-- Cor -->
                    <div class="flex flex-col col-span-2 sm:col-span-1">
                        <label class="form-label font-bold text-gray-700 text-sm mb-2 required">Cor</label>
                        <input type="text" name="car_color" class="form-control form-control-solid rounded-lg p-3"
                            value="<?= htmlspecialchars($carColor) ?>" placeholder="Ex: Prata">
                    </div>

                    <!-- Placa -->
                    <div class="flex flex-col col-span-2">
                        <label class="form-label font-bold text-gray-700 text-sm mb-2 required">Placa</label>
                        <div class="relative">
                            <input type="text" name="car_plate"
                                class="form-control form-control-solid rounded-lg p-3 uppercase"
                                value="<?= htmlspecialchars($carPlate) ?>" placeholder="ABC-1234">
                            <i class="bi bi-eye-slash-fill absolute right-4 top-3.5 text-gray-400"
                                data-bs-toggle="tooltip" title="Visível apenas para quem reservar"></i>
                        </div>
                        <span class="text-xs text-warning mt-1 flex items-center gap-1">
                            <i class="bi bi-lock-fill"></i> A placa só será revelada ao passageiro confirmado.
                        </span>
                    </div>

                    <!-- Chave Pix (Novo) -->
                    <div class="flex flex-col col-span-2 mt-2">
                        <label class="form-label font-bold text-gray-700 text-sm mb-2">Chave Pix (Opcional)</label>
                        <input type="text" name="pix_key" class="form-control form-control-solid rounded-lg p-3"
                            value="<?= htmlspecialchars($userPix) ?>" placeholder="CPF, Email ou Telefone">
                        <span class="text-xs text-gray-400 mt-1">Facilita o pagamento da contribuição.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botão Salvar -->
        <button type="submit" id="btn-save-profile"
            class="btn btn-primary font-bold py-4 rounded-xl shadow-lg shadow-primary/30 mb-4">
            Salvar Perfil
        </button>

        <!-- Logout Link (Secundário) -->
        <div class="text-center">
            <a href="api/logout.php" class="text-sm text-danger hover:underline font-medium">Sair da minha conta</a>
        </div>

    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Referências
        const driverSwitch = document.getElementById('driver-switch');
        const carFields = document.getElementById('car-fields');
        const form = document.getElementById('profile-form');
        const btnSave = document.getElementById('btn-save-profile');
        const avatarInput = document.getElementById('avatar-upload');
        const avatarImg = document.getElementById('profile-avatar-img');

        // Toggle Motorista
        driverSwitch.addEventListener('change', (e) => {
            if (e.target.checked) {
                carFields.classList.remove('hidden');
                document.querySelector('[name="car_model"]').focus();
            } else {
                carFields.classList.add('hidden');
            }
        });

        // Upload de Foto (AJAX Imediato)
        avatarInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // Validar tamanho
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({ text: 'A imagem deve ter no máximo 5MB.', icon: 'warning' });
                return;
            }

            const formData = new FormData();
            formData.append('photo', file);

            // Feedback visual
            Swal.fire({
                title: 'Enviando foto...',
                text: 'Aguarde um momento',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch('api/upload_photo.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    // Atualizar imagem no frontend
                    avatarImg.src = result.photo_url + '?' + new Date().getTime(); // Cache bust
                    Swal.fire({ text: 'Foto atualizada!', icon: 'success', timer: 1500, showConfirmButton: false });
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                Swal.fire({ text: error.message || 'Erro no upload.', icon: 'error' });
            }
        });

        // Save Profile (Dados + Carro + Pix)
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (driverSwitch.checked) {
                const model = form.querySelector('[name="car_model"]').value.trim();
                const plate = form.querySelector('[name="car_plate"]').value.trim();
                if (!model || !plate) {
                    Swal.fire({ text: "Motorista, preencha Modelo e Placa!", icon: "warning" });
                    return;
                }
            }

            const originalText = btnSave.innerText;
            btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Salvando...';
            btnSave.disabled = true;

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('api/update_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        text: "Perfil atualizado!",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ir para Home",
                        customClass: { confirmButton: "btn btn-primary rounded-lg px-6" }
                    }).then(() => {
                        // Se veio do onboarding message, manda pra home, se não reload
                        window.location.href = 'index.php?page=home';
                    });
                } else {
                    throw new Error(result.message || 'Erro ao salvar.');
                }

            } catch (error) {
                Swal.fire({ text: error.message, icon: "error" });
                btnSave.innerText = originalText;
                btnSave.disabled = false;
            }
        });
    });
</script>