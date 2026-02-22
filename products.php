<?php
require_once 'auth.php';
require_auth();

$user = current_user();
$isAdmin = is_admin();

$message = '';
$messageType = '';

// --- Handle DELETE (Admin only) ---
if (isset($_GET['delete']) && $isAdmin) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: products.php?msg=deleted");
    exit;
}

// --- Handle ADD (Admin only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add' && $isAdmin) {
    $name = trim($_POST['name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock_quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name && $category_id > 0 && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO products (name, category_id, price, stock_quantity, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category_id, $price, $stock, $description]);
        header("Location: products.php?msg=added");
        exit;
    } else {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน (ชื่อ, หมวดหมู่, ราคา)';
        $messageType = 'danger';
    }
}

// --- Handle EDIT (Admin only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit' && $isAdmin) {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $stock = (int) ($_POST['stock_quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($id > 0 && $name && $category_id > 0 && $price > 0) {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, stock_quantity = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $category_id, $price, $stock, $description, $id]);
        header("Location: products.php?msg=updated");
        exit;
    } else {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $messageType = 'danger';
    }
}

// --- Success messages ---
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':   $message = '✅ เพิ่มสินค้าเรียบร้อยแล้ว';   $messageType = 'success'; break;
        case 'deleted': $message = '✅ ลบสินค้าเรียบร้อยแล้ว';       $messageType = 'success'; break;
        case 'updated': $message = '✅ อัปเดตสินค้าเรียบร้อยแล้ว';   $messageType = 'success'; break;
    }
}

// --- Fetch categories ---
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// --- Fetch products ---
$products = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — Nournia Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Manage gaming gear products — add, edit, delete inventory items.">
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
            <a href="index.php" class="nav-link">
                <span class="nav-icon">🏠</span> หน้าร้าน
            </a>
            <?php if ($isAdmin): ?>
            <div class="nav-label">Admin</div>
            <a href="products.php" class="nav-link active">
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h2>📦 Products</h2>
            <p>จัดการสินค้าในระบบ<?= $isAdmin ? ' — เพิ่ม, แก้ไข, ลบ' : '' ?></p>
        </div>

        <div class="page-body">
            <!-- Alert Message -->
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Products Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>📋 รายการสินค้าทั้งหมด (<?= count($products) ?>)</h3>
                    <?php if ($isAdmin): ?>
                        <button class="btn btn-primary" onclick="openModal('addModal')">+ เพิ่มสินค้า</button>
                    <?php endif; ?>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อสินค้า</th>
                            <th>หมวดหมู่</th>
                            <th>ราคา</th>
                            <th>Stock</th>
                            <?php if ($isAdmin): ?><th>จัดการ</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="<?= $isAdmin ? 6 : 5 ?>" class="table-empty">
                                    ยังไม่มีสินค้า<?= $isAdmin ? ' — กดปุ่ม "เพิ่มสินค้า" เพื่อเริ่มต้น' : '' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><span class="badge badge-info">#<?= $p['id'] ?></span></td>
                                    <td style="color:var(--text-primary);font-weight:500;"><?= htmlspecialchars($p['name']) ?></td>
                                    <td><span class="badge badge-warning"><?= htmlspecialchars($p['category_name']) ?></span></td>
                                    <td class="price">฿<?= number_format($p['price'], 2) ?></td>
                                    <td>
                                        <?php if ($p['stock_quantity'] < 5): ?>
                                            <span class="stock-low"><?= $p['stock_quantity'] ?> ⚠️</span>
                                        <?php else: ?>
                                            <span class="badge badge-success"><?= $p['stock_quantity'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($isAdmin): ?>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-ghost btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($p)) ?>)">✏️ แก้ไข</button>
                                                <a href="products.php?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ต้องการลบสินค้านี้หรือไม่?')">🗑️ ลบ</a>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php if ($isAdmin): ?>
    <!-- Add Product Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <div class="modal-header">
                <h3>➕ เพิ่มสินค้าใหม่</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">ชื่อสินค้า *</label>
                        <input type="text" name="name" class="form-control" placeholder="เช่น NVIDIA RTX 4090" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">หมวดหมู่ *</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">เลือกหมวดหมู่</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ราคา (฿) *</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">จำนวน Stock</label>
                        <input type="number" name="stock_quantity" class="form-control" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">รายละเอียด</label>
                        <textarea name="description" class="form-control" placeholder="รายละเอียดสินค้า (ไม่จำเป็น)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">💾 บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3>✏️ แก้ไขสินค้า</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">ชื่อสินค้า *</label>
                        <input type="text" name="name" id="edit-name" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">หมวดหมู่ *</label>
                            <select name="category_id" id="edit-category" class="form-control" required>
                                <option value="">เลือกหมวดหมู่</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ราคา (฿) *</label>
                            <input type="number" name="price" id="edit-price" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">จำนวน Stock</label>
                        <input type="number" name="stock_quantity" id="edit-stock" class="form-control" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">รายละเอียด</label>
                        <textarea name="description" id="edit-description" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">💾 อัปเดต</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function openEditModal(product) {
            document.getElementById('edit-id').value = product.id;
            document.getElementById('edit-name').value = product.name;
            document.getElementById('edit-category').value = product.category_id;
            document.getElementById('edit-price').value = product.price;
            document.getElementById('edit-stock').value = product.stock_quantity;
            document.getElementById('edit-description').value = product.description || '';
            openModal('editModal');
        }

        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) this.classList.remove('active');
            });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
            }
        });
    </script>
    <?php include 'cart_system.php'; ?>
    <?php include 'footer.php'; ?>
</body>
</html>
