<?php
function is_logged_in() {
    return isset($_SESSION['name']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header('Location: pages/login.php');
        exit;
    }
}
?>