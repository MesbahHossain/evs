<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
redirect_if_not_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    // Add validation for all fields
    
    if (isset($_FILES['fileToUpload'])) {
       $uploadOk = image_upload($_FILES);

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk != 0) {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                echo "<script> alert('The file ". htmlspecialchars( basename( $_FILES["fileToUpload"]["name"])). " has been uploaded.')</script>";
            
                $stmt = $pdo->prepare("INSERT INTO events 
                    (title, description, img, date, time, location, capacity, created_by, category)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $title,
                    $description,
                    basename($_FILES["fileToUpload"]["name"]),
                    $_POST['date'],
                    $_POST['time'],
                    $_POST['location'],
                    $_POST['capacity'],
                    $_SESSION['user_id'],
                    $_POST['category']
                ]);
                
                header('Location: ../../index.php');
                exit;
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    } else {
        // Handle the case where the file was not uploaded
        echo "No file was uploaded.";
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