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

// Get all software products
$stmt = $conn->prepare("
    SELECT s.*, 
           CASE WHEN p.id IS NOT NULL THEN true ELSE false END as purchased
    FROM software s
    LEFT JOIN purchases p ON p.product_id = s.id AND p.user_id = ?
    ORDER BY s.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$software = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle download request
if (isset($_GET['download']) && isset($_GET['id'])) {
    $software_id = $_GET['id'];
    
    // Check if software is free or user has purchased it
    $stmt = $conn->prepare("
        SELECT s.*, p.price FROM software s
        LEFT JOIN products p ON p.name = s.name AND p.type = 'software'
        LEFT JOIN purchases pu ON pu.product_id = p.id AND pu.user_id = ?
        WHERE s.id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $software_id]);
    $software = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($software && ($software['price'] == 0 || !empty($software['pu']))) {
        // Update download count
        $stmt = $conn->prepare("UPDATE software SET download_count = download_count + 1 WHERE id = ?");
        $stmt->execute([$software_id]);
        
        // Redirect to download file (you'll need to implement actual file download)
        header("Location: " . $software['file_path']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Yazılımlar</title>
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
                    <span class="text-xl font-bold text-purple-300">Yazılımlar</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (empty($software)): ?>
            <div class="text-center text-purple-300 py-12">
                <i class="fas fa-laptop-code text-4xl mb-4"></i>
                <p>Henüz yazılım bulunmamaktadır.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($software as $item): ?>
                    <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <span class="px-3 py-1 bg-purple-700/50 rounded-full text-sm">
                                v<?php echo htmlspecialchars($item['version']); ?>
                            </span>
                        </div>
                        
                        <p class="text-purple-300 mb-4">
                            <?php echo htmlspecialchars($item['description']); ?>
                        </p>
                        
                        <div class="flex items-center justify-between text-sm text-purple-400 mb-4">
                            <span>
                                <i class="fas fa-download mr-1"></i>
                                <?php echo number_format($item['download_count']); ?> indirme
                            </span>
                            <span>
                                <i class="fas fa-calendar-alt mr-1"></i>
                                <?php echo date('d.m.Y', strtotime($item['created_at'])); ?>
                            </span>
                        </div>

                        <?php if ($item['purchased']): ?>
                            <a href="?download=true&id=<?php echo $item['id']; ?>"
                               class="block w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-center rounded-lg transition focus:outline-none focus:ring-2 focus:ring-purple-400">
                                <i class="fas fa-download mr-2"></i>
                                İndir
                            </a>
                        <?php else: ?>
                            <div class="text-center py-2 bg-purple-800/30 text-purple-400 rounded-lg cursor-not-allowed">
                                <i class="fas fa-lock mr-2"></i>
                                Satın Alınmamış
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- System Requirements Section -->
        <div class="mt-12">
            <h2 class="text-2xl font-semibold mb-6">Sistem Gereksinimleri</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-purple-700/50 rounded-lg">
                            <i class="fas fa-microchip text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">İşlemci</h3>
                            <p class="text-sm text-purple-300">Intel Core i3 veya üzeri</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-purple-700/50 rounded-lg">
                            <i class="fas fa-memory text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">RAM</h3>
                            <p class="text-sm text-purple-300">4 GB minimum</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-purple-700/50 rounded-lg">
                            <i class="fas fa-hdd text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">Depolama</h3>
                            <p class="text-sm text-purple-300">2 GB boş alan</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-purple-700/50 rounded-lg">
                            <i class="fas fa-desktop text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold">İşletim Sistemi</h3>
                            <p class="text-sm text-purple-300">Windows 10 64-bit</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
