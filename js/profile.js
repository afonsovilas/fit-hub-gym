// Profile Edit Mode Toggle
document.addEventListener('DOMContentLoaded', function() {
    function setupEditToggle(formId, editBtnId, saveBtnId, cancelBtnId) {
        const form = document.getElementById(formId);
        const editBtn = document.getElementById(editBtnId);
        const saveBtn = document.getElementById(saveBtnId);
        const cancelBtn = document.getElementById(cancelBtnId);
        if (!form || !editBtn) return;

        const formInputs = form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="password"], input[type="file"], textarea, input[type="checkbox"]');
        
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

        // Elements to update in the sidebar (only for personal form)
        const profilePhoto = document.querySelector('.profile-photo');
        const originalAvatarSrc = profilePhoto ? profilePhoto.src : '';

        // Enable edit mode
        editBtn.addEventListener('click', function() {
            formInputs.forEach(input => {
                input.disabled = false;
            });
            editBtn.style.display = 'none';
            if (saveBtn) saveBtn.style.display = 'inline-block';
            if (cancelBtn) cancelBtn.style.display = 'inline-block';
        });

        // Cancel edit mode
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                // Restore original values
                originalValues.forEach(item => {
                    if (item.element.type === 'checkbox') {
                        item.element.checked = item.checked;
                    } else {
                        item.element.value = item.value;
                    }
                });
                
                // Restore original avatar photo if this is the personal form
                if (formId === 'profile-form-personal') {
                    if (profilePhoto) {
                        profilePhoto.src = originalAvatarSrc;
                    }
                    const previewContainer = document.getElementById('avatar-preview-container');
                    if (previewContainer) {
                        previewContainer.style.display = 'none';
                    }
                }

                // Disable inputs
                formInputs.forEach(input => {
                    input.disabled = true;
                });
                editBtn.style.display = 'inline-block';
                if (saveBtn) saveBtn.style.display = 'none';
                if (cancelBtn) cancelBtn.style.display = 'none';
            });
        }
    }

    // Set up both forms independently!
    setupEditToggle('profile-form-personal', 'edit-profile-btn-personal', 'save-btn-personal', 'cancel-btn-personal');
    setupEditToggle('profile-form-professional', 'edit-profile-btn-professional', 'save-btn-professional', 'cancel-btn-professional');

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

        cropperZoomSlider.addEventListener('input', function() {
            const oldZoom = zoom;
            zoom = parseFloat(this.value);

            const center = containerSize / 2;

            currentX = center - (zoom / oldZoom) * (center - currentX);
            currentY = center - (zoom / oldZoom) * (center - currentY);

            limitBoundaries();
            updateImageTransform();
        });

        function closeAndResetCropper() {
            cropperModal.style.display = 'none';
            avatarInput.value = '';
            previewContainer.style.display = 'none';
            if (profilePhoto) {
                profilePhoto.src = originalAvatarSrc;
            }
        }

        cropperCancelBtn.addEventListener('click', closeAndResetCropper);
        cropperCloseBtn.addEventListener('click', closeAndResetCropper);

        cropperConfirmBtn.addEventListener('click', function() {
            try {
                const canvas = document.createElement('canvas');
                canvas.width = 400;
                canvas.height = 400;
                const ctx = canvas.getContext('2d');

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

                const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);
                
                const parts = croppedDataUrl.split(',');
                const mime = parts[0].match(/:(.*?);/)[1];
                const bstr = atob(parts[1]);
                let n = bstr.length;
                const u8arr = new Uint8Array(n);
                while (n--) {
                    u8arr[n] = bstr.charCodeAt(n);
                }
                const blob = new Blob([u8arr], { type: mime });

                const originalName = selectedFile ? selectedFile.name : 'avatar.jpg';
                const croppedFile = new File([blob], originalName, { type: 'image/jpeg' });

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(croppedFile);
                avatarInput.files = dataTransfer.files;

                previewImg.src = croppedDataUrl;
                previewContainer.style.display = 'flex';
                if (profilePhoto) {
                    profilePhoto.src = croppedDataUrl;
                }
            } catch (err) {
                console.error('Error cropping avatar:', err);
            }
            cropperModal.style.display = 'none';
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


    function fetchTrainerReviews(trainerId, container) {
        container.replaceChildren();

        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'trainer-reviews-loading';

        const spinner = document.createElement('span');
        spinner.className = 'spinner';
        loadingDiv.appendChild(spinner);

        const loadingText = document.createElement('p');
        loadingText.textContent = 'A carregar avaliações...';
        loadingDiv.appendChild(loadingText);

        container.appendChild(loadingDiv);

        fetch(`../api/api_get_trainer_reviews.php?trainer_id=${encodeURIComponent(trainerId)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                container.replaceChildren();

                if (!data.success) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'trainer-reviews-empty';
                    errorDiv.textContent = data.message || 'Erro ao carregar avaliações.';
                    container.appendChild(errorDiv);
                    return;
                }

                const reviews = data.reviews || [];
                if (reviews.length === 0) {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'trainer-reviews-empty';
                    emptyDiv.textContent = 'Ainda não tem avaliações das suas aulas.';
                    container.appendChild(emptyDiv);
                    container.setAttribute('data-fetched', 'true');
                    return;
                }

                reviews.forEach(review => {
                    const card = document.createElement('article');
                    card.className = 'trainer-review-card';

                    const header = document.createElement('div');
                    header.className = 'trainer-review-header';

                    const classTitle = document.createElement('h4');
                    classTitle.className = 'trainer-review-class';
                    classTitle.textContent = review.class_name;
                    header.appendChild(classTitle);

                    const dateSpan = document.createElement('span');
                    dateSpan.className = 'trainer-review-date';
                    dateSpan.textContent = formatDate(review.created_at);
                    header.appendChild(dateSpan);

                    card.appendChild(header);

                    const meta = document.createElement('div');
                    meta.className = 'trainer-review-meta';

                    const memberSpan = document.createElement('span');
                    memberSpan.className = 'trainer-review-member';
                    memberSpan.textContent = 'Por: ' + (review.member_name || 'Membro Anónimo');
                    meta.appendChild(memberSpan);

                    const starsDiv = document.createElement('div');
                    starsDiv.className = 'trainer-review-stars';
                    
                    const rating = parseInt(review.rating) || 0;
                    for (let i = 1; i <= 5; i++) {
                        const star = document.createElement('span');
                        star.textContent = '★';
                        if (i > rating) {
                            star.style.color = 'var(--color-border)';
                        }
                        starsDiv.appendChild(star);
                    }
                    meta.appendChild(starsDiv);

                    card.appendChild(meta);

                    if (review.comment && review.comment.trim() !== '') {
                        const commentP = document.createElement('p');
                        commentP.className = 'trainer-review-comment';
                        commentP.textContent = '“' + review.comment + '”';
                        card.appendChild(commentP);
                    }

                    container.appendChild(card);
                });

                container.setAttribute('data-fetched', 'true');
            })
            .catch(err => {
                console.error('Erro ao carregar avaliações:', err);
                container.replaceChildren();
                const errorDiv = document.createElement('div');
                errorDiv.className = 'trainer-reviews-empty';
                errorDiv.textContent = 'Erro de ligação ao carregar as avaliações.';
                container.appendChild(errorDiv);
            });
    }

    function formatDate(dateString) {
        try {
            const date = new Date(dateString.replace(' ', 'T'));
            if (isNaN(date.getTime())) {
                return dateString;
            }
            return date.toLocaleDateString('pt-PT', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateString;
        }
    }

    // ── Member Profile Tab Switcher ──
    const tabButtons = document.querySelectorAll('.tab-btn');

    if (tabButtons.length > 0) {
        function activateTab(tabId) {
            // Deactivate all buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));

            const tabPanes = document.querySelectorAll('[id^="pane-"]');
            tabPanes.forEach(pane => {
                pane.classList.remove('active');
                pane.style.display = 'none';
            });

            const targetBtn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
            const targetPane = document.getElementById(`pane-${tabId}`);

            if (targetBtn && targetPane) {
                targetBtn.classList.add('active');
                targetPane.classList.add('active');
                targetPane.style.display = 'block';
                
                window.history.replaceState(null, null, '#' + tabId);

                // Auto-load trainer reviews when switching to the reviews tab
                if (tabId === 'avaliacoes-aulas') {
                    const reviewsContainer = document.getElementById('trainer-reviews-container');
                    if (reviewsContainer) {
                        const trainerId = reviewsContainer.getAttribute('data-trainer-id');
                        reviewsContainer.classList.add('reviews-visible');
                        if (reviewsContainer.getAttribute('data-fetched') !== 'true') {
                            fetchTrainerReviews(trainerId, reviewsContainer);
                        }
                    }
                }
            }
        }

        // Add click events to buttons
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                if (tabId) {
                    activateTab(tabId);
                }
            });
        });

        // Initialize tab based on URL Hash
        function initTabFromHash() {
            const hash = window.location.hash.substring(1); // strip the leading '#'
            const allowedTabs = ['dados-pessoais', 'aulas-grupo', 'treino-personalizado', 'avaliacoes-historico', 'dados-profissionais', 'avaliacoes-aulas', 'nutricao'];
            
            if (hash && allowedTabs.includes(hash)) {
                activateTab(hash);
            } else {
                // If it's a member but no valid hash, set default hash silently
                if (window.location.hash === '') {
                    window.history.replaceState(null, null, '#dados-pessoais');
                }
                activateTab('dados-pessoais');
            }
        }

        // Run initialization
        initTabFromHash();

        // Listen for popstate/hashchange to allow browser back/forward navigation
        window.addEventListener('hashchange', initTabFromHash);
    }

    // ── Profile Forms Confirmation Modals ──
    const profileCancelForms = document.querySelectorAll('.profile-cancel-form');
    const profileConfirmModal = document.getElementById('profile-confirm-modal');
    const profileConfirmTitle = document.getElementById('profile-confirm-title');
    const profileConfirmMsg = document.getElementById('profile-confirm-message');
    const profileConfirmCancel = document.getElementById('profile-confirm-cancel');
    const profileConfirmClose = document.getElementById('profile-confirm-close');
    const profileConfirmProceed = document.getElementById('profile-confirm-proceed');

    if (profileConfirmModal && profileCancelForms.length > 0) {
        let activeFormToSubmit = null;

        profileCancelForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                // Prevent standard submit
                e.preventDefault();
                activeFormToSubmit = this;

                // Extract custom texts
                const titleText = this.getAttribute('data-title') || 'Confirmar Ação';
                const messageText = this.getAttribute('data-message') || 'Tem a certeza que deseja prosseguir com esta ação?';

                // Populate modal
                profileConfirmTitle.textContent = titleText;
                profileConfirmMsg.textContent = messageText;

                // Open modal
                profileConfirmModal.style.display = 'flex';
            });
        });

        // Close handlers
        function closeProfileConfirmModal() {
            profileConfirmModal.style.display = 'none';
            activeFormToSubmit = null;
        }

        if (profileConfirmCancel) profileConfirmCancel.addEventListener('click', closeProfileConfirmModal);
        if (profileConfirmClose) profileConfirmClose.addEventListener('click', closeProfileConfirmModal);

        // Click outside closes modal
        profileConfirmModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeProfileConfirmModal();
            }
        });

        // Proceed handler
        if (profileConfirmProceed) {
            profileConfirmProceed.addEventListener('click', function() {
                if (activeFormToSubmit) {
                    // Submit form directly, bypassing our interceptor
                    activeFormToSubmit.submit();
                }
                closeProfileConfirmModal();
            });
        }
    }

    // ── Diet Plan View Modal ──
    const btnViewDiets = document.querySelectorAll('.btn-view-diet');
    const dietModal = document.getElementById('diet-plan-modal');
    const dietModalBody = document.getElementById('diet-modal-body');
    const dietModalClose = document.getElementById('diet-modal-close');
    const dietModalOk = document.getElementById('diet-modal-ok');

    if (dietModal && btnViewDiets.length > 0) {
        btnViewDiets.forEach(btn => {
            btn.addEventListener('click', function() {
                const planText = this.getAttribute('data-plan') || '';
                
                // Set text securely
                dietModalBody.textContent = planText !== '' ? planText : 'Sem detalhes do plano alimentar registados.';
                
                // Show modal
                dietModal.style.display = 'flex';
            });
        });

        function closeDietModal() {
            dietModal.style.display = 'none';
        }

        if (dietModalClose) dietModalClose.addEventListener('click', closeDietModal);
        if (dietModalOk) dietModalOk.addEventListener('click', closeDietModal);

        dietModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDietModal();
            }
        });
    }

    // ── Dynamic Hours Filtering & Auto-population for PT bookings ──
    const ptDateInput = document.getElementById('pt-date');
    const ptTimeSelect = document.getElementById('pt-time');
    const ptHiddenInput = document.getElementById('pt-datetime');

    if (ptDateInput && ptTimeSelect && ptHiddenInput) {
        const ptForm = ptDateInput.closest('form');

        ptDateInput.addEventListener('change', function() {
            updateTimeOptions(this, ptTimeSelect);
            updateHiddenDateTime(ptDateInput, ptTimeSelect, ptHiddenInput);
        });
        
        ptTimeSelect.addEventListener('change', function() {
            updateHiddenDateTime(ptDateInput, ptTimeSelect, ptHiddenInput);
        });

        if (ptForm) {
            ptForm.addEventListener('submit', function() {
                updateHiddenDateTime(ptDateInput, ptTimeSelect, ptHiddenInput);
            });
        }

        // Initialize if date is already set
        if (ptDateInput.value) {
            updateTimeOptions(ptDateInput, ptTimeSelect);
            updateHiddenDateTime(ptDateInput, ptTimeSelect, ptHiddenInput);
        }
    }

    function updateHiddenDateTime(dateInput, timeSelect, hiddenInput) {
        let timeVal = timeSelect.value;
        if (!timeVal && timeSelect.options && timeSelect.options.length > 0) {
            for (let i = 0; i < timeSelect.options.length; i++) {
                if (timeSelect.options[i].value) {
                    timeVal = timeSelect.options[i].value;
                    break;
                }
            }
        }
        if (dateInput.value && timeVal) {
            hiddenInput.value = `${dateInput.value}T${timeVal}`;
        } else {
            hiddenInput.value = '';
        }
    }

    function getValidTimesForDate(dateStr) {
        if (!dateStr) return [];
        const dateParts = dateStr.split('-');
        const year = parseInt(dateParts[0]);
        const month = parseInt(dateParts[1]) - 1;
        const day = parseInt(dateParts[2]);
        const date = new Date(year, month, day);
        
        // Check holiday
        const mStr = String(month + 1).padStart(2, '0');
        const dStr = String(day).padStart(2, '0');
        const md = `${mStr}-${dStr}`;
        
        const fixedHolidays = [
            '01-01', '04-25', '05-01', '06-10', '08-15', '10-05', '11-01', '12-01', '12-08', '12-25'
        ];
        
        let isHoliday = fixedHolidays.includes(md);
        if (!isHoliday) {
            // Meeus/Jones/Butcher Easter calculation
            const a = year % 19;
            const b = Math.floor(year / 100);
            const c = year % 100;
            const d = Math.floor(b / 4);
            const e = b % 4;
            const f = Math.floor((b + 8) / 25);
            const g = Math.floor((b - f + 1) / 3);
            const h = (19 * a + b - d - g + 15) % 30;
            const i = Math.floor(c / 4);
            const k = c % 4;
            const l = (32 + 2 * e + 2 * i - h - k) % 7;
            const m = Math.floor((a + 11 * h + 22 * l) / 451);
            const easterMonth = Math.floor((h + l - 7 * m + 114) / 31) - 1;
            const easterDay = ((h + l - 7 * m + 114) % 31) + 1;
            
            const easter = new Date(year, easterMonth, easterDay);
            const goodFriday = new Date(year, easterMonth, easterDay - 2);
            const corpusChristi = new Date(year, easterMonth, easterDay + 60);
            
            const checkYMD = `${year}-${mStr}-${dStr}`;
            const formatYMD = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            
            if (checkYMD === formatYMD(easter) || checkYMD === formatYMD(goodFriday) || checkYMD === formatYMD(corpusChristi)) {
                isHoliday = true;
            }
        }
        
        const dayOfWeek = date.getDay(); // 0 = Sunday, 6 = Saturday, 1-5 = Weekdays
        let startHour, endHour;
        
        if (isHoliday) {
            startHour = 9;
            endHour = 19; // Closes at 20:00, last session starts at 19:00
        } else if (dayOfWeek === 0) { // Sunday
            startHour = 8;
            endHour = 20; // Closes at 21:00, last session starts at 20:00
        } else if (dayOfWeek === 6) { // Saturday
            startHour = 7;
            endHour = 21; // Closes at 22:00, last session starts at 21:00
        } else { // Mon-Fri
            startHour = 6;
            endHour = 22; // Closes at 23:00, last session starts at 22:00
        }
        
        const times = [];
        for (let h = startHour; h <= endHour; h++) {
            for (let m = 0; m < 60; m += 15) {
                if (h === endHour && m > 0) break; // Don't allow beyond closing hour
                times.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`);
            }
        }
        
        // If selected date is today, filter out past times
        const now = new Date();
        const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        if (dateStr === todayStr) {
            const currentHour = now.getHours();
            const currentMin = now.getMinutes();
            const currentTimeVal = currentHour * 100 + currentMin;
            return times.filter(t => {
                const parts = t.split(':');
                const val = parseInt(parts[0]) * 100 + parseInt(parts[1]);
                return val > currentTimeVal;
            });
        }
        
        return times;
    }

    function updateTimeOptions(dateInput, timeSelect, selectedTime = '') {
        const dateStr = dateInput.value;
        timeSelect.innerHTML = '';
        
        if (!dateStr) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- Selecione a data --';
            timeSelect.appendChild(opt);
            return;
        }
        
        const validTimes = getValidTimesForDate(dateStr);
        
        if (validTimes.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- Sem horários disponíveis --';
            timeSelect.appendChild(opt);
            return;
        }
        
        validTimes.forEach((time, index) => {
            const opt = document.createElement('option');
            opt.value = time;
            opt.textContent = time;
            if (time === selectedTime || (selectedTime === '' && index === 0)) {
                opt.selected = true;
            }
            timeSelect.appendChild(opt);
        });
    }
});


