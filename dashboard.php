<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirect_if_not_logged_in();

// Pagination
$per_page = 2;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $per_page;

// Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date';
$order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';
$allowed_sort = ['title', 'date', 'location', 'category', 'capacity'];
$sort = in_array($sort, $allowed_sort) ? $sort : 'date';

// Filtering
$location_filter = isset($_GET['location']) ? trim($_GET['location']) : '';
$date_filter = isset($_GET['event_date']) ? trim($_GET['event_date']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';


// Base query
$query = "SELECT e.*, u.name as organizer, ec.name as category_name, 
          (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) AS registered
          FROM events e
          JOIN users u ON e.created_by = u.id
          LEFT JOIN event_categories ec ON e.category = ec.id"; // Join with event_categories

$where = [];
$params = [];

// Apply filters
if (!empty($location_filter)) {
    $where[] = "location LIKE ?";
    $params[] = "%$location_filter%";
}

if (!empty($date_filter)) {
    $where[] = "date = ?";
    $params[] = $date_filter;
}

if (!empty($category_filter)) {
    $where[] = "category = ?";
    $params[] = $category_filter;
}

// Add where clause if filters exist
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Add sorting and pagination
$query .= " ORDER BY $sort $order LIMIT :per_page OFFSET :offset";
$params['per_page'] = $per_page;
$params['offset'] = $offset;

// Prepare and execute with named parameters
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $param_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue(":$key", $value, $param_type);
}
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM events e";
if (!empty($where)) {
    $count_query .= " WHERE " . implode(" AND ", $where);
}
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute(array_slice($params, 0, -2));
$total_events = $count_stmt->fetchColumn();
$total_pages = ceil($total_events / $per_page);

// Delete event handling
if (isset($_POST['delete_event'])) {
    $event_id = (int)$_POST['event_id'];
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND created_by = ?");
    $stmt->execute([$event_id, $_SESSION['user_id']]);
    header("Location: dashboard.php");
    exit;
}
?>

<?php 
$pageTitle = "Dashboard - Event Management System";
include 'includes/header.php'; 
?>

<div class="container mt-4">
    <h2>Event List</h2>
    
    <!-- Filter Form -->
    <form class="mb-4 bg-light p-3 rounded">
        <div class="row">
            <div class="col-md-4">
                <input type="text" name="location" class="form-control" 
                       placeholder="Filter by location" value="<?= htmlspecialchars($location_filter) ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="event_date" class="form-control" 
                       value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="dashboard.php" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <!-- Event Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>
                        <a href="?sort=title&order=<?= $sort === 'title' && $order === 'ASC' ? 'DESC' : 'ASC' ?>">
                            Title <?= $sort === 'title' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=date&order=<?= $sort === 'date' && $order === 'ASC' ? 'DESC' : 'ASC' ?>">
                            Date <?= $sort === 'date' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=location&order=<?= $sort === 'location' && $order === 'ASC' ? 'DESC' : 'ASC' ?>">
                            Location <?= $sort === 'location' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=category&order=<?= $sort === 'category' && $order === 'ASC' ? 'DESC' : 'ASC' ?>">
                            Category <?= $sort === 'category' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=capacity&order=<?= $sort === 'capacity' && $order === 'ASC' ? 'DESC' : 'ASC' ?>">
                            Capacity <?= $sort === 'capacity' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                        </a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= htmlspecialchars($event['title']) ?></td>
                    <td><?= date('M j, Y', strtotime($event['date'])) ?></td>
                    <td><?= htmlspecialchars($event['location']) ?></td>
                    <td><?= htmlspecialchars($event['category_name']) ?></td>
                    <td>
                        <span class="badge <?= $event['registered'] >= $event['capacity'] ? 'bg-danger' : 'bg-success' ?>">
                            <?= $event['registered'] ?>/<?= $event['capacity'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="pages/events/view.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-info">View</a>
                        <?php if ($_SESSION['user_id'] == $event['created_by'] || is_admin()): ?>
                            <a href="pages/events/edit.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                <button type="submit" name="delete_event" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>">Previous</a>
                </li>
            <?php endif; ?>

            <?php if($total_pages > 1) :
            for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            endif; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<?php include 'includes/footer.php'; ?>