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

// Get existing event data
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

// Check ownership
if (!$event || ($event['created_by'] != $_SESSION['user_id'] && !is_admin())) {
    add_flash_message('danger', 'You cannot edit this event');
    header('Location: ../../index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $category = (int)$_POST['category'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = htmlspecialchars($_POST['location']);
    $capacity = (int)$_POST['capacity'];
    
    $image = $event['img']; // Keep existing image by default
    
    // Handle new image upload
    if (!empty($_FILES['fileToUpload']['name'])) {
        $image = image_upload($_FILES["fileToUpload"]);
    }

    $stmt = $pdo->prepare("
        UPDATE events SET
        title = ?,
        description = ?,
        img = ?,
        date = ?,
        time = ?,
        location = ?,
        capacity = ?,
        category = ?
        WHERE id = ?
    ");
    
    if ($stmt->execute([
        $title,
        $description,
        basename($_FILES["fileToUpload"]["name"]),
        $date,
        $time,
        $location,
        $capacity,
        $category,
        $event_id
    ])) {
        add_flash_message('success', 'Event updated successfully');
        header("Location: view.php?id=$event_id");
        exit;
    } else {
        add_flash_message('danger', 'Failed to update event');
    }
}

// Get categories
$stmt = $pdo->prepare("SELECT * FROM event_categories");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Edit Event - " . htmlspecialchars($event['title']);
include '../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <h2>Edit Event</h2>
            
            <?php display_flash_messages(); ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="image" class="form-label">Current Image</label>
                    <?php if ($event['img']): ?>
                        <img src="../../uploads/<?= htmlspecialchars($event['img']) ?>" 
                             class="img-thumbnail d-block mb-2" 
                             style="max-width: 200px">
                    <?php endif; ?>
                    <input type="file" class="form-control" id="image" name="fileToUpload">
                </div>
                
                <!-- Include form fields from create.php with existing values -->
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?= htmlspecialchars($event['title']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($event['description']) ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" 
                                <?= $cat['id'] == $event['category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($event['date']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="time" class="form-label">Time</label>
                    <input type="time" class="form-control" id="time" name="time" value="<?= htmlspecialchars($event['time']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($event['location']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="capacity" class="form-label">Capacity</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" value="<?= htmlspecialchars($event['capacity']) ?>" required min="1">
                </div>
                
                <!-- Include other form fields with existing values -->
                
                <button type="submit" class="btn btn-primary">Update Event</button>
                <a href="view.php?id=<?= $event_id ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>