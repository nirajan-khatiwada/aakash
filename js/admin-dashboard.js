// Global functions
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function closeBookForm() {
    document.getElementById('bookForm').style.display = 'none';
    document.getElementById('addBookForm').reset();
}

function closeStudentModal() {
    document.getElementById('studentSelectModal').style.display = 'none';
    window.selectedStudentId = null;
    window.selectedBookId = null;
}

function showModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function assignBook(bookId) {
    window.selectedBookId = bookId;
    const students = JSON.parse(localStorage.getItem('users') || '[]').filter(user => user.role === 'student');
    
    if (students.length === 0) {
        alert('No students registered in the system');
        return;
    }

    const studentList = document.getElementById('studentList');
    studentList.innerHTML = '';
    
    students.forEach(student => {
        const div = document.createElement('div');
        div.className = 'student-item';
        div.innerHTML = `${student.name} (ID: ${student.id})`;
        div.onclick = () => selectStudent(student.id, div);
        studentList.appendChild(div);
    });

    // Set default due date to 14 days from now
    const defaultDueDate = new Date();
    defaultDueDate.setDate(defaultDueDate.getDate() + 14);
    document.getElementById('assignDueDate').value = defaultDueDate.toISOString().split('T')[0];

    showModal('studentSelectModal');
}

function selectStudent(studentId, element) {
    window.selectedStudentId = studentId;
    document.querySelectorAll('.student-item').forEach(item => {
        item.classList.remove('selected');
    });
    element.classList.add('selected');
}

function confirmAssignment() {
    if (!window.selectedStudentId) {
        alert('Please select a student');
        return;
    }

    const dueDate = document.getElementById('assignDueDate').value;
    if (!dueDate) {
        alert('Please select a due date');
        return;
    }

    const books = JSON.parse(localStorage.getItem('books') || '[]');
    const book = books.find(b => b.id === window.selectedBookId);
    
    if (book.quantity <= 0) {
        alert('No copies available for this book');
        return;
    }

    book.quantity--;
    if (book.quantity === 0) {
        book.status = 'out of stock';
    }

    const assignments = JSON.parse(localStorage.getItem('assignments') || '[]');
    const assignment = {
        id: Date.now().toString(),
        bookId: window.selectedBookId,
        studentId: window.selectedStudentId,
        assignedDate: new Date().toISOString(),
        dueDate: new Date(dueDate).toISOString()
    };
    
    assignments.push(assignment);
    
    localStorage.setItem('books', JSON.stringify(books));
    localStorage.setItem('assignments', JSON.stringify(assignments));
    
    closeStudentModal();
    window.location.reload(); // Refresh the page to update all tables and stats
}

function returnBook(assignmentId) {
    const assignments = JSON.parse(localStorage.getItem('assignments') || '[]');
    const books = JSON.parse(localStorage.getItem('books') || '[]');
    
    const assignment = assignments.find(a => a.id === assignmentId);
    const book = books.find(b => b.id === assignment.bookId);
    
    book.quantity++;
    if (book.quantity > 0) {
        book.status = 'available';
    }
    
    const updatedAssignments = assignments.filter(a => a.id !== assignmentId);
    
    localStorage.setItem('books', JSON.stringify(books));
    localStorage.setItem('assignments', JSON.stringify(updatedAssignments));
    
    window.location.reload(); // Refresh page to update all stats and tables
}

