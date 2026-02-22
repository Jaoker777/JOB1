<?php
require_once 'auth.php';
require_auth();

$user = current_user();
$isAdmin = is_admin();

$message = '';
$messageType = '';

// --- Handle New Sale ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_sale') {
    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if (empty($productIds) || empty($quantities)) {
        $message = '❌ กรุณาเลือกสินค้าอย่างน้อย 1 รายการ';
        $messageType = 'danger';
    } else {
        try {
            $pdo->beginTransaction();

            $totalAmount = 0;
            $validItems = [];

            for ($i = 0; $i < count($productIds); $i++) {
                $pid = (int) $productIds[$i];
                $qty = (int) $quantities[$i];

                if ($pid <= 0 || $qty <= 0) continue;

                $productStmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ? FOR UPDATE");
                $productStmt->execute([$pid]);
                $product = $productStmt->fetch();

                if (!$product) {
                    throw new Exception("ไม่พบสินค้า ID: $pid");
                }

                if ($product['stock_quantity'] < $qty) {
                    throw new Exception("สินค้า \"{$product['name']}\" มี stock เหลือแค่ {$product['stock_quantity']} ชิ้น");
                }

                $lineTotal = $product['price'] * $qty;
                $totalAmount += $lineTotal;
                $validItems[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'unit_price' => $product['price'],
                ];
            }

            if (empty($validItems)) {
                throw new Exception('ไม่มีรายการสินค้าที่ถูกต้อง');
            }

            // Insert sale with user_id
            $saleStmt = $pdo->prepare("INSERT INTO sales (user_id, total_amount) VALUES (?, ?)");
            $saleStmt->execute([$user['id'], $totalAmount]);
            $saleId = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

            foreach ($validItems as $item) {
                $itemStmt->execute([$saleId, $item['product_id'], $item['quantity'], $item['unit_price']]);
                $stockStmt->execute([$item['quantity'], $item['product_id']]);
            }

            $pdo->commit();
            header("Location: sales.php?msg=created&id=$saleId");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Success message
if (isset($_GET['msg']) && $_GET['msg'] === 'created') {
    $saleId = (int) ($_GET['id'] ?? 0);
    $message = "✅ บันทึกการขาย #$saleId เรียบร้อยแล้ว";
    $messageType = 'success';
}

// --- Fetch available products ---
$products = $pdo->query("
    SELECT p.id, p.name, p.price, p.stock_quantity, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.stock_quantity > 0
    ORDER BY p.name
")->fetchAll();

// --- Fetch sales history ---
$sales = $pdo->query("
    SELECT s.id, s.sale_date, s.total_amount,
           GROUP_CONCAT(CONCAT(p.name, ' x', si.quantity) SEPARATOR ', ') AS items_summary,
           SUM(si.quantity) AS total_items,
           COALESCE(u.username, '-') AS sold_by
    FROM sales s
    JOIN sale_items si ON s.id = si.sale_id
    JOIN products p ON si.product_id = p.id
    LEFT JOIN users u ON s.user_id = u.id
    GROUP BY s.id
    ORDER BY s.sale_date DESC
    LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales — Nournia Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Nournia Shop sales — create orders and view transaction history.">
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
                <span class="nav-icon">📊</span> Dashboard
            </a>
            <a href="products.php" class="nav-link">
                <span class="nav-icon">📦</span> Products
            </a>
            <a href="sales.php" class="nav-link active">
                <span class="nav-icon">💰</span> Sales
            </a>
            <div class="nav-label">บัญชี</div>
            <a href="profile.php" class="nav-link">
                <span class="nav-icon">👤</span> โปรไฟล์
            </a>
        </nav>
        <div class="sidebar-user">
            <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h2>💰 Sales</h2>
            <p>ขายสินค้าและดูประวัติการขาย</p>
        </div>

        <div class="page-body">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- New Sale Form -->
            <div class="table-container" style="margin-bottom:28px;">
                <div class="table-header">
                    <h3>🛒 สร้างรายการขายใหม่</h3>
                </div>
                <div style="padding:24px;">
                    <form method="POST" id="saleForm">
                        <input type="hidden" name="action" value="create_sale">

                        <div id="saleItems">
                            <div class="sale-item-row" data-index="0">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label">สินค้า *</label>
                                    <select name="product_id[]" class="form-control product-select" required onchange="updatePrice(this)">
                                        <option value="">เลือกสินค้า</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>" data-stock="<?= $p['stock_quantity'] ?>">
                                                <?= htmlspecialchars($p['name']) ?> — ฿<?= number_format($p['price'], 2) ?> (เหลือ <?= $p['stock_quantity'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label class="form-label">จำนวน *</label>
                                    <input type="number" name="quantity[]" class="form-control qty-input" min="1" value="1" required oninput="calculateTotal()">
                                </div>
                                <div style="padding-bottom:4px;">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)" style="margin-top:22px;">🗑️</button>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top:12px;display:flex;gap:12px;align-items:center;">
                            <button type="button" class="btn btn-ghost" onclick="addItem()">+ เพิ่มรายการ</button>
                        </div>

                        <div class="sale-summary">
                            <div class="total-row">
                                <span>ยอดรวมทั้งหมด</span>
                                <span id="grandTotal">฿0.00</span>
                            </div>
                        </div>

                        <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:12px;">
                            <button type="reset" class="btn btn-ghost" onclick="setTimeout(resetForm, 10)">ล้างข้อมูล</button>
                            <button type="submit" class="btn btn-primary">💳 บันทึกการขาย</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sales History -->
            <div class="table-container">
                <div class="table-header">
                    <h3>📋 ประวัติการขาย (<?= count($sales) ?>)</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>วันที่</th>
                            <th>ผู้ขาย</th>
                            <th>รายการสินค้า</th>
                            <th>จำนวนชิ้น</th>
                            <th>ยอดรวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)): ?>
                            <tr>
                                <td colspan="6" class="table-empty">
                                    ยังไม่มีประวัติการขาย — เริ่มขายสินค้าจากฟอร์มด้านบน
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales as $s): ?>
                                <tr>
                                    <td><span class="badge badge-info">#<?= $s['id'] ?></span></td>
                                    <td><?= date('d/m/Y H:i', strtotime($s['sale_date'])) ?></td>
                                    <td><?= htmlspecialchars($s['sold_by']) ?></td>
                                    <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($s['items_summary']) ?>">
                                        <?= htmlspecialchars($s['items_summary']) ?>
                                    </td>
                                    <td><?= $s['total_items'] ?> ชิ้น</td>
                                    <td class="price">฿<?= number_format($s['total_amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        const productsData = <?= json_encode($products) ?>;

        function addItem() {
            const container = document.getElementById('saleItems');
            const index = container.children.length;
            const optionsHtml = productsData.map(p =>
                `<option value="${p.id}" data-price="${p.price}" data-stock="${p.stock_quantity}">
                    ${p.name} — ฿${Number(p.price).toLocaleString('th-TH', {minimumFractionDigits: 2})} (เหลือ ${p.stock_quantity})
                </option>`
            ).join('');

            const row = document.createElement('div');
            row.className = 'sale-item-row';
            row.dataset.index = index;
            row.innerHTML = `
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">สินค้า *</label>
                    <select name="product_id[]" class="form-control product-select" required onchange="updatePrice(this)">
                        <option value="">เลือกสินค้า</option>
                        ${optionsHtml}
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">จำนวน *</label>
                    <input type="number" name="quantity[]" class="form-control qty-input" min="1" value="1" required oninput="calculateTotal()">
                </div>
                <div style="padding-bottom:4px;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)" style="margin-top:22px;">🗑️</button>
                </div>
            `;
            container.appendChild(row);
            row.style.animation = 'slideDown 0.3s ease';
        }

        function removeItem(btn) {
            const row = btn.closest('.sale-item-row');
            const container = document.getElementById('saleItems');
            if (container.children.length > 1) {
                row.remove();
                calculateTotal();
            }
        }

        function updatePrice(select) { calculateTotal(); }

        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.sale-item-row').forEach(row => {
                const select = row.querySelector('.product-select');
                const qtyInput = row.querySelector('.qty-input');
                const opt = select.options[select.selectedIndex];
                if (opt && opt.dataset.price) {
                    total += parseFloat(opt.dataset.price) * (parseInt(qtyInput.value) || 0);
                }
            });
            document.getElementById('grandTotal').textContent = '฿' + total.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function resetForm() {
            const container = document.getElementById('saleItems');
            while (container.children.length > 1) container.removeChild(container.lastChild);
            calculateTotal();
        }
    </script>
    <?php include 'cart_system.php'; ?>
    <?php include 'footer.php'; ?>
</body>
</html>
