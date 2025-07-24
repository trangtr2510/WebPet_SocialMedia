function pmShowTab(tabName) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.pm-tab-content');
    tabContents.forEach(content => {
        content.classList.remove('pm-active');
    });

    // Remove active class from all nav items
    const navItems = document.querySelectorAll('.pm-tab-nav-item');
    navItems.forEach(item => {
        item.classList.remove('pm-active');
    });

    // Show selected tab content
    document.getElementById(tabName).classList.add('pm-active');

    // Add active class to clicked nav item
    event.target.classList.add('pm-active');
}

function pmToggleSelectAll() {
    const selectAll = document.getElementById('pm-select-all');
    const checkboxes = document.querySelectorAll('.pm-row-checkbox');

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

// Add event listeners for row checkboxes
document.querySelectorAll('.pm-row-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function () {
        const selectAll = document.getElementById('pm-select-all');
        const checkboxes = document.querySelectorAll('.pm-row-checkbox');
        const checkedBoxes = document.querySelectorAll('.pm-row-checkbox:checked');

        if (checkedBoxes.length === checkboxes.length) {
            selectAll.checked = true;
            selectAll.indeterminate = false;
        } else if (checkedBoxes.length > 0) {
            selectAll.checked = false;
            selectAll.indeterminate = true;
        } else {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
    });
});

// Tab switching functionality
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.pm-tab-content').forEach(tab => {
        tab.style.display = 'none';
        tab.classList.remove('pm-active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.pm-tab-nav-item').forEach(btn => {
        btn.classList.remove('pm-active');
    });
    
    // Show selected tab
    const selectedTab = document.getElementById(tabName + '-tab');
    if (selectedTab) {
        selectedTab.style.display = 'block';
        selectedTab.classList.add('pm-active');
    }
    
    // Add active class to clicked button
    event.target.classList.add('pm-active');
    
    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    history.replaceState({}, '', url);
}

// Select all functionality
function toggleSelectAll(tabName) {
    const selectAllCheckbox = document.getElementById('select-all-' + tabName);
    const checkboxes = document.querySelectorAll('.post-checkbox-' + tabName);
    const selectedCount = document.getElementById('selected-count-' + tabName);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    selectedCount.textContent = selectAllCheckbox.checked ? checkboxes.length : 0;
}

// Update selected count when individual checkboxes are clicked
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('post-checkbox-pending')) {
        updateSelectedCount('pending');
    } else if (e.target.classList.contains('post-checkbox-approved')) {
        updateSelectedCount('approved');
    }
});

function updateSelectedCount(tabName) {
    const checkboxes = document.querySelectorAll('.post-checkbox-' + tabName + ':checked');
    const selectedCount = document.getElementById('selected-count-' + tabName);
    const selectAllCheckbox = document.getElementById('select-all-' + tabName);
    
    selectedCount.textContent = checkboxes.length;
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.post-checkbox-' + tabName);
    selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length;
}

// Bulk actions
function bulkAction(action) {
    const activeTab = document.querySelector('.pm-tab-content.pm-active');
    const form = activeTab.querySelector('form');
    const checkboxes = activeTab.querySelectorAll('input[name="post_ids[]"]:checked');
    
    if (checkboxes.length === 0) {
        alert('Please select at least one post');
        return;
    }
    
    let confirmMessage = '';
    switch(action) {
        case 'approve':
            confirmMessage = 'Are you sure you want to approve selected posts?';
            break;
        case 'delete':
            confirmMessage = 'Are you sure you want to delete selected posts?';
            break;
        case 'edit':
            alert('Edit functionality will be implemented');
            return;
    }
    
    if (confirm(confirmMessage)) {
        form.querySelector('input[name="action"]').value = action;
        form.submit();
    }
}

// View post functionality
function viewPost(postId) {
    // This would typically fetch post details via AJAX
    // For now, we'll just show a simple modal
    document.getElementById('post-detail-modal').style.display = 'block';
    document.getElementById('post-detail-content').innerHTML = '<p>Loading post details for ID: ' + postId + '...</p>';
}

// Edit post functionality
function editPost(postId) {
    // This would typically open an edit form
    alert('Edit functionality for post ID: ' + postId + ' will be implemented');
}

// Export functionality
function exportSelected() {
    const checkboxes = document.querySelectorAll('.post-checkbox-approved:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one post to export');
        return;
    }
    alert('Export functionality will be implemented');
}

// Close modal
function closeModal() {
    document.getElementById('post-detail-modal').style.display = 'none';
}

// Sidebar functionality (if needed)
function toggleSidebar() {
    // Implementation depends on your sidebar CSS
}

function toggleSubMenu(element) {
    // Implementation depends on your submenu CSS
}