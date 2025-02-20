// Add smooth reveal animation for feature cards
const cards = document.querySelectorAll('.feature-card');

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, {
    threshold: 0.1
});

cards.forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease-out';
    observer.observe(card);
});

// Mobile menu toggle
const menuToggle = document.querySelector('.menu-toggle');
const authButtons = document.querySelector('.auth-buttons');

let menuOpen = false;

menuToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    menuOpen = !menuOpen;
    authButtons.classList.toggle('active');
    menuToggle.querySelector('i').classList.toggle('fa-bars');
    menuToggle.querySelector('i').classList.toggle('fa-times');
});

// Close menu when clicking anywhere else
document.addEventListener('click', () => {
    if (menuOpen) {
        menuOpen = false;
        authButtons.classList.remove('active');
        menuToggle.querySelector('i').classList.add('fa-bars');
        menuToggle.querySelector('i').classList.remove('fa-times');
    }
});

// Prevent menu from closing when clicking inside it
authButtons.addEventListener('click', (e) => {
    e.stopPropagation();
});

// Add touch feedback for buttons
const buttons = document.querySelectorAll('.btn');
buttons.forEach(button => {
    button.addEventListener('touchstart', () => {
        button.style.transform = 'scale(0.98)';
    });
    button.addEventListener('touchend', () => {
        button.style.transform = 'scale(1)';
    });
});

// Enhanced hover effects for feature cards
cards.forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-10px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', () => {
        card.style.transform = 'translateY(0) scale(1)';
    });
});

// Smooth scroll handling for mobile
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
            const navbarHeight = document.querySelector('.navbar').offsetHeight;
            const targetPosition = targetElement.offsetTop - navbarHeight;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
            
            // Close mobile menu if open
            if (menuOpen) {
                menuToggle.click();
            }
        }
    });
});
