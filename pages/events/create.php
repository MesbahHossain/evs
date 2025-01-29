<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
redirect_if_not_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    // Add validation for all fields
    
    // Handle image upload
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
    }

    // Check if file already exists
    if (file_exists($target_file)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 200000) {
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" && $imageFileType != "webp" ) {
    echo "Sorry, only JPG, JPEG, PNG, WebP & GIF files are allowed.";
    $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        echo "The file ". htmlspecialchars( basename( $_FILES["fileToUpload"]["name"])). " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
    }
    
    $stmt = $pdo->prepare("INSERT INTO events 
        (title, description, date, time, location, capacity, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $title,
        $description,
        $_POST['date'],
        $_POST['time'],
        $_POST['location'],
        $_POST['capacity'],
        $_SESSION['user_id']
    ]);
    
    header('Location: ../../index.php');
    exit;
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
                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
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