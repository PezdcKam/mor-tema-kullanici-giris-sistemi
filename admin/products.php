<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $stock_messages = $_POST['stock_messages'] ?? [];
        // Debug: display submitted stock messages
        error_log('Submitted stock_messages: ' . print_r($stock_messages, true));
        // Optionally display on page for debugging (remove after fix)
        echo '<pre>Submitted stock_messages: ' . htmlspecialchars(json_encode($stock_messages)) . '</pre>';

        $stock_messages_json = json_encode($stock_messages);

        if ($_POST['action'] === 'add') {
            $stmt = $conn->prepare("
                INSERT INTO products (name, description, price, type, stock, stock_messages) 
                VALUES (?, ?, ?, 'regular', ?, ?)
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['stock'],
                $stock_messages_json
            ]);
            header("Location: products.php?success=add");
            // Debug: fetch and log saved stock_messages
            $lastId = $conn->lastInsertId();
            $stmtDebug = $conn->prepare("SELECT stock_messages FROM products WHERE id = ?");
            $stmtDebug->execute([$lastId]);
            $savedMessages = $stmtDebug->fetchColumn();
            error_log("Saved stock_messages after insert: " . $savedMessages);
            exit;
        } else {
            $stmt = $conn->prepare("
                UPDATE products 
                SET name = ?, description = ?, price = ?, stock = ?, stock_messages = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['price'],
                $_POST['stock'],
                $stock_messages_json,
                $_POST['product_id']
            ]);
            // Debug: fetch and log saved stock_messages
            $stmtDebug = $conn->prepare("SELECT stock_messages FROM products WHERE id = ?");
            $stmtDebug->execute([$_POST['product_id']]);
            $savedMessages = $stmtDebug->fetchColumn();
            error_log("Saved stock_messages after update: " . $savedMessages);
            header("Location: products.php?success=edit");
            exit;
        }
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$_POST['product_id']]);
        $success = "Ürün başarıyla silindi.";
    }
}

