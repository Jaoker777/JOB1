<?php
require_once 'db.php';

// --- Query stats ---
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 5")->fetchColumn();
$totalSales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales")->fetchColumn();

// --- Recent sales (last 5) ---
$recentSales = $pdo->query("
    SELECT s.id, s.sale_date, s.total_amount,
           COUNT(si.id) AS item_count
    FROM sales s
    LEFT JOIN sale_items si ON s.id = si.sale_id
    GROUP BY s.id
    ORDER BY s.sale_date DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Gaming Store</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Gaming Store Inventory System Dashboard — Track products, stock levels, and sales.">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">🎮</div>
            <div>
                <h1>Gaming Store</h1>
                <div class="brand-sub">Inventory System</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Menu</div>
            <a href="index.php" class="nav-link active">
                <span class="nav-icon">📊</span> Dashboard
            </a>
            <a href="products.php" class="nav-link">
                <span class="nav-icon">📦</span> Products
            </a>
            <a href="sales.php" class="nav-link">
                <span class="nav-icon">💰</span> Sales
            </a>
        </nav>
        <div class="sidebar-footer">
            Gaming Store &copy; <?= date('Y') ?>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h2>📊 Dashboard</h2>
            <p>ภาพรวมระบบจัดการสต็อกสินค้า</p>
        </div>

        <div class="page-body">
            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">📦</div>
                    <div class="stat-label">สินค้าทั้งหมด</div>
                    <div class="stat-value"><?= number_format($totalProducts) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">⚠️</div>
                    <div class="stat-label">สินค้า Stock ต่ำ (&lt;5)</div>
                    <div class="stat-value"><?= number_format($lowStock) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">💰</div>
                    <div class="stat-label">ยอดขายรวม</div>
                    <div class="stat-value">฿<?= number_format($totalSales, 2) ?></div>
                </div>
            </div>

            <!-- Recent Sales Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>🕐 ยอดขายล่าสุด</h3>
                    <a href="sales.php" class="btn btn-ghost btn-sm">ดูทั้งหมด →</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>รหัสการขาย</th>
                            <th>วันที่</th>
                            <th>จำนวนรายการ</th>
                            <th>ยอดรวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentSales)): ?>
                            <tr>
                                <td colspan="4" class="table-empty">
                                    ยังไม่มีรายการขาย — <a href="sales.php" style="color:var(--accent)">เริ่มขายสินค้า</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td><span class="badge badge-info">#<?= $sale['id'] ?></span></td>
                                    <td><?= date('d/m/Y H:i', strtotime($sale['sale_date'])) ?></td>
                                    <td><?= $sale['item_count'] ?> รายการ</td>
                                    <td class="price">฿<?= number_format($sale['total_amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
