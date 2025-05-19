<?php
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Check if user is admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$is_admin = $stmt->fetchColumn();

if (!$is_admin) {
    header("Location: ../index.php");
    exit;
}

// Get user ID from URL
$user_id = $_GET['id'] ?? 0;

// Get user details
$stmt = $conn->prepare("
    SELECT username, email, balance, is_blocked, created_at 
    FROM users 
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: users.php");
    exit;
}

// Get user's purchases
$stmt = $conn->prepare("
    SELECT 
        p.*, 
        pr.name as product_name,
        pr.description,
        pr.price,
        pr.type as product_type,
        pr.stock,
        pr.stock_messages
    FROM purchases p
    JOIN products pr ON p.product_id = pr.id
    WHERE p.user_id = ?
    ORDER BY p.purchase_date DESC
");
$stmt->execute([$user_id]);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kullanıcı Detayları - Panel</title>
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
                    <a href="users.php" class="text-purple-300 hover:text-purple-100 transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kullanıcılara Dön
                    </a>
                    <span class="text-xl font-bold text-purple-300">Kullanıcı Detayları</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- User Details -->
        <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6 mb-8">
            <h2 class="text-2xl font-semibold mb-4">Kullanıcı Bilgileri</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-purple-300">Kullanıcı Adı:</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                <div>
                    <p class="text-purple-300">E-posta:</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div>
                    <p class="text-purple-300">Bakiye:</p>
                    <p class="font-semibold">₺<?php echo number_format($user['balance'], 2); ?></p>
                </div>
                <div>
                    <p class="text-purple-300">Durum:</p>
                    <p class="font-semibold"><?php echo $user['is_blocked'] ? 'Engelli' : 'Aktif'; ?></p>
                </div>
                <div>
                    <p class="text-purple-300">Kayıt Tarihi:</p>
                    <p class="font-semibold"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- User's Purchases -->
        <div id="purchases" class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
            <h2 class="text-2xl font-semibold mb-4">Satın Alımlar</h2>
            <?php if (empty($purchases)): ?>
                <p class="text-purple-300">Henüz satın alım yapılmamış.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($purchases as $purchase): ?>
                        <div class="bg-purple-800/30 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-lg font-semibold">
                                    <?php echo htmlspecialchars($purchase['product_name']); ?>
                                </h3>
                                <span class="text-sm text-purple-400">
                                    <?php echo date('d.m.Y H:i', strtotime($purchase['purchase_date'])); ?>
                                </span>
                            </div>
                            
                            <?php if ($purchase['description']): ?>
                                <p class="text-sm text-purple-300 mb-2">
                                    <?php echo nl2br(htmlspecialchars($purchase['description'])); ?>
                                </p>
                            <?php endif; ?>

                            <div class="grid grid-cols-2 gap-4 mb-2">
                                <div>
                                    <span class="text-sm text-purple-300">Fiyat:</span>
                                    <span class="ml-2">₺<?php echo number_format($purchase['price'], 2); ?></span>
                                </div>
                                <div>
                                    <span class="text-sm text-purple-300">Tür:</span>
                                    <span class="ml-2"><?php echo $purchase['product_type'] === 'regular' ? 'Ürün' : 'Yazılım'; ?></span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center mb-2">
                                <a href="manage_purchase.php?id=<?php echo $purchase['id']; ?>" 
                                   class="text-purple-400 hover:text-purple-300 transition">
                                    <i class="fas fa-edit mr-1"></i>
                                    Teslimatı Yönet
                                </a>
                            </div>

                            <?php if ($purchase['code_or_key'] || $purchase['delivery_message']): ?>
                                <div class="bg-purple-900/50 rounded p-3 mb-2">
                                    <?php if ($purchase['code_or_key']): ?>
                                        <div class="mb-2">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-sm text-purple-300">Kod/Anahtar:</span>
                                                <button onclick="copyToClipboard('<?php echo htmlspecialchars($purchase['code_or_key']); ?>')"
                                                        class="text-purple-400 hover:text-purple-300 transition">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                            </div>
                                            <code class="block text-purple-100 font-mono text-sm break-all">
                                                <?php echo htmlspecialchars($purchase['code_or_key']); ?>
                                            </code>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($purchase['delivery_message']): ?>
                                        <div>
                                            <span class="text-sm text-purple-300 block mb-1">Teslimat Mesajı:</span>
                                            <p class="text-purple-100 text-sm">
                                                <?php echo nl2br(htmlspecialchars($purchase['delivery_message'])); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                                $stock_messages = json_decode($purchase['stock_messages'], true);
                                $stock_index = $purchase['stock'] > 0 ? $purchase['stock'] - 1 : 0;
                                $stock_message = $stock_messages[$stock_index] ?? null;
                                if ($stock_message):
                            ?>
                                <div class="text-sm">
                                    <span class="text-purple-300">Stok Mesajı:</span>
                                    <span class="ml-2"><?php echo htmlspecialchars($stock_message); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Kod kopyalandı!');
            }).catch(err => {
                console.error('Kopyalama başarısız:', err);
            });
        }
    </script>
</body>
</html>
