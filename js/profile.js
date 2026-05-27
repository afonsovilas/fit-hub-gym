// Profile Edit Mode Toggle
document.addEventListener('DOMContentLoaded', function() {
    const editBtn = document.getElementById('edit-profile-btn');
    const saveBtn = document.getElementById('save-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const profileForm = document.getElementById('profile-form');
    const formInputs = profileForm.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="password"], input[type="file"]');

    // Elements to update in the sidebar
    const profileName = document.querySelector('.profile-name');
    const profileUsername = document.querySelector('.profile-username');

    // Store original values
    const originalValues = {};
    formInputs.forEach(input => {
        originalValues[input.id] = input.value;
    });

    // Enable edit mode
    editBtn.addEventListener('click', function() {
        formInputs.forEach(input => {
            input.disabled = false;
        });
        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-block';
        cancelBtn.style.display = 'inline-block';
    });

    // Cancel edit mode
    cancelBtn.addEventListener('click', function() {
        // Restore original values
        formInputs.forEach(input => {
            input.value = originalValues[input.id];
        });
        // Disable inputs
        formInputs.forEach(input => {
            input.disabled = true;
        });
        editBtn.style.display = 'inline-block';
        saveBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
    });

    // ── Character Counter for Review TextAreas ──
    const commentTextareas = document.querySelectorAll('.review-form textarea');
    commentTextareas.forEach(textarea => {
        const charCount = textarea.parentElement.querySelector('.char-count');
        
        if (charCount) {
            // Initialize character count
            updateCharCount(textarea, charCount);
            
            // Update on input
            textarea.addEventListener('input', function() {
                updateCharCount(this, charCount);
            });
        }
    });

    function updateCharCount(textarea, charCount) {
        const maxLength = textarea.getAttribute('maxlength') || 500;
        const currentLength = textarea.value.length;
        charCount.textContent = currentLength + '/' + maxLength;
        
        // Change color if near limit
        if (currentLength > maxLength * 0.8) {
            charCount.style.color = '#dc3545';
            charCount.style.fontWeight = '600';
        } else {
            charCount.style.color = 'var(--color-text-muted)';
            charCount.style.fontWeight = 'normal';
        }
    }

    // ── Star Rating with Deselection ──
    const ratingRadios = document.querySelectorAll('.rating-radio');
    ratingRadios.forEach(radio => {
        // Store previous state
        radio.addEventListener('change', function() {
            const label = document.querySelector(`label[for="${this.id}"]`);
            if (label) {
                label.classList.add('selected');
            }
        });

        // Allow deselection by clicking the same star again
        const label = document.querySelector(`label[for="${radio.id}"]`);
        if (label) {
            label.addEventListener('click', function(e) {
                // Check if this radio is already selected
                if (radio.checked) {
                    // Prevent default radio behavior
                    e.preventDefault();
                    
                    // Uncheck the radio
                    radio.checked = false;
                    
                    // Remove selected class from all labels in this form
                    const form = radio.closest('.review-form');
                    if (form) {
                        const allLabels = form.querySelectorAll('.rating-label');
                        allLabels.forEach(lbl => lbl.classList.remove('selected'));
                    }
                }
            });
        }
    });
});
