<?php
require_once 'auth.php';
require_auth();

$user = current_user();
$isAdmin = is_admin();

// --- Fetch categories ---
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// --- Fetch products with filters ---
$where = ['1=1'];
$params = [];

// Category filter
$selectedCategory = (int) ($_GET['category'] ?? 0);
if ($selectedCategory > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $selectedCategory;
}

// Search filter
$searchQuery = trim($_GET['search'] ?? '');
if ($searchQuery !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$whereStr = implode(' AND ', $where);
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE $whereStr
    ORDER BY p.id DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// --- Quick stats for admin ---
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalSales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nournia Shop — Gaming Gear Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Nournia Shop — ร้านขายอุปกรณ์เกมมิ่งเกียร์ครบวงจร">
</head>
<body>
<div class="dashboard-grid">
    <!-- Sidebar (Column 1: 250px) -->
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
            <a href="index.php" class="nav-link active">
                <span class="nav-icon">🏠</span> หน้าร้าน
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

    <!-- Main Content (Column 2: 1fr) -->
    <main class="main-content">
        <div class="page-header">
            <h2>🏠 สินค้าเกมมิ่งเกียร์</h2>
            <p>สวัสดี, <?= htmlspecialchars($user['username']) ?>! — เลือกสินค้าที่คุณต้องการ</p>
        </div>

        <div class="page-body">
            <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
                <div class="alert alert-danger">⛔ คุณไม่มีสิทธิ์เข้าถึงหน้าดังกล่าว</div>
            <?php endif; ?>

            <?php if (isset($_GET['cart']) && $_GET['cart'] === 'added'): ?>
                <div class="alert alert-success">🛒 เพิ่มสินค้าลงตะกร้าแล้ว!</div>
            <?php endif; ?>

            <!-- Search + Filter Bar -->
            <div class="store-toolbar">
                <form method="GET" class="store-search-form" id="storeFilterForm">
                    <div class="search-box">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="search" class="search-input"
                               placeholder="ค้นหาสินค้า..."
                               value="<?= htmlspecialchars($searchQuery) ?>"
                               oninput="debounceSearch()">
                        <?php if ($searchQuery): ?>
                            <a href="index.php" class="search-clear" title="ล้างการค้นหา">✕</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Category Filter Chips -->
            <div class="category-chips">
                <a href="index.php" class="chip <?= $selectedCategory === 0 ? 'active' : '' ?>">🏷️ ทั้งหมด</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="index.php?category=<?= $cat['id'] ?>"
                       class="chip <?= $selectedCategory === (int)$cat['id'] ? 'active' : '' ?>">
                        <?= $cat['icon'] ?? '📦' ?> <?= htmlspecialchars($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Products Grid -->
            <?php if (empty($products)): ?>
                <div class="store-empty">
                    <div class="store-empty-icon">📦</div>
                    <h3>ไม่พบสินค้า</h3>
                    <p>ลองเปลี่ยนเงื่อนไขการค้นหาหรือหมวดหมู่</p>
                    <a href="index.php" class="btn btn-ghost" style="margin-top:12px;">ดูสินค้าทั้งหมด</a>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $p): ?>
                        <div class="product-card" id="product-<?= $p['id'] ?>">
                            <div class="product-image">
                                <?php if ($p['image_url']): ?>
                                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="product-image-placeholder">🎮</div>
                                <?php endif; ?>
                                <span class="product-category-tag"><?= htmlspecialchars($p['category_name']) ?></span>
                                <?php if ($p['stock_quantity'] < 5 && $p['stock_quantity'] > 0): ?>
                                    <span class="product-stock-badge low">เหลือ <?= $p['stock_quantity'] ?> ชิ้น</span>
                                <?php elseif ($p['stock_quantity'] <= 0): ?>
                                    <span class="product-stock-badge out">สินค้าหมด</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
                                <p class="product-desc"><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 80, '...')) ?></p>
                                <div class="product-footer">
                                    <div class="product-price">฿<?= number_format($p['price'], 2) ?></div>
                                    <?php if ($p['stock_quantity'] > 0): ?>
                                        <button class="btn btn-primary btn-sm btn-add-cart"
                                                onclick="addToCart(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>', <?= $p['price'] ?>, '<?= htmlspecialchars($p['image_url'] ?? '', ENT_QUOTES) ?>')"
                                                id="cartBtn-<?= $p['id'] ?>">
                                            🛒 Add
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-ghost btn-sm" disabled>สินค้าหมด</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Cart Panel (Column 3: 320px) -->
    <aside class="cart-panel">
        <div class="cart-panel-header">
            <h3>🛒 ตะกร้าสินค้า</h3>
            <span class="cart-count" id="panelCartCount">0</span>
        </div>
        <div class="cart-panel-body" id="panelCartBody">
            <div class="cart-panel-empty">
                <div class="empty-icon">🛒</div>
                <div>ตะกร้าของคุณยังว่างอยู่</div>
                <div style="font-size:12px;">เลือกสินค้าเพื่อเพิ่มลงตะกร้า</div>
            </div>
        </div>
        <div class="cart-panel-footer">
            <div class="cart-panel-total">
                <span class="total-label">ยอดรวม</span>
                <span class="total-amount" id="panelCartTotal">฿0.00</span>
            </div>
            <div class="cart-panel-actions">
                <button class="btn btn-ghost" onclick="clearCart()">🗑️ ล้าง</button>
                <button class="btn btn-primary" onclick="checkout()">💳 ชำระเงิน</button>
            </div>
        </div>
    </aside>
