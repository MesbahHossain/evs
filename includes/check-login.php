<?php
require_once __DIR__.'./config.php';
require_once __DIR__.'./auth.php';

header('Content-Type: application/json');
echo json_encode(['loggedIn' => is_logged_in()]);