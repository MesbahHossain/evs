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
?>