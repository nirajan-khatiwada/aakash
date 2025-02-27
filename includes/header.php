<?php
// Determine current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>LibraryMS</title>
    <link rel="stylesheet" href="styles.css">
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="index.php" style="text-decoration: none;">
                <i class="fas fa-book-reader"></i>
                <span>LibraryMS</span>
            </a>
        </div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="logout.php" class="btn btn-logout">Logout</a>
            </div>
        <?php else: ?>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="auth-buttons">
                <a href="login.php" class="btn btn-login">Login</a>
                <a href="register.php" class="btn btn-register">Sign Up</a>
            </div>
        <?php endif; ?>
    </nav>