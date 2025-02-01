<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

if (!isset($_GET['id'])) {
    header('Location: ../../dashboard.php');
    exit;
}

$event_id = (int)$_GET['id'];

// Get event details with category name
$stmt = $pdo->prepare("
    SELECT e.*, u.name as organizer, c.name as category_name 
    FROM events e
    JOIN users u ON e.created_by = u.id
    JOIN event_categories c ON e.category = c.id
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    add_flash_message('danger', 'Event not found');
    header('Location: ../../dashboard.php');
    exit;
}

// Get registered attendees count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendees WHERE event_id = ?");
$stmt->execute([$event_id]);
$registered = $stmt->fetchColumn();

// Get registration status for current user
$is_registered = false;
if (is_logged_in()) {
    $stmt = $pdo->prepare("
        SELECT EXISTS(
            SELECT 1 FROM attendees 
            WHERE event_id = ? AND user_id = ?
        ) AS is_registered
    ");
    $stmt->execute([$event_id, $_SESSION['user_id']]);
    $is_registered = (bool)$stmt->fetchColumn();
}

$pageTitle = $event['title'] . " - Event Details";
include '../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <?php if ($event['img']): ?>
                <img src="../../uploads/<?= htmlspecialchars($event['img']) ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($event['title']) ?>">
                <?php endif; ?>
                
                <div class="card-body">
                    <h1 class="card-title"><?= htmlspecialchars($event['title']) ?></h1>
                    <p class="text-muted">
                        Category: <?= htmlspecialchars($event['category_name']) ?>
                    </p>
                    <p class="card-text"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5>Event Details</h5>
                            <ul class="list-unstyled">
                                <li><strong>Date:</strong> <?= date('F j, Y', strtotime($event['date'])) ?></li>
                                <li><strong>Time:</strong> <?= date('g:i a', strtotime($event['time'])) ?></li>
                                <li><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></li>
                                <li><strong>Capacity:</strong> <?= $registered ?>/<?= $event['capacity'] ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Organizer</h5>
                            <p class="mb-1"><?= htmlspecialchars($event['organizer']) ?></p>
                            <p class="text-muted small">Created on <?= date('M j, Y', strtotime($event['created_at'])) ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <?php if(!is_logged_in()):
                            if ($registered < $event['capacity']): ?>
                                <a href="login.php" 
                                class="btn btn-success w-100 register-btn" 
                                data-event-id="<?= $event['id'] ?>">
                                    Login to Register
                                </a>
                            <?php else: ?>
                                <div class="alert alert-warning">This event is fully booked</div>
                            <?php endif; ?>
                        <?php else: 
                            if ($_SESSION['user_id'] == $event['created_by'] || is_admin()): ?>
                                <a href="edit.php?id=<?= $event['id'] ?>" class="btn btn-warning">Edit Event</a>
                                <a href="delete.php?id=<?= $event['id'] ?>" class="btn btn-danger">Delete Event</a>
                            <?php else: ?>
                                <?php if ($is_registered): ?>
                                    <button class="btn btn-success w-100" disabled>
                                        ✓ Registered
                                    </button>
                                <?php elseif ($registered < $event['capacity']): ?>
                                    <a href="#" 
                                    class="btn btn-success w-100 register-btn" 
                                    data-event-id="<?= $event['id'] ?>">
                                        Register Now
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-warning">This event is fully booked</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>