<?php
// Include database connection
require_once 'config/db.php';
require_once 'includes/auth.php';

// Ensure user is logged in and is a student
requireStudent();

// Set page title and CSS
$page_title = 'Student Dashboard';
$additional_css = ['css/dashboard.css'];

// Include header
include 'includes/header.php';

// Get student's borrowed books (assignments)
$stmt = $pdo->prepare('
    SELECT a.*, b.title, b.author 
    FROM assignments a
    JOIN books b ON a.book_id = b.id
    WHERE a.student_id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$assignments = $stmt->fetchAll();

// Calculate books due soon (within next 3 days)
$now = new DateTime();
$dueSoonCount = 0;

foreach ($assignments as $assignment) {
    $dueDate = new DateTime($assignment['due_date']);
    $daysUntilDue = $now->diff($dueDate)->days;
    
    if ($dueDate > $now && $daysUntilDue <= 3) {
        $dueSoonCount++;
    }
}
?>

<div class="dashboard-container">
    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>Books Borrowed</h3>
            <p id="borrowedBooks"><?php echo count($assignments); ?></p>
        </div>
        <div class="stat-card">
            <h3>Books Due Soon</h3>
            <p id="dueSoonBooks"><?php echo $dueSoonCount; ?></p>
        </div>
    </div>

    <div class="borrowed-books-section">
        <h2>My Borrowed Books</h2>
        <table id="borrowedBooksTable">
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Borrowed Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="borrowedBooksTableBody">
                <?php if (count($assignments) > 0): ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <?php 
                        $assignedDate = new DateTime($assignment['assigned_date']);
                        $dueDate = new DateTime($assignment['due_date']);
                        $now = new DateTime();
                        $status = $dueDate < $now ? 'Overdue' : 'Active';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['author']); ?></td>
                            <td><?php echo $assignedDate->format('Y-m-d'); ?></td>
                            <td><?php echo $dueDate->format('Y-m-d'); ?></td>
                            <td class="<?php echo strtolower($status); ?>"><?php echo $status; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">You have not borrowed any books yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>