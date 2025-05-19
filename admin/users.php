<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit;
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $user_id = $_POST['user_id'] ?? null;
        
        switch ($_POST['action']) {
            case 'update_balance':
                $amount = floatval($_POST['amount'] ?? 0);
                $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$amount, $user_id]);
                break;
                
            case 'block_user':
                $stmt = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
                $stmt->execute([$user_id]);
                break;
                
            case 'unblock_user':
                $stmt = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
                $stmt->execute([$user_id]);
                break;
        }
    }
}

// Get users list with their statistics
$stmt = $conn->prepare("
    SELECT 
        u.*,
        COUNT(DISTINCT p.id) as total_purchases,
        SUM(CASE WHEN p.id IS NOT NULL THEN pr.price ELSE 0 END) as total_spent
    FROM users u
    LEFT JOIN purchases p ON u.id = p.user_id
    LEFT JOIN products pr ON p.product_id = pr.id
    WHERE u.is_admin = 0
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kullanıcı Yönetimi - Admin Panel</title>
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
                    <a href="index.php" class="text-purple-300 hover:text-purple-100 transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Admin Panel
                    </a>
                    <span class="text-xl font-bold text-purple-300">Kullanıcı Yönetimi</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Users Table -->
        <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-purple-600/50">
                            <th class="px-6 py-4 text-left text-sm font-semibold">Kullanıcı</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Email</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Bakiye</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Toplam Alım</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Toplam Harcama</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Durum</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-purple-600/30">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-purple-800/30 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-purple-700/50 rounded-lg">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="font-semibold"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <div class="text-sm text-purple-400">
                                                ID: <?php echo $user['id']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-purple-300">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold">
                                        ₺<?php echo number_format($user['balance'], 2); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo number_format($user['total_purchases']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    ₺<?php echo number_format($user['total_spent'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($user['is_blocked']): ?>
                                        <span class="px-2 py-1 bg-red-500/20 text-red-300 rounded text-sm">
                                            Engelli
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-green-500/20 text-green-300 rounded text-sm">
                                            Aktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <!-- Balance Modal Trigger -->
                                        <button onclick="openBalanceModal(<?php echo $user['id']; ?>)"
                                                class="text-purple-300 hover:text-purple-100 transition"
                                                title="Bakiye Güncelle">
                                            <i class="fas fa-coins"></i>
                                        </button>
                                        
                                        <!-- Block/Unblock Form -->
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" 
                                                   value="<?php echo $user['is_blocked'] ? 'unblock_user' : 'block_user'; ?>">
                                            <button type="submit" 
                                                    class="text-purple-300 hover:text-purple-100 transition"
                                                    onclick="return confirm('Emin misiniz?')"
                                                    title="<?php echo $user['is_blocked'] ? 'Engeli Kaldır' : 'Engelle'; ?>">
                                                <i class="fas <?php echo $user['is_blocked'] ? 'fa-lock-open' : 'fa-lock'; ?>"></i>
                                            </button>
                                        </form>

                                        <!-- View Details Link -->
                                        <a href="user_details.php?id=<?php echo $user['id']; ?>"
                                           class="text-purple-300 hover:text-purple-100 transition"
                                           title="Kullanıcı Detayları">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Manage Purchases Link -->
                                        <a href="user_details.php?id=<?php echo $user['id']; ?>#purchases"
                                           class="text-purple-300 hover:text-purple-100 transition"
                                           title="Satın Alımları Yönet">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Balance Update Modal -->
    <div id="balanceModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gradient-to-br from-purple-900/90 to-purple-800/90 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6 max-w-md w-full">
                <h3 class="text-xl font-semibold mb-4">Bakiye Güncelle</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_balance">
                    <input type="hidden" name="user_id" id="balanceUserId">
                    
                    <div class="mb-4">
                        <label for="amount" class="block text-sm font-medium text-purple-300 mb-2">
                            Miktar (₺)
                        </label>
                        <input type="number" 
                               id="amount" 
                               name="amount" 
                               step="0.01"
                               required
                               class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                        <p class="mt-1 text-sm text-purple-400">
                            Negatif değer girerek bakiye düşürebilirsiniz.
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button"
                                onclick="closeBalanceModal()"
                                class="px-4 py-2 bg-purple-800/50 hover:bg-purple-700/50 rounded-lg transition">
                            İptal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg transition">
                            Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openBalanceModal(userId) {
            document.getElementById('balanceUserId').value = userId;
            document.getElementById('balanceModal').classList.remove('hidden');
        }

        function closeBalanceModal() {
            document.getElementById('balanceModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('balanceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBalanceModal();
            }
        });
    </script>
</body>
</html>
