<?php
// Include database connection
require_once 'config/db.php';
require_once 'includes/auth.php';

// Ensure user is logged in and is an admin
requireAdmin();

// Process book addition/editing
$bookMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_action'])) {
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $isbn = $_POST['isbn'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 0);
    $status = $quantity > 0 ? 'available' : 'out of stock';
    $bookId = $_POST['book_id'] ?? '';

    if (empty($title) || empty($author) || empty($isbn) || $quantity < 0) {
        $bookMessage = 'Please fill in all fields correctly';
    } else {
        try {
            // Check for duplicate ISBN
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM books WHERE isbn = ? AND id != ?');
            $stmt->execute([$isbn, $bookId]);
            if ($stmt->fetchColumn() > 0) {
                $bookMessage = 'A book with this ISBN already exists';
            } else {
                // Either update or insert
                if (!empty($bookId)) {
                    $stmt = $pdo->prepare('UPDATE books SET title = ?, author = ?, isbn = ?, quantity = ?, status = ? WHERE id = ?');
                    $stmt->execute([$title, $author, $isbn, $quantity, $status, $bookId]);
                    $bookMessage = 'Book updated successfully';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO books (title, author, isbn, quantity, status) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$title, $author, $isbn, $quantity, $status]);
                    $bookMessage = 'Book added successfully';
                }
            }
        } catch (PDOException $e) {
            $bookMessage = 'Error: ' . $e->getMessage();
        }
    }
}

// Process book deletion
if (isset($_GET['delete_book']) && is_numeric($_GET['delete_book'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM books WHERE id = ?');
        $stmt->execute([$_GET['delete_book']]);
        $bookMessage = 'Book deleted successfully';
    } catch (PDOException $e) {
        $bookMessage = 'Error deleting book: ' . $e->getMessage();
    }
}

// Process book assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_book'])) {
    $bookId = $_POST['book_id'] ?? '';
    $studentId = $_POST['student_id'] ?? '';
    $dueDate = $_POST['due_date'] ?? '';
    
    if (empty($bookId) || empty($studentId) || empty($dueDate)) {
        $assignmentMessage = 'Please fill in all assignment fields';
    } else {
        try {
            // Check if book is available
            $stmt = $pdo->prepare('SELECT quantity, status FROM books WHERE id = ?');
            $stmt->execute([$bookId]);
            $book = $stmt->fetch();
            
            if ($book && $book['quantity'] > 0) {
                // Create assignment
                $stmt = $pdo->prepare('INSERT INTO assignments (book_id, student_id, due_date) VALUES (?, ?, ?)');
                $stmt->execute([$bookId, $studentId, $dueDate]);
                
                // Update book quantity
                $newQuantity = $book['quantity'] - 1;
                $newStatus = $newQuantity > 0 ? 'available' : 'out of stock';
                
                $stmt = $pdo->prepare('UPDATE books SET quantity = ?, status = ? WHERE id = ?');
                $stmt->execute([$newQuantity, $newStatus, $bookId]);
                
                $assignmentMessage = 'Book assigned successfully';
            } else {
                $assignmentMessage = 'Book is not available for assignment';
            }
        } catch (PDOException $e) {
            $assignmentMessage = 'Error assigning book: ' . $e->getMessage();
        }
    }
}

// Process book return
if (isset($_GET['return_assignment']) && is_numeric($_GET['return_assignment'])) {
    try {
        // Get the book ID first
        $stmt = $pdo->prepare('SELECT book_id FROM assignments WHERE id = ?');
        $stmt->execute([$_GET['return_assignment']]);
        $assignment = $stmt->fetch();
        
        if ($assignment) {
            // Update book quantity and status
            $stmt = $pdo->prepare('UPDATE books SET quantity = quantity + 1, status = "available" WHERE id = ?');
            $stmt->execute([$assignment['book_id']]);
            
            // Delete the assignment
            $stmt = $pdo->prepare('DELETE FROM assignments WHERE id = ?');
            $stmt->execute([$_GET['return_assignment']]);
            
            $returnMessage = 'Book returned successfully';
        } else {
            $returnMessage = 'Assignment not found';
        }
    } catch (PDOException $e) {
        $returnMessage = 'Error returning book: ' . $e->getMessage();
    }
}

// Get all books for the table
$books = $pdo->query('SELECT * FROM books ORDER BY title')->fetchAll();

// Get all students (for assigning books)
$students = $pdo->query('SELECT id, name FROM users WHERE role = "student" ORDER BY name')->fetchAll();