</div>

<script>
    // --- Cart System (localStorage) ---
    let cart = JSON.parse(localStorage.getItem('nournia_cart') || '[]');
    renderCartPanel();

    function addToCart(id, name, price, image) {
        const existing = cart.find(item => item.id === id);
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({ id, name, price, image, qty: 1 });
        }
        saveCart();
        renderCartPanel();

        const btn = document.getElementById('cartBtn-' + id);
        if (btn) {
            btn.textContent = '✅ เพิ่มแล้ว!';
            btn.style.background = 'var(--success)';
            setTimeout(() => {
                btn.innerHTML = '🛒 Add';
                btn.style.background = '';
            }, 1200);
        }
    }

    function removeFromCart(id) {
        cart = cart.filter(item => item.id !== id);
        saveCart();
        renderCartPanel();
    }

    function updateQty(id, delta) {
        const item = cart.find(i => i.id === id);
        if (item) {
            item.qty += delta;
            if (item.qty <= 0) {
                removeFromCart(id);
                return;
            }
        }
        saveCart();
        renderCartPanel();
    }

    function clearCart() {
        if (cart.length === 0) return;
        if (!confirm('ยืนยันล้างตะกร้าสินค้าทั้งหมด?')) return;
        cart = [];
        saveCart();
        renderCartPanel();
    }

    function saveCart() {
        localStorage.setItem('nournia_cart', JSON.stringify(cart));
    }

    function renderCartPanel() {
        const body = document.getElementById('panelCartBody');
        const countBadge = document.getElementById('panelCartCount');
        const totalEl = document.getElementById('panelCartTotal');
        const totalItems = cart.reduce((sum, i) => sum + i.qty, 0);

        countBadge.textContent = totalItems;

        if (cart.length === 0) {
            body.innerHTML = `
                <div class="cart-panel-empty">
                    <div class="empty-icon">🛒</div>
                    <div>ตะกร้าของคุณยังว่างอยู่</div>
                    <div style="font-size:12px;">เลือกสินค้าเพื่อเพิ่มลงตะกร้า</div>
                </div>`;
            totalEl.textContent = '฿0.00';
            return;
        }

        let total = 0;
        let html = '';
        cart.forEach(item => {
            const lineTotal = item.price * item.qty;
            total += lineTotal;
            html += `
                <div class="cart-panel-item">
                    <div class="cart-panel-item-img">
                        ${item.image ? `<img src="${item.image}" alt="${item.name}">` : '<span style="font-size:18px;">🎮</span>'}
                    </div>
                    <div class="cart-panel-item-details">
                        <div class="cart-panel-item-name">${item.name}</div>
                        <div class="cart-panel-item-price">฿${item.price.toLocaleString()}</div>
                    </div>
                    <div class="cart-panel-item-qty">
                        <button onclick="updateQty(${item.id}, -1)">−</button>
                        <span>${item.qty}</span>
                        <button onclick="updateQty(${item.id}, 1)">+</button>
                    </div>
                    <button class="cart-panel-item-remove" onclick="removeFromCart(${item.id})">✕</button>
                </div>`;
        });
        body.innerHTML = html;
        totalEl.textContent = '฿' + total.toLocaleString('th-TH', {minimumFractionDigits:2});
    }

    function checkout() {
        if (cart.length === 0) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'sales.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'create_sale';
        form.appendChild(actionInput);

        cart.forEach(item => {
            const pid = document.createElement('input');
            pid.type = 'hidden';
            pid.name = 'product_id[]';
            pid.value = item.id;
            form.appendChild(pid);

            const qty = document.createElement('input');
            qty.type = 'hidden';
            qty.name = 'quantity[]';
            qty.value = item.qty;
            form.appendChild(qty);
        });

        document.body.appendChild(form);
        localStorage.removeItem('nournia_cart');
        form.submit();
    }

    // Search debounce
    let searchTimer;
    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            document.getElementById('storeFilterForm').submit();
        }, 600);
    }
</script>
<?php include 'footer.php'; ?>
</body>
</html>
