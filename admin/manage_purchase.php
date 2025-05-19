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

// Get purchase details
$purchase_id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("
    SELECT 
        p.*,
        u.username,
        pr.name as product_name,
        pr.description,
        pr.price,
        pr.type as product_type
    FROM purchases p
    JOIN users u ON p.user_id = u.id
    JOIN products pr ON p.product_id = pr.id
    WHERE p.id = ?
");
$stmt->execute([$purchase_id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    header("Location: users.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("
        UPDATE purchases 
        SET code_or_key = ?, delivery_message = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $_POST['code_or_key'],
        $_POST['delivery_message'],
        $purchase_id
    ]);
    $success = "Teslimat bilgileri güncellendi.";
    
    // Refresh purchase data
    $stmt->execute([$purchase_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Satın Alma Yönetimi - Panel</title>
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
                    <a href="user_details.php?id=<?php echo $purchase['user_id']; ?>" class="text-purple-300 hover:text-purple-100 transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kullanıcı Detaylarına Dön
                    </a>
                    <span class="text-xl font-bold text-purple-300">Satın Alma Yönetimi</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-200 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Purchase Details -->
        <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6 mb-8">
            <h2 class="text-2xl font-semibold mb-4">Satın Alma Detayları</h2>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-purple-300">Kullanıcı:</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($purchase['username']); ?></p>
                </div>
                <div>
                    <p class="text-purple-300">Ürün:</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($purchase['product_name']); ?></p>
                </div>
                <div>
                    <p class="text-purple-300">Fiyat:</p>
                    <p class="font-semibold">₺<?php echo number_format($purchase['price'], 2); ?></p>
                </div>
                <div>
                    <p class="text-purple-300">Tarih:</p>
                    <p class="font-semibold"><?php echo date('d.m.Y H:i', strtotime($purchase['purchase_date'])); ?></p>
                </div>
            </div>

            <!-- Delivery Form -->
            <form method="POST" class="space-y-6">
                <div>
                    <label for="code_or_key" class="block text-sm font-medium text-purple-300 mb-2">
                        Kod/Anahtar
                    </label>
                    <input type="text" 
                           id="code_or_key" 
                           name="code_or_key" 
                           value="<?php echo htmlspecialchars($purchase['code_or_key'] ?? ''); ?>"
                           class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2"
                           placeholder="Ürün kodu veya anahtarı">
                </div>

                <div>
                    <label for="delivery_message" class="block text-sm font-medium text-purple-300 mb-2">
                        Teslimat Mesajı
                    </label>
                    <textarea id="delivery_message" 
                              name="delivery_message" 
                              rows="4"
                              class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2"
                              placeholder="Kullanıcıya özel teslimat mesajı"><?php echo htmlspecialchars($purchase['delivery_message'] ?? ''); ?></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-6 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg transition">
                        Güncelle
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
