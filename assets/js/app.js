console.log('LifeQuest iniciado correctamente.');

// Toggle Sidebar
(function initSidebarToggle() {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.lq-sidebar');
    const app = document.querySelector('.lifequest-app');
    
    if (!toggleBtn || !sidebar || !app) {
        return;
    }
    
    // Recuperar estado del localStorage
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    if (isCollapsed) {
        sidebar.classList.add('sidebar-collapsed');
        app.classList.add('sidebar-hidden');
        toggleBtn.setAttribute('aria-label', 'Abrir navegación');
    }
    
    // Toggle al hacer click
    toggleBtn.addEventListener('click', function() {
        const isCurrentlyCollapsed = sidebar.classList.toggle('sidebar-collapsed');
        app.classList.toggle('sidebar-hidden', isCurrentlyCollapsed);
        
        // Guardar estado en localStorage
        localStorage.setItem('sidebarCollapsed', isCurrentlyCollapsed);
        
        // Actualizar aria-label
        toggleBtn.setAttribute(
            'aria-label', 
            isCurrentlyCollapsed ? 'Abrir navegación' : 'Cerrar navegación'
        );
    });
})();
// Goals / metas / retos / misiones modal
(function initGoalsModal() {
    const modal = document.querySelector('[data-goal-form-modal]');
    const backdrop = document.querySelector('.metas-modal-backdrop');
    const openButton = document.querySelector('[data-goal-modal-open]');
    const closeButtons = document.querySelectorAll('[data-goal-modal-close]');

    if (!modal || !openButton) {
        return;
    }

    const openModal = () => {
        modal.classList.add('is-open');

        if (backdrop) {
            backdrop.classList.add('is-open');
        }

        document.body.classList.add('lq-modal-lock');

        const firstField = modal.querySelector('input:not([type="hidden"]), textarea, select');

        if (firstField) {
            setTimeout(() => firstField.focus(), 80);
        }
    };

    const closeModal = (event) => {
        if (event) {
            event.preventDefault();
        }

        const closeLink = modal.querySelector('.metas-modal-close');

        if (closeLink && closeLink.getAttribute('href')) {
            window.location.href = closeLink.getAttribute('href');
            return;
        }

        modal.classList.remove('is-open');

        if (backdrop) {
            backdrop.classList.remove('is-open');
        }

        document.body.classList.remove('lq-modal-lock');
    };

    openButton.addEventListener('click', openModal);

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal(event);
        }
    });

    if (modal.classList.contains('is-open')) {
        document.body.classList.add('lq-modal-lock');

        if (backdrop) {
            backdrop.classList.add('is-open');
        }
    }
})();

(function initAreaIconPicker() {
    const picker = document.querySelector('[data-area-icon-picker]');
    const colorInput = document.querySelector('.metas-form-modal input[name="color"]');

    if (!picker || !colorInput) {
        return;
    }

    const syncPickerColor = () => {
        picker.style.setProperty('--picker-color', colorInput.value || '#16C79A');
    };

    colorInput.addEventListener('input', syncPickerColor);
    colorInput.addEventListener('change', syncPickerColor);
    syncPickerColor();
})();

