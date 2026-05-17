<?php
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');

if (!isset($_SESSION['company_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$company_id = $_SESSION['company_id'];
$alert_count = 0;

// QUICK ROLE CHECK (Adjust this logic based on how you tell Admins and Guards apart)
$is_admin = false;
if (strpos($company_id, 'ADMIN') !== false || $company_id === 'admin') { 
    $is_admin = true; 
}

if ($is_admin) {
    // FOR ADMINS: Count how many DGDs are currently sitting as 'Pending'
    $query = $conn->query("SELECT COUNT(*) as total FROM DGD WHERE Status = 'Pending'");
    if ($query) {
        $alert_count = $query->fetch_assoc()['total'];
    }
} else {
    // FOR GUARDS: Count unread memos in the bridge table we discussed earlier
    // (Assuming you named the column is_read and it defaults to 0)
    $query = $conn->query("SELECT COUNT(*) as total FROM memo_recipients WHERE Company_ID = '$company_id' AND is_read = 0");
    if ($query) {
        $alert_count = $query->fetch_assoc()['total'];
    }
}

echo json_encode(['count' => (int)$alert_count]);
?>