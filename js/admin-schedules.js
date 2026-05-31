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
    const formScheduledDate = document.getElementById('form-scheduled-date');
    const formScheduledTime = document.getElementById('form-scheduled-time');
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
        if (formScheduledTime) {
            formScheduledTime.innerHTML = '<option value="">-- Selecione data --</option>';
        }
        if (formScheduledAt) {
            formScheduledAt.value = '';
        }
    }

    if (formScheduledDate && formScheduledTime && formScheduledAt) {
        formScheduledDate.addEventListener('change', function() {
            const dateVal = this.value;
            if (dateVal) {
                const dateParts = dateVal.split('-');
                const year = parseInt(dateParts[0]);
                const month = parseInt(dateParts[1]) - 1;
                const day = parseInt(dateParts[2]);
                const selectedDate = new Date(year, month, day);
                
                if (selectedDate.getDay() === 0) { // Sunday
                    if (typeof showToast === 'function') {
                        showToast('O FitHub encontra-se encerrado aos Domingos. Por favor, selecione outro dia (Segunda a Sábado).', 'error');
                    } else {
                        alert('O FitHub encontra-se encerrado aos Domingos. Por favor, selecione outro dia (Segunda a Sábado).');
                    }
                    this.value = '';
                    formScheduledTime.innerHTML = '<option value="">-- Selecione data --</option>';
                    formScheduledAt.value = '';
                    return;
                }
            }
            updateTimeOptions(this, formScheduledTime);
            updateHiddenDateTime(formScheduledDate, formScheduledTime, formScheduledAt);
        });
        
        formScheduledTime.addEventListener('change', function() {
            updateHiddenDateTime(formScheduledDate, formScheduledTime, formScheduledAt);
        });
    }

    function updateHiddenDateTime(dateInput, timeSelect, hiddenInput) {
        if (dateInput.value && timeSelect.value) {
            hiddenInput.value = `${dateInput.value}T${timeSelect.value}`;
        } else {
            hiddenInput.value = '';
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
            if (formScheduledDate) {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                formScheduledDate.min = `${year}-${month}-${day}`;
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
            if (formDuration) formDuration.value = durationMin;
            if (formRoom) formRoom.value = room;
            if (formDescription) formDescription.value = description;

            if (scheduledAt) {
                const datePart = scheduledAt.substring(0, 10);
                const timePart = scheduledAt.substring(11, 16);
                
                if (formScheduledDate) {
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    formScheduledDate.min = `${year}-${month}-${day}`;
                    formScheduledDate.value = datePart;
                }
                
                if (formScheduledTime) {
                    updateTimeOptions(formScheduledDate, formScheduledTime, timePart);
                }
                
                if (formScheduledAt) {
                    formScheduledAt.value = scheduledAt;
                }
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

    // ── Deletion Custom Modal Logic ──
    const deleteModal = document.getElementById('delete-class-confirm-modal');
    const deleteMessage = document.getElementById('delete-class-message');
    const btnDeleteCancel = document.getElementById('delete-class-cancel');
    const btnDeleteProceed = document.getElementById('delete-class-proceed');
    const btnDeleteClose = document.getElementById('delete-class-close');

    let activeFormToDelete = null;

    // Intercept delete form submissions
    document.querySelectorAll('.delete-class-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            activeFormToDelete = this;

            if (deleteModal && deleteMessage) {
                const className = this.dataset.className || 'esta aula';
                deleteMessage.innerHTML = `Deseja realmente remover a aula <strong>"${className}"</strong>?<br><span style="font-size: 0.85em; color: #f87171;">Atenção: Isto também removerá todas as inscrições associadas!</span>`;
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

    // Validation for schedules creation/edit
    if (classForm) {
        classForm.addEventListener('submit', function(e) {
            if (formScheduledAt) {
                const val = formScheduledAt.value;
                const res = validateGymTime(val);
                if (!res.success) {
                    e.preventDefault();
                    if (typeof showToast === 'function') {
                        showToast(res.message, 'error');
                    } else {
                        alert(res.message);
                    }
                }
            }
        });
    }

    function validateGymTime(dateTimeStr) {
        if (!dateTimeStr) return { success: false, message: 'Selecione uma data e hora.' };
        
        const date = new Date(dateTimeStr);
        if (isNaN(date.getTime())) {
            return { success: false, message: 'Data e hora inválidas.' };
        }
        
        // 1. Check 15-minute interval
        const minutes = date.getMinutes();
        if (minutes % 15 !== 0) {
            return { 
                success: false, 
                message: 'O horário deve ser agendado em intervalos de 15 minutos (ex: :00, :15, :30, :45).' 
            };
        }
        
        // 2. Check Portuguese holidays
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const md = `${month}-${day}`;
        
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
            
            const checkYMD = `${year}-${month}-${day}`;
            const formatYMD = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            
            if (checkYMD === formatYMD(easter) || checkYMD === formatYMD(goodFriday) || checkYMD === formatYMD(corpusChristi)) {
                isHoliday = true;
            }
        }
        
        // 3. Get opening hours limits
        const dayOfWeek = date.getDay();
        if (dayOfWeek === 0) {
            return {
                success: false,
                message: 'O FitHub encontra-se encerrado aos Domingos. Por favor, selecione outro dia (Segunda a Sábado).'
            };
        }
        const hours = date.getHours();
        const mins = date.getMinutes();
        const timeVal = hours * 100 + mins;
        
        let open, close, dayName;
        if (isHoliday) {
            open = 900;
            close = 2000;
            dayName = 'Feriado';
        } else if (dayOfWeek === 6) {
            open = 700;
            close = 2200;
            dayName = 'Sábado';
        } else {
            open = 600;
            close = 2300;
            dayName = 'Segunda a Sexta';
        }
        
        if (timeVal < open || timeVal > close) {
            const formatTime = (t) => {
                const h = String(Math.floor(t / 100)).padStart(2, '0');
                const m = String(t % 100).padStart(2, '0');
                return `${h}:${m}`;
            };
            return {
                success: false,
                message: `O horário de funcionamento ao ${dayName} é das ${formatTime(open)} às ${formatTime(close)}. A hora de início (${formatTime(timeVal)}) não pode ser anterior à de abertura nem posterior à de fecho.`
            };
        }
        
        return { success: true };
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
        if (dayOfWeek === 0) {
            return []; // Sunday is closed!
        }
        let startHour, endHour;
        
        if (isHoliday) {
            startHour = 9;
            endHour = 20;
        } else if (dayOfWeek === 6) { // Saturday
            startHour = 7;
            endHour = 22;
        } else { // Mon-Fri
            startHour = 6;
            endHour = 23;
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
        
        validTimes.forEach(time => {
            const opt = document.createElement('option');
            opt.value = time;
            opt.textContent = time;
            if (time === selectedTime) {
                opt.selected = true;
            }
            timeSelect.appendChild(opt);
        });
    }
});
