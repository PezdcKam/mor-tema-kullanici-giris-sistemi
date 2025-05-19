<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php");
    exit;
}

// Handle announcement actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $conn->prepare("
                    INSERT INTO announcements (title, content) 
                    VALUES (?, ?)
                ");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['content']
                ]);
                $success = "Duyuru başarıyla eklendi.";
                break;

            case 'edit':
                $stmt = $conn->prepare("
                    UPDATE announcements 
                    SET title = ?, content = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['content'],
                    $_POST['announcement_id']
                ]);
                $success = "Duyuru başarıyla güncellendi.";
                break;

            case 'delete':
                $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
                $stmt->execute([$_POST['announcement_id']]);
                $success = "Duyuru başarıyla silindi.";
                break;
        }
    }
}

// Get all announcements
$stmt = $conn->prepare("SELECT * FROM announcements ORDER BY created_at DESC");
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Duyuru Yönetimi - Admin Panel</title>
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
                    <span class="text-xl font-bold text-purple-300">Duyuru Yönetimi</span>
                </div>
                <button onclick="openAddModal()"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>
                    Yeni Duyuru
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

        <!-- Announcements List -->
        <div class="space-y-6">
            <?php foreach ($announcements as $announcement): ?>
                <div class="bg-gradient-to-br from-purple-900/70 to-purple-800/60 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-semibold">
                                <?php echo htmlspecialchars($announcement['title']); ?>
                            </h3>
                            <p class="text-sm text-purple-400">
                                <?php echo date('d.m.Y H:i', strtotime($announcement['created_at'])); ?>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($announcement)); ?>)"
                                    class="p-2 text-purple-300 hover:text-purple-100 transition">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('Bu duyuruyu silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                <button type="submit" class="p-2 text-purple-300 hover:text-red-400 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="prose prose-invert max-w-none">
                        <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($announcements)): ?>
                <div class="text-center text-purple-300 py-12">
                    <i class="fas fa-bullhorn text-4xl mb-4"></i>
                    <p>Henüz duyuru bulunmamaktadır.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add/Edit Announcement Modal -->
    <div id="announcementModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gradient-to-br from-purple-900/90 to-purple-800/90 backdrop-blur-xl rounded-xl border border-purple-600/50 p-6 max-w-md w-full">
                <h3 id="modalTitle" class="text-xl font-semibold mb-4">Yeni Duyuru</h3>
                <form method="POST">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="announcement_id" id="announcementId">
                    
                    <div class="space-y-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-purple-300 mb-2">
                                Başlık
                            </label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   required
                                   class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2">
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium text-purple-300 mb-2">
                                İçerik
                            </label>
                            <textarea id="content" 
                                      name="content" 
                                      rows="6"
                                      required
                                      class="w-full rounded-lg bg-purple-800/50 border border-purple-500 text-purple-100 placeholder-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 px-4 py-2"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button"
                                onclick="closeAnnouncementModal()"
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
            document.getElementById('modalTitle').textContent = 'Yeni Duyuru';
            document.getElementById('formAction').value = 'add';
            document.getElementById('announcementId').value = '';
            document.getElementById('title').value = '';
            document.getElementById('content').value = '';
            document.getElementById('announcementModal').classList.remove('hidden');
        }

        function openEditModal(announcement) {
            document.getElementById('modalTitle').textContent = 'Duyuruyu Düzenle';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('announcementId').value = announcement.id;
            document.getElementById('title').value = announcement.title;
            document.getElementById('content').value = announcement.content;
            document.getElementById('announcementModal').classList.remove('hidden');
        }

        function closeAnnouncementModal() {
            document.getElementById('announcementModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('announcementModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAnnouncementModal();
            }
        });
    </script>
</body>
</html>
