/**
 * js/admin-schedules.js
 *
 * Gym Group Class Schedules - Administrative Logic
 * Handles:
 * - Modal display, close controls, and overlay backdrop clicks
 * - Populating modal form fields from card dataset attributes during edits
 * - Form clean resets on creation mode
 */

document.addEventListener('DOMContentLoaded', function() {
    const btnCreateClass = document.getElementById('btn-create-class');
    const btnCancelForm = document.getElementById('btn-cancel-form');
    const btnCloseModal = document.getElementById('btn-close-modal');
    const classForm = document.getElementById('class-form');
    const classModal = document.getElementById('class-modal');
    const formTitle = document.getElementById('form-title');
    
    // Form Inputs
    const formClassId = document.getElementById('form-class-id');
    const formName = document.getElementById('form-name');
    const formInstructor = document.getElementById('form-instructor');
    const formCapacity = document.getElementById('form-capacity');
    const formScheduledAt = document.getElementById('form-scheduled-at');
    const formDuration = document.getElementById('form-duration');
    const formRoom = document.getElementById('form-room');
    const formDescription = document.getElementById('form-description');

    /**
     * Reset form fields to clean state
     */
    function resetForm() {
        if (classForm) {
            classForm.reset();
        }
        if (formClassId) {
            formClassId.value = '';
        }
    }

    /**
     * Open modal in create mode
     */
    if (btnCreateClass && classModal) {
        btnCreateClass.addEventListener('click', function() {
            resetForm();
            if (formTitle) {
                formTitle.textContent = 'Nova Aula de Grupo';
            }
            // Enforce minimum date/time to local current moment
            if (formScheduledAt) {
                const now = new Date();
                const tzOffset = now.getTimezoneOffset() * 60000;
                const localISOTime = (new Date(now - tzOffset)).toISOString().slice(0, 16);
                formScheduledAt.min = localISOTime;
            }
            classModal.style.display = 'flex';
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
            const description = this.dataset.description;
            const instructorId = this.dataset.instructorId;
            const capacity = this.dataset.capacity;
            const scheduledAt = this.dataset.scheduledAt;
            const durationMin = this.dataset.durationMin;
            const room = this.dataset.room;

            // Populate form fields
            if (formClassId) formClassId.value = id;
            if (formName) formName.value = name;
            if (formInstructor) formInstructor.value = instructorId;
            if (formCapacity) formCapacity.value = capacity;
            if (formScheduledAt) formScheduledAt.value = scheduledAt;
            if (formDuration) formDuration.value = durationMin;
            if (formRoom) formRoom.value = room;
            if (formDescription) formDescription.value = description;

            // Enforce minimum date/time to local current moment
            if (formScheduledAt) {
                const now = new Date();
                const tzOffset = now.getTimezoneOffset() * 60000;
                const localISOTime = (new Date(now - tzOffset)).toISOString().slice(0, 16);
                formScheduledAt.min = localISOTime;
            }

            if (formTitle) {
                formTitle.textContent = `Editar: ${name}`;
            }

            if (classModal) {
                classModal.style.display = 'flex';
            }
        });
    });

    /**
     * Close triggers (Cancel button)
     */
    if (btnCancelForm && classModal) {
        btnCancelForm.addEventListener('click', function() {
            classModal.style.display = 'none';
            resetForm();
        });
    }

    /**
     * Close triggers (X button)
     */
    if (btnCloseModal && classModal) {
        btnCloseModal.addEventListener('click', function() {
            classModal.style.display = 'none';
            resetForm();
        });
    }

    /**
     * Close triggers (Click outside modal content)
     */
    if (classModal) {
        classModal.addEventListener('click', function(event) {
            if (event.target === classModal) {
                classModal.style.display = 'none';
                resetForm();
            }
        });
    }
});
