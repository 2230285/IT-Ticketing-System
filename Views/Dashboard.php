<?php
// views/dashboard.php
require_once __DIR__ . '/../Helpers/Auth_guard.php';
require_once __DIR__ . '/../Config/Database.php';

// Make sure user already login
requireAuth();

$pdo = getDBConnection();
$userId =$_SESSION['user_id'];
$userRole =$_SESSION['role'];
$userName =$_SESSION['user_name'];

// 1. Get statistic matric base on role (RBAC)
if ($userRole === 'admin') {
    // admin get overall statistics
    $stmtTotal =$pdo->query("SELECT COUNT(*) FROM tickets");
    $stmtOpen =$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'");
    $stmtProgress =$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress'");
    $stmtResolved =$pdo->query("SELECT COUNT(*) FROM tickets WHERE status IN ('resolved', 'closed')");
} elseif ($userRole === 'agent') {
    // Agent can see tasking ticket (assigned/unassigned)
    $stmtTotal =$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE assigned_to = :agent_id OR assigned_to IS NULL");
    $stmtTotal->execute([':agent_id' =>$userId]);

    $stmtOpen =$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open' AND assigned_to IS NULL");
    
    $stmtProgress =$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress' AND assigned_to = :agent_id");
    $stmtProgress->execute([':agent_id' =>$userId]);

    $stmtResolved =$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status IN ('resolved', 'closed') AND assigned_to = :agent_id");
    $stmtResolved->execute([':agent_id' =>$userId]);
} else {
    // Normal user only see their tickets
    $stmtTotal =$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = :user_id");
    $stmtTotal->execute([':user_id' =>$userId]);

    $stmtOpen =$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status = 'open' AND user_id = :user_id");
    $stmtOpen->execute([':user_id' =>$userId]);

    $stmtProgress =$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress' AND user_id = :user_id");
    $stmtProgress->execute([':user_id' =>$userId]);

    $stmtResolved =$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status IN ('resolved', 'closed') AND user_id = :user_id");
    $stmtResolved->execute([':user_id' =>$userId]);
}

$countTotal =$stmtTotal->fetchColumn() ?: 0;
$countOpen =$stmtOpen->fetchColumn() ?: 0;
$countProgress =$stmtProgress->fetchColumn() ?: 0;
$countResolved =$stmtResolved->fetchColumn() ?: 0;

