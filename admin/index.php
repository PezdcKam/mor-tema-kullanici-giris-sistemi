<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit;
}

// Get admin information
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ? AND is_admin = 1");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'users' => $conn->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn(),
    'products' => $conn->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'software' => $conn->query("SELECT COUNT(*) FROM software")->fetchColumn(),
    'purchases' => $conn->query("SELECT COUNT(*) FROM purchases")->fetchColumn(),
    'total_balance' => $conn->query("SELECT SUM(balance) FROM users")->fetchColumn(),
    'total_sales' => $conn->query("SELECT COUNT(*) FROM purchases WHERE status = 'completed'")->fetchColumn(),
];

// Get recent activities
$stmt = $conn->prepare("
    SELECT 
        p.*, 
        u.username,
        pr.name as product_name,
        pr.price
    FROM purchases p
    JOIN users u ON p.user_id = u.id
    JOIN products pr ON p.product_id = pr.id
    ORDER BY p.purchase_date DESC
    LIMIT 5
");
$stmt->execute();
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Panel</title>
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
                    <span class="text-xl font-bold text-purple-300">Admin Panel</span>
                    <div class="hidden md:flex space-x-4">
                        <a href="users.php" class="text-purple-300 hover:text-purple-100 px-3 py-2 rounded-md transition">
                            <i class="fas fa-users mr-2"></i>Kullanıcılar
                        </a>
                        <a href="products.php" class="text-purple-300 hover:text-purple-100 px-3 py-2 rounded-md transition">
                            <i class="fas fa-box mr-2"></i>Ürünler
                        </a>
                        <a href="software.php" class="text-purple-300 hover:text-purple-100 px-3 py-2 rounded-md transition">
                            <i class="fas fa-laptop-code mr-2"></i>Yazılımlar
                        </a>
                        <a href="announcements.php" class="text-purple-300 hover:text-purple-100 px-3 py-2 rounded-md transition">
                            <i class="fas fa-bullhorn mr-2"></i>Duyurular
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-purple-300">
                        <i class="fas fa-user-shield mr-2"></i>
                        <?php echo htmlspecialchars($admin['username']); ?>
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
        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-300">Toplam Kullanıcı</p>
                        <h3 class="text-3xl font-bold"><?php echo number_format($stats['users']); ?></h3>
                    </div>
                    <div class="p-3 bg-purple-700/50 rounded-lg">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-300">Toplam Satış</p>
                        <h3 class="text-3xl font-bold"><?php echo number_format($stats['total_sales']); ?></h3>
                    </div>
                    <div class="p-3 bg-purple-700/50 rounded-lg">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-300">Toplam Bakiye</p>
                        <h3 class="text-3xl font-bold">₺<?php echo number_format($stats['total_balance'], 2); ?></h3>
                    </div>
                    <div class="p-3 bg-purple-700/50 rounded-lg">
                        <i class="fas fa-coins text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Son Aktiviteler</h2>
            <?php if (empty($recent_activities)): ?>
                <p class="text-purple-300">Henüz aktivite bulunmamaktadır.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="flex items-center justify-between p-4 bg-purple-800/30 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="p-2 bg-purple-700/50 rounded-lg">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">
                                        <?php echo htmlspecialchars($activity['username']); ?>
                                    </p>
                                    <p class="text-sm text-purple-300">
                                        <?php echo htmlspecialchars($activity['product_name']); ?>
                                        - ₺<?php echo number_format($activity['price'], 2); ?>
                                    </p>
                                </div>
                            </div>
                            <span class="text-sm text-purple-400">
                                <?php echo date('d.m.Y H:i', strtotime($activity['purchase_date'])); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <a href="products.php?action=new" class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl p-6 rounded-xl border border-purple-600/50 hover:border-purple-500/50 transition group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-700/50 rounded-lg group-hover:bg-purple-600/50 transition">
                        <i class="fas fa-plus text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Yeni Ürün</h3>
                        <p class="text-purple-300 text-sm">Ürün ekle</p>
                    </div>
                </div>
            </a>

            <a href="software.php?action=new" class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl p-6 rounded-xl border border-purple-600/50 hover:border-purple-500/50 transition group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-700/50 rounded-lg group-hover:bg-purple-600/50 transition">
                        <i class="fas fa-laptop-code text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Yeni Yazılım</h3>
                        <p class="text-purple-300 text-sm">Yazılım ekle</p>
                    </div>
                </div>
            </a>

            <a href="announcements.php?action=new" class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl p-6 rounded-xl border border-purple-600/50 hover:border-purple-500/50 transition group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-700/50 rounded-lg group-hover:bg-purple-600/50 transition">
                        <i class="fas fa-bullhorn text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Yeni Duyuru</h3>
                        <p class="text-purple-300 text-sm">Duyuru ekle</p>
                    </div>
                </div>
            </a>

            <a href="users.php" class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl p-6 rounded-xl border border-purple-600/50 hover:border-purple-500/50 transition group">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-700/50 rounded-lg group-hover:bg-purple-600/50 transition">
                        <i class="fas fa-users-cog text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Kullanıcılar</h3>
                        <p class="text-purple-300 text-sm">Kullanıcıları yönet</p>
                    </div>
                </div>
            </a>
        </div>
    </main>
</body>
</html>
