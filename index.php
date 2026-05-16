<?php
session_start();
require_once __DIR__ . '/backend/csrf.php';
if(isset($_SESSION['userID'])){
    header("Location: ./frontend/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>7Evelyn POS – Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --ev-primary:    #008161;
    --ev-primary-dk: #005e47;
    --ev-dark:       #002e22;
    --ev-accent:     #f01b2d;
    --ev-orange:     #f4821f;
    --ev-bg:         #f0f7f5;
    --ev-gradient:   linear-gradient(135deg, #008161 0%, #005e47 100%);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    min-height: 100vh;
    display: flex;
    background: var(--ev-bg);
    font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif;
}

/* ── LEFT PANEL ── */
.login-left {
    width: 46%;
    background: linear-gradient(160deg, var(--ev-dark) 0%, #004a38 45%, var(--ev-primary) 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 48px;
    position: relative;
    overflow: hidden;
}

/* Decorative circles */
.login-left::before {
    content: '';
    position: absolute;
    top: -90px; right: -90px;
    width: 340px; height: 340px;
    border-radius: 50%;
    background: rgba(244,130,31,0.13);
    pointer-events: none;
}
.login-left::after {
    content: '';
    position: absolute;
    bottom: -70px; left: -70px;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    pointer-events: none;
}
/* Extra accent dot */
.left-dot {
    position: absolute;
    bottom: 120px; right: -40px;
    width: 160px; height: 160px;
    border-radius: 50%;
    border: 2px solid rgba(244,130,31,0.18);
    pointer-events: none;
}

.brand-logo-wrap {
    width: 108px; height: 108px;
    border-radius: 28px;
    background: rgba(255,255,255,0.1);
    border: 2px solid rgba(255,255,255,0.22);
    display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: #fff;
    margin-bottom: 28px;
    position: relative; z-index: 1;
    overflow: hidden;
    backdrop-filter: blur(6px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.22);
    transition: transform 0.3s;
}
.brand-logo-wrap:hover { transform: scale(1.04); }
.brand-logo-wrap img {
    width: 100%; height: 100%;
    object-fit: cover; border-radius: 26px; display: none;
}

.left-title {
    font-size: 2.1rem; font-weight: 800;
    color: #fff; letter-spacing: -0.5px;
    position: relative; z-index: 1;
    text-align: center; margin-bottom: 6px;
}
.left-sub {
    font-size: 11.5px; color: rgba(255,255,255,0.55);
    text-align: center; letter-spacing: 2px;
    text-transform: uppercase; font-weight: 600;
    position: relative; z-index: 1;
    margin-bottom: 44px;
}

/* Orange underline accent */
.left-title span {
    display: inline-block;
    position: relative;
}
.left-title span::after {
    content: '';
    position: absolute;
    left: 0; bottom: -4px;
    width: 100%; height: 3px;
    background: linear-gradient(90deg, #f4821f, #f01b2d);
    border-radius: 2px;
}

.feature-list { list-style: none; width: 100%; position: relative; z-index: 1; }
.feature-list li {
    display: flex; align-items: center; gap: 13px;
    color: rgba(255,255,255,0.82); font-size: 13.5px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.feature-list li:last-child { border-bottom: none; }
.feature-list li .fi {
    width: 34px; height: 34px; border-radius: 9px;
    background: rgba(244,130,31,0.22);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: #f4821f; flex-shrink: 0;
    transition: background 0.2s;
}
.feature-list li:hover .fi { background: rgba(244,130,31,0.38); }

/* ── RIGHT PANEL ── */
.login-right {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 32px;
    background: var(--ev-bg);
}
.login-form-wrap {
    width: 100%;
    max-width: 400px;
}

/* Brand mark on right for mobile fallback */
.right-brand {
    display: none;
    align-items: center; gap: 10px;
    margin-bottom: 28px;
}
.right-brand-icon {
    width: 42px; height: 42px; border-radius: 12px;
    background: var(--ev-gradient);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: #fff;
    box-shadow: 0 4px 12px rgba(0,129,97,0.35);
}

.form-heading {
    font-size: 1.65rem; font-weight: 800;
    color: var(--ev-dark); margin-bottom: 4px;
}
.form-subheading {
    font-size: 13px; color: #6b7a74;
    margin-bottom: 34px;
}

.form-label {
    font-size: 12.5px; font-weight: 700;
    color: var(--ev-primary-dk); margin-bottom: 5px;
    display: flex; align-items: center; gap: 5px;
}
.form-control {
    border-radius: 10px;
    border: 1.5px solid #c8ddd8;
    padding: 10px 14px;
    font-size: 14px;
    background: #fff;
    color: var(--ev-dark);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.form-control:focus {
    border-color: var(--ev-primary);
    box-shadow: 0 0 0 3px rgba(0,129,97,0.13);
    outline: none;
}
.form-control::placeholder { color: #b0c4bc; }

.input-group .form-control { border-right: none; border-radius: 10px 0 0 10px; }
.input-group-text {
    border: 1.5px solid #c8ddd8; border-left: none;
    border-radius: 0 10px 10px 0;
    background: #fff; cursor: pointer;
    color: var(--ev-primary); padding: 0 14px;
    transition: background 0.2s, color 0.2s;
}
.input-group-text:hover { background: #f0faf7; color: var(--ev-primary-dk); }
.input-group:focus-within .form-control,
.input-group:focus-within .input-group-text {
    border-color: var(--ev-primary);
}
.input-group:focus-within .input-group-text {
    box-shadow: 0 0 0 3px rgba(0,129,97,0.13);
}

.btn-login {
    background: var(--ev-gradient);
    border: none; color: #fff;
    font-weight: 700; font-size: 15px;
    border-radius: 10px;
    padding: 12px;
    letter-spacing: 0.3px;
    transition: all 0.25s;
    box-shadow: 0 4px 16px rgba(0,129,97,0.32);
    position: relative; overflow: hidden;
}
.btn-login::after {
    content: '';
    position: absolute; inset: 0;
    background: rgba(255,255,255,0);
    transition: background 0.2s;
}
.btn-login:hover {
    background: linear-gradient(135deg, #006e54, #008161);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(0,129,97,0.42);
}
.btn-login:hover::after { background: rgba(255,255,255,0.07); }
.btn-login:active { transform: translateY(0); }

/* Divider */
.divider-line {
    display: flex; align-items: center; gap: 12px;
    margin: 24px 0; color: #aac4bb; font-size: 11.5px;
    font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px;
}
.divider-line::before, .divider-line::after {
    content: ''; flex: 1;
    border-top: 1px solid #d6e9e4;
}

/* Credential hint box */
.cred-box {
    background: #f0faf7;
    border-radius: 10px; padding: 13px 16px;
    border: 1.5px solid #b3ddd2;
    border-left: 4px solid var(--ev-orange);
}
.cred-box .cred-role {
    font-size: 11.5px; color: var(--ev-primary-dk);
    font-weight: 700; margin-bottom: 4px;
    display: flex; align-items: center; gap: 6px;
}
.cred-box .cred-val { font-size: 12.5px; color: #4a7a6e; }
.cred-box .cred-val code {
    background: rgba(0,129,97,0.1); padding: 1px 6px;
    border-radius: 4px; font-size: 12px; color: var(--ev-primary-dk);
}

/* Alerts */
.alert { border-radius: 10px; font-size: 13.5px; }
.alert-danger  {
    background: #fff0f1; border: 1.5px solid #f9c0c4; color: #b0192a;
    border-left: 4px solid var(--ev-accent);
}
.alert-success {
    background: #f0faf7; border: 1.5px solid #b3ddd2; color: #005e47;
    border-left: 4px solid var(--ev-primary);
}

/* Footer */
.login-footer-text {
    text-align: center; font-size: 11.5px;
    color: #9db8b0; margin-top: 32px;
}
.login-footer-text strong { color: var(--ev-primary); }

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    body { flex-direction: column; }
    .login-left { width: 100%; padding: 36px 24px 28px; }
    .feature-list { display: none; }
    .left-sub { margin-bottom: 0; }
    .login-right { padding: 28px 20px; }
    .right-brand { display: flex; }
    .form-heading { font-size: 1.4rem; }
}
</style>
</head>
<body>

<!-- ══ LEFT: Brand Panel ══ -->
<div class="login-left">
    <div class="left-dot"></div>
    <div class="brand-logo-wrap" id="loginLogoWrap">
        <img id="loginLogoImg" src="" alt="Logo">
        <i class="bi bi-shop-window" id="loginLogoIcon"></i>
    </div>
    <div class="left-title"><span id="loginBrandName">7Evelyn</span></div>
    <div class="left-sub">Point of Sale System</div>

    <ul class="feature-list">
        <li><span class="fi"><i class="bi bi-cart3"></i></span> Fast &amp; Easy Point of Sale</li>
        <li><span class="fi"><i class="bi bi-archive"></i></span> Real-time Inventory Tracking</li>
        <li><span class="fi"><i class="bi bi-people"></i></span> Customer Credit Management</li>
        <li><span class="fi"><i class="bi bi-bar-chart-line"></i></span> Sales Reports &amp; Analytics</li>
        <li><span class="fi"><i class="bi bi-shield-check"></i></span> Role-based Access Control</li>
    </ul>
</div>

<!-- ══ RIGHT: Login Form ══ -->
<div class="login-right">
    <div class="login-form-wrap">

        <!-- Mobile brand mark -->
        <div class="right-brand">
            <div class="right-brand-icon"><i class="bi bi-shop-window"></i></div>
            <span style="font-weight:800;color:var(--ev-dark);font-size:1.1rem;" id="mobileBrandName">7Evelyn</span>
        </div>

        <div class="form-heading">Welcome back 👋</div>
        <div class="form-subheading">Sign in to your account to continue</div>

        <?php if(isset($_GET['invalid'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> <strong>Access Denied.</strong> Wrong email or password.
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if(isset($_GET['logout'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
            <i class="bi bi-check-circle me-1"></i> You've been signed out successfully.
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form action="./backend/loginAuth.php" method="POST">
            <?php csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-envelope"></i> Email Address
                </label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">
                    <i class="bi bi-lock"></i> Password
                </label>
                <div class="input-group">
                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" required>
                    <span class="input-group-text" onclick="togglePw()" title="Show/Hide password">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" name="loginAuth" class="btn btn-login btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </div>
        </form>



        <div class="login-footer-text">
            &copy; <?php echo date('Y'); ?> <strong>7Evelyn POS</strong> &nbsp;&bull;&nbsp; v1.0.0
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw(){
    const inp = document.getElementById('passwordInput');
    const ico = document.getElementById('eyeIcon');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

(function(){
    const logo = localStorage.getItem('ev_store_logo');
    const name = localStorage.getItem('ev_store_name');
    if(logo){
        const img  = document.getElementById('loginLogoImg');
        const icon = document.getElementById('loginLogoIcon');
        if(img && icon){ img.src = logo; img.style.display = 'block'; icon.style.display = 'none'; }
    }
    if(name){
        ['loginBrandName','mobileBrandName'].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.textContent = name;
        });
    }
})();
</script>
</body>
</html>
