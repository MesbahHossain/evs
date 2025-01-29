<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirect_if_not_logged_in();

if (!is_admin()) {
    die('Unauthorized access');
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendees.csv"');

$stmt = $pdo->prepare("SELECT * FROM attendees WHERE event_id = ?");
$stmt->execute([$_GET['event_id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$output = fopen('php://output', 'w');
fputcsv($output, array_keys($results[0]));

foreach ($results as $row) {
    fputcsv($output, $row);
}
fclose($output);
exit;
?>