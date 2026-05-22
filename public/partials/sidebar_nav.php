<?php
$activeNav = $activeNav ?? '';
?>
<a href="dashboard.php" class="lq-logo">
    <span>Life<span>Quest</span><i>✦</i></span>
</a>

<nav class="lq-nav">
    <a href="dashboard.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">
        <span class="nav-icon" aria-hidden="true">
            <svg class="icon-outline" viewBox="0 0 24 24" focusable="false">
                <path d="M4.8 10.5 12 4.9l7.2 5.6"></path>
                <path d="M6.8 10.1v7.3c0 1 .8 1.8 1.8 1.8h2.7v-3.8a1 1 0 0 1 1-1h.2a1 1 0 0 1 1 1v3.8h2.7c1 0 1.8-.8 1.8-1.8v-7.3"></path>
            </svg>
            <svg class="icon-solid" viewBox="0 0 24 24" focusable="false">
                <path d="M11 4.8a1.6 1.6 0 0 1 2 0l7 5.3a1 1 0 0 1-1.2 1.6l-.3-.2v6a2 2 0 0 1-2 2H14a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1h0a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H7.5a2 2 0 0 1-2-2v-6l-.3.2A1 1 0 0 1 4 10.1z"></path>
            </svg>
        </span>
        Inicio
    </a>
    <a href="goals.php" class="<?= $activeNav === 'goals' ? 'active' : '' ?>">
        <span class="nav-icon" aria-hidden="true">
            <svg class="icon-outline" viewBox="0 0 24 24" focusable="false">
                <path d="M6.8 15.9 5.4 18.6l2.7-1.4 6.8-6.8-1.3-1.3-6.8 6.8z"></path>
                <path d="M13.6 9.1 15.2 7.5a2.2 2.2 0 0 1 3.1 0l.2.2a2.2 2.2 0 0 1 0 3.1l-1.6 1.6"></path>
                <circle cx="9" cy="15" r=".8"></circle>
            </svg>
            <svg class="icon-solid" viewBox="0 0 24 24" focusable="false">
                <path d="M15.6 6.9a2.5 2.5 0 0 1 3.5 0l.3.3a2.5 2.5 0 0 1 0 3.5l-1.5 1.5-3.8-3.8 1.5-1.5zM13.5 8.9l3.8 3.8-8.8 8.8-3.9.9.9-3.9 8-9.6z"></path>
            </svg>
        </span>
        Metas
    </a>
    <a href="areas.php" class="<?= $activeNav === 'areas' ? 'active' : '' ?>">
        <span class="nav-icon" aria-hidden="true">
            <svg class="icon-outline" viewBox="0 0 24 24" focusable="false">
                <circle cx="7" cy="7" r="2.1"></circle>
                <circle cx="17" cy="7" r="2.1"></circle>
                <circle cx="7" cy="17" r="2.1"></circle>
                <circle cx="17" cy="17" r="2.1"></circle>
                <path d="M9 7h6M7 9v6M17 9v6M9 17h6"></path>
            </svg>
            <svg class="icon-solid" viewBox="0 0 24 24" focusable="false">
                <path d="M7 4.5A2.5 2.5 0 1 1 7 9.5 2.5 2.5 0 0 1 7 4.5zM17 4.5A2.5 2.5 0 1 1 17 9.5 2.5 2.5 0 0 1 17 4.5zM7 14.5A2.5 2.5 0 1 1 7 19.5 2.5 2.5 0 0 1 7 14.5zM17 14.5A2.5 2.5 0 1 1 17 19.5 2.5 2.5 0 0 1 17 14.5z"></path>
            </svg>
        </span>
        Áreas
    </a>
    <a href="habits.php" class="<?= $activeNav === 'habits' ? 'active' : '' ?>">
        <span class="nav-icon" aria-hidden="true">
            <svg class="icon-outline" viewBox="0 0 24 24" focusable="false">
                <path d="M12 19.8s-6.7-4.1-8.6-7.6c-1.5-2.6-.2-5.8 2.5-7 2-.9 4.1-.3 5.3 1.3.4.5.6.5 1 0 1.2-1.6 3.3-2.2 5.3-1.3 2.7 1.2 4 4.4 2.5 7-1.9 3.5-8.6 7.6-8.6 7.6z"></path>
            </svg>
            <svg class="icon-solid" viewBox="0 0 24 24" focusable="false">
                <path d="M12 20.5s-6.9-4.1-9-7.9C1.3 9.4 2.7 5.4 6.1 4.1c2.2-.9 4.4-.2 5.9 1.7 1.5-1.9 3.7-2.6 5.9-1.7 3.4 1.3 4.8 5.3 3.1 8.5-2.1 3.8-9 7.9-9 7.9z"></path>
            </svg>
        </span>
        Hábitos
    </a>
    <a href="shop.php" class="<?= $activeNav === 'shop' ? 'active' : '' ?>">
        <span class="nav-icon" aria-hidden="true">
            <svg class="icon-outline" viewBox="0 0 24 24" focusable="false">
                <path d="M6.5 8h11l-1 10.6a1.5 1.5 0 0 1-1.5 1.4H9a1.5 1.5 0 0 1-1.5-1.4L6.5 8z"></path>
                <path d="M9 8V7a3 3 0 1 1 6 0v1"></path>
            </svg>
            <svg class="icon-solid" viewBox="0 0 24 24" focusable="false">
                <path d="M6 7h12a1 1 0 0 1 1 1l-1 11a2 2 0 0 1-2 1.8H8a2 2 0 0 1-2-1.8L5 8a1 1 0 0 1 1-1zm3-1a3 3 0 0 1 6 0v1h-2V6a1 1 0 1 0-2 0v1H9z"></path>
            </svg>
        </span>
        Tienda
    </a>
    <a href="progress.php" class="<?= $activeNav === 'progress' ? 'active' : '' ?>">
        <span class="nav-icon" aria-hidden="true">
            <svg class="icon-outline" viewBox="0 0 24 24" focusable="false">
                <rect x="4" y="12" width="3.2" height="8" rx="1"></rect>
                <rect x="10.4" y="9" width="3.2" height="11" rx="1"></rect>
                <rect x="16.8" y="5" width="3.2" height="15" rx="1"></rect>
            </svg>
            <svg class="icon-solid" viewBox="0 0 24 24" focusable="false">
                <path d="M5.2 11.5h1.6a1.2 1.2 0 0 1 1.2 1.2v6a1.2 1.2 0 0 1-1.2 1.2H5.2A1.2 1.2 0 0 1 4 18.7v-6a1.2 1.2 0 0 1 1.2-1.2zm6.4-3h1.6a1.2 1.2 0 0 1 1.2 1.2v9a1.2 1.2 0 0 1-1.2 1.2h-1.6a1.2 1.2 0 0 1-1.2-1.2v-9a1.2 1.2 0 0 1 1.2-1.2zm6.4-4h1.6a1.2 1.2 0 0 1 1.2 1.2v13a1.2 1.2 0 0 1-1.2 1.2H18a1.2 1.2 0 0 1-1.2-1.2v-13A1.2 1.2 0 0 1 18 4.5z"></path>
            </svg>
        </span>
        Progreso
    </a>
    <a href="profile.php" class="<?= $activeNav === 'profile' ? 'active' : '' ?>">
        <span class="nav-icon" aria-hidden="true">
            <svg class="icon-outline" viewBox="0 0 24 24" focusable="false">
                <circle cx="12" cy="8" r="3.2"></circle>
                <path d="M5.7 19.2c.9-2.8 3.4-4.4 6.3-4.4s5.4 1.6 6.3 4.4"></path>
            </svg>
            <svg class="icon-solid" viewBox="0 0 24 24" focusable="false">
                <path d="M12 4.2a3.8 3.8 0 1 1 0 7.6 3.8 3.8 0 0 1 0-7.6zM12 13.8c3.2 0 6 1.9 7 4.9.2.8-.4 1.6-1.3 1.6H6.3c-.9 0-1.5-.8-1.3-1.6 1-3 3.8-4.9 7-4.9z"></path>
            </svg>
        </span>
        Perfil
    </a>
</nav>
