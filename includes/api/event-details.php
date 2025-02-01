<?php
require_once '../config.php';
require_once '../auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Initialize response array
    $response = ['status' => 'success'];

    // Check if specific event ID is requested
    $event_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if ($event_id) {
        // Single event details
        $stmt = $pdo->prepare("
            SELECT 
                e.id,
                e.title,
                e.description,
                e.date,
                e.time,
                e.location,
                e.capacity,
                e.img,
                ec.name AS category,
                u.name AS organizer,
                (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) AS registered
            FROM events e
            JOIN users u ON e.created_by = u.id
            LEFT JOIN event_categories ec ON e.category = ec.id
            WHERE e.id = ?
        ");
        
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Event not found'
            ]);
            exit;
        }

        $response['data'] = formatEvent($event);

    } else {
        // All events
        $stmt = $pdo->query("
            SELECT 
                e.id,
                e.title,
                e.description,
                e.date,
                e.time,
                e.location,
                e.capacity,
                e.img,
                ec.name AS category,
                u.name AS organizer,
                (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) AS registered
            FROM events e
            JOIN users u ON e.created_by = u.id
            LEFT JOIN event_categories ec ON e.category = ec.id
            ORDER BY e.date DESC
        ");

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['data'] = array_map('formatEvent', $events);
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

function formatEvent($event) {
    return [
        'id' => (int)$event['id'],
        'title' => $event['title'],
        'description' => $event['description'],
        'date' => $event['date'],
        'time' => $event['time'],
        'location' => $event['location'],
        'capacity' => (int)$event['capacity'],
        'registered' => (int)$event['registered'],
        'category' => $event['category'],
        'organizer' => $event['organizer'],
        'image_url' => $event['img'] ? 'uploads/' . $event['img'] : null,
        'links' => [
            'view' => "/events/view.php?id={$event['id']}",
            'api' => "/api.php?id={$event['id']}"
        ]
    ];
}