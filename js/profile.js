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

    // Handle form submission
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Update sidebar with new values
        const fullNameInput = document.getElementById('full-name');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');

        profileName.textContent = fullNameInput.value;
        profileUsername.textContent = '@' + usernameInput.value;

        // Update the profile details in sidebar
        const emailDetail = document.querySelector('.profile-detail:nth-child(1)');
        const memberSinceDetail = document.querySelector('.profile-detail:nth-child(2)');
        const statusDetail = document.querySelector('.profile-detail:nth-child(3)');

        if (emailDetail) {
            emailDetail.innerHTML = '<span class="detail-label">Email:</span><span>' + emailInput.value + '</span>';
        }

        // Update original values with new values
        formInputs.forEach(input => {
            originalValues[input.id] = input.value;
        });
        
        // Disable inputs after save
        formInputs.forEach(input => {
            input.disabled = true;
        });
        editBtn.style.display = 'inline-block';
        saveBtn.style.display = 'none';
        cancelBtn.style.display = 'none';

        // Show success message (optional)
        console.log('Profile updated successfully');
        
        // You can add a visual feedback here (e.g., toast notification)
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

    // ── Star Rating Keyboard Navigation ──
    const ratingRadios = document.querySelectorAll('.rating-radio');
    ratingRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Optional: Add visual feedback on selection
            const label = document.querySelector(`label[for="${this.id}"]`);
            if (label) {
                label.classList.add('selected');
            }
        });
    });
});
