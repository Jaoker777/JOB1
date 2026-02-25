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
    <?php include 'head_icons.php'; ?>
</head>
<body>
<div class="dashboard-grid">
    <?php $currentPage = 'home'; include 'sidebar.php'; ?>

    <!-- Main Content (Column 2: 1fr) -->
    <main class="main-content">
        <div class="page-header">
            <h2>สินค้าเกมมิ่งเกียร์</h2>
            <p>สวัสดี, <?= htmlspecialchars($user['username']) ?>! — เลือกสินค้าที่คุณต้องการ</p>
        </div>

        <div class="page-body">
            <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
                <div class="alert alert-danger">คุณไม่มีสิทธิ์เข้าถึงหน้าดังกล่าว</div>
            <?php endif; ?>

            <?php if (isset($_GET['cart']) && $_GET['cart'] === 'added'): ?>
                <div class="alert alert-success">เพิ่มสินค้าลงตะกร้าแล้ว!</div>
            <?php endif; ?>

            <!-- Search + Filter Bar -->
            <div class="store-toolbar">
                <form method="GET" class="store-search-form" id="storeFilterForm">
                    <div class="search-box">
                        <span class="search-icon"><i data-lucide="search"></i></span>
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
                            <div class="product-image" onclick="openLightbox('<?= htmlspecialchars($p['image_url'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>')">
                                <?php if ($p['image_url']): ?>
                                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" style="cursor: zoom-in;">
                                <?php else: ?>
                                    <div class="product-image-placeholder" style="cursor: default;">🎮</div>
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
                                            <i data-lucide="shopping-cart" style="width:14px;height:14px"></i> Add
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
</div>

<?php include 'cart_system.php'; ?>

<!-- Product Image Lightbox Modal -->
<div class="lightbox-overlay" id="productLightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
    <div class="lightbox-content" onclick="event.stopPropagation()">
        <img id="lightboxImg" src="" alt="Zoomed Product">
        <div id="lightboxCaption" style="margin-top: 12px; font-weight: 700; text-align: center; color: var(--text-primary);"></div>
    </div>
</div>

<script>
    // Initial UI sync is handled by cart_system.php
    
    // --- Lightbox Functions ---
    function openLightbox(src, name) {
        if (!src || src === '') return;
        const overlay = document.getElementById('productLightbox');
        const img = document.getElementById('lightboxImg');
        const caption = document.getElementById('lightboxCaption');
        if (!overlay || !img) return;
        img.src = src;
        caption.textContent = name;
        overlay.style.display = 'flex';
        setTimeout(() => overlay.classList.add('active'), 50);
        document.body.style.overflow = 'hidden'; 
    }

    function closeLightbox() {
        const overlay = document.getElementById('productLightbox');
        if (!overlay) return;
        overlay.classList.remove('active');
        setTimeout(() => {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }

    // --- Interaction Search ---
    let searchTimer;
    function debounceSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            document.getElementById('storeFilterForm').submit();
        }, 600);
    }

    // Initialize Lucide Icons
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
