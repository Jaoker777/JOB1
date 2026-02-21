<?php
require_once 'auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = attempt_login($pdo, $email, $password);

    if ($result['success']) {
        header('Location: index.php');
        exit;
    } else {
        $errors = $result['errors'];
    }
}

// Success message from registration
$registerSuccess = isset($_GET['registered']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ — Nournia Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="เข้าสู่ระบบ Nournia Shop — ร้านขายอุปกรณ์เกมมิ่งเกียร์">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="brand-logo">🎮</div>
                <h1>Nournia Shop</h1>
                <p>เข้าสู่ระบบเพื่อจัดการร้านค้า</p>
            </div>

            <?php if ($registerSuccess): ?>
                <div class="alert alert-success">
                    ✅ สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate id="loginForm">
                <div class="form-group">
                    <label class="form-label">อีเมล</label>
                    <input type="email" name="email" id="loginEmail" class="form-control"
                           placeholder="example@nournia.com"
                           value="<?= htmlspecialchars($email) ?>" required>
                    <div class="form-hint" id="emailHint" style="color:var(--danger);display:none;"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">รหัสผ่าน</label>
                    <input type="password" name="password" id="loginPassword" class="form-control"
                           placeholder="••••••••" required>
                    <div class="form-hint" id="passwordHint" style="color:var(--danger);display:none;"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
                    🔐 เข้าสู่ระบบ
                </button>
            </form>

            <div class="auth-footer">
                ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('loginForm');
        const emailInput = document.getElementById('loginEmail');
        const passwordInput = document.getElementById('loginPassword');
        const emailHint = document.getElementById('emailHint');
        const passwordHint = document.getElementById('passwordHint');

        form.addEventListener('submit', function(e) {
            let valid = true;

            // Email validation
            emailHint.style.display = 'none';
            emailInput.classList.remove('is-invalid');
            if (!emailInput.value.trim()) {
                emailHint.textContent = 'กรุณากรอกอีเมล';
                emailHint.style.display = 'block';
                emailInput.classList.add('is-invalid');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim())) {
                emailHint.textContent = 'รูปแบบอีเมลไม่ถูกต้อง';
                emailHint.style.display = 'block';
                emailInput.classList.add('is-invalid');
                valid = false;
            }

            // Password validation
            passwordHint.style.display = 'none';
            passwordInput.classList.remove('is-invalid');
            if (!passwordInput.value) {
                passwordHint.textContent = 'กรุณากรอกรหัสผ่าน';
                passwordHint.style.display = 'block';
                passwordInput.classList.add('is-invalid');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    </script>
</body>
</html>
