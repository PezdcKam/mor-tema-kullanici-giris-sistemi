<?php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Get user information
$stmt = $conn->prepare("SELECT username, balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's recent purchases
$stmt = $conn->prepare("
    SELECT p.*, pr.name as product_name 
    FROM purchases p 
    JOIN products pr ON p.product_id = pr.id 
    WHERE p.user_id = ? 
    ORDER BY p.purchase_date DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get latest announcements
$stmt = $conn->prepare("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kontrol Paneli - <?php echo htmlspecialchars($user['username']); ?></title>
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
                <div class="flex items-center">
                    <span class="text-xl font-bold text-purple-300">Panel</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-purple-300">
                        <i class="fas fa-coins mr-2"></i>
                        Bakiye: <?php echo number_format($user['balance'], 2); ?> ₺
                    </span>
                    <span class="text-purple-300">
                        <i class="fas fa-user mr-2"></i>
                        <?php echo htmlspecialchars($user['username']); ?>
                    </span>
                    <a href="../logout.php" class="text-purple-300 hover:text-purple-100 transition">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <a href="products.php" class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl p-6 rounded-xl border border-purple-600/50 hover:border-purple-500/50 transition group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-700/50 rounded-lg group-hover:bg-purple-600/50 transition">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Ürünler</h3>
                        <p class="text-purple-300 text-sm">Tüm ürünleri görüntüle</p>
                    </div>
                </div>
            </a>

            <a href="balance.php" class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl p-6 rounded-xl border border-purple-600/50 hover:border-purple-500/50 transition group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-700/50 rounded-lg group-hover:bg-purple-600/50 transition">
                        <i class="fas fa-wallet text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Bakiye Yükle</h3>
                        <p class="text-purple-300 text-sm">Hesabına bakiye ekle</p>
                    </div>
                </div>
            </a>

            <a href="software.php" class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl p-6 rounded-xl border border-purple-600/50 hover:border-purple-500/50 transition group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-700/50 rounded-lg group-hover:bg-purple-600/50 transition">
                        <i class="fas fa-download text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Yazılımlar</h3>
                        <p class="text-purple-300 text-sm">Yazılımları indir</p>
                    </div>
                </div>
            </a>

            <a href="purchases.php" class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl p-6 rounded-xl border border-purple-600/50 hover:border-purple-500/50 transition group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-700/50 rounded-lg group-hover:bg-purple-600/50 transition">
                        <i class="fas fa-history text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Satın Alınanlar</h3>
                        <p class="text-purple-300 text-sm">Satın aldığın ürünler</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Recent Purchases & Announcements -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Purchases -->
            <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                <h2 class="text-xl font-semibold mb-4">Son Satın Alımlar</h2>
                <?php if (empty($recent_purchases)): ?>
                    <p class="text-purple-300">Henüz satın alım yapılmamış.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_purchases as $purchase): ?>
                            <div class="flex items-center justify-between p-4 bg-purple-800/30 rounded-lg">
                                <div>
                                    <h3 class="font-semibold"><?php echo htmlspecialchars($purchase['product_name']); ?></h3>
                                    <p class="text-sm text-purple-300"><?php echo date('d.m.Y H:i', strtotime($purchase['purchase_date'])); ?></p>
                                </div>
                                <?php if ($purchase['code_or_key']): ?>
                                    <span class="px-3 py-1 bg-purple-700/50 rounded text-sm">
                                        <?php echo htmlspecialchars($purchase['code_or_key']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Announcements -->
            <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                <h2 class="text-xl font-semibold mb-4">Duyurular</h2>
                <?php if (empty($announcements)): ?>
                    <p class="text-purple-300">Henüz duyuru yok.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="p-4 bg-purple-800/30 rounded-lg">
                                <h3 class="font-semibold mb-2"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                <p class="text-sm text-purple-300"><?php echo htmlspecialchars($announcement['content']); ?></p>
                                <p class="text-xs text-purple-400 mt-2"><?php echo date('d.m.Y H:i', strtotime($announcement['created_at'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
