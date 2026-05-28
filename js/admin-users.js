/**
 * js/admin-users.js
 *
 * Admin User Dashboard Refactoring - Frontend Logic
 * Handles:
 * - Client-side dynamic tab filtering (Todos, Membros, Treinadores, Staff, Admins)
 * - Dynamic Form Panel toggling based on chosen user role
 * - Form populating from metadata data-attributes during edits inside Modal
 * - Modal display, close controls, and overlay backdrop clicks
 * - Locking of Role dropdown during creations with submit override
 * - AJAX security actions (status toggle, make admin) with CSRF validation
 */

document.addEventListener('DOMContentLoaded', function() {
    const btnCreateUser = document.getElementById('btn-create-user');
    const btnCancelForm = document.getElementById('btn-cancel-form');
    const btnCloseModal = document.getElementById('btn-close-modal');
    const userForm = document.getElementById('user-form');
    const userModal = document.getElementById('user-modal');
    const formTitle = document.getElementById('form-title');
    const formUserId = document.getElementById('form-user-id');
    const formPassword = document.getElementById('form-password');
    const passwordNote = document.getElementById('password-note');
    const selectRole = document.getElementById('form-role');

    // Active Tab tracking (All by default)
    let currentActiveRole = 'all';

    // Dynamic Panels
    const trainerPanel = document.getElementById('trainer-fields-panel');
    const staffPanel = document.getElementById('staff-fields-panel');

    // Inputs inside panels
    const inputPosition = document.getElementById('form-position');
    const inputDepartment = document.getElementById('form-department');

    // CSRF Token from DOM
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;

    // ──────────────────────────────────────────────────────────────────────
    // Dynamic Role Form Toggle
    // ──────────────────────────────────────────────────────────────────────

    if (selectRole) {
        selectRole.addEventListener('change', function() {
            toggleRolePanels(this.value);
        });
    }

    // Real-time Username Formatting (convert spaces to underscores and strip invalid chars)
    const inputUsername = document.getElementById('form-username');
    if (inputUsername) {
        inputUsername.addEventListener('input', function() {
            let val = this.value;
            // Replace spaces with underscores
            val = val.replace(/\s+/g, '_');
            // Remove any character that is not a-zA-Z0-9_
            val = val.replace(/[^a-zA-Z0-9_]/g, '');
            this.value = val;
        });
    }

    /**
     * Toggles visibility and required validation of role-specific panels
     */
    function toggleRolePanels(role) {
        if (!trainerPanel || !staffPanel) return;

        if (role === 'trainer') {
            trainerPanel.style.display = 'block';
            staffPanel.style.display = 'none';

            // Disable staff validation
            if (inputPosition) inputPosition.required = false;
            if (inputDepartment) inputDepartment.required = false;
        } else if (role === 'staff') {
            trainerPanel.style.display = 'none';
            staffPanel.style.display = 'block';

            // Enable staff validation
            if (inputPosition) inputPosition.required = true;
            if (inputDepartment) inputDepartment.required = true;
        } else {
            // Member or Admin
            trainerPanel.style.display = 'none';
            staffPanel.style.display = 'none';

            if (inputPosition) inputPosition.required = false;
            if (inputDepartment) inputDepartment.required = false;
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Form & Modal Management
    // ──────────────────────────────────────────────────────────────────────

    if (btnCreateUser && btnCancelForm && userForm && userModal) {
        // Show form for creating a new user (opens Modal)
        btnCreateUser.addEventListener('click', function() {
            resetForm();
            formUserId.value = '';

            const roleNames = {
                'member': 'Membro',
                'trainer': 'Treinador',
                'staff': 'Pessoal',
                'admin': 'Administrador'
            };

            // Context-Aware Preselection and Dynamic Panel Display
            if (currentActiveRole !== 'all') {
                if (selectRole) {
                    selectRole.value = currentActiveRole;
                    selectRole.disabled = true; // Lock dropdown when adding contextually
                }
                toggleRolePanels(currentActiveRole);

                const roleName = roleNames[currentActiveRole] || 'Utilizador';
                if (formTitle) formTitle.textContent = `Novo ${roleName}`;
            } else {
                if (selectRole) {
                    selectRole.disabled = false; // general default
                }
                toggleRolePanels('');
                if (formTitle) formTitle.textContent = 'Novo Utilizador';
            }

            if (passwordNote) passwordNote.textContent = '(obrigatória para novos)';
            if (formPassword) formPassword.required = true;
            
            userModal.style.display = 'flex';
        });

        // Hide Modal (Cancel button click)
        btnCancelForm.addEventListener('click', function() {
            userModal.style.display = 'none';
            resetForm();
        });

        // Hide Modal (Close &times; click)
        if (btnCloseModal) {
            btnCloseModal.addEventListener('click', function() {
                userModal.style.display = 'none';
                resetForm();
            });
        }

        // Hide Modal when clicking on the outside blurred overlay background
        userModal.addEventListener('click', function(event) {
            if (event.target === userModal) {
                userModal.style.display = 'none';
                resetForm();
            }
        });

        // Ensure disabled select elements are submitted in the POST request!
        userForm.addEventListener('submit', function() {
            if (selectRole) {
                selectRole.disabled = false; // Temporarily enable before submit so POST receives the value!
            }
        });
    }

    // Edit user - populate form with existing data
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            populateEditForm(this);
        });
    });

    // ──────────────────────────────────────────────────────────────────────
    // Client-Side Tab Filtering & Dynamic Add Button Adaptability
    // ──────────────────────────────────────────────────────────────────────

    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const roleFilter = this.getAttribute('data-tab'); // e.g. "all", "member", "trainer", etc.
            currentActiveRole = roleFilter;

            // Reset active classes on tabs
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });

            // Set current tab active
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');

            // Toggle Button visibility and customize text contextually
            if (roleFilter === 'all') {
                if (btnCreateUser) btnCreateUser.style.display = 'none';
            } else {
                if (btnCreateUser) {
                    btnCreateUser.style.display = 'inline-flex';
                    
                    const roleNames = {
                        'member': 'Membro',
                        'trainer': 'Treinador',
                        'staff': 'Pessoal',
                        'admin': 'Administrador'
                    };
                    const roleName = roleNames[roleFilter] || 'Utilizador';
                    btnCreateUser.innerHTML = `➕ Adicionar ${roleName}`;
                }
            }

            // Filter rows instantly in the DOM
            document.querySelectorAll('.user-row').forEach(row => {
                const rowRole = row.getAttribute('data-user-role');
                if (roleFilter === 'all' || rowRole === roleFilter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
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



    // ──────────────────────────────────────────────────────────────────────
    // Helper Functions
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Reset form fields to clean state
     */
    function resetForm() {
        if (userForm) {
            userForm.reset();
            if (formUserId) formUserId.value = '';
            if (formPassword) formPassword.required = true;
            if (passwordNote) passwordNote.textContent = '(obrigatória para novos)';
            
            // Uncheck trainer specializations checkboxes
            document.querySelectorAll('#trainer-fields-panel input[type="checkbox"]').forEach(cb => {
                cb.checked = false;
            });
        }
    }

    /**
     * Populate the unified form inside Modal during edits using data attributes
     */
    function populateEditForm(button) {
        if (!userForm || !userModal) return;

        resetForm();

        const userId = button.dataset.userId;
        const name = button.dataset.name;
        const email = button.dataset.email;
        const username = button.dataset.username;
        const role = button.dataset.role;
        const phone = button.dataset.phone;

        // Populate basic info
        formUserId.value = userId;
        document.getElementById('form-name').value = name;
        document.getElementById('form-email').value = email;
        document.getElementById('form-username').value = username;
        document.getElementById('form-phone').value = phone;
        selectRole.value = role;
        if (selectRole) {
            selectRole.disabled = false; // Always make sure it is enabled/unlocked when editing!
        }

        // Password is not required when editing
        if (formPassword) {
            formPassword.value = '';
            formPassword.required = false;
        }
        if (passwordNote) {
            passwordNote.textContent = '(deixar em branco para não alterar)';
        }

        if (formTitle) {
            formTitle.textContent = `Editar: ${name}`;
        }

        // Toggle dynamic sub-panels
        toggleRolePanels(role);

        // Populate role-specific info
        if (role === 'trainer') {
            document.getElementById('form-bio').value = button.dataset.bio || '';
            document.getElementById('form-certifications').value = button.dataset.certifications || '';

            // Check checkboxes
            const rawSpecs = button.dataset.specializations || '';
            const specsArray = rawSpecs.split('/').map(s => s.trim().toLowerCase());

            document.querySelectorAll('#trainer-fields-panel input[type="checkbox"]').forEach(cb => {
                cb.checked = specsArray.includes(cb.value.toLowerCase());
            });

        } else if (role === 'staff') {
            if (inputPosition) inputPosition.value = button.dataset.position || '';
            if (inputDepartment) inputDepartment.value = button.dataset.department || '';
        }

        // Display Modal
        userModal.style.display = 'flex';
    }

    /**
     * Toggle user active/inactive status via AJAX
     */
    function toggleUserStatus(userId, currentStatus) {
        if (!confirm('Tem a certeza que deseja alterar o estado deste utilizador?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('user_id', userId);
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }

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
                const newStatus = data.new_status;
                button.dataset.currentStatus = newStatus;
                button.textContent = newStatus ? '🔒 Desativar' : '🔓 Ativar';

                const statusBadge = document.getElementById(`status-${userId}`);
                if (statusBadge) {
                    statusBadge.textContent = data.status_text;
                    statusBadge.className = `status-badge status-${newStatus ? 'active' : 'inactive'}`;
                }

                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Erro ao alterar estado.', 'error');
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

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});

// Toast animations
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
