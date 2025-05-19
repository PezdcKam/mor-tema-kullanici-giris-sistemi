<?php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Get user's balance
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    
    // Get product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $quantity <= $product['stock'] && $user['balance'] >= $product['price'] * $quantity) {
        try {
            $conn->beginTransaction();
            
            // Deduct balance
            $total_price = $product['price'] * $quantity;
            $new_balance = $user['balance'] - $total_price;
            $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $stmt->execute([$new_balance, $_SESSION['user_id']]);
            
            // Get stock messages
            $stock_messages = json_decode($product['stock_messages'], true);
            if (!is_array($stock_messages)) {
                $stock_messages = [];
            }

            // Get current stock count to determine which messages to use
            $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $current_stock = $stmt->fetch(PDO::FETCH_COLUMN);

            // Generate purchase records with stock messages
            for ($i = 0; $i < $quantity; $i++) {
                // Calculate which message to use based on current stock position
                $message_index = ($current_stock - $i - 1);
                $stock_message = isset($stock_messages[$message_index]) ? $stock_messages[$message_index] : null;
                
                $stmt = $conn->prepare("INSERT INTO purchases (user_id, product_id, delivery_message) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $product_id, $stock_message]);
            }
            
            // Reduce product stock
            $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$quantity, $product_id]);
            
            $conn->commit();
            $success = "Ürün başarıyla satın alındı! Toplam kod sayısı: " . $quantity;
            
            // Update user's balance in the session
            $user['balance'] = $new_balance;
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "İşlem sırasında bir hata oluştu.";
        }
    } else {
        $error = "Yetersiz bakiye, stok veya ürün bulunamadı.";
    }
}

// Get all products
$stmt = $conn->prepare("SELECT * FROM products WHERE type = 'regular' ORDER BY price ASC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ürünler</title>
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
                    <span class="text-xl font-bold text-purple-300">Ürünler</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-purple-300">
                        <i class="fas fa-coins mr-2"></i>
                        Bakiye: <?php echo number_format($user['balance'], 2); ?> ₺
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($error)): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-200 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-200 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                    <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="text-purple-300 mb-4"><?php echo htmlspecialchars($product['description']); ?></p>
                    <div class="flex items-center justify-between space-x-4">
                        <div>
                            <span class="text-lg font-semibold text-purple-300">
                                <?php echo number_format($product['price'], 2); ?> ₺
                            </span>
                            <p class="text-sm text-purple-400">Stok: <?php echo $product['stock']; ?></p>
                        </div>
                        <form method="POST" class="inline flex items-center space-x-2">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="number" name="quantity" min="1" max="<?php echo $product['stock']; ?>" value="1" class="w-16 rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-2 py-1" required>
                            <button type="submit" 
                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-purple-400"
                                    <?php echo ($user['balance'] < $product['price']) ? 'disabled' : ''; ?>>
                                <?php if ($user['balance'] >= $product['price']): ?>
                                    Satın Al
                                <?php else: ?>
                                    Yetersiz Bakiye
                                <?php endif; ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center text-purple-300 py-12">
                <i class="fas fa-box-open text-4xl mb-4"></i>
                <p>Henüz ürün bulunmamaktadır.</p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
