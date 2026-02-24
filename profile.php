<?php
require_once 'auth.php';
require_auth();

$user = current_user();
$isAdmin = is_admin();

$message = '';
$messageType = '';

// --- Fetch full user data from DB ---
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

// --- Handle Profile Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newPhone = trim($_POST['phone'] ?? '');
    $newAddress = trim($_POST['address'] ?? '');
    $errors = [];

    // Validate username
    if (empty($newUsername)) {
        $errors[] = 'กรุณากรอกชื่อผู้ใช้';
    } elseif (strlen($newUsername) < 3) {
        $errors[] = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';
    }

    // Validate email
    if (empty($newEmail)) {
        $errors[] = 'กรุณากรอกอีเมล';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    // Validate phone (optional, but if filled must be valid)
    if ($newPhone !== '' && !preg_match('/^[0-9\-\+\s]{8,15}$/', $newPhone)) {
        $errors[] = 'รูปแบบเบอร์โทรไม่ถูกต้อง';
    }

    // Check duplicate email (if changed)
    if (empty($errors) && $newEmail !== $profile['email']) {
        $dup = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $dup->execute([$newEmail, $user['id']]);
        if ($dup->fetch()) {
            $errors[] = 'อีเมลนี้ถูกใช้งานแล้ว';
        }
    }

    // Check duplicate username (if changed)
    if (empty($errors) && $newUsername !== $profile['username']) {
        $dup = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $dup->execute([$newUsername, $user['id']]);
        if ($dup->fetch()) {
            $errors[] = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว';
        }
    }

    if (!empty($errors)) {
        $message = implode('<br>', array_map('htmlspecialchars', $errors));
        $messageType = 'danger';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$newUsername, $newEmail, $newPhone, $newAddress, $user['id']]);

        // Update session
        $_SESSION['user_name'] = $newUsername;
        $_SESSION['user_email'] = $newEmail;
        $user = current_user();

        // Refresh profile data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch();

        $message = '✅ อัปเดตโปรไฟล์เรียบร้อยแล้ว';
        $messageType = 'success';
    }
}

