<?php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Get transaction details
$transaction_id = $_GET['transaction_id'] ?? null;
if (!$transaction_id) {
    header("Location: balance.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT bt.*, u.username, u.balance 
    FROM balance_transactions bt
    JOIN users u ON bt.user_id = u.id
    WHERE bt.id = ? AND bt.user_id = ?
");
$stmt->execute([$transaction_id, $_SESSION['user_id']]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    header("Location: balance.php");
    exit;
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Simulate payment processing
    $stmt = $conn->prepare("UPDATE balance_transactions SET status = 'completed' WHERE id = ?");
    $stmt->execute([$transaction_id]);
    
    // Update user balance
    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$transaction['amount'], $_SESSION['user_id']]);
    
    header("Location: dashboard.php?success=payment");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ödeme - Panel</title>
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
                    <a href="balance.php" class="text-purple-300 hover:text-purple-100 transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Geri Dön
                    </a>
                    <span class="text-xl font-bold text-purple-300">Ödeme</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-purple-300">
                        <i class="fas fa-coins mr-2"></i>
                        Mevcut Bakiye: <?php echo number_format($transaction['balance'], 2); ?> ₺
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-md mx-auto">
            <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                <h2 class="text-2xl font-semibold mb-6">Ödeme Detayları</h2>
                
                <div class="space-y-4 mb-6">
                    <div class="bg-purple-800/30 p-4 rounded-lg">
                        <div class="text-sm text-purple-400">İşlem Numarası</div>
                        <div class="font-mono">#<?php echo str_pad($transaction['id'], 8, '0', STR_PAD_LEFT); ?></div>
                    </div>

                    <div class="bg-purple-800/30 p-4 rounded-lg">
                        <div class="text-sm text-purple-400">Yüklenecek Tutar</div>
                        <div class="text-2xl font-semibold">₺<?php echo number_format($transaction['amount'], 2); ?></div>
                    </div>
                </div>

                <!-- Credit Card Form -->
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-purple-300 mb-2">
                            Kart Numarası
                        </label>
                        <input type="text" 
                               pattern="[0-9]{16}"
                               maxlength="16"
                               placeholder="1234 5678 9012 3456"
                               required
                               class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-purple-300 mb-2">
                                Son Kullanma
                            </label>
                            <input type="text" 
                                   pattern="(0[1-9]|1[0-2])\/[0-9]{2}"
                                   placeholder="MM/YY"
                                   maxlength="5"
                                   required
                                   class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-purple-300 mb-2">
                                CVV
                            </label>
                            <input type="text" 
                                   pattern="[0-9]{3,4}"
                                   maxlength="4"
                                   placeholder="123"
                                   required
                                   class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg shadow-lg transition focus:outline-none focus:ring-4 focus:ring-purple-400">
                        Ödemeyi Tamamla
                    </button>
                </form>

                <div class="mt-6 text-center text-sm text-purple-400">
                    <i class="fas fa-lock mr-2"></i>
                    256-bit SSL ile şifrelenmiş güvenli ödeme
                </div>
            </div>
        </div>
    </main>

    <script>
        // Format credit card number with spaces
        document.querySelector('input[pattern="[0-9]{16}"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            e.target.value = value.replace(/(.{4})/g, '$1 ').trim();
        });

        // Format expiry date
        document.querySelector('input[pattern="(0[1-9]|1[0-2])\\/[0-9]{2}"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '/' + value.slice(2);
            }
            e.target.value = value;
        });

        // Format CVV
        document.querySelector('input[pattern="[0-9]{3,4}"]').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
        });
    </script>
</body>
</html>
