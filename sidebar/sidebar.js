// Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    // Highlight active page
    const currentPage = window.location.pathname.split('/').pop();
    const navItems = document.querySelectorAll('.navigation');
    
    navItems.forEach(item => {
        const link = item.parentElement.getAttribute('href');
        if (link === currentPage) {
            item.classList.add('active');
        }
    });
    
    // Toggle sidebar
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const toggleIcon = document.getElementById('toggle-icon');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Change icon
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.textContent = '☰';
            } else {
                toggleIcon.textContent = '✕';
            }
        });
    }
});
