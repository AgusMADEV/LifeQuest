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
