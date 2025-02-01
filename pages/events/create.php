<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
redirect_if_not_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['title', 'description', 'date', 'time', 'location', 'capacity', 'category'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Check if file was uploaded
        if (!isset($_FILES['fileToUpload']) || $_FILES['fileToUpload']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Please select an image file');
        }

        // Upload image
        $upload_result = image_upload($_FILES['fileToUpload']);
        if (!$upload_result['success']) {
            throw new Exception($upload_result['message']);
        }

        // Insert event into database
        $stmt = $pdo->prepare("INSERT INTO events 
            (title, description, img, date, time, location, capacity, created_by, category)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            htmlspecialchars($_POST['title']),
            htmlspecialchars($_POST['description']),
            $upload_result['filename'],
            $_POST['date'],
            $_POST['time'],
            htmlspecialchars($_POST['location']),
            (int)$_POST['capacity'],
            $_SESSION['user_id'],
            (int)$_POST['category']
        ]);

        add_flash_message('success', 'Event created successfully!');
        header('Location: create.php');
        exit;

    } catch (Exception $e) {
        add_flash_message('danger', $e->getMessage());
        header('Location: create.php');
        exit;
    }
}
?>

<?php 
$pageTitle = "Create Event - Event Management System";
include '../../includes/header.php'; 
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <h2>Create New Event</h2>
            <form method="POST" action="create.php" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="image" class="form-label">Image</label>
                    <input type="file" class="form-control" id="image" name="fileToUpload" accept="image/*" required>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM event_categories");
                $stmt->execute();
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="" disabled selected>Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" required>
                </div>
                <div class="mb-3">
                    <label for="time" class="form-label">Time</label>
                    <input type="time" class="form-control" id="time" name="time" required>
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" required>
                </div>
                <div class="mb-3">
                    <label for="capacity" class="form-label">Capacity</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" required min="1">
                </div>
                <button type="submit" class="btn btn-primary">Create Event</button>
                <a href="../../index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>