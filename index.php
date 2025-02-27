<?php
// Include database connection and authentication
require_once 'config/db.php';
require_once 'includes/auth.php';

// Set page title
$page_title = 'Home';

// Include header
include 'includes/header.php';
?>

<main>
    <section class="hero">
        <div class="hero-content">
            <h1>Modern Library Management</h1>
            <p>Simplify your library operations</p>
            <a href="login.php" class="btn btn-cta">Get Started</a>
        </div>
        <div class="hero-stats">
            <?php
            // Get actual stats from database
            $bookCount = $pdo->query('SELECT SUM(quantity) as total FROM books')->fetch()['total'] ?? 0;
            $userCount = $pdo->query('SELECT COUNT(*) as total FROM users WHERE role = "student"')->fetch()['total'] ?? 0;
            ?>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($bookCount); ?>+</span>
                <span class="stat-label">Books</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($userCount); ?>+</span>
                <span class="stat-label">Users</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">99%</span>
                <span class="stat-label">Satisfaction</span>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="feature-grid">
            <div class="feature-card">
                <i class="fas fa-book"></i>
                <h3>Book Management</h3>
            </div>
            <div class="feature-card">
                <i class="fas fa-users"></i>
                <h3>User System</h3>
            </div>
            <div class="feature-card">
                <i class="fas fa-bell"></i>
                <h3>Notifications</h3>
            </div>
        </div>
    </section>
</main>

<?php
// Include footer
include 'includes/footer.php';
?>