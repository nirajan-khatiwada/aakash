// Check authentication
const currentUser = checkAuth();
if (currentUser.role !== 'student') {
    window.location.href = 'login.html';
}

document.getElementById('userName').textContent = currentUser.name;

// Get data from localStorage
const books = JSON.parse(localStorage.getItem('books') || '[]');
const assignments = JSON.parse(localStorage.getItem('assignments') || '[]');

// Update dashboard stats
function updateStats() {
    const userAssignments = assignments.filter(a => a.studentId === currentUser.id);
    const now = new Date();
    const dueSoon = userAssignments.filter(a => {
        const dueDate = new Date(a.dueDate);
        const daysUntilDue = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24));
        return daysUntilDue <= 3 && daysUntilDue > 0;
    });

    document.getElementById('borrowedBooks').textContent = userAssignments.length;
    document.getElementById('dueSoonBooks').textContent = dueSoon.length;
}

// Refresh borrowed books table
function refreshBorrowedBooksTable() {
    const tbody = document.getElementById('borrowedBooksTableBody');
    tbody.innerHTML = '';

    const userAssignments = assignments.filter(a => a.studentId === currentUser.id);
    
    userAssignments.forEach(assignment => {
        const book = books.find(b => b.id === assignment.bookId);
        const dueDate = new Date(assignment.dueDate);
        const now = new Date();
        const status = dueDate < now ? 'Overdue' : 'Active';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${book.title}</td>
            <td>${book.author}</td>
            <td>${new Date(assignment.assignedDate).toLocaleDateString()}</td>
            <td>${new Date(assignment.dueDate).toLocaleDateString()}</td>
            <td class="${status.toLowerCase()}">${status}</td>
        `;
        tbody.appendChild(row);
    });
}

// Initial load
updateStats();
refreshBorrowedBooksTable();
