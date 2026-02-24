<?php
require_once 'auth.php';
require_auth();

$user = current_user();
$isAdmin = is_admin();

// Fetch active coupons that haven't expired
$stmt = $pdo->query("
    SELECT * 
    FROM coupons 
    WHERE is_active = 1 
    AND expires_at >= CURDATE()
    ORDER BY discount_amount DESC
");
$coupons = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คูปองส่วนลด — Nournia Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="dashboard-grid">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">🎮</div>
            <div>
                <h1>Nournia Shop</h1>
                <div class="brand-sub">Gaming Gear Store</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Menu</div>
            <a href="index.php" class="nav-link">
                <span class="nav-icon">🏠</span> หน้าร้าน
            </a>
            <a href="coupons.php" class="nav-link active">
                <span class="nav-icon">🎟️</span> คูปองส่วนลด
            </a>
            <?php if ($isAdmin): ?>
            <div class="nav-label">Admin</div>
            <a href="products.php" class="nav-link">
                <span class="nav-icon">📦</span> จัดการสินค้า
            </a>
            <a href="categories.php" class="nav-link">
                <span class="nav-icon">🏷️</span> หมวดหมู่
            </a>
            <?php endif; ?>
            <a href="sales.php" class="nav-link">
                <span class="nav-icon">💰</span> Sales
            </a>
            <div class="nav-label">บัญชี</div>
            <a href="profile.php" class="nav-link">
                <span class="nav-icon">👤</span> โปรไฟล์
            </a>
            <a href="javascript:void(0)" class="nav-link" onclick="openCartModal()">
                <span class="nav-icon">🛒</span> ตะกร้าสินค้า <span class="cart-sidebar-badge" id="sidebarCartCount"></span>
            </a>
        </nav>
        <div class="sidebar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user['username']) ?></div>
                <div class="user-role"><?= $user['role'] === 'admin' ? '🛠 Admin' : '👤 User' ?></div>
            </div>
            <a href="logout.php" class="btn-logout" title="ออกจากระบบ">🚪</a>
        </div>
        <div class="sidebar-footer">
            Nournia Shop &copy; <?= date('Y') ?>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php include 'navbar.php'; ?>

        <div class="page-header">
            <h2>🎟️ คูปองส่วนลดพิเศษ</h2>
            <p>เลือกรับโค้ดส่วนลดเพื่อนำไปใช้ในการสั่งซื้อสินค้าของคุณ</p>
        </div>

        <div class="page-body">
            <?php if (empty($coupons)): ?>
                <div class="store-empty">
                    <div class="store-empty-icon">🎟️</div>
                    <h3>ยังไม่มีคูปองที่ใช้งานได้ในขณะนี้</h3>
                    <p>คอยติดตามโปรโมชั่นใหม่ๆ ได้เร็วๆ นี้!</p>
                </div>
            <?php else: ?>
                <div class="coupon-grid">
                    <?php foreach ($coupons as $c): ?>
                        <div class="coupon-card">
                            <div class="coupon-badge">🔥 Hot Deal</div>
                            <div class="coupon-details">
                                <div class="coupon-discount">฿<?= number_format($c['discount_amount']) ?> OFF</div>
                                <div class="coupon-meta">
                                    <span>ซื้อขั้นต่ำ: <b>฿<?= number_format($c['min_order_amount']) ?></b></span>
                                    <span>หมดอายุ: <b><?= date('d M Y', strtotime($c['expires_at'])) ?></b></span>
                                </div>
                            </div>
                            <div class="coupon-code-box">
                                <span class="coupon-code-text" id="code-<?= $c['id'] ?>"><?= htmlspecialchars($c['code']) ?></span>
                                <button class="btn-copy" onclick="copyCoupon('<?= htmlspecialchars($c['code']) ?>', this)">
                                    Copy
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include 'cart_system.php'; ?>

<script>
    function copyCoupon(code, btn) {
        navigator.clipboard.writeText(code).then(() => {
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            btn.classList.add('copied');
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('copied');
            }, 2000);
        });
    }
</script>

<?php include 'footer.php'; ?>
</body>
</html>
