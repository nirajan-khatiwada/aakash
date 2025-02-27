<?php
// Include database connection
require_once 'config/db.php';
require_once 'includes/auth.php';

// Initialize variables
$errors = [];
$success = false;

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

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $role = $_POST['role'] ?? '';

    // Basic validation
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }

    if ($password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Passwords do not match';
    }

    if (empty($role)) {
        $errors['role'] = 'Role is required';
    }

    // Check if email already exists
    if (empty($errors['email'])) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'Email already registered';
        }
    }

    // If no errors, insert user into database
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hashedPassword, $role]);
            
            $success = true;
        } catch (PDOException $e) {
            $errors['general'] = 'Registration failed: ' . $e->getMessage();
        }
    }
}

// Set page title
$page_title = 'Register';
$additional_css = ['css/auth.css'];

// Include header
include 'includes/header.php';
?>

<div class="auth-container">
    <form class="auth-form" method="post" action="">
        <h2>Create Account</h2>

        <?php if ($success): ?>
            <div class="success-message" style="display:block; color:green; margin-bottom:15px; text-align:center;">
                Registration successful! <a href="login.php">Login here</a>
            </div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <div class="error-message" style="display:block;"><?php echo htmlspecialchars($errors['general']); ?></div>
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
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
            <?php if (isset($errors['name'])): ?>
                <div class="error-message" style="display:block;"><?php echo htmlspecialchars($errors['name']); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            <?php if (isset($errors['email'])): ?>
                <div class="error-message" style="display:block;"><?php echo htmlspecialchars($errors['email']); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <?php if (isset($errors['password'])): ?>
                <div class="error-message" style="display:block;"><?php echo htmlspecialchars($errors['password']); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirmPassword">Confirm Password</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required>
            <?php if (isset($errors['confirmPassword'])): ?>
                <div class="error-message" style="display:block;"><?php echo htmlspecialchars($errors['confirmPassword']); ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="auth-btn">Register</button>

        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </form>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>