<?php
session_start();

// Jika pengguna sudah login, langsung arahkan ke dashboard yang sesuai
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: ../founder-access/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

include __DIR__ . '/../db.php'; // Menggunakan koneksi PDO ($pdo)
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fungsi kirim email (tetap sama)
function send_verification_code($pdo, $email, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'zeronestore.shop@gmail.com';
        $mail->Password   = 'qrgsmukxspsauqjq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->setFrom('zeronestore.shop@gmail.com', 'Zerous Shop');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi Anda - Zerous Shop';
        $mail->Body    = "<p>Kode verifikasi Anda adalah: <b style='font-size:18px;'>$code</b></p><p>Kode ini berlaku selama 5 menit.</p>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Menangani permintaan KODE BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_code'])) {
    $email = trim($_POST['email']);
    $code = rand(100000, 999999);
    $expiration = date("Y-m-d H:i:s", time() + 300); // 5 menit

    // Gunakan ON DUPLICATE KEY UPDATE untuk menyisipkan atau memperbarui pengguna
    $stmt = $pdo->prepare("
        INSERT INTO users (email, login_code, code_expiration, created_at) 
        VALUES (:email, :code, :exp, NOW()) 
        ON DUPLICATE KEY UPDATE login_code = :code_update, code_expiration = :exp_update
    ");
    $stmt->execute([
        ':email' => $email, 
        ':code' => $code, 
        ':exp' => $expiration, 
        ':code_update' => $code, 
        ':exp_update' => $expiration
    ]);

    if (send_verification_code($pdo, $email, $code)) {
        $_SESSION['verification_email'] = $email; // SESI SEMENTARA
        $_SESSION['notif'] = ['type' => 'success', 'message' => "Kode verifikasi telah dikirim ke <b>$email</b>."];
    } else {
        $_SESSION['notif'] = ['type' => 'error', 'message' => "Gagal mengirim email."];
    }
    header("Location: login.php");
    exit;
}

// Menangani VERIFIKASI KODE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    if (isset($_SESSION['verification_email'])) {
        $email = $_SESSION['verification_email'];
        $input_code = trim($_POST['code']);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $input_code === $user['login_code'] && strtotime($user['code_expiration']) > time()) {
            // Verifikasi Berhasil! Buat Sesi Login Asli.
            $pdo->prepare("UPDATE users SET login_code = NULL, code_expiration = NULL WHERE email = :email")->execute([':email' => $email]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            unset($_SESSION['verification_email']);

            // **INI BAGIAN KUNCI YANG DIPERBAIKI**
            // Arahkan berdasarkan role setelah login berhasil
            if ($user['role'] === 'admin') {
                header('Location: ../founder-acces/dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $_SESSION['notif'] = ['type' => 'error', 'message' => "❌ Kode verifikasi salah atau telah kedaluwarsa!"];
        }
    } else {
        $_SESSION['notif'] = ['type' => 'error', 'message' => "Sesi verifikasi tidak ditemukan. Silakan minta kode baru."];
    }
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login - Zerous Shop</title>
    <link rel="stylesheet" href="../style.css" />
    <style>
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 25vh;
        }
        .login-form {
            background: #121224;
            padding: 35px 40px;
            border-radius: 12px;
            width: 400px;
            max-width: 100%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.7);
            margin-top: 130px;
        }
        .login-form h2 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #fff;
        }
        .login-form label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 10px;
            color: #ccc;
        }
        .login-form p.subtext {
            font-size: 12px;
            color: #888;
            margin: 0 0 10px 0;
        }
        .login-form input[type="email"],
        .login-form input[type="text"] {
            width: 100%;
            padding: 10px 14px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
            background: #222238;
            color: #eee;
            outline: none;
            box-sizing: border-box;
            transition: box-shadow 0.3s ease;
        }
        .login-form input:focus {
            box-shadow: 0 0 8px #5858ff;
        }
        .login-form button.continue-btn {
            width: 100%;
            padding: 12px 0;
            background: #5858ff;
            color: #fff;
            font-weight: 600;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            transition: background 0.3s ease;
        }
        .login-form button.continue-btn:hover {
            background: #4343d9;
        }
        .login-form button.continue-btn svg {
            width: 16px;
            height: 16px;
            stroke: #fff;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        /* Notif style */
        .notif {
            max-width: 420px;
            margin: 0 auto 20px auto;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
            box-sizing: border-box;
        }
        .notif.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .notif.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }
        .login-alert {
            background-color: #ffe0e0;
            color: #b20000;
            border: 1px solid #f5c2c2;
            padding: 12px 16px;
            margin: 20px auto;
            max-width: 400px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            text-align: center;
        }

        footer {
        position: relative;
        bottom: 0;
        width: 100%;
        background: rgba(18, 18, 36, 0.9);
        backdrop-filter: blur(10px);
        border-top: 1px solid rgba(88, 88, 255, 0.2);
        margin-top: auto;
        padding: 1rem;
        text-align: center;
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.6);
        transition: all 0.3s ease;
        }

        footer:hover {
            color: rgba(255, 255, 255, 0.8);
            border-top-color: rgba(88, 88, 255, 0.4);
        }

        /* Pastikan body menggunakan flexbox untuk sticky footer */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Main content area */
        .main-content {
            flex: 1;
        }

        /* Responsive footer */
        @media (max-width: 768px) {
            footer {
                font-size: 0.75rem;
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>

<!-- Loading Screen -->
<div id="loading-screen">
    <div class="spinner"></div>
    <p>Loading...</p>
</div>

<header>
    <div class="logo-title">
        <img src="../assets/logo.webp" alt="Logo" class="logo" />
        <h1>Zerous Shop</h1>
    </div>

    <nav class="nav-grid">
        <a href="../index.php" class="nav-btn">Products</a>
        <a href="../reviews.php" class="nav-btn">Reviews</a>
        <a href="../index.php#news" class="nav-btn">News</a>
        <a href="../faq.php" class="nav-btn">FAQ</a>
        <a href="#" class="nav-btn active">My Account</a>
    </nav>
</header>
<?php
if (isset($_SESSION['login_notice'])) {
    echo "<div class='login-alert'>" . htmlspecialchars($_SESSION['login_notice']) . "</div>";
    unset($_SESSION['login_notice']);
}?>
    <div class="login-wrapper">
        <div class="login-form">
            <?php if (isset($_SESSION['notif'])): ?>
                <div class="notif <?= $_SESSION['notif']['type'] ?>"><?= $_SESSION['notif']['message'] ?></div>
                <?php unset($_SESSION['notif']); ?>
            <?php endif; ?>

            <?php if (!isset($_SESSION['verification_email'])): ?>
                <h2>Log in to Your Account</h2>
                <form method="POST" action="login.php">
                    <input type="hidden" name="request_code" value="1">
                    <label for="emailInput">E-mail Address</label>
                    <input type="email" id="emailInput" name="email" placeholder="name@domain.com" required/>
                    <button type="submit" class="continue-btn">Continue</button>
                </form>
            <?php else: ?>
                <h2>Enter Verification Code</h2>
                <form method="POST" action="login.php">
                    <p class="subtext">A 6-digit code has been sent to <strong><?= htmlspecialchars($_SESSION['verification_email']) ?></strong>.</p>
                    <input type="hidden" name="verify_code" value="1">
                    <label for="codeInput">6-Digit Code</label>
                    <input type="text" id="codeInput" name="code" placeholder="123456" required/>
                    <button type="submit" class="continue-btn">Verify & Login</button>
                </form>
            <?php endif; ?>
    </div>
</div>

<?php include '../contact-widget.php'; ?>
    <!-- Footer yang diperbaiki -->
    <footer>
        <div style="max-width: 1200px; margin: 0 auto;">
            &copy; <?php echo date('Y'); ?> Zerous Shop. All rights reserved.
            <br>
            <small style="opacity: 0.7; font-size: 0.75rem;">
                Secure Login System | Last updated: <?php echo date('M Y'); ?>
            </small>
        </div>
    </footer>

<!-- ====== LOADING SCREEN SCRIPT ====== -->
<script>
window.addEventListener('load', () => {
    const loader = document.getElementById('loading-screen');
    loader.style.opacity = '0';
    setTimeout(() => {
        loader.style.display = 'none';
    }, 400);
});
        // Footer enhancement script
        document.addEventListener('DOMContentLoaded', function() {
            const footer = document.querySelector('footer');
            
            // Add smooth fade-in animation
            footer.style.opacity = '0';
            footer.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                footer.style.transition = 'all 0.5s ease';
                footer.style.opacity = '1';
                footer.style.transform = 'translateY(0)';
            }, 500);
            
            // Add current time update (optional)
            function updateTime() {
                const now = new Date();
                const timeElement = footer.querySelector('.current-time');
                if (timeElement) {
                    timeElement.textContent = now.toLocaleTimeString('id-ID');
                }
            }
            
            // Uncomment if you want to show current time
            // setInterval(updateTime, 1000);
        });
</script>

</body>
</html>
