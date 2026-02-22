<header class="top-navbar">
    <a href="index.php" class="navbar-brand">
        <span class="brand-icon">🎮</span>
        <span>Nournia Shop</span>
    </a>
    
    <nav class="navbar-links">
        <a href="index.php" class="nav-link-top <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">🏠 Dashboard</a>
        <a href="profile.php" class="nav-link-top <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">👤 Profile</a>
        <a href="sales.php" class="nav-link-top <?= basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : '' ?>">💰 Sales</a>
        <?php if (isset($isAdmin) && $isAdmin): ?>
        <a href="products.php" class="nav-link-top <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">📦 Products</a>
        <?php endif; ?>
    </nav>
    
    <div class="navbar-actions">
        <button class="nav-btn-icon" onclick="openCartModal()" id="navCartBtn">
            🛒
            <span class="nav-badge" id="navCartCount">0</span>
        </button>
        <a href="logout.php" class="nav-btn-icon" title="Logout">🚪</a>
    </div>
</header>