(function initHabitModal() {
    const modal = document.querySelector('[data-habit-modal]');
    const openButton = document.querySelector('[data-habit-modal-open]');
    const closeButtons = document.querySelectorAll('[data-habit-modal-close]');
    const editTriggers = document.querySelectorAll('[data-habit-edit-open]');

    if (!modal || !openButton) {
        return;
    }

    const title = modal.querySelector('[data-habit-modal-title]');
    const eyebrow = modal.querySelector('[data-habit-modal-eyebrow]');
    const subtitle = modal.querySelector('[data-habit-modal-sub]');
    const form = modal.querySelector('[data-habit-modal-form]');
    const actionInput = modal.querySelector('[data-habit-modal-action]');
    const habitIdInput = modal.querySelector('[data-habit-modal-id]');
    const submitButton = modal.querySelector('[data-habit-modal-submit]');
    const deleteButton = modal.querySelector('[data-habit-modal-delete]');
    const kindField = modal.querySelector('[data-habit-kind-field]');
    const kindOptions = modal.querySelectorAll('[data-habit-kind-option]');

    const fields = {
        name: modal.querySelector('[data-habit-field="name"]'),
        description: modal.querySelector('[data-habit-field="description"]'),
        frequency: modal.querySelector('[data-habit-field="frequency"]'),
        areaId: modal.querySelector('[data-habit-field="area_id"]'),
        goalId: modal.querySelector('[data-habit-field="goal_id"]'),
    };

    const setKindValue = (value) => {
        const targetValue = value === 'control' ? 'control' : 'positive';
        const radio = modal.querySelector(`input[name="kind"][value="${targetValue}"]`);

        if (radio instanceof HTMLInputElement) {
            radio.checked = true;
        }

        kindOptions.forEach((option) => {
            const input = option.querySelector('input[name="kind"]');
            option.classList.toggle('is-selected', input instanceof HTMLInputElement && input.checked);
        });
    };

    const resetToCreateMode = () => {
        if (title) {
            title.textContent = 'Crear nuevo hábito';
        }

        if (eyebrow) {
            eyebrow.textContent = 'Nuevo hábito';
        }

        if (subtitle) {
            subtitle.textContent = 'Añádelo a tu rutina y empieza a seguir su progreso desde hoy.';
        }

        if (actionInput) {
            actionInput.value = 'create';
        }

        if (habitIdInput) {
            habitIdInput.value = '';
        }

        if (submitButton) {
            submitButton.textContent = 'Crear hábito';
        }

        if (deleteButton) {
            deleteButton.hidden = true;
        }

        if (kindField) {
            kindField.hidden = false;
        }

        if (fields.name instanceof HTMLInputElement) fields.name.value = '';
        if (fields.description instanceof HTMLInputElement) fields.description.value = '';
        if (fields.frequency instanceof HTMLSelectElement) fields.frequency.value = 'daily';
        if (fields.areaId instanceof HTMLSelectElement) fields.areaId.value = '';
        if (fields.goalId instanceof HTMLSelectElement) fields.goalId.value = '';

        setKindValue('positive');
    };

    const openModal = () => {
        resetToCreateMode();
        modal.hidden = false;
        modal.classList.add('is-open');
        document.body.classList.add('lq-modal-lock');

        const firstField = modal.querySelector('input:not([type="hidden"]), textarea, select');

        if (firstField) {
            setTimeout(() => firstField.focus(), 80);
        }
    };

    const populateEditModal = (trigger) => {
        const habitId = trigger.getAttribute('data-habit-id') || '';
        const habitName = trigger.getAttribute('data-habit-name') || '';
        const habitDescription = trigger.getAttribute('data-habit-description') || '';
        const habitFrequency = trigger.getAttribute('data-habit-frequency') || 'daily';
        const habitAreaId = trigger.getAttribute('data-habit-area-id') || '';
        const habitGoalId = trigger.getAttribute('data-habit-goal-id') || '';
        const habitKind = trigger.getAttribute('data-habit-kind') || 'positive';

        if (title) {
            title.textContent = 'Editar hábito';
        }

        if (eyebrow) {
            eyebrow.textContent = 'Editar hábito';
        }

        if (subtitle) {
            subtitle.textContent = 'Actualiza la información de este hábito o elimínalo si ya no lo necesitas.';
        }

        if (actionInput) {
            actionInput.value = 'update';
        }

        if (habitIdInput) {
            habitIdInput.value = habitId;
        }

        if (submitButton) {
            submitButton.textContent = 'Guardar cambios';
        }

        if (deleteButton) {
            deleteButton.hidden = false;
        }

        if (kindField) {
            kindField.hidden = false;
        }

        if (fields.name instanceof HTMLInputElement) fields.name.value = habitName;
        if (fields.description instanceof HTMLInputElement) fields.description.value = habitDescription;
        if (fields.frequency instanceof HTMLSelectElement) fields.frequency.value = habitFrequency;
        if (fields.areaId instanceof HTMLSelectElement) fields.areaId.value = habitAreaId;
        if (fields.goalId instanceof HTMLSelectElement) fields.goalId.value = habitGoalId;

        setKindValue(habitKind);

        modal.hidden = false;
        modal.classList.add('is-open');
        document.body.classList.add('lq-modal-lock');

        const firstField = modal.querySelector('input:not([type="hidden"]), textarea, select');
        if (firstField) {
            setTimeout(() => firstField.focus(), 80);
        }
    };

    const closeModal = (event) => {
        if (event) {
            event.preventDefault();
        }

        modal.classList.remove('is-open');
        modal.hidden = true;
        document.body.classList.remove('lq-modal-lock');
    };

    openButton.addEventListener('click', openModal);

    editTriggers.forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            const target = event.target;
            if (target instanceof Element && target.closest('button, a, input, select, textarea, label, form, [data-habit-state-open]')) {
                return;
            }

            populateEditModal(trigger);
        });

        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                populateEditModal(trigger);
            }
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    if (deleteButton && form) {
        deleteButton.addEventListener('click', () => {
            const habitName = fields.name instanceof HTMLInputElement ? fields.name.value : 'este hábito';

            if (!window.confirm(`¿Eliminar ${habitName || 'este hábito'}? Esta acción no se puede deshacer.`)) {
                return;
            }

            if (actionInput) {
                actionInput.value = 'delete';
            }

            form.requestSubmit();
        });
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal(event);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal(event);
        }
    });

    if (modal.classList.contains('is-open')) {
        document.body.classList.add('lq-modal-lock');

        const firstField = modal.querySelector('input:not([type="hidden"]), textarea, select');

        if (firstField) {
            setTimeout(() => firstField.focus(), 80);
        }
    }
})();

