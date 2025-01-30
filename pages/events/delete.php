<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
redirect_if_not_logged_in();

if (!isset($_GET['id'])) {
    header('Location: ../../index.php');
    exit;
}

$event_id = (int)$_GET['id'];

// Verify ownership
$stmt = $pdo->prepare("SELECT created_by FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$owner = $stmt->fetchColumn();

if (!$owner || ($owner != $_SESSION['user_id'] && !is_admin())) {
    add_flash_message('danger', 'Unauthorized operation');
    header('Location: ../../index.php');
    exit;
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Delete attendees first
        $stmt = $pdo->prepare("DELETE FROM attendees WHERE event_id = ?");
        $stmt->execute([$event_id]);
        
        // Delete event
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        
        $pdo->commit();
        
        add_flash_message('success', 'Event deleted successfully');
        header('Location: ../../index.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        add_flash_message('danger', 'Error deleting event: ' . $e->getMessage());
        header("Location: view.php?id=$event_id");
        exit;
    }
}

$pageTitle = "Delete Event";
include '../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Delete Event</h2>
                    <p>Are you sure you want to delete this event? This action cannot be undone.</p>
                    
                    <form method="POST">
                        <button type="submit" class="btn btn-danger">Confirm Delete</button>
                        <a href="view.php?id=<?= $event_id ?>" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>