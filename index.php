<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Homepage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="bg-dark text-white py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand text-decoration-none text-white" href="index.php">
                <h1 class="fw-bold mb-0">EVS</h1>
                <p class="mb-0">Event Management System</p>
            </a>
            <div class="nav-buttons">
                <?php if (is_logged_in()): ?>
                    <?php echo is_admin() ? 
                    '<a href="/evs-home/dashboard.php" class="btn btn-secondary me-sm-2">Dashboard</a>' : 
                    '<span class="d-none d-sm-inline border-end pe-2 me-2">Welcome, ' . htmlspecialchars($_SESSION['name']) . '</span>' ?>
                    <a href="logout.php" class="btn btn-secondary me-sm-2">Logout</a>
                <?php else: ?>
                    <a href="/evs-home/pages/login.php" class="btn btn-secondary me-sm-2">Login</a>
                    <a href="/evs-home/pages/register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="hero-event py-sm-5">
        <?php 
        // Fetch closest upcoming event
        // $stmt_closest = $pdo->query("
        //     SELECT e.*, u.name as organizer, ec.name as category_name
        //     FROM events e
        //     JOIN users u ON e.created_by = u.id
        //     LEFT JOIN event_categories ec ON e.category = ec.id
        //     WHERE e.date >= CURDATE()
        //     ORDER BY e.date ASC
        //     LIMIT 1
        // ");

        // For closest event query
        $stmt_closest = $pdo->query("
            SELECT e.*, 
            (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) AS registered_count,
            ". (is_logged_in() ? "(SELECT EXISTS(SELECT 1 FROM attendees WHERE event_id = e.id AND user_id = ".$_SESSION['user_id']."))" : "0"). " AS is_registered,
            u.name as organizer, 
            ec.name as category_name
            FROM events e
            JOIN users u ON e.created_by = u.id
            LEFT JOIN event_categories ec ON e.category = ec.id
            WHERE e.date >= CURDATE()
            ORDER BY e.date ASC
            LIMIT 1
        ");

        $closest_event = $stmt_closest->fetch(PDO::FETCH_ASSOC);
        ?>
        
        <?php if ($closest_event): ?>
        <div class="container text-center bg-glass">
            <h2 id="hero-title" class="mb-4">
                <span class="d-none d-sm-inline">Next Event: </span><?= htmlspecialchars($closest_event['title']) ?>
            </h2>
            <div id="countdown" class="countdown mb-4" data-date="<?= $closest_event['date'] ?> <?= $closest_event['time'] ?>">
                <div class="countdown-section">
                    <span id="days"></span>
                    <p>Days</p>
                </div>
                <div class="countdown-section">
                    <span id="hours"></span>
                     <p>Hours</p>
                </div>
                <div class="countdown-section">
                    <span id="minutes"></span>
                    <p>Minutes</p>
                </div>
                <div class="countdown-section">
                    <span id="seconds"></span>
                     <p>Seconds</p>
                </div>
            </div>
            <p id="hero-description" class="lead mb-1">Join us for the ultimate <?= strtolower($closest_event['category_name']) ?> experience!</p>
            <p class="mb-4">Location: <?= htmlspecialchars($closest_event['location']) ?></p>
            <a href="#" class="btn btn-register btn-lg register-btn <?= $closest_event['is_registered'] ? 'btn-success disabled' : '' ?>" data-event-id="<?= $closest_event['id'] ?>">
                <?php if ($closest_event['is_registered']): ?>
                    ✓ Registered
                <?php else: ?>
                    <?= 'Register Now' ?>
                <?php endif; ?>
            </a>
        </div>
        <?php else: ?>
        <div class="container text-center bg-glass">
            <h2 class="mb-4">No upcoming events found</h2>
        </div>
        <?php endif; ?>
    </section>

    <?php
    // For upcoming events query
    $stmt_upcoming = $pdo->query("
        SELECT e.*,
        (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) AS registered_count,
        ". (is_logged_in() ? "(SELECT EXISTS(SELECT 1 FROM attendees WHERE event_id = e.id AND user_id = ".$_SESSION['user_id']."))" : "0"). " AS is_registered,
        u.name as organizer, 
        ec.name as category_name
        FROM events e
        JOIN users u ON e.created_by = u.id
        LEFT JOIN event_categories ec ON e.category = ec.id
        WHERE e.date >= CURDATE()
        ". ($closest_event ? " AND e.id != ".$closest_event['id'] : "")."
        ORDER BY e.date ASC
    ");
    $upcoming_events = $stmt_upcoming->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <section class="other-events py-5">
        <div class="container">
            <h2 class="text-center mb-4">Upcoming Events</h2>
            <div class="row">
                <?php if (!empty($upcoming_events)): ?>
                    <?php foreach ($upcoming_events as $event): ?>
                    <div class="col-md-4 mb-4">
                        <div class="event-card">
                            <a href="pages/events/view.php?id=<?= $event['id'] ?>">
                                <img src="uploads/<?= htmlspecialchars($event['img']) ?>" alt="<?= htmlspecialchars($event['title']) ?>" class="event-image img-fluid">
                            </a>
                            <div class="event-content">
                                <div class="d-flex justify-content-between">
                                    <a href="pages/events/view.php?id=<?= $event['id'] ?>" class="text-decoration-none text-body">
                                        <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                                    </a>
                                    <span class="badge <?= $event['registered_count'] >= $event['capacity'] ? 'bg-danger' : 'bg-primary' ?> mb-2">
                                        <?= $event['registered_count'] ?>/<?= $event['capacity'] ?>
                                    </span>
                                </div>
                                <p class="event-date">Date: <?= date('M j, Y', strtotime($event['date'])) ?></p>
                                <a href="#" class="btn btn-sm register-btn <?= $event['is_registered'] ? 'btn-success disabled' : 'btn-secondary' ?>" 
                                data-event-id="<?= $event['id'] ?>">
                                    <?php if ($event['is_registered']): ?>
                                        ✓ Registered
                                    <?php else: ?>
                                        <?= is_logged_in() ? 'Attend' : 'Login to Attend' ?>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p>No other upcoming events</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php require 'includes/footer.php'; ?>