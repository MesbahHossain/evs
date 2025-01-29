<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Check if the email format is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['is_admin'] = $user['is_admin'] ?? false;

                add_flash_message('success', 'Login successful!');
                header('Location: ../index.php');
                exit;
            } else {
                $error = 'Invalid email or password';
            }
        }
    } else {
        $error = 'Please fill in all fields';
    }
}
?>

<?php 
$pageTitle = "Login - Event Management System";
include '../includes/header.php'; 
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Login</div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return validateLoginForm()">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Login</button>
                        <a href="register.php" class="btn btn-link">Register here</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Validates the login form. If the email or password fields are empty, an alert is
     * shown and the function returns false. Otherwise, the function returns true.
     * @returns {boolean} Whether the form is valid.
     */
    const validateLoginForm = () => {
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        if (email.trim() === '' || password.trim() === '') {
            alert('Email and password are required.');
            return false;
        }

        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address.');
            return false;
        }

        return true;
    };
</script>

<?php include '../includes/footer.php'; ?>