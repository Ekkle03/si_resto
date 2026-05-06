<?php
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <title>Login – Ayam Goreng Kabayan</title>
    <link rel="icon" href="admin/assets/img/logo/logo_resto.png" type="image/x-icon" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            font-family: 'Inter', sans-serif;
            padding: 24px 16px;
        }

        /* ── Wrapper ───────────────────────────── */
        .login-wrap {
            display: flex;
            width: 100%;
            max-width: 820px;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,.12);
        }

        /* ── Left Panel ────────────────────────── */
        .panel-left {
            flex: 1;
            background: #fff;
            padding: 48px 40px 44px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid #f0f0f0;
        }

        .brand-logo-img {
            width: 170px;
            height: auto;
            object-fit: contain;
            display: block;
            mix-blend-mode: multiply;
        }

        .brand-sub {
            font-size: 12px;
            color: #b0b8c4;
            margin-top: 10px;
            letter-spacing: .3px;
            font-weight: 400;
        }

        .left-tagline {
            font-size: 26px;
            font-weight: 800;
            color: #1a1a1a;
            line-height: 1.38;
            margin-bottom: 0;
        }

        .highlight-yellow { color: #F5A623; }

        .left-dots {
            display: flex;
            gap: 7px;
            align-items: center;
            margin-top: 32px;
        }
        .dot { width: 7px; height: 7px; border-radius: 50%; background: #e2e8f0; }
        .dot.active { width: 22px; border-radius: 4px; background: #C0392B; }

        /* ── Right Panel ───────────────────────── */
        .panel-right {
            width: 360px;
            background: #C0392B;
            padding: 48px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .badge-system {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 11px;
            color: #fff;
            font-weight: 600;
            margin-bottom: 20px;
            width: fit-content;
        }

        .right-title {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }

        .right-sub {
            font-size: 13px;
            color: rgba(255,255,255,.65);
            margin-bottom: 28px;
        }

        /* ── Error Message ─────────────────────── */
        .message {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .error-message {
            background: rgba(0,0,0,.2);
            border: 1px solid rgba(255,255,255,.15);
            color: #fff;
        }
        .error-message i { font-size: 12px; flex-shrink: 0; }

        /* ── Fields ────────────────────────────── */
        .field-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: rgba(255,255,255,.7);
            margin-bottom: 6px;
        }

        .field-wrap {
            position: relative;
            margin-bottom: 16px;
        }
        .field-wrap input {
            width: 100%;
            height: 44px;
            padding: 0 42px 0 14px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #1a1a1a;
            background: #fff;
            border: none;
            border-radius: 8px;
            outline: none;
            transition: box-shadow .2s;
        }
        .field-wrap input:focus {
            box-shadow: 0 0 0 3px rgba(255,255,255,.4);
        }
        .field-wrap input::placeholder { color: #cbd5e1; }
        .field-wrap .field-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            cursor: pointer;
            transition: color .2s;
        }

        /* ── Button ────────────────────────────── */
        .btn-login {
            width: 100%;
            height: 46px;
            background: #fff;
            border: none;
            border-radius: 8px;
            color: #C0392B;
            font-size: 14px;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            margin-top: 6px;
            transition: background .2s, transform .1s;
        }
        .btn-login:hover { background: #f8f8f8; }
        .btn-login:active { transform: scale(0.99); }

        .footer-note {
            font-size: 11px;
            color: rgba(255,255,255,.4);
            text-align: center;
            margin-top: 16px;
        }

        /* ── Responsive ────────────────────────── */
        @media (max-width: 600px) {
            .panel-left { display: none; }
            .panel-right { width: 100%; border-radius: 18px; }
            .login-wrap { max-width: 400px; }
        }
    </style>
</head>
<body>
    <div class="login-wrap">

        <!-- Left Panel -->
        <div class="panel-left">
            <div>
                <img src="admin/assets/img/logo/logo_resto.png"
                     class="brand-logo-img"
                     alt="Ayam Goreng Kabayan">
                <div class="brand-sub">Information System</div>
            </div>
            <div>
                <div class="left-tagline">
                    Kelola restoran<br>
                    dengan <span class="highlight-yellow">lebih cepat</span><br>
                    dan efisien.
                </div>
                <div class="left-dots">
                    <div class="dot active"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                </div>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="panel-right">
            <div class="badge-system">
                <i class="fas fa-lock" style="font-size:10px;"></i>
                Sistem Internal
            </div>
            <div class="right-title">Selamat datang!</div>
            <div class="right-sub">Masuk ke akun Anda untuk melanjutkan</div>

            <form action="login.php" method="POST">
                <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="message error-message"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>

                <div class="field-label">Username</div>
                <div class="field-wrap">
                    <input type="text" name="username" placeholder="Masukkan username" required>
                    <span class="field-icon"><i class="fas fa-user"></i></span>
                </div>

                <div class="field-label">Password</div>
                <div class="field-wrap">
                    <input type="password" name="password" id="passwordInput" placeholder="Masukkan password" required>
                    <span class="field-icon" id="togglePassword">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </span>
                </div>

                <button type="submit" class="btn-login">Masuk</button>
            </form>

            <div class="footer-note">Hubungi admin jika lupa password</div>
        </div>

    </div>

    <script>
        const togglePassword = document.querySelector("#togglePassword");
        const password = document.querySelector("#passwordInput");
        const eyeIcon = document.querySelector("#eyeIcon");

        togglePassword.addEventListener("click", function () {
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);

            if (type === "password") {
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            } else {
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            }
        });
    </script>
</body>
</html>