// --- Handle Password Change ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $errors = [];

    if (empty($currentPassword)) {
        $errors[] = 'กรุณากรอกรหัสผ่านปัจจุบัน';
    } elseif (!password_verify($currentPassword, $profile['password_hash'])) {
        $errors[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
    }

    if (empty($newPassword)) {
        $errors[] = 'กรุณากรอกรหัสผ่านใหม่';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'รหัสผ่านใหม่ไม่ตรงกัน';
    }

    if (!empty($errors)) {
        $message = implode('<br>', array_map('htmlspecialchars', $errors));
        $messageType = 'danger';
    } else {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);

        $message = '🔒 เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ — Nournia Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="จัดการโปรไฟล์ผู้ใช้ — Nournia Shop">
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
            <a href="categories.php" class="nav-link">
                <span class="nav-icon">🏷️</span> หมวดหมู่
            </a>
            <?php endif; ?>
            <a href="sales.php" class="nav-link">
                <span class="nav-icon">💰</span> Sales
            </a>
            <div class="nav-label">บัญชี</div>
            <a href="profile.php" class="nav-link active">
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
            <h2>👤 โปรไฟล์ของฉัน</h2>
            <p>ดูและแก้ไขข้อมูลส่วนตัว</p>
        </div>

            <div class="page-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <div class="profile-grid">
                    <!-- Profile Card -->
                    <div class="profile-card">
                        <div class="profile-avatar-section">
                            <div class="profile-avatar-lg">
                                <?= strtoupper(substr($profile['username'], 0, 1)) ?>
                            </div>
                            <div class="profile-user-info">
                                <h3><?= htmlspecialchars($profile['username']) ?></h3>
                                <span class="badge <?= $profile['role'] === 'admin' ? 'badge-info' : 'badge-success' ?>">
                                    <?= $profile['role'] === 'admin' ? '🛠 Admin' : '👤 User' ?>
                                </span>
                            </div>
                            <div class="profile-meta">
                                <div class="profile-meta-item">
                                    <span class="profile-meta-label">สมาชิกตั้งแต่</span>
                                    <span class="profile-meta-value"><?= date('d/m/Y', strtotime($profile['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Profile Form -->
                    <div class="profile-form-section">
                        <div class="table-container">
                            <div class="table-header">
                                <h3>📝 แก้ไขข้อมูลส่วนตัว</h3>
                            </div>
                            <div style="padding:24px;">
                                <form method="POST" id="profileForm" novalidate>
                                    <input type="hidden" name="action" value="update_profile">

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">ชื่อผู้ใช้ *</label>
                                            <input type="text" name="username" id="profUsername" class="form-control"
                                                   value="<?= htmlspecialchars($profile['username']) ?>"
                                                   required minlength="3" maxlength="50">
                                            <div class="form-hint" id="usernameHint" style="color:var(--danger);display:none;"></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">อีเมล *</label>
                                            <input type="email" name="email" id="profEmail" class="form-control"
                                                   value="<?= htmlspecialchars($profile['email']) ?>"
                                                   required>
                                            <div class="form-hint" id="emailHint" style="color:var(--danger);display:none;"></div>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">เบอร์โทรศัพท์</label>
                                            <input type="tel" name="phone" id="profPhone" class="form-control"
                                                   value="<?= htmlspecialchars($profile['phone'] ?? '') ?>"
                                                   placeholder="08x-xxx-xxxx">
                                            <div class="form-hint" id="phoneHint" style="color:var(--danger);display:none;"></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">บทบาท</label>
                                            <input type="text" class="form-control" value="<?= $profile['role'] === 'admin' ? 'Admin' : 'User' ?>" disabled>
                                            <div class="form-hint">ไม่สามารถเปลี่ยนบทบาทได้</div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">ที่อยู่</label>
                                        <textarea name="address" class="form-control" rows="3"
                                                  placeholder="บ้านเลขที่ ซอย ถนน แขวง เขต จังหวัด รหัสไปรษณีย์"><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>
                                    </div>

                                    <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:8px;">
                                        <button type="submit" class="btn btn-primary">💾 บันทึกข้อมูล</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="table-container" style="margin-top:24px;">
                            <div class="table-header">
                                <h3>🔒 เปลี่ยนรหัสผ่าน</h3>
                            </div>
                            <div style="padding:24px;">
                                <form method="POST" id="passwordForm" novalidate>
                                    <input type="hidden" name="action" value="change_password">

                                    <div class="form-group">
                                        <label class="form-label">รหัสผ่านปัจจุบัน *</label>
                                        <input type="password" name="current_password" id="curPassword" class="form-control"
                                               placeholder="••••••••" required>
                                        <div class="form-hint" id="curPwdHint" style="color:var(--danger);display:none;"></div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">รหัสผ่านใหม่ *</label>
                                            <input type="password" name="new_password" id="newPassword" class="form-control"
                                                   placeholder="อย่างน้อย 8 ตัวอักษร" required minlength="8">
                                            <div class="form-hint" id="newPwdHint" style="color:var(--danger);display:none;"></div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">ยืนยันรหัสผ่านใหม่ *</label>
                                            <input type="password" name="confirm_password" id="confirmPwd" class="form-control"
                                                   placeholder="กรอกรหัสผ่านใหม่อีกครั้ง" required>
                                            <div class="form-hint" id="confirmPwdHint" style="color:var(--danger);display:none;"></div>
                                        </div>
                                    </div>

                                    <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:8px;">
                                        <button type="submit" class="btn btn-primary">🔑 เปลี่ยนรหัสผ่าน</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // --- Profile Form Validation ---
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            let valid = true;

            const username = document.getElementById('profUsername');
            const email = document.getElementById('profEmail');
            const phone = document.getElementById('profPhone');

            // Reset
            ['usernameHint', 'emailHint', 'phoneHint'].forEach(id => {
                document.getElementById(id).style.display = 'none';
                document.getElementById(id).previousElementSibling?.classList.remove('is-invalid');
            });
            username.classList.remove('is-invalid');
            email.classList.remove('is-invalid');
            phone.classList.remove('is-invalid');

            if (!username.value.trim() || username.value.trim().length < 3) {
                document.getElementById('usernameHint').textContent = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';
                document.getElementById('usernameHint').style.display = 'block';
                username.classList.add('is-invalid');
                valid = false;
            }

            if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                document.getElementById('emailHint').textContent = 'กรุณากรอกอีเมลที่ถูกต้อง';
                document.getElementById('emailHint').style.display = 'block';
                email.classList.add('is-invalid');
                valid = false;
            }

            if (phone.value.trim() && !/^[0-9\-\+\s]{8,15}$/.test(phone.value.trim())) {
                document.getElementById('phoneHint').textContent = 'รูปแบบเบอร์โทรไม่ถูกต้อง';
                document.getElementById('phoneHint').style.display = 'block';
                phone.classList.add('is-invalid');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });

        // --- Password Form Validation ---
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            let valid = true;

            const cur = document.getElementById('curPassword');
            const newP = document.getElementById('newPassword');
            const conf = document.getElementById('confirmPwd');

            ['curPwdHint', 'newPwdHint', 'confirmPwdHint'].forEach(id => {
                document.getElementById(id).style.display = 'none';
            });
            cur.classList.remove('is-invalid');
            newP.classList.remove('is-invalid');
            conf.classList.remove('is-invalid');

            if (!cur.value) {
                document.getElementById('curPwdHint').textContent = 'กรุณากรอกรหัสผ่านปัจจุบัน';
                document.getElementById('curPwdHint').style.display = 'block';
                cur.classList.add('is-invalid');
                valid = false;
            }

            if (!newP.value || newP.value.length < 8) {
                document.getElementById('newPwdHint').textContent = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
                document.getElementById('newPwdHint').style.display = 'block';
                newP.classList.add('is-invalid');
                valid = false;
            }

            if (conf.value !== newP.value) {
                document.getElementById('confirmPwdHint').textContent = 'รหัสผ่านใหม่ไม่ตรงกัน';
                document.getElementById('confirmPwdHint').style.display = 'block';
                conf.classList.add('is-invalid');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    </script>
    </main>
</div>
    <?php include 'cart_system.php'; ?>
    <?php include 'footer.php'; ?>
</body>
</html>