(function initHabitStatePopover() {
    const popover = document.querySelector('[data-habit-state-popover]');

    if (!popover) {
        return;
    }

    const openButtons = document.querySelectorAll('[data-habit-state-open]');
    const closeButtons = popover.querySelectorAll('[data-habit-state-popover-close]');
    const title = popover.querySelector('[data-habit-state-popover-title]');
    const summary = popover.querySelector('[data-habit-state-popover-summary]');
    const form = popover.querySelector('[data-habit-state-form]');
    const habitIdInput = popover.querySelector('input[name="habit_id"]');
    const currentStatusInput = popover.querySelector('input[name="current_status"]');
    const statusInput = popover.querySelector('input[name="status"]');
    const optionButtons = popover.querySelectorAll('[data-habit-state-option]');

    if (!openButtons.length || !form || !habitIdInput || !currentStatusInput || !statusInput) {
        return;
    }

    const positionPopover = (trigger) => {
        const rect = trigger.getBoundingClientRect();
        const popoverRect = popover.getBoundingClientRect();
        const spacing = 10;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        let left = rect.left + (rect.width / 2) - (popoverRect.width / 2);
        left = Math.max(12, Math.min(left, viewportWidth - popoverRect.width - 12));

        let top = rect.bottom + spacing;
        if (top + popoverRect.height > viewportHeight - 12) {
            top = rect.top - popoverRect.height - spacing;
        }
        top = Math.max(12, top);

        popover.style.left = `${Math.round(left)}px`;
        popover.style.top = `${Math.round(top)}px`;
    };

    let activeTrigger = null;

    const closePopover = (event) => {
        if (event) {
            event.preventDefault();
        }

        popover.classList.remove('is-open');
        popover.hidden = true;
        if (activeTrigger instanceof HTMLElement) {
            activeTrigger.setAttribute('aria-expanded', 'false');
        }
        activeTrigger = null;
    };

    const submitSelectedStatus = (status) => {
        statusInput.value = status;
        if (form instanceof HTMLFormElement) {
            form.requestSubmit();
        }
    };

    const openPopover = (trigger) => {
        const habitName = trigger.getAttribute('data-habit-name') || 'Hábito en control';
        const currentStatus = trigger.getAttribute('data-habit-current-status') || 'empty';

        habitIdInput.value = trigger.getAttribute('data-habit-id') || '';
        currentStatusInput.value = currentStatus;

        if (title) {
            title.textContent = habitName;
        }

        if (summary) {
            summary.textContent = 'Elige un estado para hoy. El cambio se guarda al tocar una opción.';
        }

        optionButtons.forEach((button) => {
            button.classList.toggle('is-selected', button.getAttribute('data-status') === currentStatus);
        });

        statusInput.value = '';

        popover.hidden = false;
        popover.classList.add('is-open');
        activeTrigger = trigger;
        positionPopover(trigger);

        const selectedButton = popover.querySelector('.habit-state-option.is-selected') || optionButtons[0];
        if (selectedButton instanceof HTMLElement) {
            setTimeout(() => selectedButton.focus(), 60);
        }
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => openPopover(button));
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closePopover);
    });

    document.addEventListener('click', (event) => {
        if (!popover.classList.contains('is-open')) {
            return;
        }

        const target = event.target;
        if (!(target instanceof Node)) {
            return;
        }

        if (activeTrigger instanceof HTMLElement && activeTrigger.contains(target)) {
            return;
        }

        if (!popover.contains(target)) {
            closePopover(event);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && popover.classList.contains('is-open')) {
            closePopover(event);
        }
    });

    window.addEventListener('resize', () => {
        if (activeTrigger instanceof HTMLElement && popover.classList.contains('is-open')) {
            positionPopover(activeTrigger);
        }
    });

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            openButtons.forEach((item) => item.setAttribute('aria-expanded', 'false'));
            button.setAttribute('aria-expanded', 'true');
        });
    });

    optionButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const status = button.getAttribute('data-status') || 'empty';
            if (summary) {
                summary.textContent = status === 'completed'
                    ? 'Registrando día controlado...'
                    : status === 'partial'
                        ? 'Registrando recaída...'
                        : 'Quitando el registro de hoy...';
            }

            closePopover();
            submitSelectedStatus(status);
        });
    });
})();
