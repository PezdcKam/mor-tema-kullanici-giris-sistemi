<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit;
}

// Handle software actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $conn->prepare("
                    INSERT INTO software (name, description, version, file_path) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['version'],
                    $_POST['file_path']
                ]);

                // Also add as a product
                $software_id = $conn->lastInsertId();
                $stmt = $conn->prepare("
                    INSERT INTO products (name, description, price, type, download_link) 
                    VALUES (?, ?, ?, 'software', ?)
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['price'],
                    $_POST['file_path']
                ]);
                
                $success = "Yazılım başarıyla eklendi.";
                break;

            case 'edit':
                $stmt = $conn->prepare("
                    UPDATE software 
                    SET name = ?, description = ?, version = ?, file_path = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['version'],
                    $_POST['file_path'],
                    $_POST['software_id']
                ]);

                // Update product info
                $stmt = $conn->prepare("
                    UPDATE products 
                    SET name = ?, description = ?, price = ?, download_link = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['price'],
                    $_POST['file_path'],
                    $_POST['product_id']
                ]);
                
                $success = "Yazılım başarıyla güncellendi.";
                break;

            case 'delete':
                $stmt = $conn->prepare("DELETE FROM software WHERE id = ?");
                $stmt->execute([$_POST['software_id']]);
                
                // Delete associated product
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$_POST['product_id']]);
                
                $success = "Yazılım başarıyla silindi.";
                break;
        }
    }
}

// Get all software with download statistics
$stmt = $conn->prepare("
    SELECT 
        s.*,
        p.id as product_id,
        p.price,
        COUNT(pu.id) as total_purchases
    FROM software s
    LEFT JOIN products p ON p.name = s.name AND p.type = 'software'
    LEFT JOIN purchases pu ON pu.product_id = p.id
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
$stmt->execute();
$software_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Yazılım Yönetimi - Admin Panel</title>
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
                    <span class="text-xl font-bold text-purple-300">Yazılım Yönetimi</span>
                </div>
                <button onclick="openAddModal()"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>
                    Yeni Yazılım
                </button>
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

        <!-- Software Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($software_list as $software): ?>
                <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-semibold">
                                <?php echo htmlspecialchars($software['name']); ?>
                            </h3>
                            <span class="inline-block px-2 py-1 bg-purple-700/50 rounded text-sm mt-1">
                                v<?php echo htmlspecialchars($software['version']); ?>
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($software)); ?>)"
                                    class="p-2 text-purple-300 hover:text-purple-100 transition">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('Bu yazılımı silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="software_id" value="<?php echo $software['id']; ?>">
                                <input type="hidden" name="product_id" value="<?php echo $software['product_id']; ?>">
                                <button type="submit" class="p-2 text-purple-300 hover:text-red-400 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <p class="text-purple-300 mb-4">
                        <?php echo nl2br(htmlspecialchars($software['description'])); ?>
                    </p>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-purple-800/30 p-3 rounded-lg">
                            <div class="text-sm text-purple-400">Fiyat</div>
                            <div class="font-semibold">₺<?php echo number_format($software['price'], 2); ?></div>
                        </div>
                        <div class="bg-purple-800/30 p-3 rounded-lg">
                            <div class="text-sm text-purple-400">İndirme</div>
                            <div class="font-semibold"><?php echo number_format($software['download_count']); ?></div>
                        </div>
                    </div>

                    <div class="bg-purple-800/30 p-3 rounded-lg mb-4">
                        <div class="text-sm text-purple-400 mb-1">İndirme Linki</div>
                        <div class="font-mono text-sm break-all">
                            <?php echo htmlspecialchars($software['file_path']); ?>
                        </div>
                    </div>

                    <div class="text-sm text-purple-400">
                        <i class="fas fa-clock mr-1"></i>
                        Eklenme: <?php echo date('d.m.Y H:i', strtotime($software['created_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($software_list)): ?>
            <div class="text-center text-purple-300 py-12">
                <i class="fas fa-laptop-code text-4xl mb-4"></i>
                <p>Henüz yazılım bulunmamaktadır.</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Add/Edit Software Modal -->
    <div id="softwareModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gradient-to-br from-purple-900/90 to-purple-800/90 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6 max-w-md w-full">
                <h3 id="modalTitle" class="text-xl font-semibold mb-4">Yeni Yazılım</h3>
                <form method="POST">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="software_id" id="softwareId">
                    <input type="hidden" name="product_id" id="productId">
                    
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-purple-300 mb-2">
                                Yazılım Adı
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
                                <label for="version" class="block text-sm font-medium text-purple-300 mb-2">
                                    Versiyon
                                </label>
                                <input type="text" 
                                       id="version" 
                                       name="version" 
                                       required
                                       placeholder="1.0.0"
                                       class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                            </div>

                            <div>
                                <label for="price" class="block text-sm font-medium text-purple-300 mb-2">
                                    Fiyat (₺)
                                </label>
                                <input type="number" 
                                       id="price" 
                                       name="price" 
                                       step="0.01"
                                       min="0"
                                       value="0"
                                       required
                                       class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                            </div>
                        </div>

                        <div>
                            <label for="file_path" class="block text-sm font-medium text-purple-300 mb-2">
                                İndirme Linki
                            </label>
                            <input type="text" 
                                   id="file_path" 
                                   name="file_path" 
                                   required
                                   placeholder="https://example.com/download/software.zip"
                                   class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button"
                                onclick="closeSoftwareModal()"
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
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Yeni Yazılım';
            document.getElementById('formAction').value = 'add';
            document.getElementById('softwareId').value = '';
            document.getElementById('productId').value = '';
            document.getElementById('name').value = '';
            document.getElementById('description').value = '';
            document.getElementById('version').value = '';
            document.getElementById('price').value = '';
            document.getElementById('file_path').value = '';
            document.getElementById('softwareModal').classList.remove('hidden');
        }

        function openEditModal(software) {
            document.getElementById('modalTitle').textContent = 'Yazılımı Düzenle';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('softwareId').value = software.id;
            document.getElementById('productId').value = software.product_id;
            document.getElementById('name').value = software.name;
            document.getElementById('description').value = software.description;
            document.getElementById('version').value = software.version;
            document.getElementById('price').value = software.price;
            document.getElementById('file_path').value = software.file_path;
            document.getElementById('softwareModal').classList.remove('hidden');
        }

        function closeSoftwareModal() {
            document.getElementById('softwareModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('softwareModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSoftwareModal();
            }
        });
    </script>
</body>
</html>
