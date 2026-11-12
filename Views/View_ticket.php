<?php
// views/view_ticket.php
require_once __DIR__ . '/../Helpers/Auth_guard.php';
require_once __DIR__ . '/../Config/Database.php';

requireAuth();

$ticketId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ticketId) {
    header("Location: Dashboard.php");
    exit;
}

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// 1. Dapatkan Maklumat Lengkap Tiket
$sql = "SELECT t.*, 
               u.name AS requester_name, u.email AS requester_email,
               d.name AS department_name,
               c.name AS category_name,
               a.name AS agent_name
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users a ON t.assigned_to = a.id
        WHERE t.id = :id LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header("Location: Dashboard.php");
    exit;
}

// Kawalan Akses RBAC: User biasa hanya boleh melihat tiket miliknya sendiri
if ($userRole === 'user' && (int)$ticket['user_id'] !== (int)$userId) {
    header("Location: Unauthorized.php");
    exit;
}

// 2. Dapatkan Lampiran Fail (Jika Ada)
$stmtAttach = $pdo->prepare("SELECT * FROM ticket_attachments WHERE ticket_id = :tid");
$stmtAttach->execute([':tid' => $ticketId]);
$attachments = $stmtAttach->fetchAll();

// 3. Dapatkan Senarai Balasan / Komen
// User biasa TIDAK boleh melihat komen yang ditanda is_internal_note = 1
if ($userRole === 'user') {
    $sqlReplies = "SELECT r.*, u.name AS sender_name, u.role AS sender_role 
                   FROM ticket_replies r 
                   JOIN users u ON r.user_id = u.id 
                   WHERE r.ticket_id = :tid AND r.is_internal_note = 0 
                   ORDER BY r.created_at ASC";
} else {
    $sqlReplies = "SELECT r.*, u.name AS sender_name, u.role AS sender_role 
                   FROM ticket_replies r 
                   JOIN users u ON r.user_id = u.id 
                   WHERE r.ticket_id = :tid 
                   ORDER BY r.created_at ASC";
}
$stmtReplies = $pdo->prepare($sqlReplies);
$stmtReplies->execute([':tid' => $ticketId]);
$replies = $stmtReplies->fetchAll();

