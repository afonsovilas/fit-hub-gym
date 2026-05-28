<?php
/**
 * templates/profile/shared_sidebar.php
 *
 * Renderiza a barra lateral do perfil (avatar e detalhes).
 */

declare(strict_types=1);
?>
<aside class="profile-sidebar">
    <div class="profile-card">
        <div class="profile-header">
             <img src="<?= $user['avatar'] ? h(BASE_URL_PATH . $user['avatar']) : '../assets/images/avatars/profile-placeholder.png' ?>" 
                  alt="Foto de perfil de <?= h($user['name']) ?>" 
                  class="profile-photo"
                  onerror="this.src='../assets/images/avatars/profile-placeholder.png';">
            <div class="profile-info">
                <h2 class="profile-name"><?= h($user['name']) ?></h2>
                <p class="profile-username">@<?= h($user['username'] ?? strtolower(str_replace(' ', '', $user['name']))) ?></p>
            </div>
        </div>
        <div class="profile-details">
            <p class="profile-detail">
                <span class="detail-label">Email:</span>
                <span><?= h($user['email']) ?></span>
            </p>
            <p class="profile-detail">
                <span class="detail-label">Membro desde:</span>
                <span><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
            </p>
            <p class="profile-detail">
                <span class="detail-label">Função:</span>
                <span class="badge badge-<?= h($user['role']) ?>"><?= h($user['role']) ?></span>
            </p>
            <p class="profile-detail">
                <span class="detail-label">Status:</span>
                <span class="status-badge status-active"><?= $user['is_active'] ? 'Ativo' : 'Inativo' ?></span>
            </p>
        </div>
    </div>
</aside>
