/**
 * js/admin-users.js
 *
 * Admin User Management - Frontend Logic
 * Handles:
 * - Create/Edit user form visibility and submission
 * - AJAX actions: toggle_status, make_admin
 * - Dynamic UI updates based on responses
 */

document.addEventListener('DOMContentLoaded', function() {
    const btnCreateUser = document.getElementById('btn-create-user');
    const btnCancelForm = document.getElementById('btn-cancel-form');
    const userForm = document.getElementById('user-form');
    const formSection = document.getElementById('user-form-section');
    const formTitle = document.getElementById('form-title');
    const formUserId = document.getElementById('form-user-id');
    const formPassword = document.getElementById('form-password');
    const passwordNote = document.getElementById('password-note');

    // ──────────────────────────────────────────────────────────────────────
    // Form Management
    // ──────────────────────────────────────────────────────────────────────

    // Show form for creating new user
    btnCreateUser.addEventListener('click', function() {
        resetForm();
        formUserId.value = '';
        formTitle.textContent = 'Novo Utilizador';
        passwordNote.textContent = '(obrigatória para novos)';
        formPassword.required = true;
        formSection.style.display = 'block';
        formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // Hide form
    btnCancelForm.addEventListener('click', function() {
        formSection.style.display = 'none';
        resetForm();
    });

    // Edit user - populate form with existing data
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            editUser(userId);
        });
    });

    // ──────────────────────────────────────────────────────────────────────
    // AJAX Actions
    // ──────────────────────────────────────────────────────────────────────

    // Toggle user status (activate/deactivate)
    document.querySelectorAll('.btn-toggle-status').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const currentStatus = parseInt(this.dataset.currentStatus);
            toggleUserStatus(userId, currentStatus);
        });
    });

    // Make user admin
    document.querySelectorAll('.btn-make-admin').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            makeUserAdmin(userId);
        });
    });

    // ──────────────────────────────────────────────────────────────────────
    // Helper Functions
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Reset form to empty state
     */
    function resetForm() {
        userForm.reset();
        formUserId.value = '';
        formPassword.required = true;
        passwordNote.textContent = '(obrigatória para novos)';
    }

    /**
     * Load user data and populate form for editing
     */
    async function editUser(userId) {
        try {
            // Get the row element to extract data
            const row = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (!row) {
                showToast('Erro ao carregar dados do utilizador.', 'error');
                return;
            }

            // Extract data from table cells
            const name = row.querySelector('.col-name').textContent;
            const email = row.querySelector('.col-email').textContent;
            const username = row.querySelector('.col-username').textContent;
            const roleText = row.querySelector('.col-role .role-badge').textContent.trim();

            // Map role text back to value
            const roleMap = {
                'Membro': 'member',
                'Treinador': 'trainer',
                'Pessoal': 'staff',
                'Administrador': 'admin',
            };

            const role = roleMap[roleText] || 'member';

            // Populate form
            formUserId.value = userId;
            document.getElementById('form-name').value = name;
            document.getElementById('form-email').value = email;
            document.getElementById('form-username').value = username;
            document.getElementById('form-role').value = role;
            document.getElementById('form-password').value = '';

            // Update form UI for edit mode
            formTitle.textContent = `Editar: ${name}`;
            passwordNote.textContent = '(deixar em branco para não alterar)';
            formPassword.required = false;

            // Show form
            formSection.style.display = 'block';
            formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

        } catch (error) {
            console.error('Erro ao editar utilizador:', error);
            showToast('Erro ao carregar dados do utilizador.', 'error');
        }
    }

    /**
     * Toggle user active/inactive status via AJAX
     */
    function toggleUserStatus(userId, currentStatus) {
        if (!confirm('Tem certeza que deseja alterar o status deste utilizador?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('user_id', userId);

        const button = document.querySelector(`button[data-user-id="${userId}"].btn-toggle-status`);

        fetch('../../api/api_admin_actions.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update button text and data attribute
                const newStatus = data.new_status;
                button.dataset.currentStatus = newStatus;
                button.textContent = newStatus ? '🔒 Desativar' : '🔓 Ativar';

                // Update status badge
                const statusBadge = document.getElementById(`status-${userId}`);
                if (statusBadge) {
                    statusBadge.textContent = data.status_text;
                    statusBadge.className = `status-badge status-${newStatus ? 'active' : 'inactive'}`;
                }

                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Erro ao alterar status.', 'error');
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            showToast('Erro ao conectar com o servidor.', 'error');
        });
    }

    /**
     * Promote user to admin via AJAX
     */
    function makeUserAdmin(userId) {
        if (!confirm('Tem certeza que deseja promover este utilizador a administrador?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'make_admin');
        formData.append('user_id', userId);

        const button = document.querySelector(`button[data-user-id="${userId}"].btn-make-admin`);

        fetch('../../api/api_admin_actions.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update role badge
                const row = document.querySelector(`tr[data-user-id="${userId}"]`);
                if (row) {
                    const roleBadge = row.querySelector('.col-role .role-badge');
                    roleBadge.textContent = data.role_text;
                    roleBadge.className = 'role-badge role-admin';

                    // Hide the "Make Admin" button
                    button.style.display = 'none';
                }

                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Erro ao promover utilizador.', 'error');
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            showToast('Erro ao conectar com o servidor.', 'error');
        });
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 6px;
            background: ${type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(toast);

        // Auto remove after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});

// Toast animations (add to document if not already present)
if (!document.querySelector('style[data-toasts]')) {
    const style = document.createElement('style');
    style.setAttribute('data-toasts', '');
    style.textContent = `
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }
    `;
    document.head.appendChild(style);
}
