/**
 * js/admin-equipment.js
 *
 * Gym Equipment Dashboard Refactoring - Frontend Logic
 * Handles:
 * - Modal display, close controls, and overlay backdrop clicks
 * - Populating modal form fields from card dataset attributes during edits
 * - Form clean resets on creation mode
 */

document.addEventListener('DOMContentLoaded', function() {
    const btnCreateEquipment = document.getElementById('btn-create-equipment');
    const btnCancelForm = document.getElementById('btn-cancel-form');
    const btnCloseModal = document.getElementById('btn-close-modal');
    const equipmentForm = document.getElementById('equipment-form');
    const equipmentModal = document.getElementById('equipment-modal');
    const formTitle = document.getElementById('form-title');
    
    // Form Inputs
    const formEquipmentId = document.getElementById('form-equipment-id');
    const formName = document.getElementById('form-name');
    const formCategory = document.getElementById('form-category');
    const formStatus = document.getElementById('form-status');
    const formDescription = document.getElementById('form-description');

    /**
     * Reset form fields to clean state
     */
    function resetForm() {
        if (equipmentForm) {
            equipmentForm.reset();
        }
        if (formEquipmentId) {
            formEquipmentId.value = '';
        }
    }

    /**
     * Open modal in create mode
     */
    if (btnCreateEquipment && equipmentModal) {
        btnCreateEquipment.addEventListener('click', function() {
            resetForm();
            if (formTitle) {
                formTitle.textContent = 'Novo Equipamento';
            }
            equipmentModal.style.display = 'flex';
        });
    }

    /**
     * Bind edit buttons
     */
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            resetForm();

            const id = this.dataset.id;
            const name = this.dataset.name;
            const category = this.dataset.category;
            const status = this.dataset.status;
            const description = this.dataset.description;

            // Populate form fields
            if (formEquipmentId) formEquipmentId.value = id;
            if (formName) formName.value = name;
            if (formCategory) formCategory.value = category;
            if (formStatus) formStatus.value = status;
            if (formDescription) formDescription.value = description;

            if (formTitle) {
                formTitle.textContent = `Editar: ${name}`;
            }

            if (equipmentModal) {
                equipmentModal.style.display = 'flex';
            }
        });
    });

    /**
     * Close triggers (Cancel button)
     */
    if (btnCancelForm && equipmentModal) {
        btnCancelForm.addEventListener('click', function() {
            equipmentModal.style.display = 'none';
            resetForm();
        });
    }

    /**
     * Close triggers (X button)
     */
    if (btnCloseModal && equipmentModal) {
        btnCloseModal.addEventListener('click', function() {
            equipmentModal.style.display = 'none';
            resetForm();
        });
    }

    /**
     * Close triggers (Click outside modal content)
     */
    if (equipmentModal) {
        equipmentModal.addEventListener('click', function(event) {
            if (event.target === equipmentModal) {
                equipmentModal.style.display = 'none';
                resetForm();
            }
        });
    }

    // ── Deletion Custom Modal Logic ──
    const deleteModal = document.getElementById('delete-equipment-confirm-modal');
    const deleteMessage = document.getElementById('delete-equipment-message');
    const btnDeleteCancel = document.getElementById('delete-equipment-cancel');
    const btnDeleteProceed = document.getElementById('delete-equipment-proceed');
    const btnDeleteClose = document.getElementById('delete-equipment-close');

    let activeFormToDelete = null;

    // Intercept delete form submissions
    document.querySelectorAll('.delete-equipment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            activeFormToDelete = this;

            if (deleteModal && deleteMessage) {
                const equipmentName = this.dataset.equipmentName || 'este equipamento';
                deleteMessage.innerHTML = `Deseja realmente remover o equipamento <strong>"${equipmentName}"</strong>?`;
                deleteModal.style.display = 'flex';
            }
        });
    });

    // Close handlers for delete modal
    function closeDeleteModal() {
        if (deleteModal) deleteModal.style.display = 'none';
        activeFormToDelete = null;
    }

    if (btnDeleteCancel) btnDeleteCancel.addEventListener('click', closeDeleteModal);
    if (btnDeleteClose) btnDeleteClose.addEventListener('click', closeDeleteModal);
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                closeDeleteModal();
            }
        });
    }

    // Proceed with deletion form submit
    if (btnDeleteProceed) {
        btnDeleteProceed.addEventListener('click', function() {
            if (activeFormToDelete) {
                const form = activeFormToDelete;
                closeDeleteModal();
                form.submit();
            }
        });
    }
});
