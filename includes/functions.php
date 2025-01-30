<?php
function display_flash_messages() {
    if (isset($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $type => $messages) {
            foreach ($messages as $message) {
                echo '<div class="alert alert-'.$type.' alert-dismissible fade show">';
                echo htmlspecialchars($message);
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                echo '</div>';
            }
        }
        unset($_SESSION['flash']);
    }
}

function add_flash_message($type, $message) {
    $_SESSION['flash'][$type][] = $message;
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function format_date($date_string) {
    return date('M j, Y', strtotime($date_string));
}

function image_upload($file) {
     // Handle image upload
     $target_dir = "../../uploads/";
     $target_file = $target_dir . basename($file["fileToUpload"]["name"]);
     $uploadOk = 1;
     $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

     // Check if image file is a actual image or fake image
     if(isset($_POST["submit"])) {
     $check = getimagesize($file["fileToUpload"]["tmp_name"]);
     if($check !== false) {
         echo "<script> alert('File is an image - " . $check["mime"] . ".')</script>";
         $uploadOk = 1;
     } else {
         echo "<script> alert('File is not an image.')</script>";
         $uploadOk = 0;
     }
     }

     // Check if file already exists
     // if (file_exists($target_file)) {
     // echo "<script> alert('Sorry, file already exists.')</script>";
     // $uploadOk = 0;
     // }

     // Check file size
     if ($file["fileToUpload"]["size"] > 200000) {
     echo "<script> alert('Sorry, your file is too large.')</script>";
     $uploadOk = 0;
     }

     // Allow certain file formats
     if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
     && $imageFileType != "gif" && $imageFileType != "webp" ) {
        echo "<script> alert('Sorry, only JPG, JPEG, PNG, WebP & GIF files are allowed.')</script>";
        $uploadOk = 0;
     }

     return $uploadOk;
}
?>