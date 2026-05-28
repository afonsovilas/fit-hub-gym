<?php
/**
 * templates/profile/shared_personal_data.php
 *
 * Renderiza o formulário de dados pessoais comum a alunos, treinadores e administradores.
 */

declare(strict_types=1);
?>
<form class="profile-form" id="profile-form-personal" method="POST" action="../actions/update_profile.php" enctype="multipart/form-data">
    <?= get_csrf_input() ?>
    <div id="pane-dados-pessoais" class="tab-pane active">
        <div class="profile-section">
            <div class="profile-section-header">
                <h3>Editar Perfil (Base de Dados)</h3>
                <button type="button" class="btn btn-primary btn-small edit-profile-btn" id="edit-profile-btn-personal">Editar</button>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="full-name">Nome Completo:</label>
                    <input type="text" id="full-name" name="full-name" value="<?= h($user['name']) ?>" disabled required>
                </div>
                <div class="form-group">
                    <label for="username">Nome de Utilizador:</label>
                    <input type="text" id="username" name="username" value="<?= h($user['username'] ?? '') ?>" disabled required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?= h($user['email']) ?>" disabled required>
                </div>
                <div class="form-group">
                    <label for="phone">Telefone:</label>
                    <input type="tel" id="phone" name="phone" value="<?= h($user['phone'] ?? '+351 900 000 000') ?>" disabled>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Nova Senha (deixe em branco para não alterar):</label>
                <input type="password" id="password" name="password" placeholder="••••••••" disabled>
            </div>

            <div class="form-group">
                <label for="password-confirm">Confirmar Senha:</label>
                <input type="password" id="password-confirm" name="password-confirm" placeholder="••••••••" disabled>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label for="avatar">Foto de Perfil (Avatar):</label>
                <div style="display: flex; gap: 15px; align-items: center; margin-top: 5px; flex-wrap: wrap;">
                    <input type="file" id="avatar" name="avatar" accept="image/*" disabled class="file-input" style="flex: 1; min-width: 200px;">
                    <div id="avatar-preview-container" style="display: none; align-items: center; gap: 8px; cursor: pointer;" title="Clique para ajustar o recorte novamente">
                        <img id="avatar-preview-img" src="" alt="Previsualização" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--color-primary); box-shadow: 0 4px 10px rgba(207, 90, 43, 0.2);">
                        <span style="font-size: 0.85em; color: var(--color-text-muted); font-weight: 500;">(Pré-visualização)</span>
                    </div>
                </div>
                <small class="form-hint" style="color: #94a3b8; font-size: 0.8em; margin-top: 4px; display: block;">Envie uma imagem JPG, PNG ou GIF de até 2MB.</small>
            </div>

            <div class="form-actions" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" id="save-btn-personal" style="display: none;">Guardar Alterações</button>
                <button type="button" class="btn btn-secondary btn-small" id="cancel-btn-personal" style="display: none;">Cancelar</button>
            </div>
        </div>
    </div>
</form>
