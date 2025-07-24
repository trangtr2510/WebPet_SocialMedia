const toggleButton = document.getElementById('toggle-btn');
const sidebar = document.getElementById('sidebar');

function toggleSidebar() {
    sidebar.classList.toggle('close');

    // Toggle icon rotation for the toggle button
    const icon = toggleButton.querySelector('i');
    if (sidebar.classList.contains('close')) {
        // When sidebar is closed, rotate icon to point right (to indicate expand)
        icon.style.transform = 'rotate(180deg)';
    } else {
        // When sidebar is open, icon points left (to indicate collapse)
        icon.style.transform = 'rotate(0deg)';
    }

    closeAllSubMenus();
}

function toggleSubMenu(button) {
    if (!button.nextElementSibling.classList.contains('show')) {
        closeAllSubMenus();
    }
    button.nextElementSibling.classList.toggle('show');
    button.classList.toggle('rotate');

    // If sidebar is closed, open it when accessing submenu
    if (sidebar.classList.contains('close')) {
        sidebar.classList.remove('close');
        const icon = toggleButton.querySelector('i');
        icon.style.transform = 'rotate(0deg)';
    }
}

function closeAllSubMenus() {
    Array.from(sidebar.getElementsByClassName('show')).forEach(ul => {
        ul.classList.remove('show');
        ul.previousElementSibling.classList.remove('rotate');
    });
}

// Toggle switch functionality
document.addEventListener('DOMContentLoaded', function () {
    const toggleSwitches = document.querySelectorAll('.toggle-switch input');

    toggleSwitches.forEach(toggle => {
        toggle.addEventListener('change', function () {
            const card = this.closest('.product-card');
            const statusSpan = card.querySelector('.product-status');

            if (this.checked) {
                statusSpan.textContent = 'Active';
                statusSpan.className = 'product-status status-active';
                console.log('Product enabled');
            } else {
                statusSpan.textContent = 'Draft';
                statusSpan.className = 'product-status status-draft';
                console.log('Product disabled');
            }
        });
    });
});