// Get all assignments for the table
$stmt = $pdo->query('
    SELECT a.id, a.assigned_date, a.due_date, b.title, u.name as student_name
    FROM assignments a
    JOIN books b ON a.book_id = b.id
    JOIN users u ON a.student_id = u.id
    ORDER BY a.due_date ASC
');
$assignments = $stmt->fetchAll();

// Calculate dashboard stats
$totalBooks = $pdo->query('SELECT SUM(quantity) as total FROM books')->fetch()['total'] ?? 0;
$assignedBooks = $pdo->query('SELECT COUNT(*) as total FROM assignments')->fetch()['total'] ?? 0;
$totalStudents = $pdo->query('SELECT COUNT(*) as total FROM users WHERE role = "student"')->fetch()['total'] ?? 0;

// Calculate due/overdue books
$now = new DateTime();
$today = $now->format('Y-m-d');

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM assignments WHERE due_date < ?');
$stmt->execute([$today]);
$overdueCount = $stmt->fetch()['total'] ?? 0;

// Set page title and CSS
$page_title = 'Admin Dashboard';
$additional_css = ['css/dashboard.css'];

// Include header
include 'includes/header.php';
?>

<div class="dashboard-container">
    <?php if (!empty($bookMessage)): ?>
        <div class="alert <?php echo strpos($bookMessage, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>" style="padding: 10px; margin-bottom: 20px; border-radius: 4px; background-color: <?php echo strpos($bookMessage, 'Error') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($bookMessage, 'Error') !== false ? '#721c24' : '#155724'; ?>;">
            <?php echo htmlspecialchars($bookMessage); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($assignmentMessage)): ?>
        <div class="alert <?php echo strpos($assignmentMessage, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>" style="padding: 10px; margin-bottom: 20px; border-radius: 4px; background-color: <?php echo strpos($assignmentMessage, 'Error') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($assignmentMessage, 'Error') !== false ? '#721c24' : '#155724'; ?>;">
            <?php echo htmlspecialchars($assignmentMessage); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($returnMessage)): ?>
        <div class="alert <?php echo strpos($returnMessage, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>" style="padding: 10px; margin-bottom: 20px; border-radius: 4px; background-color: <?php echo strpos($returnMessage, 'Error') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($returnMessage, 'Error') !== false ? '#721c24' : '#155724'; ?>;">
            <?php echo htmlspecialchars($returnMessage); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>Total Books Remaining</h3>
            <p><?php echo $totalBooks; ?></p>
        </div>
        <div class="stat-card">
            <h3>Books Assigned</h3>
            <p><?php echo $assignedBooks; ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Students</h3>
            <p><?php echo $totalStudents; ?></p>
        </div>
        <div class="stat-card">
            <h3>Due Students</h3>
            <p class="due-students"><?php echo $overdueCount; ?></p>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="main-section">
            <h2 class="section-title">Students Overview</h2>
            <div class="students-grid" id="studentsGrid">
                <?php
                // Get all students
                $studentsList = $pdo->query('
                    SELECT u.id, u.name, COUNT(a.id) as borrowed_count
                    FROM users u
                    LEFT JOIN assignments a ON u.id = a.student_id
                    WHERE u.role = "student"
                    GROUP BY u.id
                    ORDER BY borrowed_count DESC
                    LIMIT 4
                ')->fetchAll();
                
                foreach ($studentsList as $student) {
                    // Get the most recent book borrowed by this student
                    $stmt = $pdo->prepare('
                        SELECT b.title
                        FROM assignments a
                        JOIN books b ON a.book_id = b.id
                        WHERE a.student_id = ?
                        ORDER BY a.assigned_date DESC
                        LIMIT 1
                    ');
                    $stmt->execute([$student['id']]);
                    $recentBook = $stmt->fetch();
                    
                    echo '<div class="student-card">';
                    echo '<h3>' . htmlspecialchars($student['name']) . '</h3>';
                    echo '<p>ID: ' . htmlspecialchars($student['id']) . '</p>';
                    echo '<p>Books Borrowed: ' . htmlspecialchars($student['borrowed_count']) . '</p>';
                    if ($recentBook) {
                        echo '<p>Recent: ' . htmlspecialchars($recentBook['title']) . '</p>';
                    } else {
                        echo '<p>No books borrowed</p>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="side-section">
            <h2 class="section-title">Due Students</h2>
            <div id="dueStudentsList">
                <?php
                // Get all overdue assignments
                $stmt = $pdo->prepare('
                    SELECT a.*, u.name as student_name, b.title as book_title
                    FROM assignments a
                    JOIN users u ON a.student_id = u.id
                    JOIN books b ON a.book_id = b.id
                    WHERE a.due_date < ?
                    ORDER BY a.due_date ASC
                ');
                $stmt->execute([$today]);
                $overdueAssignments = $stmt->fetchAll();
                
                foreach ($overdueAssignments as $assignment) {
                    $dueDate = new DateTime($assignment['due_date']);
                    $daysOverdue = $now->diff($dueDate)->days;
                    
                    echo '<div class="student-card overdue">';
                    echo '<h3>' . htmlspecialchars($assignment['student_name']) . '</h3>';
                    echo '<p>Book: ' . htmlspecialchars($assignment['book_title']) . '</p>';
                    echo '<p>Due Date: ' . htmlspecialchars($assignment['due_date']) . '</p>';
                    echo '<p>Days Overdue: ' . $daysOverdue . '</p>';
                    echo '</div>';
                }
                
                if (empty($overdueAssignments)) {
                    echo '<p style="text-align:center;">No overdue books</p>';
                }
                ?>
            </div>
        </div>
    </div>

    <div class="section-divider"></div>

    <div class="main-section">
        <div class="dashboard-actions">
            <button type="button" class="btn" onclick="document.getElementById('bookForm').style.display='flex';">Add New Book</button>
        </div>
        <h2 class="section-title">Books Management</h2>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                <tr>
                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                    <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                    <td><?php echo htmlspecialchars($book['quantity']); ?></td>
                    <td><?php echo htmlspecialchars($book['status']); ?></td>
                    <td>
                        <?php if ($book['quantity'] > 0): ?>
                            <button onclick="document.getElementById('assignBookId').value='<?php echo $book['id']; ?>'; document.getElementById('studentSelectModal').style.display='flex';" class="btn btn-small">Assign</button>
                        <?php endif; ?>
                        <button onclick="editBook(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>', '<?php echo addslashes($book['author']); ?>', '<?php echo addslashes($book['isbn']); ?>', <?php echo $book['quantity']; ?>)" class="btn btn-small btn-edit">Edit</button>
                        <a href="?delete_book=<?php echo $book['id']; ?>" onclick="return confirm('Are you sure you want to delete this book?')" class="btn btn-small btn-danger">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($books)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">No books available</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section-divider"></div>

    <div class="main-section">
        <h2 class="section-title">Book Assignments</h2>
        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Student Name</th>
                    <th>Assigned Date</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $assignment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                    <td><?php echo htmlspecialchars($assignment['student_name']); ?></td>
                    <td><?php echo (new DateTime($assignment['assigned_date']))->format('Y-m-d'); ?></td>
                    <td><?php echo htmlspecialchars($assignment['due_date']); ?></td>
                    <td>
                        <a href="?return_assignment=<?php echo $assignment['id']; ?>" class="btn btn-small">Return</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($assignments)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">No assignments</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Book Form Modal -->
    <div id="bookForm" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('bookForm').style.display='none';">&times;</span>
            <h2 id="bookFormTitle">Add New Book</h2>
            <form method="post" action="">
                <input type="hidden" id="bookId" name="book_id">
                <input type="hidden" name="book_action" value="1">
                
                <div class="form-group">
                    <label for="bookTitle">Title</label>
                    <input type="text" id="bookTitle" name="title" placeholder="e.g. Harry Potter" required>
                </div>
                <div class="form-group">
                    <label for="bookAuthor">Author</label>
                    <input type="text" id="bookAuthor" name="author" placeholder="e.g. J.K. Rowling" required>
                </div>
                <div class="form-group">
                    <label for="bookISBN">ISBN</label>
                    <input type="text" id="bookISBN" name="isbn" placeholder="e.g. 978-0-7475-3269-9" required>
                </div>
                <div class="form-group">
                    <label for="bookQuantity">Quantity</label>
                    <input type="number" id="bookQuantity" name="quantity" min="0" value="1" required>
                </div>
                <button type="submit" class="btn" id="bookSubmitBtn">Add Book</button>
            </form>
        </div>
    </div>

    <!-- Student Select Modal -->
    <div id="studentSelectModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('studentSelectModal').style.display='none';">&times;</span>
            <h2 class="modal-title">Assign Book to Student</h2>
            <form method="post" action="">
                <input type="hidden" id="assignBookId" name="book_id">
                <input type="hidden" name="assign_book" value="1">
                
                <div class="form-group">
                    <label for="studentId">Select Student</label>
                    <select id="studentId" name="student_id" class="form-control" required>
                        <option value="">-- Select a student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="dueDate">Due Date</label>
                    <?php
                    $defaultDueDate = new DateTime();
                    $defaultDueDate->add(new DateInterval('P14D')); // Add 14 days
                    ?>
                    <input type="date" id="dueDate" name="due_date" value="<?php echo $defaultDueDate->format('Y-m-d'); ?>" required>
                </div>
                
                <button type="submit" class="btn">Assign Book</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Function to handle book editing
    function editBook(id, title, author, isbn, quantity) {
        document.getElementById('bookId').value = id;
        document.getElementById('bookTitle').value = title;
        document.getElementById('bookAuthor').value = author;
        document.getElementById('bookISBN').value = isbn;
        document.getElementById('bookQuantity').value = quantity;
        document.getElementById('bookFormTitle').textContent = 'Edit Book';
        document.getElementById('bookSubmitBtn').textContent = 'Update Book';
        document.getElementById('bookForm').style.display = 'flex';
    }
    
    // Function to reset the book form for adding a new book
    function resetBookForm() {
        document.getElementById('bookId').value = '';
        document.getElementById('bookTitle').value = '';
        document.getElementById('bookAuthor').value = '';
        document.getElementById('bookISBN').value = '';
        document.getElementById('bookQuantity').value = '1';
        document.getElementById('bookFormTitle').textContent = 'Add New Book';
        document.getElementById('bookSubmitBtn').textContent = 'Add Book';
    }
</script>

<?php
// Include footer
include 'includes/footer.php';
?>