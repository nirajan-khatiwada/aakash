const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');

// Helper function to show error messages
const showError = (elementId, message) => {
    const errorElement = document.getElementById(elementId);
    errorElement.textContent = message;
    errorElement.style.display = 'block';
};

// Helper function to clear error messages
const clearErrors = () => {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.style.display = 'none';
        element.textContent = '';
    });
};

// Handle Registration
if (registerForm) {
    registerForm.addEventListener('submit', (e) => {
        e.preventDefault();
        clearErrors();

        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const role = document.querySelector('input[name="role"]:checked').value;

        // Validation
        if (password !== confirmPassword) {
            showError('confirmPasswordError', 'Passwords do not match');
            return;
        }

        if (password.length < 6) {
            showError('passwordError', 'Password must be at least 6 characters');
            return;
        }

        // Get existing users or initialize empty array
        const users = JSON.parse(localStorage.getItem('users') || '[]');

        // Check if email already exists
        if (users.some(user => user.email === email)) {
            showError('emailError', 'Email already registered');
            return;
        }

        // Add new user
        users.push({
            name,
            email,
            password,
            role,
            id: Date.now().toString()
        });

        localStorage.setItem('users', JSON.stringify(users));
        
        alert('Registration successful! Please login.');
        window.location.href = 'login.html';
    });
}

// Handle Login
if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        clearErrors();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const role = document.querySelector('input[name="role"]:checked').value;

        const users = JSON.parse(localStorage.getItem('users') || '[]');
        const user = users.find(u => u.email === email && u.password === password && u.role === role);

        if (!user) {
            showError('emailError', 'Invalid email, password, or role');
            return;
        }

        // Store logged in user info
        const sessionUser = {
            id: user.id,
            name: user.name,
            email: user.email,
            role: user.role
        };
        
        localStorage.setItem('currentUser', JSON.stringify(sessionUser));

        // Redirect based on role
        if (role === 'admin') {
            window.location.href = 'admin-dashboard.html';
        } else {
            window.location.href = 'student-dashboard.html';
        }
    });
}

// Check if user is logged in
const checkAuth = () => {
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (!currentUser) {
        window.location.href = 'login.html';
    }
    return currentUser;
};

// Logout function
const logout = () => {
    localStorage.removeItem('currentUser');
    window.location.href = 'login.html';
};
