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
