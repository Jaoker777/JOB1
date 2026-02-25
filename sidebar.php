<?php
// sidebar.php — Shared sidebar component
// Usage: set $currentPage before including, e.g. $currentPage = 'home';
$currentPage = $currentPage ?? '';
$user = $user ?? current_user();
$isAdmin = $isAdmin ?? ($user['role'] === 'admin');
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i data-lucide="gamepad-2"></i>
        </div>
        <div>
            <h1>Nournia Shop</h1>
            <div class="brand-sub">Gaming Gear Store</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Menu</div>
        <a href="index.php" class="nav-link <?= $currentPage === 'home' ? 'active' : '' ?>">
            <span class="nav-icon"><i data-lucide="home"></i></span> หน้าร้าน
        </a>
        <a href="coupons.php" class="nav-link <?= $currentPage === 'coupons' ? 'active' : '' ?>">
            <span class="nav-icon"><i data-lucide="ticket"></i></span> คูปองส่วนลด
        </a>

        <?php if ($isAdmin): ?>
        <div class="nav-label">Admin</div>
        <a href="products.php" class="nav-link <?= $currentPage === 'products' ? 'active' : '' ?>">
            <span class="nav-icon"><i data-lucide="package"></i></span> จัดการสินค้า
        </a>
        <a href="categories.php" class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>">
            <span class="nav-icon"><i data-lucide="tags"></i></span> หมวดหมู่
        </a>
        <?php endif; ?>

        <a href="sales.php" class="nav-link <?= $currentPage === 'sales' ? 'active' : '' ?>">
            <span class="nav-icon"><i data-lucide="receipt"></i></span> Sales
        </a>

        <div class="nav-label">บัญชี</div>
        <a href="profile.php" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
            <span class="nav-icon"><i data-lucide="user"></i></span> โปรไฟล์
        </a>
        <a href="#" class="nav-link" onclick="openCartModal(); return false;">
            <span class="nav-icon"><i data-lucide="shopping-cart"></i></span> ตะกร้าสินค้า
            <span class="cart-sidebar-badge" id="sidebarCartCount">0</span>
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(mb_substr($user['username'], 0, 1)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user['username']) ?></div>
            <div class="user-role"><?= $user['role'] === 'admin' ? 'Admin' : 'User' ?></div>
        </div>
        <a href="logout.php" class="btn-logout" title="ออกจากระบบ">
            <i data-lucide="log-out"></i>
        </a>
    </div>

    <div class="sidebar-footer">
        Nournia Shop &copy; <?= date('Y') ?>
    </div>
</aside>
