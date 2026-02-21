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
            <a href="index.php" class="nav-link active">
                <span class="nav-icon">🏠</span> หน้าร้าน
            </a>
            <?php if ($isAdmin): ?>
            <div class="nav-label">Admin</div>
            <a href="products.php" class="nav-link">
                <span class="nav-icon">📦</span> จัดการสินค้า
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

    <!-- Main Content -->
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
                            <a href="index.php<?= $selectedCategory ? '?category=' . $selectedCategory : '' ?>" class="search-clear">&times;</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($selectedCategory > 0): ?>
                        <input type="hidden" name="category" value="<?= $selectedCategory ?>">
                    <?php endif; ?>
                </form>

                <div class="category-chips">
                    <a href="index.php<?= $searchQuery ? '?search=' . urlencode($searchQuery) : '' ?>"
                       class="chip <?= $selectedCategory === 0 ? 'chip-active' : '' ?>">
                        ทั้งหมด
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?category=<?= $cat['id'] ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>"
                           class="chip <?= $selectedCategory === $cat['id'] ? 'chip-active' : '' ?>">
                            <?= $cat['icon'] ?? '📦' ?> <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Results info -->
            <div class="store-results-info">
                <span>แสดง <?= count($products) ?> รายการ<?= $searchQuery ? ' สำหรับ "' . htmlspecialchars($searchQuery) . '"' : '' ?></span>
            </div>

            <!-- Product Grid -->
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
                                            🛒 Add to Cart
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

    <!-- Cart Floating Button -->
    <div class="cart-floating" id="cartFloating" onclick="openCartModal()" style="display:none;">
        <span class="cart-floating-icon">🛒</span>
        <span class="cart-floating-count" id="cartCount">0</span>
    </div>

    <!-- Cart Modal -->
    <div class="modal-overlay" id="cartModal">
        <div class="modal" style="max-width:600px;">
            <div class="modal-header">
                <h3>🛒 ตะกร้าสินค้า</h3>
                <button class="modal-close" onclick="closeModal('cartModal')">&times;</button>
            </div>
            <div class="modal-body" id="cartBody">
                <!-- Cart items injected by JS -->
            </div>
            <div class="modal-footer" style="flex-direction:column;gap:12px;">
                <div class="sale-summary" style="width:100%;margin:0;">
                    <div class="total-row">
                        <span>ยอดรวม</span>
                        <span id="cartTotal">฿0.00</span>
                    </div>
                </div>
                <div style="display:flex;gap:12px;width:100%;justify-content:flex-end;">
                    <button class="btn btn-ghost" onclick="clearCart()">🗑️ ล้างตะกร้า</button>
                    <button class="btn btn-primary" onclick="checkout()">💳 ชำระเงิน</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- Cart System (localStorage) ---
        let cart = JSON.parse(localStorage.getItem('nournia_cart') || '[]');
        updateCartUI();

        function addToCart(id, name, price, image) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                existing.qty += 1;
            } else {
                cart.push({ id, name, price, image, qty: 1 });
            }
            saveCart();
            updateCartUI();

            // Animate button
            const btn = document.getElementById('cartBtn-' + id);
            if (btn) {
                btn.textContent = '✅ เพิ่มแล้ว!';
                btn.style.background = 'var(--success)';
                setTimeout(() => {
                    btn.innerHTML = '🛒 Add to Cart';
                    btn.style.background = '';
                }, 1200);
            }
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            saveCart();
            updateCartUI();
            renderCartModal();
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
            updateCartUI();
            renderCartModal();
        }

        function clearCart() {
            cart = [];
            saveCart();
            updateCartUI();
            renderCartModal();
        }

        function saveCart() {
            localStorage.setItem('nournia_cart', JSON.stringify(cart));
        }

        function updateCartUI() {
            const totalItems = cart.reduce((sum, i) => sum + i.qty, 0);
            const floatBtn = document.getElementById('cartFloating');
            const countBadge = document.getElementById('cartCount');

            if (totalItems > 0) {
                floatBtn.style.display = 'flex';
                countBadge.textContent = totalItems;
            } else {
                floatBtn.style.display = 'none';
            }
        }

        function openCartModal() {
            renderCartModal();
            document.getElementById('cartModal').classList.add('active');
        }

        function renderCartModal() {
            const body = document.getElementById('cartBody');
            if (cart.length === 0) {
                body.innerHTML = '<div class="table-empty" style="padding:32px;">ตะกร้าว่าง — เลือกสินค้าที่ต้องการ</div>';
                document.getElementById('cartTotal').textContent = '฿0.00';
                return;
            }

            let total = 0;
            let html = '';
            cart.forEach(item => {
                const lineTotal = item.price * item.qty;
                total += lineTotal;
                html += `
                    <div class="cart-item">
                        <div class="cart-item-image">
                            ${item.image ? `<img src="${item.image}" alt="${item.name}">` : '<div class="product-image-placeholder" style="width:56px;height:56px;font-size:20px;">🎮</div>'}
                        </div>
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">฿${item.price.toLocaleString('th-TH', {minimumFractionDigits:2})}</div>
                        </div>
                        <div class="cart-item-qty">
                            <button class="qty-btn" onclick="updateQty(${item.id}, -1)">−</button>
                            <span class="qty-value">${item.qty}</span>
                            <button class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
                        </div>
                        <div class="cart-item-total">
                            ฿${lineTotal.toLocaleString('th-TH', {minimumFractionDigits:2})}
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="removeFromCart(${item.id})" style="padding:4px 8px;">✕</button>
                    </div>
                `;
            });
            body.innerHTML = html;
            document.getElementById('cartTotal').textContent = '฿' + total.toLocaleString('th-TH', {minimumFractionDigits:2});
        }

        function checkout() {
            if (cart.length === 0) return;
            // Build a form and submit to sales.php
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

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) this.classList.remove('active');
            });
        });

        // Search debounce
        let searchTimer;
        function debounceSearch() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                document.getElementById('storeFilterForm').submit();
            }, 600);
        }
    </script>
</body>
</html>