// 4. Dapatkan Rekod Jejak Audit (Logs)
$stmtLogs = $pdo->prepare("SELECT l.*, u.name AS actor_name 
                           FROM ticket_logs l 
                           JOIN users u ON l.user_id = u.id 
                           WHERE l.ticket_id = :tid 
                           ORDER BY l.created_at DESC");
$stmtLogs->execute([':tid' => $ticketId]);
$logs = $stmtLogs->fetchAll();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($ticket['ticket_no']) ?> - IT Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen font-sans text-slate-800">

    <!-- Navigasi Atas -->
    <nav class="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center shadow-sm">
        <a href="Dashboard.php" class="text-xl font-bold text-indigo-600">Helpdesk Portal</a>
        <a href="Dashboard.php" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition">&larr; Kembali ke Papan Pemuka</a>
    </nav>

    <main class="max-w-6xl mx-auto px-6 py-8">

        <?php if (isset($_GET['created'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg">
                Tiket berjaya dihantar! Nombor rujukan anda ialah <strong><?= htmlspecialchars($ticket['ticket_no']) ?></strong>.
            </div>
        <?php elseif (isset($_GET['updated'])): ?>
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-lg">
                Rekod tiket telah berjaya dikemas kini.
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LAJUR KIRI: Butiran Masalah & Aliran Perbualan -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Kad Butiran Tiket -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex flex-wrap justify-between items-start gap-2 mb-4 pb-4 border-b border-slate-100">
                        <div>
                            <span class="font-mono text-xs font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded">
                                <?= htmlspecialchars($ticket['ticket_no']) ?>
                            </span>
                            <h1 class="text-xl font-bold text-slate-900 mt-2"><?= htmlspecialchars($ticket['subject']) ?></h1>
                        </div>
                    </div>

                    <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-line mb-6">
                        <?= htmlspecialchars($ticket['description']) ?>
                    </div>

                    <!-- Lampiran Fail -->
                    <?php if (!empty($attachments)): ?>
                        <div class="pt-4 border-t border-slate-100">
                            <h3 class="text-xs font-semibold uppercase text-slate-400 tracking-wider mb-2">Lampiran Fail</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($attachments as $att): ?>
                                    <a href="../<?= htmlspecialchars($att['file_path']) ?>" target="_blank"
                                       class="inline-flex items-center px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-xs font-medium text-indigo-600 transition">
                                        📎 <?= htmlspecialchars($att['file_name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Ruangan Perbualan & Balasan -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h2 class="text-base font-bold text-slate-800 mb-4">Aliran Perbualan & Tindakan</h2>

                    <div class="space-y-4 mb-6">
                        <?php if (empty($replies)): ?>
                            <p class="text-sm text-slate-400 italic">Belum ada sebarang balasan buat masa ini.</p>
                        <?php else: ?>
                            <?php foreach ($replies as $rep): ?>
                                <?php $isNote = (int)$rep['is_internal_note'] === 1; ?>
                                <div class="p-4 rounded-xl border <?= $isNote ? 'bg-amber-50 border-amber-200' : 'bg-slate-50 border-slate-200' ?>">
                                    <div class="flex justify-between items-center mb-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($rep['sender_name']) ?></span>
                                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-slate-200 text-slate-700">
                                                <?= htmlspecialchars($rep['sender_role']) ?>
                                            </span>
                                            <?php if ($isNote): ?>
                                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-amber-200 text-amber-900">
                                                    Internal Note (Sulit IT)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs text-slate-400">
                                            <?= date('d M Y, h:i A', strtotime($rep['created_at'])) ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-700 whitespace-pre-line"><?= htmlspecialchars($rep['message']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Borang Hantar Balasan Baharu -->
                    <?php if ($ticket['status'] !== 'closed'): ?>
                        <form action="../Controllers/Update_ticket.php" method="POST" class="pt-4 border-t border-slate-100 space-y-3">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                            <input type="hidden" name="action_type" value="add_reply">

                            <label for="message" class="block text-sm font-medium text-slate-700">Tulis Balasan</label>
                            <textarea id="message" name="message" rows="3" required
                                      placeholder="Masukkan maklum balas atau langkah penyelesaian..."
                                      class="w-full px-3.5 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>

                            <div class="flex flex-wrap justify-between items-center gap-3">
                                <?php if (in_array($userRole, ['agent', 'admin'], true)): ?>
                                    <label class="inline-flex items-center space-x-2 text-xs font-medium text-amber-800 cursor-pointer">
                                        <input type="checkbox" name="is_internal_note" value="1" class="rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                                        <span>Tanda sebagai <strong>Internal Note</strong> (Hanya dilihat oleh staf IT)</span>
                                    </label>
                                <?php else: ?>
                                    <span></span>
                                <?php endif; ?>

                                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                                    Hantar Balasan
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="p-3 bg-slate-100 text-slate-600 text-xs rounded-lg text-center">
                            Tiket ini telah ditutup (Closed). Tiada balasan baharu dibenarkan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- LAJUR KANAN: Info Status, Kawalan Agent & Audit Log -->
            <div class="space-y-6">

                <!-- Panel Status & Tindakan Agent/Admin -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Maklumat Tiket</h2>

                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Status Semasa:</span>
                            <span class="font-semibold uppercase text-indigo-600"><?= str_replace('_', ' ', htmlspecialchars($ticket['status'])) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Keutamaan:</span>
                            <span class="font-semibold uppercase text-slate-800"><?= htmlspecialchars($ticket['priority']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Kategori:</span>
                            <span class="font-medium text-slate-800"><?= htmlspecialchars($ticket['category_name'] ?? 'Umum') ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Pemohon:</span>
                            <span class="font-medium text-slate-800"><?= htmlspecialchars($ticket['requester_name']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Jabatan:</span>
                            <span class="font-medium text-slate-800"><?= htmlspecialchars($ticket['department_name'] ?? '-') ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Ditugaskan Kepada:</span>
                            <span class="font-medium text-slate-800"><?= htmlspecialchars($ticket['agent_name'] ?? 'Belum Ditugaskan') ?></span>
                        </div>
                    </div>

                    <!-- Kawalan Khusus Agent / Admin -->
                    <?php if (in_array($userRole, ['agent', 'admin'], true)): ?>
                        <div class="pt-4 border-t border-slate-100 space-y-3">
                            
                            <!-- Butang Ambil Alih Tiket -->
                            <?php if ((int)$ticket['assigned_to'] !== (int)$userId): ?>
                                <form action="../Controllers/Update_ticket.php" method="POST">
                                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                    <input type="hidden" name="action_type" value="assign_self">
                                    <button type="submit" class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition">
                                        Ambil Alih Tiket (Assign to Me)
                                    </button>
                                </form>
                            <?php endif; ?>

                            <!-- Borang Kemas Kini Status -->
                            <form action="../Controllers/Update_ticket.php" method="POST" class="space-y-2">
                                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                <input type="hidden" name="action_type" value="change_status">
                                
                                <label for="status" class="block text-xs font-semibold text-slate-600">Tukar Status Tiket</label>
                                <div class="flex space-x-2">
                                    <select id="status" name="status" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs bg-white">
                                        <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                        <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="pending" <?= $ticket['status'] === 'pending' ? 'selected' : '' ?>>Pending User</option>
                                        <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                        <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                    </select>
                                    <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-lg transition">
                                        Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Garis Masa Log Audit -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Sejarah & Log Audit</h2>
                    <div class="space-y-3 border-l-2 border-slate-200 pl-4">
                        <?php foreach ($logs as $log): ?>
                            <div class="relative">
                                <div class="w-2 h-2 bg-indigo-500 rounded-full absolute -left-[21px] top-1.5"></div>
                                <p class="text-xs text-slate-700 font-medium"><?= htmlspecialchars($log['action']) ?></p>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Oleh <?= htmlspecialchars($log['actor_name']) ?> &bull; <?= date('d M, h:i A', strtotime($log['created_at'])) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>

</body>
</html>