// Profile Edit Mode Toggle
document.addEventListener('DOMContentLoaded', function() {
    const editBtn = document.getElementById('edit-profile-btn');
    const saveBtn = document.getElementById('save-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const profileForm = document.getElementById('profile-form');
    const formInputs = profileForm.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="password"], input[type="file"], textarea, input[type="checkbox"]');

    // Elements to update in the sidebar
    const profileName = document.querySelector('.profile-name');
    const profileUsername = document.querySelector('.profile-username');
    const profilePhoto = document.querySelector('.profile-photo');
    const originalAvatarSrc = profilePhoto ? profilePhoto.src : '';

    // Store original values
    const originalValues = [];
    formInputs.forEach(input => {
        if (input.type === 'checkbox') {
            originalValues.push({
                element: input,
                checked: input.checked
            });
        } else {
            originalValues.push({
                element: input,
                value: input.value
            });
        }
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
        originalValues.forEach(item => {
            if (item.element.type === 'checkbox') {
                item.element.checked = item.checked;
            } else {
                item.element.value = item.value;
            }
        });
        // Restore original avatar photo
        if (profilePhoto) {
            profilePhoto.src = originalAvatarSrc;
        }
        const previewContainer = document.getElementById('avatar-preview-container');
        if (previewContainer) {
            previewContainer.style.display = 'none';
        }
        // Disable inputs
        formInputs.forEach(input => {
            input.disabled = true;
        });
        editBtn.style.display = 'inline-block';
        saveBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
    });

    // ── Dynamic Avatar Pre-visualization with Interactive Drag-and-Zoom Cropper ──
    const avatarInput = document.getElementById('avatar');
    const previewContainer = document.getElementById('avatar-preview-container');
    const previewImg = document.getElementById('avatar-preview-img');
    
    // Modal components
    const cropperModal = document.getElementById('avatar-cropper-modal');
    const cropperViewport = document.getElementById('cropper-viewport');
    const cropperImg = document.getElementById('cropper-preview-img');
    const cropperZoomSlider = document.getElementById('cropper-zoom-slider');
    const cropperConfirmBtn = document.getElementById('cropper-confirm-btn');
    const cropperCancelBtn = document.getElementById('cropper-cancel-btn');
    const cropperCloseBtn = document.getElementById('cropper-close-btn');

    // Cropper State variables
    const containerSize = 280; // size of the circle viewport
    let zoom = 1;
    let currentX = 0;
    let currentY = 0;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let baseWidth = 0;
    let baseHeight = 0;
    let selectedFile = null;

    if (avatarInput && previewImg && previewContainer && cropperModal) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Check file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('A imagem de perfil deve ter no máximo 2MB.');
                    this.value = '';
                    return;
                }
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Apenas são permitidas imagens JPG, PNG, GIF ou WEBP.');
                    this.value = '';
                    return;
                }

                selectedFile = file;
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Set src on the cropper image
                    cropperImg.src = e.target.result;
                    cropperModal.style.display = 'flex';
                }
                reader.readAsDataURL(file);
            }
        });

        // Clicking the preview container opens the cropper modal again
        previewContainer.addEventListener('click', function() {
            if (selectedFile) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    cropperImg.src = e.target.result;
                    cropperModal.style.display = 'flex';
                }
                reader.readAsDataURL(selectedFile);
            }
        });

        // Initialize Image once it is loaded inside the modal
        cropperImg.onload = function() {
            const naturalWidth = cropperImg.naturalWidth;
            const naturalHeight = cropperImg.naturalHeight;

            if (naturalWidth > naturalHeight) {
                // Landscape: height fits container, width scales proportionally
                baseHeight = containerSize;
                baseWidth = (naturalWidth / naturalHeight) * containerSize;
            } else {
                // Portrait: width fits container, height scales proportionally
                baseWidth = containerSize;
                baseHeight = (naturalHeight / naturalWidth) * containerSize;
            }

            cropperImg.style.width = baseWidth + 'px';
            cropperImg.style.height = baseHeight + 'px';

            // Center initially
            currentX = (containerSize - baseWidth) / 2;
            currentY = (containerSize - baseHeight) / 2;
            zoom = 1;
            cropperZoomSlider.value = 1;

            updateImageTransform();
        };

        function updateImageTransform() {
            cropperImg.style.transform = `translate(${currentX}px, ${currentY}px) scale(${zoom})`;
        }

        function limitBoundaries() {
            const renderedWidth = baseWidth * zoom;
            const renderedHeight = baseHeight * zoom;

            if (currentX > 0) currentX = 0;
            if (currentY > 0) currentY = 0;

            if (currentX < containerSize - renderedWidth) {
                currentX = containerSize - renderedWidth;
            }
            if (currentY < containerSize - renderedHeight) {
                currentY = containerSize - renderedHeight;
            }
        }

        // ── Drag Listeners ──
        function onDragStart(e) {
            isDragging = true;
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            startX = clientX - currentX;
            startY = clientY - currentY;
            cropperViewport.style.cursor = 'grabbing';
        }

        function onDragMove(e) {
            if (!isDragging) return;
            e.preventDefault(); // Prevent scrolling on touch
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            currentX = clientX - startX;
            currentY = clientY - startY;

            limitBoundaries();
            updateImageTransform();
        }

        function onDragEnd() {
            isDragging = false;
            cropperViewport.style.cursor = 'grab';
        }

        // Attach Mouse events
        cropperViewport.addEventListener('mousedown', onDragStart);
        window.addEventListener('mousemove', onDragMove);
        window.addEventListener('mouseup', onDragEnd);

        // Attach Touch events
        cropperViewport.addEventListener('touchstart', onDragStart, { passive: false });
        window.addEventListener('touchmove', onDragMove, { passive: false });
        window.addEventListener('touchend', onDragEnd);

        // ── Zoom Zoom centered calculation ──
        cropperZoomSlider.addEventListener('input', function() {
            const oldZoom = zoom;
            zoom = parseFloat(this.value);

            const center = containerSize / 2;

            // Zoom centered relative to viewport pivot
            currentX = center - (zoom / oldZoom) * (center - currentX);
            currentY = center - (zoom / oldZoom) * (center - currentY);

            limitBoundaries();
            updateImageTransform();
        });

        // ── Close / Reset Cropper modal functions ──
        function closeAndResetCropper() {
            cropperModal.style.display = 'none';
            avatarInput.value = ''; // Clear file input
            previewContainer.style.display = 'none';
            if (profilePhoto) {
                profilePhoto.src = originalAvatarSrc;
            }
        }

        cropperCancelBtn.addEventListener('click', closeAndResetCropper);
        cropperCloseBtn.addEventListener('click', closeAndResetCropper);

        // ── Canvas Cropping and DataTransfer Submission ──
        cropperConfirmBtn.addEventListener('click', function() {
            const canvas = document.createElement('canvas');
            canvas.width = 400;
            canvas.height = 400;
            const ctx = canvas.getContext('2d');

            // Map viewport coordinates back to original unscaled image scale
            const sx = -currentX / zoom;
            const sy = -currentY / zoom;
            const sw = containerSize / zoom;
            const sh = containerSize / zoom;

            const scaleFactor = cropperImg.naturalWidth / baseWidth;

            const finalSx = sx * scaleFactor;
            const finalSy = sy * scaleFactor;
            const finalSw = sw * scaleFactor;
            const finalSh = sh * scaleFactor;

            ctx.drawImage(cropperImg, finalSx, finalSy, finalSw, finalSh, 0, 0, 400, 400);

            // Convert to Blob and set as File input value
            canvas.toBlob(function(blob) {
                if (blob) {
                    const originalName = selectedFile ? selectedFile.name : 'avatar.jpg';
                    const croppedFile = new File([blob], originalName, { type: 'image/jpeg' });

                    // DataTransfer magic
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(croppedFile);
                    avatarInput.files = dataTransfer.files;

                    // Update visuals
                    const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);
                    previewImg.src = croppedDataUrl;
                    previewContainer.style.display = 'flex';
                    if (profilePhoto) {
                        profilePhoto.src = croppedDataUrl;
                    }
                }
                cropperModal.style.display = 'none';
            }, 'image/jpeg', 0.9);
        });
    }

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
