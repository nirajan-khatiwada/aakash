<?php
// Database setup script - Run this first to set up your database

// Database configuration
$host = 'localhost'; 
$username = 'root';
$password = '';

try {
    // Connect without database selected
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Library Management System - Database Setup</h1>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS library_ms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Database 'library_ms' created or already exists.</p>";
    
    // Switch to the new database
    $pdo->exec("USE library_ms");
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'student') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Users table created successfully.</p>";
    
    // Create books table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(100) NOT NULL,
            isbn VARCHAR(20) NOT NULL UNIQUE,
            quantity INT NOT NULL DEFAULT 0,
            status ENUM('available', 'out of stock') NOT NULL DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Books table created successfully.</p>";
    
    // Create assignments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NOT NULL,
            student_id INT NOT NULL,
            assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            due_date DATE NOT NULL,
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Assignments table created successfully.</p>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@example.com'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn() > 0;
    
    if (!$adminExists) {
        // Create default admin user (password: 'password')
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Admin User', 'admin@example.com', $hashedPassword, 'admin']);
        echo "<p>✅ Default admin user created. Email: admin@example.com, Password: password</p>";
    } else {
        echo "<p>✅ Admin user already exists.</p>";
    }
    
    // Add sample data
    $sampleBooks = [
        ['Clean Code', 'Robert C. Martin', '9780132350884', 5],
        ['The Pragmatic Programmer', 'Andrew Hunt', '9780201616224', 3],
        ['Design Patterns', 'Erich Gamma, Richard Helm', '9780201633610', 2],
        ['Refactoring', 'Martin Fowler', '9780201485677', 4]
    ];
    
    $bookCount = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
    if ($bookCount == 0) {
        $stmt = $pdo->prepare("INSERT INTO books (title, author, isbn, quantity, status) VALUES (?, ?, ?, ?, 'available')");
        foreach ($sampleBooks as $book) {
            $stmt->execute($book);
        }
        echo "<p>✅ Sample books added successfully.</p>";
    } else {
        echo "<p>✅ Books already exist in the database.</p>";
    }
    
    echo "<div style='margin-top:20px; padding:10px; background-color:#d4edda; color:#155724; border-radius:5px;'>";
    echo "<h2>Setup Complete!</h2>";
    echo "<p>Your database has been set up successfully. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='index.php'>Go to home page</a></li>";
    echo "<li><a href='login.php'>Login to the system</a> (admin@example.com / password)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='margin-top:20px; padding:10px; background-color:#f8d7da; color:#721c24; border-radius:5px;'>";
    echo "<h2>Setup Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
}
?>