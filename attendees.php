<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
redirect_if_not_logged_in();

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;
if(isset($event_id) && $event_id !== null) {
    
    // Verify user has permission (admin or event organizer)
    $stmt = $pdo->prepare("SELECT created_by FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event || (!is_admin() && $_SESSION['user_id'] != $event['created_by'])) {
        add_flash_message('danger', 'Unauthorized operation');
        header('Location: dashboard.php');
        exit;
    }
    
    // Get event details
    $stmt = $pdo->prepare("SELECT e.title, e.date, COUNT(a.id) AS attendee_count 
        FROM events e
        LEFT JOIN attendees a ON e.id = a.event_id
        WHERE e.id = ?
    ");
    $stmt->execute([$event_id]);
    $event_details = $stmt->fetch();
    
    $title = $event_details['title'] . ' Attendees';
    $attendee_count = $event_details['attendee_count'];
    // Get attendees list
    $stmt = $pdo->prepare("SELECT * FROM attendees WHERE event_id = ? ORDER BY registered_at DESC");
    $stmt->execute([$event_id]);
    $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} else {
    if(is_admin()){
        $stmt = $pdo->prepare("SELECT *, (SELECT COUNT(id) FROM attendees) AS attendee_count FROM attendees ORDER BY registered_at DESC");
        $stmt->execute();
        $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $title = "All Attendees";
        $attendee_count = $attendees[0]['attendee_count'];
    }
}

// Handle CSV export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendees_' . $event_id . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array_keys($attendees[0]));
    
    foreach ($attendees as $attendee) {
        fputcsv($output, $attendee);
    }
    fclose($output);
    exit;
}

$pageTitle = "Attendees List - ";
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-md-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-md-0">
            <?= htmlspecialchars($title) ?> 
            <small class="text-muted">(<?= $attendee_count ?> registrations)</small>
        </h2>
        <a href="attendees.php<?= $event_id ? '?event_id=' . $event_id .'&': '?' ?>export=1" class="btn btn-success<?= empty($attendees) ? ' disabled' : '' ?>">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>

    <?php if (empty($attendees)): ?>
        <div class="alert alert-info">No attendees registered yet</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover border">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendees as $attendee): ?>
                    <tr>
                        <td><?= htmlspecialchars($attendee['name']) ?></td>
                        <td><?= htmlspecialchars($attendee['email']) ?></td>
                        <td><?= htmlspecialchars($attendee['phone']) ?? '' ?></td>
                        <td><?= htmlspecialchars($attendee['age']) ?></td>
                        <td><?= htmlspecialchars($attendee['gender']) ?></td>
                        <td><?= date('M j, Y - h:i A', strtotime($attendee['registered_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <a href="dashboard.php" class="btn btn-secondary mt-3">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php include 'includes/footer.php'; ?>