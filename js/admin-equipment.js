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
});
