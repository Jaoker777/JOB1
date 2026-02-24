<?php
require_once 'auth.php';
require_auth();

$user = current_user();
$isAdmin = is_admin();

// Only admin can manage categories
if (!$isAdmin) {
    header('Location: index.php?error=unauthorized');
    exit;
}

$message = '';
$messageType = '';

// --- Handle ADD Category ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '📦');

    if ($name) {
        $dup = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $dup->execute([$name]);
        if ($dup->fetch()) {
            $message = '❌ หมวดหมู่นี้มีอยู่แล้ว';
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
            $stmt->execute([$name, $icon]);
            header("Location: categories.php?msg=added");
            exit;
        }
    } else {
        $message = 'กรุณากรอกชื่อหมวดหมู่';
        $messageType = 'danger';
    }
}

// --- Handle EDIT Category ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_category') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '📦');

    if ($id > 0 && $name) {
        $dup = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $dup->execute([$name, $id]);
        if ($dup->fetch()) {
            $message = '❌ ชื่อหมวดหมู่นี้ถูกใช้แล้ว';
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?");
            $stmt->execute([$name, $icon, $id]);
            header("Location: categories.php?msg=updated");
            exit;
        }
    } else {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $messageType = 'danger';
    }
}

// --- Handle DELETE Category ---
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    // Check if category has products
    $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $check->execute([$id]);
    $count = $check->fetchColumn();

    if ($count > 0) {
        $message = "❌ ไม่สามารถลบได้ — มีสินค้า $count รายการในหมวดหมู่นี้";
        $messageType = 'danger';
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: categories.php?msg=deleted");
        exit;
    }
}

// --- Success messages ---
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':   $message = '✅ เพิ่มหมวดหมู่เรียบร้อยแล้ว';     $messageType = 'success'; break;
        case 'deleted': $message = '✅ ลบหมวดหมู่เรียบร้อยแล้ว';         $messageType = 'success'; break;
        case 'updated': $message = '✅ อัปเดตหมวดหมู่เรียบร้อยแล้ว';     $messageType = 'success'; break;
    }
}

// --- Fetch categories with product counts ---
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.id
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหมวดหมู่ — Nournia Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Admin — จัดการหมวดหมู่สินค้า Nournia Shop">
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
            <a href="coupons.php" class="nav-link">
                <span class="nav-icon">🎟️</span> คูปองส่วนลด
            </a>
            <?php if ($isAdmin): ?>
            <div class="nav-label">Admin</div>
            <a href="products.php" class="nav-link">
                <span class="nav-icon">📦</span> จัดการสินค้า
            </a>
            <a href="categories.php" class="nav-link active">
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h2>🏷️ จัดการหมวดหมู่</h2>
            <p>เพิ่ม แก้ไข ลบ หมวดหมู่สินค้า</p>
        </div>

        <div class="page-body">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <div class="table-header">
                    <h3>📋 หมวดหมู่ทั้งหมด (<?= count($categories) ?>)</h3>
                    <button class="btn btn-primary" onclick="openModal('addCatModal')">+ เพิ่มหมวดหมู่</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>ชื่อหมวดหมู่</th>
                            <th>จำนวนสินค้า</th>
                            <th>สร้างเมื่อ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="6" class="table-empty">ยังไม่มีหมวดหมู่</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?= $cat['id'] ?></td>
                                    <td style="font-size:24px;"><?= $cat['icon'] ?? '📦' ?></td>
                                    <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                                    <td>
                                        <span class="badge <?= $cat['product_count'] > 0 ? 'badge-success' : 'badge-info' ?>">
                                            <?= $cat['product_count'] ?> สินค้า
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($cat['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-ghost btn-sm"
                                                    onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['icon'] ?? '📦', ENT_QUOTES) ?>')">
                                                ✏️ แก้ไข
                                            </button>
                                            <?php if ($cat['product_count'] == 0): ?>
                                                <a href="categories.php?delete=<?= $cat['id'] ?>"
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('ยืนยันลบหมวดหมู่ \'<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>\'?')">
                                                    🗑️ ลบ
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-ghost btn-sm" disabled title="มีสินค้าอยู่ ไม่สามารถลบได้">
                                                    🔒 ลบไม่ได้
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add Category Modal -->
    <div class="modal-overlay" id="addCatModal">
        <div class="modal" style="max-width:480px;">
            <div class="modal-header">
                <h3>➕ เพิ่มหมวดหมู่ใหม่</h3>
                <button class="modal-close" onclick="closeModal('addCatModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Emoji Icon</label>
                        <input type="text" name="icon" class="form-control" value="📦" maxlength="5"
                               style="font-size:24px;text-align:center;width:80px;">
                        <div class="form-hint">เลือก emoji สำหรับหมวดหมู่</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ชื่อหมวดหมู่ *</label>
                        <input type="text" name="name" class="form-control" required placeholder="เช่น เมาส์, คีย์บอร์ด">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('addCatModal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">✅ เพิ่มหมวดหมู่</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal-overlay" id="editCatModal">
        <div class="modal" style="max-width:480px;">
            <div class="modal-header">
                <h3>✏️ แก้ไขหมวดหมู่</h3>
                <button class="modal-close" onclick="closeModal('editCatModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="id" id="editCatId">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Emoji Icon</label>
                        <input type="text" name="icon" id="editCatIcon" class="form-control" maxlength="5"
                               style="font-size:24px;text-align:center;width:80px;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ชื่อหมวดหมู่ *</label>
                        <input type="text" name="name" id="editCatName" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('editCatModal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">💾 บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        function editCategory(id, name, icon) {
            document.getElementById('editCatId').value = id;
            document.getElementById('editCatName').value = name;
            document.getElementById('editCatIcon').value = icon;
            openModal('editCatModal');
        }
        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) this.classList.remove('active');
            });
        });
    </script>
    </main>
</div>
    <?php include 'cart_system.php'; ?>
    <?php include 'footer.php'; ?>
</body>
</html>