// Main initialization
document.addEventListener('DOMContentLoaded', () => {
    // Check authentication
    const currentUser = checkAuth();
    if (currentUser.role !== 'admin') {
        window.location.href = 'login.html';
    }

    document.getElementById('userName').textContent = currentUser.name;

    // Initialize data structures
    let books = JSON.parse(localStorage.getItem('books') || '[]');
    let assignments = JSON.parse(localStorage.getItem('assignments') || '[]');
    let users = JSON.parse(localStorage.getItem('users') || '[]');

    // Add event listeners
    document.getElementById('addBookBtn').addEventListener('click', showAddBookForm);
    document.getElementById('addBookForm').addEventListener('submit', handleFormSubmission);

    let selectedStudentId = null;
    let selectedBookId = null;

    // Function definitions
    function showAddBookForm(bookId = null) {
        const form = document.getElementById('bookForm');
        const submitBtn = document.getElementById('bookSubmitBtn');
        form.style.display = 'flex';
        
        if (bookId) {
            const book = books.find(b => b.id === bookId);
            if (book) {
                document.getElementById('bookTitle').value = book.title;
                document.getElementById('bookAuthor').value = book.author;
                document.getElementById('bookISBN').value = book.isbn;
                document.getElementById('bookQuantity').value = book.quantity;
                document.getElementById('addBookForm').dataset.editId = bookId;
                submitBtn.textContent = 'Update Book';
            }
        } else {
            document.getElementById('addBookForm').reset();
            delete document.getElementById('addBookForm').dataset.editId;
            submitBtn.textContent = 'Add Book';
        }
    }

    function handleFormSubmission(e) {
        e.preventDefault();
        
        const bookData = {
            title: document.getElementById('bookTitle').value,
            author: document.getElementById('bookAuthor').value,
            isbn: document.getElementById('bookISBN').value,
            quantity: parseInt(document.getElementById('bookQuantity').value),
            status: 'available'
        };

        const editId = e.target.dataset.editId;
        
        // Check for duplicate ISBN
        const existingBook = books.find(b => b.isbn === bookData.isbn && b.id !== editId);
        if (existingBook) {
            alert('A book with this ISBN already exists!');
            return;
        }

        if (editId) {
            const index = books.findIndex(b => b.id === editId);
            books[index] = { ...books[index], ...bookData };
        } else {
            bookData.id = Date.now().toString();
            books.push(bookData);
        }

        localStorage.setItem('books', JSON.stringify(books));
        closeBookForm();
        refreshBooksTable();
        updateStats();
    }

    // Refresh books table
    function refreshBooksTable() {
        const tbody = document.getElementById('booksTableBody');
        tbody.innerHTML = '';

        books.forEach(book => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${book.title}</td>
                <td>${book.author}</td>
                <td>${book.isbn}</td>
                <td>${book.quantity}</td>
                <td>${book.status}</td>
                <td>
                    ${book.quantity > 0 ? 
                        `<button onclick="assignBook('${book.id}')" class="btn btn-small">Assign</button>` : 
                        ''}
                    <button onclick="showAddBookForm('${book.id}')" class="btn btn-small btn-edit">Edit</button>
                    <button onclick="deleteBook('${book.id}')" class="btn btn-small btn-danger">Delete</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Delete book
    function deleteBook(bookId) {
        if (confirm('Are you sure you want to delete this book?')) {
            books = books.filter(book => book.id !== bookId);
            localStorage.setItem('books', JSON.stringify(books));
            refreshBooksTable();
            updateStats();
        }
    }

    // Refresh assignments table
    function refreshAssignmentsTable() {
        const tbody = document.getElementById('assignmentsTableBody');
        tbody.innerHTML = '';

        assignments.forEach(assignment => {
            const book = books.find(b => b.id === assignment.bookId);
            const student = users.find(u => u.id === assignment.studentId);
            
            // Skip if book or student no longer exists
            if (!book || !student) {
                return;
            }
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${book.title}</td>
                <td>${student.name}</td>
                <td>${new Date(assignment.assignedDate).toLocaleDateString()}</td>
                <td>${new Date(assignment.dueDate).toLocaleDateString()}</td>
                <td>
                    <button onclick="returnBook('${assignment.id}')" class="btn btn-small">Return</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Clean up orphaned assignments
        const validAssignments = assignments.filter(assignment => {
            const book = books.find(b => b.id === assignment.bookId);
            const student = users.find(u => u.id === assignment.studentId);
            return book && student;
        });

        if (validAssignments.length !== assignments.length) {
            assignments = validAssignments;
            localStorage.setItem('assignments', JSON.stringify(assignments));
        }
    }

    // Update dashboard stats
    function updateStats() {
        // Calculate total books by summing quantities
        const totalQuantity = books.reduce((sum, book) => sum + book.quantity, 0);
        document.getElementById('totalBooks').textContent = totalQuantity;
        
        document.getElementById('assignedBooks').textContent = assignments.length;
        document.getElementById('totalStudents').textContent = 
            users.filter(user => user.role === 'student').length;
        
        // Add due students count
        const today = new Date();
        const dueCount = assignments.filter(a => new Date(a.dueDate) < today).length;
        document.getElementById('dueStudents').textContent = dueCount;

        refreshStudentsGrid();
    }

    // Initial load
    updateStats();
    refreshBooksTable();
    refreshAssignmentsTable();
    refreshStudentsGrid();

    function showAllStudentsModal() {
        const modal = document.getElementById('allStudentsModal');
        const grid = document.getElementById('allStudentsGrid');
        grid.innerHTML = '';

        const students = users.filter(user => user.role === 'student');
        students.forEach(student => {
            const card = createStudentCard(student);
            grid.appendChild(card);
        });

        modal.style.display = 'flex';
    }

    function showDueStudentsModal() {
        const modal = document.getElementById('dueStudentsModal');
        const list = document.getElementById('dueStudentsList');
        list.innerHTML = '';

        const today = new Date();
        const dueAssignments = assignments.filter(a => new Date(a.dueDate) < today);
        
        dueAssignments.forEach(assignment => {
            const student = users.find(u => u.id === assignment.studentId);
            const book = books.find(b => b.id === assignment.bookId);
            
            const card = document.createElement('div');
            card.className = 'student-card overdue';
            card.innerHTML = `
                <h3>${student.name}</h3>
                <p>Book: ${book.title}</p>
                <p>Due Date: ${new Date(assignment.dueDate).toLocaleDateString()}</p>
                <p>Days Overdue: ${Math.floor((today - new Date(assignment.dueDate)) / (1000 * 60 * 60 * 24))}</p>
            `;
            list.appendChild(card);
        });

        modal.style.display = 'flex';
    }

    function createStudentCard(student) {
        const card = document.createElement('div');
        card.className = 'student-card';
        const studentAssignments = assignments.filter(a => a.studentId === student.id);
        
        let recentBookInfo = 'No books borrowed';
        if (studentAssignments.length > 0) {
            const lastAssignment = studentAssignments[studentAssignments.length - 1];
            const book = books.find(b => b.id === lastAssignment.bookId);
            if (book) {
                recentBookInfo = `Recent: ${book.title}`;
            } else {
                recentBookInfo = 'Recent book not found';
            }
        }

        card.innerHTML = `
            <h3>${student.name}</h3>
            <p>ID: ${student.id}</p>
            <p>Books Borrowed: ${studentAssignments.length}</p>
            <p>${recentBookInfo}</p>
        `;
        return card;
    }

    function refreshStudentsGrid() {
        const grid = document.getElementById('studentsGrid');
        grid.innerHTML = '';
        
        const students = users.filter(user => user.role === 'student')
            .slice(0, 4); // Show only first 4 students in dashboard
        
        students.forEach(student => {
            const card = createStudentCard(student);
            grid.appendChild(card);
        });
    }

    // Make functions available globally
    window.closeModal = closeModal;
    window.closeBookForm = closeBookForm;
    window.closeStudentModal = closeStudentModal;
    window.showAllStudentsModal = showAllStudentsModal;
    window.showDueStudentsModal = showDueStudentsModal;
    window.assignBook = assignBook;
    window.confirmAssignment = confirmAssignment;
    window.returnBook = returnBook;
    window.showAddBookForm = showAddBookForm; // Add this
    window.deleteBook = deleteBook; // Add this
});
