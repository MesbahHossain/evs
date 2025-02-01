<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate input
    $required = ['event_id', 'name', 'email', 'phone', 'age', 'gender'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("$field is required");
        }
    }

    $event_id = (int)$_POST['event_id'];
    $name = clean_input($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
    $age = (int)$_POST['age'];

    if (!$email) {
        throw new Exception('Invalid email address');
    }

    if (strlen($phone) !== 11) {
        throw new Exception('Invalid phone number');
    }

    if ($age < 1 || $age > 100) {
        throw new Exception('Invalid age value');
    }

    $allowed_genders = ['male', 'female', 'other'];
    $gender = $_POST['gender'];
    if (!in_array($gender, $allowed_genders)) {
        throw new Exception('Invalid gender selection');
    }

    // Check event capacity
    $stmt = $pdo->prepare("SELECT capacity FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        throw new Exception('Event not found');
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendees WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $registered = $stmt->fetchColumn();

    if ($registered >= $event['capacity']) {
        throw new Exception('Event is full');
    }

    // Check existing registration - Fixed SQL syntax
    $stmt = $pdo->prepare("
        SELECT 1 FROM attendees 
        WHERE event_id = ? AND (email = ? OR phone = ?)
        LIMIT 1
    ");
    $stmt->execute([$event_id, $email, $phone]);
    $exists = (bool)$stmt->fetchColumn();

    if ($exists) {
        throw new Exception('Email or phone already registered for this event');
    }

    // Insert attendee
    $stmt = $pdo->prepare("
        INSERT INTO attendees (event_id, name, email, phone, age, gender)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$event_id, $name, $email, $phone, $age, $gender]);

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful!',
        'registered' => $registered + 1
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function clean_input($data) {
    return htmlspecialchars(trim($data));
}