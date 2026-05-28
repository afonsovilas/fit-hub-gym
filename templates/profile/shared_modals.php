<?php
/**
 * templates/profile/shared_modals.php
 *
 * Modais partilhados pelas páginas de perfil (recorte de avatar, confirmação, plano alimentar).
 */

declare(strict_types=1);
?>
<!-- Modal de Recorte de Imagem (Avatar Cropper Modal) -->
<div id="avatar-cropper-modal" class="modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: hidden; background-color: rgba(15, 23, 42, 0.85); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(8px);">
    <div style="background: #1e293b; border: 1px solid #334155; border-radius: 16px; width: 100%; max-width: 420px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); overflow: hidden; display: flex; flex-direction: column; animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
        <!-- Header -->
        <div style="padding: 20px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: #fff; font-size: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-primary);"><path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/></svg>
                Ajustar Foto de Perfil
            </h3>
            <button type="button" id="cropper-close-btn" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; transition: color 0.2s;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <!-- Body -->
        <div style="padding: 24px; display: flex; flex-direction: column; align-items: center; gap: 20px;">
            <!-- Viewport (Circular crop mask) -->
            <div id="cropper-viewport" style="position: relative; width: 280px; height: 280px; overflow: hidden; border-radius: 50%; border: 3px solid var(--color-primary); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); background-color: #0f172a; cursor: grab; display: flex; align-items: center; justify-content: center;">
                <img id="cropper-preview-img" src="" alt="Ajustar imagem" style="position: absolute; left: 0; top: 0; transform-origin: 0 0; user-select: none; pointer-events: none; max-width: none;">
            </div>
            
            <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; margin: 0; line-height: 1.4;">
                Arraste a foto para centrar e utilize a barra deslizante abaixo para aproximar.
            </p>

            <!-- Zoom Control -->
            <div style="width: 100%; display: flex; align-items: center; gap: 12px; padding: 0 10px;">
                <span style="color: #94a3b8; font-size: 0.9rem; font-weight: bold;">-</span>
                <input type="range" id="cropper-zoom-slider" min="1" max="4" step="0.01" value="1" style="flex: 1; height: 6px; border-radius: 3px; background: #334155; outline: none; cursor: pointer; accent-color: var(--color-primary);">
                <span style="color: #94a3b8; font-size: 0.9rem; font-weight: bold;">+</span>
            </div>
        </div>
        <!-- Footer -->
        <div style="padding: 20px; border-top: 1px solid #334155; background: #1e293b; display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" id="cropper-cancel-btn" class="btn btn-secondary btn-small" style="margin: 0; padding: 8px 16px;">Cancelar</button>
            <button type="button" id="cropper-confirm-btn" class="btn btn-primary btn-small" style="margin: 0; padding: 8px 20px; background: var(--color-primary); border-color: var(--color-primary);">Confirmar</button>
        </div>
    </div>
</div>

<style>
@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
#avatar-cropper-modal input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--color-primary);
    cursor: pointer;
    transition: transform 0.1s ease;
}
#avatar-cropper-modal input[type="range"]::-webkit-slider-thumb:hover {
    transform: scale(1.2);
}
</style>

<!-- Modal de Confirmação Geral do Perfil (Custom Profile Confirmation Modal) -->
<div id="profile-confirm-modal" class="modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.65); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);">
    <div style="background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; max-width: 450px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.6); display: flex; flex-direction: column; overflow: hidden; animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
        <!-- Header -->
        <div style="padding: 20px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; background: #0b0f19;">
            <h3 id="profile-confirm-title" style="margin: 0; color: #cf5a2b; font-size: 1.25em; font-weight: 700;">Confirmar Ação</h3>
            <button type="button" id="profile-confirm-close" style="background: none; border: none; color: #94a3b8; font-size: 1.8em; cursor: pointer; line-height: 1; transition: color 0.2s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#94a3b8'">&times;</button>
        </div>
        <!-- Body -->
        <div style="padding: 20px; color: #cbd5e1; font-size: 0.95rem; line-height: 1.5; background: #0f172a;">
            <p id="profile-confirm-message" style="margin: 0;">Tem a certeza que deseja prosseguir com esta ação?</p>
        </div>
        <!-- Footer -->
        <div style="padding: 15px 20px; border-top: 1px solid #1e293b; display: flex; justify-content: flex-end; gap: 10px; background: #0b0f19;">
            <button type="button" id="profile-confirm-cancel" class="btn btn-secondary" style="padding: 0.5rem 1.25rem; font-size: 0.9em; margin: 0; border-radius: 6px;">Cancelar</button>
            <button type="button" id="profile-confirm-proceed" class="btn btn-primary" style="padding: 0.5rem 1.25rem; font-size: 0.9em; margin: 0; border-radius: 6px; background-color: #cf5a2b; color: white; font-weight: bold; border: none; cursor: pointer;">Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal de Visualização de Plano Alimentar (Diet Plan View Modal) -->
<div id="diet-plan-modal" class="modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.65); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);">
    <div style="background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; max-width: 600px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.6); display: flex; flex-direction: column; overflow: hidden; animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
        <!-- Header -->
        <div style="padding: 20px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; background: #0b0f19;">
            <h3 style="margin: 0; color: #10b981; font-size: 1.25em; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                🍎 Plano Alimentar Prescrito
            </h3>
            <button type="button" id="diet-modal-close" style="background: none; border: none; color: #94a3b8; font-size: 1.8em; cursor: pointer; line-height: 1; transition: color 0.2s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#94a3b8'">&times;</button>
        </div>
        <!-- Body -->
        <div style="padding: 24px; color: #cbd5e1; font-size: 0.95rem; line-height: 1.6; background: #0f172a; white-space: pre-wrap; max-height: 60vh; overflow-y: auto;" id="diet-modal-body">
        </div>
        <!-- Footer -->
        <div style="padding: 15px 20px; border-top: 1px solid #1e293b; display: flex; justify-content: flex-end; background: #0b0f19;">
            <button type="button" id="diet-modal-ok" class="btn" style="padding: 0.5rem 1.5rem; font-size: 0.9em; margin: 0; border-radius: 6px; background-color: #10b981; color: white; font-weight: bold; border: none; cursor: pointer;">Fechar</button>
        </div>
    </div>
</div>
