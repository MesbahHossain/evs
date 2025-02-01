<?php
    require_once 'includes/config.php';
    require_once 'includes/auth.php';

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
    
    $pageTitle = "Event Management System";
    include 'includes/header.php';
    
    if ($closest_event): ?>
    <section class="hero-event py-sm-5" style="background-image: url('uploads/<?= htmlspecialchars($closest_event['img']) ?>');">
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
    </section>
    <?php else: ?>
    <div class="container text-center bg-glass">
        <h2 class="mb-4">No upcoming events found</h2>
    </div>
    <?php endif; ?>

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
                                        Attend
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

    <!-- Registration Modal -->
    <div class="modal fade" id="registrationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Event Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="registrationForm">
                    <div class="modal-body">
                        <input type="hidden" name="event_id" id="modalEventId">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                            <div class="invalid-feedback">Please enter your full name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                            <div class="invalid-feedback" id="emailError">Please enter a valid email address</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" pattern="[0-9]{11}" required>
                            <div class="invalid-feedback" id="phoneError">Please enter a valid 11-digit phone number</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" class="form-control" min="1" max="100" required>
                            <div class="invalid-feedback">Please enter a valid age (1-100)</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="" disabled selected>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select your gender</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </form>
            </div>
        </div>

    <?php require 'includes/footer.php'; ?>