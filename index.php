<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Lütfen kullanıcı adı ve şifrenizi giriniz.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];

            // Redirect based on user type
            if ($user['is_admin']) {
                header("Location: admin/index.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit;
        } else {
            $error = 'Geçersiz kullanıcı adı veya şifre.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Giriş Yap - Mor Tema</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #1a0527;
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
        }
        .neon-lines {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            overflow: visible;
            z-index: 0;
            filter: drop-shadow(0 0 6px #a855f7);
        }
        .neon-line {
            position: absolute;
            width: 3px;
            height: 130vh;
            background: linear-gradient(180deg, #a855f7, #d8b4fe88, #a855f7);
            filter: drop-shadow(0 0 8px #a855f7) drop-shadow(0 0 15px #c084fc) drop-shadow(0 0 20px #d8b4fe);
            animation-timing-function: linear;
            opacity: 0.75;
            border-radius: 2px;
            mix-blend-mode: screen;
        }
        .line1 { left: 8%; animation: slideDown 9s linear infinite; animation-delay: 0s; }
        .line2 { left: 22%; animation: slideDown 11s linear infinite; animation-delay: 2.5s; }
        .line3 { left: 36%; animation: slideDown 8s linear infinite; animation-delay: 1.2s; }
        .line4 { left: 50%; animation: slideDown 10s linear infinite; animation-delay: 3.3s; }
        .line5 { left: 64%; animation: slideDown 12s linear infinite; animation-delay: 0.7s; }
        .line6 { left: 78%; animation: slideDown 9.5s linear infinite; animation-delay: 1.8s; }
        .line7 { left: 92%; animation: slideDown 10.5s linear infinite; animation-delay: 2.1s; }
        
        @keyframes slideDown {
            0% { transform: translateY(-130vh); opacity: 0.75; }
            50% { opacity: 1; }
            100% { transform: translateY(130vh); opacity: 0.75; }
        }
        
        .glow-orb {
            position: fixed;
            border-radius: 50%;
            filter: drop-shadow(0 0 10px #a855f7) drop-shadow(0 0 20px #c084fc);
            mix-blend-mode: screen;
            opacity: 0.3;
            animation-timing-function: ease-in-out;
            animation-iteration-count: infinite;
            animation-direction: alternate;
            z-index: 0;
        }
        
        .orb1 {
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, #a855f7 0%, transparent 70%);
            top: 15%;
            left: 10%;
            animation: floatUpDown1 6s infinite;
        }
        
        .orb2 {
            width: 90px;
            height: 90px;
            background: radial-gradient(circle, #d8b4fe 0%, transparent 70%);
            top: 70%;
            left: 80%;
            animation: floatUpDown2 8s infinite;
        }
        
        .orb3 {
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, #c084fc 0%, transparent 70%);
            top: 50%;
            left: 45%;
            animation: floatUpDown3 7s infinite;
        }
        
        @keyframes floatUpDown1 {
            0% { transform: translateY(0); opacity: 0.3; }
            100% { transform: translateY(-20px); opacity: 0.5; }
        }
        
        @keyframes floatUpDown2 {
            0% { transform: translateY(0); opacity: 0.3; }
            100% { transform: translateY(25px); opacity: 0.5; }
        }
        
        @keyframes floatUpDown3 {
            0% { transform: translateY(0); opacity: 0.3; }
            100% { transform: translateY(-15px); opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="neon-lines" aria-hidden="true">
        <div class="neon-line line1"></div>
        <div class="neon-line line2"></div>
        <div class="neon-line line3"></div>
        <div class="neon-line line4"></div>
        <div class="neon-line line5"></div>
        <div class="neon-line line6"></div>
        <div class="neon-line line7"></div>
    </div>

    <div class="glow-orb orb1" aria-hidden="true"></div>
    <div class="glow-orb orb2" aria-hidden="true"></div>
    <div class="glow-orb orb3" aria-hidden="true"></div>

    <main class="relative z-10 min-h-screen flex items-center justify-center px-6 py-12">
        <section class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-3xl shadow-2xl max-w-md w-full p-10 border border-purple-600/50">
            <h1 class="text-4xl font-extrabold text-purple-300 mb-8 text-center drop-shadow-[0_0_15px_rgba(192,132,252,0.9)]">
                Giriş Yap
            </h1>
            
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" novalidate>
                <div>
                    <label for="username" class="block text-purple-300 font-semibold mb-2 select-none">
                        Kullanıcı Adı
                    </label>
                    <div class="relative">
                        <input type="text" id="username" name="username" required
                               class="w-full rounded-lg bg-transparent border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-3 pr-10 shadow-[0_0_15px_rgba(192,132,252,0.7)] transition"
                               placeholder="Kullanıcı adınızı girin">
                        <i class="fas fa-user absolute right-3 top-1/2 -translate-y-1/2 text-purple-400 pointer-events-none"></i>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-purple-300 font-semibold mb-2 select-none">
                        Şifre
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               class="w-full rounded-lg bg-transparent border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-3 pr-10 shadow-[0_0_15px_rgba(192,132,252,0.7)] transition"
                               placeholder="Şifrenizi girin">
                        <i class="fas fa-lock absolute right-3 top-1/2 -translate-y-1/2 text-purple-400 pointer-events-none"></i>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg shadow-lg shadow-purple-500/60 transition focus:outline-none focus:ring-4 focus:ring-purple-400">
                    Giriş Yap
                </button>
            </form>

            <p class="mt-6 text-center text-purple-300 text-sm">
                Hesabınız yok mu?
                <a href="register.php"
                   class="text-purple-400 font-semibold hover:underline focus:outline-none focus:ring-2 focus:ring-purple-400 rounded">
                    Kayıt Ol
                </a>
            </p>
        </section>
    </main>
</body>
</html>
