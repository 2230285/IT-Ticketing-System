<?php
// views/create_ticket.php
require_once __DIR__ . '/../Helpers/Auth_guard.php';
require_once __DIR__ . '/../Config/Database.php';

requireAuth();

$pdo = getDBConnection();

// Get all categories from DB
$stmtCat = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $stmtCat->fetchAll();

$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raise New Ticket - IT Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen font-sans text-slate-800">

    <!-- Navigasi Atas -->
    <nav class="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center shadow-sm">
        <div class="flex items-center space-x-3">
            <a href="dashboard.php" class="text-xl font-bold text-indigo-600">Helpdesk Portal</a>
        </div>
        <div class="flex items-center space-x-4">
            <a href="dashboard.php" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition">&larr; Back to Dashboard</a>
        </div>
    </nav>

    <!-- Kandungan Utama -->
    <main class="max-w-3xl mx-auto px-6 py-8">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8">
            <div class="mb-6 pb-4 border-b border-slate-100">
                <h1 class="text-xl font-bold text-slate-900">Send technical support ticket</h1>
                <p class="text-sm text-slate-500 mt-1">Give the full details to IT technical support can respond instantlly.</p>
            </div>

            <?php if ($error === 'empty_fields'): ?>
                <div class="mb-5 p-3.5 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg">
                    Ensure the subject, categories and problem details complete.
                </div>
            <?php elseif ($error === 'invalid_file'): ?>
                <div class="mb-5 p-3.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                    File format can't support or the size larger than 5MB. Only JPG, PNG, and PDF format allow.
                </div>
            <?php elseif ($error === 'system_error'): ?>
                <div class="mb-5 p-3.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                    Have an error while storing ticket, Please try again in a moment.
                </div>
            <?php endif; ?>

            <form action="../Controllers/Store_ticket.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                
                <!-- Subjek Tiket -->
                <div>
                    <label for="subject" class="block text-sm font-medium text-slate-700 mb-1">Subject / Problem Summary <span class="text-red-500">*</span></label>
                    <input type="text" id="subject" name="subject" required maxlength="200"
                           placeholder="Contoh: The printer on the 2nd floor cannot connect to the network."
                           class="w-full px-3.5 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>

                <!-- Grid Kategori & Tahap Keutamaan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1">Kategori Masalah <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" required
                                class="w-full px-3.5 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="priority" class="block text-sm font-medium text-slate-700 mb-1">Level Priority<span class="text-red-500">*</span></label>
                        <select id="priority" name="priority" required
                                class="w-full px-3.5 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>

                <!-- Penerangan Lengkap -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Details Description <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="5" required
                              placeholder="Terangkan langkah yang berlaku sebelum ralat muncul, mesej ralat yang dipaparkan, atau lokasi aset..."
                              class="w-full px-3.5 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"></textarea>
                </div>

                <!-- Lampiran Fail (Pilihan) -->
                <div>
                    <label for="attachment" class="block text-sm font-medium text-slate-700 mb-1">attachment for proved / Document (Optional)</label>
                    <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf"
                           class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-lg p-1.5">
                    <p class="text-xs text-slate-400 mt-1">Format only: JPG, PNG, PDF (Max 5MB).</p>
                </div>

                <!-- Butang Hantar -->
                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                    <a href="Dashboard.php" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 transition">Cancel</a>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                        Raise
                    </button>
                </div>

            </form>
        </div>
    </main>

</body>
</html>