<?php
/**
 * Coupon API — Nournia Shop
 * Handles coupon validation and application
 */
require_once 'auth.php';
require_auth();

$user = current_user();

// --- Auto-create tables if not exist ---
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS coupons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(30) NOT NULL UNIQUE,
            discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            min_order_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            max_uses INT NOT NULL DEFAULT 1,
            used_count INT NOT NULL DEFAULT 0,
            expires_at DATE NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS coupon_usage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            coupon_id INT NOT NULL,
            user_id INT NOT NULL,
            sale_id INT,
            used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_coupon_user (coupon_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {
    // Tables might already exist - that's fine
}

// --- AJAX: Validate coupon code ---
if (isset($_POST['action']) && $_POST['action'] === 'validate_coupon') {
    header('Content-Type: application/json');

    $code = strtoupper(trim($_POST['code'] ?? ''));
    $orderTotal = floatval($_POST['order_total'] ?? 0);

    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกรหัสคูปอง']);
        exit;
    }

    // Find coupon
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบคูปองนี้หรือคูปองถูกปิดใช้งาน']);
        exit;
    }

    // Check expiry
    if (strtotime($coupon['expires_at']) < strtotime('today')) {
        echo json_encode(['success' => false, 'message' => 'คูปองนี้หมดอายุแล้ว (หมดอายุ ' . date('d/m/Y', strtotime($coupon['expires_at'])) . ')']);
        exit;
    }

    // Check max uses
    if ($coupon['used_count'] >= $coupon['max_uses']) {
        echo json_encode(['success' => false, 'message' => 'คูปองนี้ถูกใช้ครบจำนวนแล้ว']);
        exit;
    }

    // Check if user already used this coupon
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
    $stmt->execute([$coupon['id'], $user['id']]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'คุณเคยใช้คูปองนี้แล้ว (ใช้ได้ 1 ครั้ง/คน)']);
        exit;
    }

    // Check minimum order
    if ($orderTotal < $coupon['min_order_amount']) {
        echo json_encode([
            'success' => false,
            'message' => 'ยอดสั่งซื้อขั้นต่ำ ฿' . number_format($coupon['min_order_amount'], 2) . ' (ยอดปัจจุบัน ฿' . number_format($orderTotal, 2) . ')'
        ]);
        exit;
    }

    // Valid!
    $discountedTotal = max(0, $orderTotal - $coupon['discount_amount']);
    echo json_encode([
        'success' => true,
        'message' => '✅ ใช้คูปองสำเร็จ! ลด ฿' . number_format($coupon['discount_amount'], 2),
        'coupon_id' => $coupon['id'],
        'discount' => $coupon['discount_amount'],
        'new_total' => $discountedTotal,
        'code' => $coupon['code']
    ]);
    exit;
}

// --- Apply coupon to sale (called from checkout) ---
function applyCouponToSale($pdo, $couponId, $userId, $saleId) {
    if (!$couponId) return;

    try {
        // Record usage
        $stmt = $pdo->prepare("INSERT IGNORE INTO coupon_usage (coupon_id, user_id, sale_id) VALUES (?, ?, ?)");
        $stmt->execute([$couponId, $userId, $saleId]);

        // Increment used_count
        $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$couponId]);
    } catch (PDOException $e) {
        // Silently fail — sale already created
    }
}
