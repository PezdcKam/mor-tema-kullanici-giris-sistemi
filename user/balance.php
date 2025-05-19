<?php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Get user's current balance
$stmt = $conn->prepare("SELECT username, balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle balance addition request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);
    
    if ($amount < 10) {
        $error = "Minimum yükleme tutarı 10₺'dir.";
    } elseif ($amount > 1000) {
        $error = "Maksimum yükleme tutarı 1000₺'dir.";
    } else {
        // Create transaction record
        $stmt = $conn->prepare("INSERT INTO balance_transactions (user_id, amount, type) VALUES (?, ?, 'deposit')");
        $stmt->execute([$_SESSION['user_id'], $amount]);
        
        // Get transaction ID
        $transaction_id = $conn->lastInsertId();
        
        // Redirect to payment gateway (you'll need to replace this with your actual payment gateway)
        header("Location: payment_gateway.php?transaction_id=" . $transaction_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bakiye Yükle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #1a0527;
            min-height: 100vh;
        }
    </style>
</head>
<body class="text-purple-100">
    <!-- Navigation -->
    <nav class="bg-purple-900/70 border-b border-purple-600/50 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="dashboard.php" class="text-purple-300 hover:text-purple-100 transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Panele Dön
                    </a>
                    <span class="text-xl font-bold text-purple-300">Bakiye Yükle</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-purple-300">
                        <i class="fas fa-coins mr-2"></i>
                        Mevcut Bakiye: <?php echo number_format($user['balance'], 2); ?> ₺
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-md mx-auto">
            <?php if (isset($error)): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-200 px-4 py-3 rounded-lg mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                <h2 class="text-2xl font-semibold mb-6">Bakiye Yükleme</h2>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="amount" class="block text-purple-300 font-semibold mb-2">
                            Yüklenecek Tutar (₺)
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   id="amount" 
                                   name="amount" 
                                   min="10" 
                                   max="1000" 
                                   step="1" 
                                   required
                                   class="w-full rounded-lg bg-transparent border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-3 pr-12"
                                   placeholder="0.00">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-purple-400">₺</span>
                        </div>
                        <p class="mt-2 text-sm text-purple-400">
                            Min: 10₺ - Max: 1000₺
                        </p>
                    </div>

                    <!-- Quick Amount Buttons -->
                    <div class="grid grid-cols-3 gap-4">
                        <button type="button" 
                                onclick="document.getElementById('amount').value='50'"
                                class="px-4 py-2 bg-purple-800/50 hover:bg-purple-700/50 rounded-lg transition">
                            50₺
                        </button>
                        <button type="button"
                                onclick="document.getElementById('amount').value='100'"
                                class="px-4 py-2 bg-purple-800/50 hover:bg-purple-700/50 rounded-lg transition">
                            100₺
                        </button>
                        <button type="button"
                                onclick="document.getElementById('amount').value='200'"
                                class="px-4 py-2 bg-purple-800/50 hover:bg-purple-700/50 rounded-lg transition">
                            200₺
                        </button>
                    </div>

                    <button type="submit"
                            class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg shadow-lg transition focus:outline-none focus:ring-4 focus:ring-purple-400">
                        Ödemeye Geç
                    </button>
                </form>

                <!-- Payment Methods -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold mb-4">Ödeme Yöntemleri</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="flex items-center justify-center p-4 bg-purple-800/30 rounded-lg">
                            <i class="fas fa-credit-card text-2xl"></i>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-purple-800/30 rounded-lg">
                            <i class="fas fa-university text-2xl"></i>
                        </div>
                        <div class="flex items-center justify-center p-4 bg-purple-800/30 rounded-lg">
                            <i class="fas fa-wallet text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="mt-6 text-center text-sm text-purple-400">
                    <i class="fas fa-lock mr-2"></i>
                    Tüm ödemeler 256-bit SSL ile şifrelenmektedir.
                </div>
            </div>
        </div>
    </main>

    <script>
        // Validate amount input
        document.getElementById('amount').addEventListener('input', function(e) {
            let value = parseFloat(e.target.value);
            if (value < 10) {
                e.target.setCustomValidity('Minimum yükleme tutarı 10₺\'dir.');
            } else if (value > 1000) {
                e.target.setCustomValidity('Maksimum yükleme tutarı 1000₺\'dir.');
            } else {
                e.target.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
