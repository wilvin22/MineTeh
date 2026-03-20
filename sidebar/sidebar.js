// Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    // Highlight active page
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.navigation').forEach(item => {
        const link = item.parentElement.getAttribute('href');
        if (link === currentPage) item.classList.add('active');
    });

    const sidebar   = document.getElementById('sidebar');
    const closeBtn  = document.getElementById('sidebar-close');
    const openBtn   = document.getElementById('sidebar-open');

    if (closeBtn) {
        closeBtn.addEventListener('click', () => sidebar.classList.add('collapsed'));
    }

    if (openBtn) {
        openBtn.addEventListener('click', () => sidebar.classList.remove('collapsed'));
    }
});
