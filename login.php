<?php
// Include database connection
require_once 'config/db.php';
require_once 'includes/auth.php';

// Initialize variables
$error = '';

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to appropriate dashboard
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin-dashboard.php');
    } else {
        header('Location: student-dashboard.php');
    }
    exit;
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Basic validation
    if (empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required';
    } else {
        // Prepare SQL statement
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ?');
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        // Check if user exists and password is correct
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Redirect to appropriate dashboard
            if ($role === 'admin') {
                header('Location: admin-dashboard.php');
            } else {
                header('Location: student-dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email, password, or role';
        }
    }
}

// Set page title
$page_title = 'Login';
$additional_css = ['css/auth.css'];

// Include header
include 'includes/header.php';
?>

<div class="auth-container">
    <form class="auth-form" method="post" action="">
        <h2>Login to LibraryMS</h2>
        
        <?php if ($error): ?>
            <div class="error-message" style="display: block;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="role-selector">
            <div class="role-option">
                <input type="radio" id="student" name="role" value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'checked' : 'checked'; ?>>
                <label for="student">Student</label>
            </div>
            <div class="role-option">
                <input type="radio" id="admin" name="role" value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'checked' : ''; ?>>
                <label for="admin">Admin</label>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="auth-btn">Login</button>

        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </form>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>