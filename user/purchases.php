<?php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Get user's information
$stmt = $conn->prepare("SELECT username, balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all purchases with product details
$stmt = $conn->prepare("
    SELECT p.*, pr.name as product_name, pr.type as product_type, pr.description, pr.stock_messages
    FROM purchases p
    JOIN products pr ON p.product_id = pr.id
    WHERE p.user_id = ?
    ORDER BY p.purchase_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group purchases by type
$regular_purchases = array_filter($purchases, function($p) { return $p['product_type'] == 'regular'; });
$software_purchases = array_filter($purchases, function($p) { return $p['product_type'] == 'software'; });
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Satın Alınanlar</title>
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
                    <span class="text-xl font-bold text-purple-300">Satın Alınanlar</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (empty($purchases)): ?>
            <div class="text-center text-purple-300 py-12">
                <i class="fas fa-shopping-bag text-4xl mb-4"></i>
                <p>Henüz satın alım yapılmamış.</p>
            </div>
        <?php else: ?>
            <!-- Regular Products -->
            <?php if (!empty($regular_purchases)): ?>
                <div class="mb-12">
                    <h2 class="text-2xl font-semibold mb-6">Ürünler</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($regular_purchases as $purchase): ?>
                            <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="text-lg font-semibold">
                                        <?php echo htmlspecialchars($purchase['product_name']); ?>
                                    </h3>
                                    <span class="text-sm text-purple-400">
                                        <?php echo date('d.m.Y', strtotime($purchase['purchase_date'])); ?>
                                    </span>
                                </div>
                                
                                <?php if ($purchase['description']): ?>
                                    <p class="text-sm text-purple-300 mb-4">
                                        <?php echo htmlspecialchars($purchase['description']); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ($purchase['delivery_message']): ?>
                                    <div class="bg-purple-800/30 p-4 rounded-lg">
                                        <div>
                                            <span class="text-sm text-purple-300 block mb-2">Stok Mesajı:</span>
                                            <p class="text-purple-100 text-sm">
                                                <?php echo nl2br(htmlspecialchars($purchase['delivery_message'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Software Products -->
            <?php if (!empty($software_purchases)): ?>
                <div>
                    <h2 class="text-2xl font-semibold mb-6">Yazılımlar</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($software_purchases as $purchase): ?>
                            <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="text-lg font-semibold">
                                        <?php echo htmlspecialchars($purchase['product_name']); ?>
                                    </h3>
                                    <span class="text-sm text-purple-400">
                                        <?php echo date('d.m.Y', strtotime($purchase['purchase_date'])); ?>
                                    </span>
                                </div>

                                <?php if ($purchase['description']): ?>
                                    <p class="text-sm text-purple-300 mb-4">
                                        <?php echo htmlspecialchars($purchase['description']); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="space-y-4">
                                    <?php if ($purchase['delivery_message']): ?>
                                        <div class="bg-purple-800/30 p-4 rounded-lg mb-4">
                                            <div>
                                                <span class="text-sm text-purple-300 block mb-2">Stok Mesajı:</span>
                                                <p class="text-purple-100 text-sm">
                                                    <?php echo nl2br(htmlspecialchars($purchase['delivery_message'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <a href="software.php?download=true&id=<?php echo $purchase['product_id']; ?>"
                                       class="block w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-center rounded-lg transition focus:outline-none focus:ring-2 focus:ring-purple-400">
                                        <i class="fas fa-download mr-2"></i>
                                        İndir
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // You could add a toast notification here
                alert('Kod kopyalandı!');
            }).catch(err => {
                console.error('Kopyalama başarısız:', err);
            });
        }
    </script>
</body>
</html>
