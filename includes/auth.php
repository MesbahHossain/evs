<?php
function is_logged_in() {
    return isset($_SESSION['name']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function redirect_if_logged_in() {
    if (is_logged_in()) {
        if(is_admin()) {
            header('Location: /evs-office/dashboard.php');
            exit;
        }
        header('Location: /evs-office/index.php');
        exit;
    }
}

function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header('Location: /evs-office/pages/login.php');
        exit;
    } elseif (!is_admin()) {
        header('Location: /evs-office/index.php');
        exit;
    }
}
?>