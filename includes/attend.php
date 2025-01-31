<?php
require_once __DIR__.'./config.php';
require_once __DIR__.'./auth.php';

if (!is_logged_in()) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Please login to register']));
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    
    if (!$event_id) {
        throw new Exception('Invalid event ID');
    }

    // Check if event exists and has capacity
    $stmt = $pdo->prepare("SELECT capacity FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        throw new Exception('Event not found');
    }

    // Check current attendees
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendees WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $registered = $stmt->fetchColumn();

    if ($registered >= $event['capacity']) {
        throw new Exception('Event is full');
    }

    // Check if already registered
    $stmt = $pdo->prepare("SELECT id FROM attendees WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        throw new Exception('Already registered for this event');
    }

    // Register attendee
    $stmt = $pdo->prepare("INSERT INTO attendees (event_id, user_id, name, email) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $event_id,
        $_SESSION['user_id'],
        $_SESSION['name'],
        $_SESSION['email']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Successfully registered for the event!',
        'new_count' => $registered + 1
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}