// 2. Listing recent ticket (max 5 ticket)
if ($userRole === 'admin') {$sqlTickets = "SELECT t.*, u.name AS requester_name, c.name AS category_name 
                   FROM tickets t 
                   JOIN users u ON t.user_id = u.id 
                   LEFT JOIN categories c ON t.category_id = c.id 
                   ORDER BY t.created_at DESC LIMIT 5";
    $stmtRecent = $pdo->query($sqlTickets);
} elseif ($userRole === 'agent') {$sqlTickets = "SELECT t.*, u.name AS requester_name, c.name AS category_name 
                   FROM tickets t 
                   JOIN users u ON t.user_id = u.id 
                   LEFT JOIN categories c ON t.category_id = c.id 
                   WHERE t.assigned_to = :agent_id OR t.assigned_to IS NULL 
                   ORDER BY t.created_at DESC LIMIT 5";
    $stmtRecent = $pdo->prepare($sqlTickets);
    $stmtRecent->execute([':agent_id' =>$userId]);
} else {
    $sqlTickets = "SELECT t.*, u.name AS requester_name, c.name AS category_name 
                   FROM tickets t 
                   JOIN users u ON t.user_id = u.id 
                   LEFT JOIN categories c ON t.category_id = c.id 
                   WHERE t.user_id = :user_id 
                   ORDER BY t.created_at DESC LIMIT 5";
    $stmtRecent = $pdo->prepare($sqlTickets);
    $stmtRecent->execute([':user_id' =>$userId]);
}
$recentTickets =$stmtRecent->fetchAll();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IT Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen font-sans text-slate-800">

    <!-- Navigasi Atas -->
    <nav class="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center shadow-sm">
        <div class="flex items-center space-x-3">
            <span class="text-xl font-bold text-indigo-600">Helpdesk Portal</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider 
                <?= $userRole === 'admin' ? 'bg-purple-100 text-purple-700' : ($userRole === 'agent' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700') ?>">
                <?= htmlspecialchars($userRole) ?>
            </span>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm font-medium text-slate-600">Hai, <?= htmlspecialchars($userName) ?></span>
            <a href="../Auth/Logout.php" class="text-sm font-medium text-red-600 hover:text-red-700 transition">Log Keluar</a>
        </div>
    </nav>

    <!-- Kandungan Papan Pemuka -->
    <main class="max-w-6xl mx-auto px-6 py-8">
        
        <!-- Header & Tindakan Cepat -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
                <p class="text-sm text-slate-500">Status and flow ticket.</p>
            </div>
            <div>
                <a href="create_ticket.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                    + Raise new ticket
                </a>
            </div>
        </div>

        <!-- Kad Metrik Ringkas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Sum Ticket</p>
                <p class="text-2xl font-bold text-slate-900 mt-2"><?= $countTotal ?></p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-semibold text-amber-500 uppercase tracking-wider">New Ticket (Open)</p>
                <p class="text-2xl font-bold text-amber-600 mt-2"><?= $countOpen ?></p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-semibold text-blue-500 uppercase tracking-wider">Processing</p>
                <p class="text-2xl font-bold text-blue-600 mt-2"><?= $countProgress ?></p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-semibold text-emerald-500 uppercase tracking-wider">Complete / Close</p>
                <p class="text-2xl font-bold text-emerald-600 mt-2"><?= $countResolved ?></p>
            </div>
        </div>

        <!-- Jadual Tiket Terkini -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-base font-semibold text-slate-800">Recent Ticket</h2>
                <span class="text-xs text-slate-400">5 recent record</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">No. Ticket</th>
                            <th class="py-3 px-4">Subject</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Priority</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php if (empty($recentTickets)): ?>
                            <tr>
                                <td colspan="7" class="py-6 text-center text-slate-400 text-sm">
                                    Doesn't have record ticket for now.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentTickets as$ticket): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3.5 px-4 font-mono font-medium text-xs text-indigo-600">
                                        <?= htmlspecialchars($ticket['ticket_no']) ?>
                                    </td>
                                    <td class="py-3.5 px-4 font-medium text-slate-900">
                                        <?= htmlspecialchars($ticket['subject']) ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-xs text-slate-500">
                                        <?= htmlspecialchars($ticket['category_name'] ?? 'Public') ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <?php
                                        $pColors = [ 'critical' => 'bg-rose-100 text-rose-700', 'high'     => 'bg-orange-100 text-orange-700', 'medium'   => 'bg-yellow-100 text-yellow-700', 'low'      => 'bg-slate-100 text-slate-600', ];$pClass = $pColors[$ticket['priority']] ?? 'bg-slate-100 text-slate-600';
                                        ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-medium uppercase <?= $pClass ?>">
                                            <?= htmlspecialchars($ticket['priority']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <?php
                                        $sColors = [ 'open'        => 'bg-amber-100 text-amber-700', 'in_progress' => 'bg-blue-100 text-blue-700', 'pending'     => 'bg-purple-100 text-purple-700', 'resolved'    => 'bg-emerald-100 text-emerald-700', 'closed'      => 'bg-slate-100 text-slate-600', ];$sClass = $sColors[$ticket['status']] ?? 'bg-slate-100 text-slate-600';
                                        ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $sClass ?>">
                                            <?= str_replace('_', ' ', htmlspecialchars($ticket['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-xs text-slate-400">
                                        <?= date('d M Y, h:i A', strtotime($ticket['created_at'])) ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <a href="View_ticket.php?id=<?= $ticket['id'] ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                            See &rarr;
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>