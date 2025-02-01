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
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : '';
$date_filter = isset($_GET['event_date']) ? trim($_GET['event_date']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query
$query = "SELECT e.*, u.name as organizer, ec.name as category_name, 
          (SELECT COUNT(*) FROM attendees WHERE event_id = e.id) AS registered
          FROM events e
          JOIN users u ON e.created_by = u.id
          LEFT JOIN event_categories ec ON e.category = ec.id";

$where = [];
$params = [];

// Apply filters
if (!empty($category_filter)) {
    $where[] = "e.category = :category";
    $params[':category'] = $category_filter;
}

if (!empty($date_filter)) {
    $where[] = "e.date = :date";
    $params[':date'] = $date_filter;
}

if (!empty($search_query)) {
    $where[] = "(e.title LIKE :search OR e.description LIKE :search)";
    $params[':search'] = "%$search_query%";
}

// Check if user is admin
if (!is_admin()) {
    $where[] = "e.created_by = :created_by";
    $params[':created_by'] = $_SESSION['user_id'];
}

// Add where clause if filters exist
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Add sorting
$query .= " ORDER BY $sort $order";

// Modified Pagination handling
$per_page = isset($_GET['per_page']) && is_numeric($_GET['per_page']) ? (int)$_GET['per_page'] : 2;
if($per_page > 0) { // 0 means show all
    $query .= " LIMIT :per_page OFFSET :offset";
    $params[':per_page'] = $per_page;
    $params[':offset'] = ($page - 1) * $per_page;
} elseif($per_page === 0) {
    unset($params[':per_page']);
    unset($params[':offset']);
}

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $param_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $param_type);
}
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM event_categories")->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM events e";
if (!empty($where)) {
    $count_query .= " WHERE " . implode(" AND ", $where);
}
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute(array_slice($params, 0, -2));
$total_events = $count_stmt->fetchColumn();
if ($per_page > 0) {
    $total_pages = ceil($total_events / $per_page);
} else {
    $total_pages = 1;
    $page = 1; 
}
?>

<?php 
$pageTitle = "Dashboard - Event Management System";
include 'includes/header.php'; 
?>

<div class="container mt-4">
    <div class="pb-1 mb-3 border-bottom d-flex justify-content-between align-items-center">
        <h2>Events</h2>
        <a href="create_event.php" class="btn btn-primary">Create New Event</a>
    </div>
    
    <!-- Filter Form -->
    <form class="mb-4 bg-body-secondary p-3 rounded">
        <div class="row g-3">
            <div class="col-sm-6 col-lg-4">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <input type="date" name="event_date" class="form-control" 
                    value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            <div class="col-sm-6 col-lg-3">
                <input type="text" name="search" class="form-control" 
                    placeholder="Search events..." value="<?= htmlspecialchars($search_query) ?>">
            </div>
            <div class="col-sm-6 col-lg-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="dashboard.php" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <!-- Event Table -->
    <div class="table-responsive">
        <table class="table table-hover border">
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
                            <a href="pages/events/delete.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
                            <a href="attendees.php?event_id=<?= $event['id'] ?>" class="btn btn-sm btn-primary" title="View Attendees">
                                <i class="bi bi-people"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav>
        <div class="row align-items-center">
            <div class="col-md-6">
                <form class="d-inline">
                    <!-- ... existing hidden inputs ... -->
                    <select name="per_page" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                        <option value="2" <?= $per_page == 2 ? 'selected' : '' ?>>2 per page</option>
                        <option value="5" <?= $per_page == 5 ? 'selected' : '' ?>>5 per page</option>
                        <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10 per page</option>
                        <option value="0" <?= $per_page == 0 ? 'selected' : '' ?>>Show All</option>
                    </select>
                </form>
            </div>
            
            <?php if ($per_page > 0): ?>
            <div class="col-md-6">
                <ul class="pagination justify-content-md-end mt-2 mt-md-0 mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php if($total_pages > 1) : ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>
</div>

<?php include 'includes/footer.php'; ?>