// Get all products with sales statistics
$stmt = $conn->prepare("
    SELECT 
        p.*,
        COUNT(pu.id) as total_sales,
        SUM(CASE WHEN pu.id IS NOT NULL THEN p.price ELSE 0 END) as total_revenue
    FROM products p
    LEFT JOIN purchases pu ON p.id = pu.product_id
    WHERE p.type = 'regular'
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ürün Yönetimi - Admin Panel</title>
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
                    <span class="text-xl font-bold text-purple-300">Ürün Yönetimi</span>
                </div>
                <button onclick="openAddModal()"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>
                    Yeni Ürün
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-200 px-4 py-3 rounded-lg mb-6">
                <?php 
                    if ($_GET['success'] === 'add') {
                        echo "Ürün başarıyla eklendi.";
                    } elseif ($_GET['success'] === 'edit') {
                        echo "Ürün başarıyla güncellendi.";
                    } elseif ($_GET['success'] === 'delete') {
                        echo "Ürün başarıyla silindi.";
                    }
                ?>
            </div>
        <?php endif; ?>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="flex items-center space-x-2">
<button data-product='<?php echo json_encode($product, JSON_HEX_APOS | JSON_HEX_QUOT); ?>' onclick="openEditModalFromData(this)"
        class="p-2 text-purple-300 hover:text-purple-100 transition">
    <i class="fas fa-edit"></i>
</button>

<script>
function openEditModalFromData(button) {
    const productJson = button.getAttribute('data-product');
    try {
        const product = JSON.parse(productJson);
        openEditModal(product);
    } catch (e) {
        console.error('Failed to parse product JSON:', e);
    }
}
</script>
                            <form method="POST" class="inline" onsubmit="return confirm('Bu ürünü silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="p-2 text-purple-300 hover:text-red-400 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <p class="text-purple-300 mb-4">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-purple-800/30 p-3 rounded-lg">
                            <div class="text-sm text-purple-400">Fiyat</div>
                            <div class="font-semibold">₺<?php echo number_format($product['price'], 2); ?></div>
                        </div>
                        <div class="bg-purple-800/30 p-3 rounded-lg">
                            <div class="text-sm text-purple-400">Stok</div>
                            <div class="font-semibold"><?php echo number_format($product['stock']); ?></div>
                        </div>
                    </div>

                    <?php if (!empty($product['stock_messages'])): ?>
                        <div class="bg-purple-800/30 p-3 rounded-lg mb-4">
                            <div class="text-sm text-purple-400 mb-2">Stok Mesajları</div>
                            <?php 
                            $messages = json_decode($product['stock_messages'], true);
                            if (is_array($messages)):
                                foreach ($messages as $index => $message): 
                            ?>
                                <div class="text-sm mb-1">
                                    <span class="text-purple-400">Stok <?php echo $index + 1; ?>:</span>
                                    <span class="text-purple-100"><?php echo htmlspecialchars($message); ?></span>
                                </div>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-purple-800/30 p-3 rounded-lg">
                            <div class="text-sm text-purple-400">Toplam Satış</div>
                            <div class="font-semibold"><?php echo number_format($product['total_sales']); ?></div>
                        </div>
                        <div class="bg-purple-800/30 p-3 rounded-lg">
                            <div class="text-sm text-purple-400">Toplam Gelir</div>
                            <div class="font-semibold">₺<?php echo number_format($product['total_revenue'], 2); ?></div>
                        </div>
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

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gradient-to-br from-purple-900/90 to-purple-800/90 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6 max-w-md w-full">
                <h3 id="modalTitle" class="text-xl font-semibold mb-4">Yeni Ürün</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="product_id" id="productId">
                    
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-purple-300 mb-2">
                                Ürün Adı
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   required
                                   class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-purple-300 mb-2">
                                Açıklama
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="3"
                                      class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="price" class="block text-sm font-medium text-purple-300 mb-2">
                                    Fiyat (₺)
                                </label>
                                <input type="number" 
                                       id="price" 
                                       name="price" 
                                       step="0.01"
                                       required
                                       class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                            </div>

                            <div>
                                <label for="stock" class="block text-sm font-medium text-purple-300 mb-2">
                                    Stok
                                </label>
                                <input type="number" 
                                       id="stock" 
                                       name="stock" 
                                       required
                                       onchange="updateStockMessages(this.value)"
                                       class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                            </div>
                        </div>

                        <div id="stockMessagesContainer" class="space-y-4">
                            <label class="block text-sm font-medium text-purple-300 mb-2">
                                Stok Mesajları
                            </label>
                            <div id="stockMessages" class="space-y-2">
                                <!-- Stock messages will be dynamically added here -->
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button"
                                onclick="closeProductModal()"
                                class="px-4 py-2 bg-purple-800/50 hover:bg-purple-700/50 rounded-lg transition">
                            İptal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg transition">
                            Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateStockMessages(stockCount) {
            const container = document.getElementById('stockMessages');
            container.innerHTML = '';
            
            for (let i = 1; i <= stockCount; i++) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'flex items-center space-x-2';
                messageDiv.innerHTML = `
                    <input type="text"
                           name="stock_messages[]"
                           placeholder="Stok ${i} için mesaj"
                           class="flex-1 rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                `;
                container.appendChild(messageDiv);
            }
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Yeni Ürün';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productId').value = '';
            document.getElementById('name').value = '';
            document.getElementById('description').value = '';
            document.getElementById('price').value = '';
            document.getElementById('stock').value = '';
            document.getElementById('stockMessages').innerHTML = '';
            document.getElementById('productModal').classList.remove('hidden');
        }

        function openEditModal(product) {
            document.getElementById('modalTitle').textContent = 'Ürünü Düzenle';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('productId').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('description').value = product.description;
            document.getElementById('price').value = product.price;
            document.getElementById('stock').value = product.stock;

            // Build stock messages input fields based on saved messages if available,
            // otherwise use the stock count as a fallback.
            let messages = [];
            try {
                messages = product.stock_messages ? JSON.parse(product.stock_messages) : [];
            } catch (e) {
                console.error('Error parsing stock messages JSON', e);
            }
            const count = messages.length > 0 ? messages.length : product.stock;
            updateStockMessages(count);  // Update to create 'count' number of inputs

            const inputs = document.getElementsByName('stock_messages[]');
            messages.forEach((message, index) => {
                if (inputs[index]) {
                    inputs[index].value = message;
                }
            });

            document.getElementById('productModal').classList.remove('hidden');
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProductModal();
            }
        });
    </script>
</body>
</html>
