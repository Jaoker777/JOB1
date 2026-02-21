<?php
require_once 'auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$username = '';
$email = '';

// Handle registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $result = register_user($pdo, $username, $email, $password, $confirmPassword);

    if ($result['success']) {
        header('Location: login.php?registered=1');
        exit;
    } else {
        $errors = $result['errors'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก — Nournia Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="สมัครสมาชิก Nournia Shop — ร้านขายอุปกรณ์เกมมิ่งเกียร์">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="brand-logo">🎮</div>
                <h1>Nournia Shop</h1>
                <p>สร้างบัญชีใหม่</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate id="registerForm">
                <div class="form-group">
                    <label class="form-label">ชื่อผู้ใช้</label>
                    <input type="text" name="username" id="regUsername" class="form-control"
                           placeholder="เช่น gamer99"
                           value="<?= htmlspecialchars($username) ?>" required minlength="3" maxlength="50">
                    <div class="form-hint" id="usernameHint" style="color:var(--danger);display:none;"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">อีเมล</label>
                    <input type="email" name="email" id="regEmail" class="form-control"
                           placeholder="example@nournia.com"
                           value="<?= htmlspecialchars($email) ?>" required>
                    <div class="form-hint" id="emailHint" style="color:var(--danger);display:none;"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">รหัสผ่าน</label>
                    <input type="password" name="password" id="regPassword" class="form-control"
                           placeholder="อย่างน้อย 8 ตัวอักษร" required minlength="8">
                    <div class="form-hint" id="passwordHint" style="color:var(--danger);display:none;"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">ยืนยันรหัสผ่าน</label>
                    <input type="password" name="confirm_password" id="regConfirmPassword" class="form-control"
                           placeholder="กรอกรหัสผ่านอีกครั้ง" required>
                    <div class="form-hint" id="confirmHint" style="color:var(--danger);display:none;"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
                    ✨ สมัครสมาชิก
                </button>
            </form>

            <div class="auth-footer">
                มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('registerForm');
        const fields = {
            username: { input: document.getElementById('regUsername'), hint: document.getElementById('usernameHint') },
            email: { input: document.getElementById('regEmail'), hint: document.getElementById('emailHint') },
            password: { input: document.getElementById('regPassword'), hint: document.getElementById('passwordHint') },
            confirm: { input: document.getElementById('regConfirmPassword'), hint: document.getElementById('confirmHint') }
        };

        function showError(field, msg) {
            field.hint.textContent = msg;
            field.hint.style.display = 'block';
            field.input.classList.add('is-invalid');
        }

        function clearError(field) {
            field.hint.style.display = 'none';
            field.input.classList.remove('is-invalid');
        }

        form.addEventListener('submit', function(e) {
            let valid = true;

            // Username
            clearError(fields.username);
            if (!fields.username.input.value.trim()) {
                showError(fields.username, 'กรุณากรอกชื่อผู้ใช้');
                valid = false;
            } else if (fields.username.input.value.trim().length < 3) {
                showError(fields.username, 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร');
                valid = false;
            }

            // Email
            clearError(fields.email);
            if (!fields.email.input.value.trim()) {
                showError(fields.email, 'กรุณากรอกอีเมล');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fields.email.input.value.trim())) {
                showError(fields.email, 'รูปแบบอีเมลไม่ถูกต้อง');
                valid = false;
            }

            // Password
            clearError(fields.password);
            if (!fields.password.input.value) {
                showError(fields.password, 'กรุณากรอกรหัสผ่าน');
                valid = false;
            } else if (fields.password.input.value.length < 8) {
                showError(fields.password, 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
                valid = false;
            }

            // Confirm password
            clearError(fields.confirm);
            if (!fields.confirm.input.value) {
                showError(fields.confirm, 'กรุณายืนยันรหัสผ่าน');
                valid = false;
            } else if (fields.confirm.input.value !== fields.password.input.value) {
                showError(fields.confirm, 'รหัสผ่านไม่ตรงกัน');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    </script>
</body>
</html>
