<?php
// controllers/store_ticket.php
require_once __DIR__ . '/../helpers/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/create_ticket.php");
    exit;
}

$userId      = $_SESSION['user_id'];
$subject     = trim($_POST['subject'] ?? '');
$categoryId  = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$priority    = $_POST['priority'] ?? 'medium';
$description = trim($_POST['description'] ?? '');

// Field/Form Validation
$allowedPriorities = ['low', 'medium', 'high', 'critical'];
if (empty($subject) || !$categoryId || empty($description) || !in_array($priority, $allowedPriorities, true)) {
    header("Location: ../views/create_ticket.php?error=empty_fields");
    exit;
}

$pdo = getDBConnection();

try {
    $pdo->beginTransaction();

    // 1. Generate unique no. ticket (EX: TCK-20261112-001)
    $todayPrefix = 'TCK-' . date('Ymd') . '-';
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE ticket_no LIKE :prefix");
    $stmtCount->execute([':prefix' => $todayPrefix . '%']);
    $dailySequence = $stmtCount->fetchColumn() + 1;
    $ticketNo = $todayPrefix . str_pad((string)$dailySequence, 3, '0', STR_PAD_LEFT);

    // 2. Insert ticket record to table
    $sqlTicket = "INSERT INTO tickets (ticket_no, user_id, category_id, priority, status, subject, description) 
                  VALUES (:ticket_no, :user_id, :category_id, :priority, 'open', :subject, :description)";
    $stmtTicket = $pdo->prepare($sqlTicket);
    $stmtTicket->execute([
        ':ticket_no'   => $ticketNo,
        ':user_id'     => $userId,
        ':category_id' => $categoryId,
        ':priority'    => $priority,
        ':subject'     => $subject,
        ':description' => $description
    ]);

    $ticketId = (int)$pdo->lastInsertId();

    // 3. Uploading process (If applicable)
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['attachment'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Ralat muat naik fail.");
        }

        // Max size check (5MB) (5 * 1024 * 1024 bytes)
        if ($file['size'] > 5242880) {
            $pdo->rollBack();
            header("Location: ../views/create_ticket.php?error=invalid_file");
            exit;
        }

        // MIME type check (Bukan bergantung pada sambungan nama fail semata-mata)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedMimes = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'application/pdf' => 'pdf'
        ];

        if (!array_key_exists($mimeType, $allowedMimes)) {
            $pdo->rollBack();
            header("Location: ../views/create_ticket.php?error=invalid_file");
            exit;
        }

        // Generate random file name (Avoid overwrite & Path traversal)
        $extension = $allowedMimes[$mimeType];
        $safeFileName = bin2hex(random_bytes(16)) . '.' . $extension;
        $uploadDir = __DIR__ . '/../uploads/';
        $destination = $uploadDir . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException("Gagal memindahkan fail ke direktori simpanan.");
        }

        // Store attachment to Db`ticket_attachments`
        $stmtAttach = $pdo->prepare("INSERT INTO ticket_attachments (ticket_id, file_name, file_path, file_size) 
                                     VALUES (:ticket_id, :file_name, :file_path, :file_size)");
        $stmtAttach->execute([
            ':ticket_id' => $ticketId,
            ':file_name' => basename($file['name']),
            ':file_path' => 'uploads/' . $safeFileName,
            ':file_size' => $file['size']
        ]);
    }

    // 4. Audit record to Db`ticket_logs`
    $stmtLog = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, user_id, action) VALUES (:ticket_id, :user_id, :action)");
    $stmtLog->execute([
        ':ticket_id' => $ticketId,
        ':user_id'   => $userId,
        ':action'    => "Ticket created with status Open (Priority: " . strtoupper($priority) . ")"
    ]);

    $pdo->commit();

    // Go to ticket details (Created ticket just now)
    header("Location: ../views/view_ticket.php?id=" . $ticketId . "&created=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Ticket Creation Error: " . $e->getMessage());
    header("Location: ../views/create_ticket.php?error=system_error");
    exit;
}