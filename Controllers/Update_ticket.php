<?php
// controllers/update_ticket.php
require_once __DIR__ . '/../Helpers/Auth_guard.php';
require_once __DIR__ . '/../Config/Database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Views/Dashboard.php");
    exit;
}

$ticketId = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
$actionType = $_POST['action_type'] ?? '';
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

if (!$ticketId) {
    header("Location: ../Views/Dashboard.php");
    exit;
}

$pdo = getDBConnection();

// Semak kewujudan tiket dan pemilikannya
$stmtCheck = $pdo->prepare("SELECT * FROM tickets WHERE id = :id LIMIT 1");
$stmtCheck->execute([':id' => $ticketId]);
$ticket = $stmtCheck->fetch();

if (!$ticket) {
    header("Location: ../Views/Dashboard.php");
    exit;
}

// Sekat akses jika user biasa cuba mengubah tiket orang lain
if ($userRole === 'user' && (int)$ticket['user_id'] !== (int)$userId) {
    header("Location: ../Views/Unauthorized.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // TINDAKAN 1: Agent/Admin mengambil alih tiket
    if ($actionType === 'assign_self' && in_array($userRole, ['agent', 'admin'], true)) {
        $stmtAssign = $pdo->prepare("UPDATE tickets SET assigned_to = :agent_id, status = 'in_progress' WHERE id = :id");
        $stmtAssign->execute([
            ':agent_id' => $userId,
            ':id'       => $ticketId
        ]);

        $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, user_id, action) VALUES (:tid, :uid, :act)");
        $stmtLog->execute([
            ':tid' => $ticketId,
            ':uid' => $userId,
            ':act' => "Tiket diambil alih oleh " . $_SESSION['user_name'] . " (Status: In Progress)"
        ]);
    }

    // TINDAKAN 2: Agent/Admin menukar status tiket
    elseif ($actionType === 'change_status' && in_array($userRole, ['agent', 'admin'], true)) {
        $newStatus = $_POST['status'] ?? '';
        $allowedStatuses = ['open', 'in_progress', 'pending', 'resolved', 'closed'];

        if (in_array($newStatus, $allowedStatuses, true) && $newStatus !== $ticket['status']) {
            // Rekod masa selesai jika status ditukar kepada resolved
            $resolvedSql = ($newStatus === 'resolved') ? ", resolved_at = NOW()" : "";
            
            $stmtStatus = $pdo->prepare("UPDATE tickets SET status = :status $resolvedSql WHERE id = :id");
            $stmtStatus->execute([
                ':status' => $newStatus,
                ':id'     => $ticketId
            ]);

            $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, user_id, action) VALUES (:tid, :uid, :act)");
            $stmtLog->execute([
                ':tid' => $ticketId,
                ':uid' => $userId,
                ':act' => "Status ditukar daripada '" . strtoupper($ticket['status']) . "' kepada '" . strtoupper($newStatus) . "'"
            ]);
        }
    }

    // TINDAKAN 3: Menghantar balasan komen atau nota sulit
    elseif ($actionType === 'add_reply') {
        $message = trim($_POST['message'] ?? '');
        // Hanya agent/admin dibenarkan menanda nota sulit
        $isInternal = (in_array($userRole, ['agent', 'admin'], true) && isset($_POST['is_internal_note'])) ? 1 : 0;

        if (!empty($message)) {
            $stmtReply = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_internal_note) 
                                        VALUES (:tid, :uid, :msg, :internal)");
            $stmtReply->execute([
                ':tid'      => $ticketId,
                ':uid'      => $userId,
                ':msg'      => $message,
                ':internal' => $isInternal
            ]);

            $logText = $isInternal ? "Menambah nota sulit dalaman (Internal Note)" : "Menghantar balasan baharu pada tiket";
            $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, user_id, action) VALUES (:tid, :uid, :act)");
            $stmtLog->execute([
                ':tid' => $ticketId,
                ':uid' => $userId,
                ':act' => $logText
            ]);
        }
    }

    $pdo->commit();
    header("Location: ../Views/View_ticket.php?id=" . $ticketId . "&updated=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Ticket Update Error: " . $e->getMessage());
    header("Location: ../Views/View_ticket.php?id=" . $ticketId . "&error=1");
    exit;
}