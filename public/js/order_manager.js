// Functions for order management
function showOrderTab(tabName) {
    // Hide all tabs
    const tabs = document.querySelectorAll('.pm-tab-content');
    tabs.forEach(tab => {
        tab.style.display = 'none';
        tab.classList.remove('pm-active');
    });

    // Remove active class from all nav items
    const navItems = document.querySelectorAll('.pm-tab-nav-item');
    navItems.forEach(item => {
        item.classList.remove('pm-active');
    });

    // Show selected tab
    const selectedTab = document.getElementById(tabName + '-tab');
    if (selectedTab) {
        selectedTab.style.display = 'block';
        selectedTab.classList.add('pm-active');
    }

    // Add active class to clicked nav item
    event.target.classList.add('pm-active');

    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    url.searchParams.set('page', '1'); // Reset to page 1
    window.history.pushState({}, '', url);
}

function bulkOrderAction(action) {
    const activeTab = document.querySelector('.pm-tab-content.pm-active');
    const form = activeTab.querySelector('form');
    const checkboxes = form.querySelectorAll('input[name="order_ids[]"]:checked');

    if (checkboxes.length === 0) {
        alert('Vui lòng chọn ít nhất một đơn hàng để thực hiện hành động.');
        return;
    }

    let confirmMessage = '';
    switch (action) {
        case 'ship':
            confirmMessage = `Bạn có chắc chắn muốn xác nhận ${checkboxes.length} đơn hàng đã chọn?`;
            break;
        case 'confirm':
            confirmMessage = `Bạn có chắc chắn muốn xác nhận ${checkboxes.length} đơn hàng đã chọn?`;
            break;
        case 'complete':
            confirmMessage = `Bạn có chắc chắn muốn hoàn thành ${checkboxes.length} đơn hàng đã chọn?`;
            break;
        case 'cancel':
            confirmMessage = `Bạn có chắc chắn muốn hủy ${checkboxes.length} đơn hàng đã chọn?`;
            break;
        default:
            confirmMessage = `Bạn có chắc chắn muốn thực hiện hành động này với ${checkboxes.length} đơn hàng đã chọn?`;
    }

    if (confirm(confirmMessage)) {
        if (form) {
            form.querySelector('input[name="action"]').value = 'bulk_' + action;
            form.submit();
        }
    }
}

function toggleSelectAll(tabName) {
    const checkbox = document.getElementById('select-all-' + tabName);
    const checkboxes = document.querySelectorAll('.post-checkbox-' + tabName);
    const counter = document.getElementById('selected-count-' + tabName);

    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });

    counter.textContent = checkbox.checked ? checkboxes.length : 0;
}

function updateSelectedCount(tabName) {
    const checkboxes = document.querySelectorAll('.post-checkbox-' + tabName + ':checked');
    const counter = document.getElementById('selected-count-' + tabName);
    const selectAllCheckbox = document.getElementById('select-all-' + tabName);
    const allCheckboxes = document.querySelectorAll('.post-checkbox-' + tabName);

    counter.textContent = checkboxes.length;

    // Update select all checkbox state
    if (checkboxes.length === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (checkboxes.length === allCheckboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

function viewOrderDetail(orderId) {
    // Show modal
    document.getElementById('order-detail-modal').style.display = 'block';
    document.getElementById('order-detail-content').innerHTML = '<div class="loading">Đang tải...</div>';

    // Load order details via AJAX
    fetch('../../../app/controllers/OrderController.php?action=get_order_detail&id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('order-detail-content').innerHTML = data.html;
            } else {
                document.getElementById('order-detail-content').innerHTML = '<p class="error">Không thể tải chi tiết đơn hàng: ' + (data.message || 'Lỗi không xác định') + '</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('order-detail-content').innerHTML = '<p class="error">Có lỗi xảy ra khi tải dữ liệu.</p>';
        });
}

function closeOrderModal() {
    document.getElementById('order-detail-modal').style.display = 'none';
}

function confirmSingleAction(action, orderId, orderNumber) {
    let message = '';
    switch (action) {
        case 'ship':
            message = `Bạn có chắc chắn muốn giao đơn hàng #${orderNumber}?`;
            break;
        case 'confirm':
            message = `Bạn có chắc chắn muốn xác nhận đơn hàng #${orderNumber}?`;
            break;
        case 'complete':
            message = `Bạn có chắc chắn muốn hoàn thành đơn hàng #${orderNumber}?`;
            break;
        case 'cancel':
            message = `Bạn có chắc chắn muốn hủy đơn hàng #${orderNumber}?`;
            break;
        default:
            return true;
    }

    return confirm(message);
}

// Close modal when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById('order-detail-modal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('order-detail-modal');
        if (modal.style.display === 'block') {
            closeOrderModal();
        }
    }
});

// Update selected count when individual checkboxes are changed
document.addEventListener('change', function (e) {
    if (e.target.classList.contains('post-checkbox-pending')) {
        updateSelectedCount('pending');
    } else if (e.target.classList.contains('post-checkbox-processing')) {
        updateSelectedCount('processing');
    }
});

// Initialize page
document.addEventListener('DOMContentLoaded', function () {
    // Update counts for all tabs on page load
    updateSelectedCount('pending');
    updateSelectedCount('processing');

    // Add click handlers to action links for confirmation
    document.querySelectorAll('a[href*="single_confirm"], a[href*="single_complete"], a[href*="single_cancel"]').forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            const urlParams = new URLSearchParams(href.split('?')[1]);
            const action = urlParams.get('action').replace('single_', '');
            const orderId = urlParams.get('id');

            // Get order number from the table row
            const row = this.closest('tr');
            const orderNumberCell = row.querySelector('td:nth-child(3)'); // Assuming order number is in 3rd column
            const orderNumber = orderNumberCell ? orderNumberCell.textContent.trim() : orderId;

            if (!confirmSingleAction(action, orderId, orderNumber)) {
                e.preventDefault();
            }
        });
    });

    // Show success/error messages with auto-hide
    const messages = document.querySelectorAll('.success-message, .error-message');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.style.display = 'none';
            }, 300);
        }, 5000);
    });
});



// Order Search and Filter JavaScript
class OrderSearchFilter {
    constructor() {
        this.searchTimeout = null;
        this.currentTab = 'pending';
        this.isLoading = false;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupRangeFilterLogic();
        this.updateRangeInputsBasedOnSort();
    }

    bindEvents() {
        // Tìm kiếm real-time
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.debounceSearch(e.target.value);
            });
        }

        // Thay đổi sort by
        const sortSelect = document.querySelector('#sortBySelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                this.updateRangeInputsBasedOnSort();
                this.performSearch();
            });
        }

        // Range filter inputs
        const rangeFromInput = document.querySelector('#rangeFrom');
        const rangeToInput = document.querySelector('#rangeTo');
        
        if (rangeFromInput && rangeToInput) {
            rangeFromInput.addEventListener('input', () => this.debounceSearch());
            rangeToInput.addEventListener('input', () => this.debounceSearch());
        }

        // Apply button
        const applyBtn = document.querySelector('.pm-apply-btn');
        if (applyBtn) {
            applyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }

        // Tab switching
        const tabButtons = document.querySelectorAll('.pm-tab-nav-item');
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                if (e.target.getAttribute('onclick')) return; // Skip if has onclick
                const tabName = this.extractTabFromOnclick(e.target.getAttribute('onclick'));
                if (tabName) {
                    this.switchTab(tabName);
                }
            });
        });

        // Form submission override
        const searchForm = document.querySelector('form[action="order_manager.php"]');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }
    }

    setupRangeFilterLogic() {
        const sortSelect = document.querySelector('#sortBySelect');
        const rangeLabel = document.querySelector('#rangeLabel');
        const rangeFrom = document.querySelector('#rangeFrom');
        const rangeTo = document.querySelector('#rangeTo');

        if (!sortSelect || !rangeLabel || !rangeFrom || !rangeTo) return;

        // Set initial state
        this.updateRangeInputsBasedOnSort();
    }

    updateRangeInputsBasedOnSort() {
        const sortSelect = document.querySelector('#sortBySelect');
        const rangeLabel = document.querySelector('#rangeLabel');
        const rangeFrom = document.querySelector('#rangeFrom');
        const rangeTo = document.querySelector('#rangeTo');

        if (!sortSelect || !rangeLabel || !rangeFrom || !rangeTo) return;

        const sortBy = sortSelect.value;

        if (sortBy === 'created_at') {
            rangeLabel.textContent = 'Lọc theo ngày';
            rangeFrom.placeholder = 'Từ ngày (dd/mm/yyyy)';
            rangeTo.placeholder = 'Đến ngày (dd/mm/yyyy)';
            rangeFrom.setAttribute('data-type', 'date');
            rangeTo.setAttribute('data-type', 'date');
            
            // Add date input formatting
            this.setupDateInputs();
        } else if (sortBy === 'total_amount') {
            rangeLabel.textContent = 'Lọc theo giá';
            rangeFrom.placeholder = 'Từ giá (VNĐ)';
            rangeTo.placeholder = 'Đến giá (VNĐ)';
            rangeFrom.setAttribute('data-type', 'price');
            rangeTo.setAttribute('data-type', 'price');
            
            // Add price input formatting
            this.setupPriceInputs();
        }
    }

    setupDateInputs() {
        const rangeFrom = document.querySelector('#rangeFrom');
        const rangeTo = document.querySelector('#rangeTo');

        [rangeFrom, rangeTo].forEach(input => {
            if (input && input.getAttribute('data-type') === 'date') {
                // Remove previous event listeners
                input.replaceWith(input.cloneNode(true));
                const newInput = input.getAttribute('name') === 'range_from' ? 
                    document.querySelector('#rangeFrom') : document.querySelector('#rangeTo');
                
                newInput.addEventListener('input', (e) => {
                    this.formatDateInput(e.target);
                    this.debounceSearch();
                });

                newInput.addEventListener('blur', (e) => {
                    this.validateDateInput(e.target);
                });
            }
        });
    }

    setupPriceInputs() {
        const rangeFrom = document.querySelector('#rangeFrom');
        const rangeTo = document.querySelector('#rangeTo');

        [rangeFrom, rangeTo].forEach(input => {
            if (input && input.getAttribute('data-type') === 'price') {
                // Remove previous event listeners
                input.replaceWith(input.cloneNode(true));
                const newInput = input.getAttribute('name') === 'range_from' ? 
                    document.querySelector('#rangeFrom') : document.querySelector('#rangeTo');
                
                newInput.addEventListener('input', (e) => {
                    this.formatPriceInput(e.target);
                    this.debounceSearch();
                });
            }
        });
    }

    formatDateInput(input) {
        let value = input.value.replace(/\D/g, ''); // Remove non-digits
        
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2);
        }
        if (value.length >= 5) {
            value = value.substring(0, 5) + '/' + value.substring(5, 9);
        }
        
        input.value = value;
    }

    validateDateInput(input) {
        const datePattern = /^(\d{2})\/(\d{2})\/(\d{4})$/;
        const match = input.value.match(datePattern);
        
        if (input.value && !match) {
            input.setCustomValidity('Vui lòng nhập ngày theo định dạng dd/mm/yyyy');
            input.reportValidity();
            return false;
        }
        
        if (match) {
            const [, day, month, year] = match;
            const date = new Date(year, month - 1, day);
            
            if (date.getDate() != day || date.getMonth() != month - 1 || date.getFullYear() != year) {
                input.setCustomValidity('Ngày không hợp lệ');
                input.reportValidity();
                return false;
            }
        }
        
        input.setCustomValidity('');
        return true;
    }

    formatPriceInput(input) {
        let value = input.value.replace(/\D/g, ''); // Remove non-digits
        
        if (value) {
            // Format with thousand separators
            value = parseInt(value).toLocaleString('vi-VN');
        }
        
        input.value = value;
    }

    debounceSearch(searchTerm = null) {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        this.searchTimeout = setTimeout(() => {
            this.performSearch(searchTerm);
        }, 500); // Delay 500ms
    }

    async performSearch(searchTerm = null) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();

        try {
            const searchParams = this.getSearchParameters(searchTerm);
            
            const response = await fetch('/WebsitePet/app/controllers/OrderController.php?action=ajax_search', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(searchParams)
            });

            const result = await response.json();

            if (result.success) {
                this.updateOrderTable(result.data);
                this.updatePagination(result.data);
                this.updateCountBadges();
            } else {
                this.showError(result.message || 'Có lỗi xảy ra khi tìm kiếm');
            }
        } catch (error) {
            console.error('Search error:', error);
            this.showError('Có lỗi xảy ra khi tìm kiếm');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    getSearchParameters(searchTerm = null) {
        const searchInput = document.querySelector('input[name="search"]');
        const sortSelect = document.querySelector('#sortBySelect');
        const rangeFrom = document.querySelector('#rangeFrom');
        const rangeTo = document.querySelector('#rangeTo');
        const currentTab = document.querySelector('input[name="tab"]');

        const search = searchTerm !== null ? searchTerm : (searchInput ? searchInput.value.trim() : '');
        const sortBy = sortSelect ? sortSelect.value : 'created_at';
        const status = currentTab ? currentTab.value : this.currentTab;

        const filters = {};

        // Parse range filters based on sort type
        if (rangeFrom && rangeFrom.value.trim()) {
            if (sortBy === 'created_at') {
                filters.date_from = this.parseDateInput(rangeFrom.value.trim());
            } else if (sortBy === 'total_amount') {
                filters.price_from = this.parsePriceInput(rangeFrom.value.trim());
            }
        }

        if (rangeTo && rangeTo.value.trim()) {
            if (sortBy === 'created_at') {
                filters.date_to = this.parseDateInput(rangeTo.value.trim());
            } else if (sortBy === 'total_amount') {
                filters.price_to = this.parsePriceInput(rangeTo.value.trim());
            }
        }

        return {
            status: status,
            search: search,
            sort_by: sortBy,
            filters: filters,
            page: 1 // Reset to first page on new search
        };
    }

    parseDateInput(dateStr) {
        const datePattern = /^(\d{2})\/(\d{2})\/(\d{4})$/;
        const match = dateStr.match(datePattern);
        
        if (match) {
            const [, day, month, year] = match;
            return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
        }
        
        return null;
    }

    parsePriceInput(priceStr) {
        // Remove formatting and convert to number
        const numericValue = priceStr.replace(/[^\d]/g, '');
        return numericValue ? parseInt(numericValue) : null;
    }

    updateOrderTable(data) {
        const activeTab = document.querySelector('.pm-tab-content.pm-active');
        if (!activeTab) return;

        const tbody = activeTab.querySelector('.pm-data-table tbody');
        if (!tbody) return;

        // Clear current rows
        tbody.innerHTML = '';

        if (data.orders.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px;">
                        <i class="fas fa-search" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                        <p style="color: #666;">Không tìm thấy đơn hàng nào phù hợp</p>
                    </td>
                </tr>
            `;
            return;
        }

        // Render new rows
        data.orders.forEach(order => {
            const row = this.createOrderRow(order);
            tbody.appendChild(row);
        });

        // Update select all checkbox state
        const selectAllCheckbox = activeTab.querySelector('[id^="select-all-"]');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            this.updateSelectedCount(this.currentTab);
        }
    }

    createOrderRow(order) {
        const row = document.createElement('tr');
        
        const customerAvatar = order.customer_avatar && order.customer_avatar !== 'default.jpg' 
            ? `../../../public/uploads/avatar/${order.customer_avatar}`
            : '../../../public/uploads/avatar/default.jpg';

        row.innerHTML = `
            <td>
                <input type="checkbox" name="order_ids[]" value="${order.order_id}" class="post-checkbox-${this.currentTab}">
            </td>
            <td>${order.order_id}</td>
            <td>${this.escapeHtml(order.order_number)}</td>
            <td>
                <div class="author-info">
                    <img src="${customerAvatar}" alt="Customer Avatar" class="author-avatar">
                    <span>${this.escapeHtml(order.customer_name || 'N/A')}</span>
                </div>
            </td>
            <td>${this.formatMoney(order.total_amount)}</td>
            <td>
                <span class="status-badge ${this.getStatusBadgeClass(order.status)}">
                    ${this.capitalizeFirst(order.status)}
                </span>
            </td>
            <td>
                <span class="status-badge ${this.getStatusBadgeClass(order.payment_status)}">
                    ${this.capitalizeFirst(order.payment_status)}
                </span>
            </td>
            <td>${this.formatDate(order.created_at)}</td>
            <td>
                <div class="pm-post-actions">
                    <div class="action-buttons">
                        ${this.getActionButtons(order)}
                    </div>
                </div>
            </td>
        `;

        // Bind checkbox event
        const checkbox = row.querySelector('input[type="checkbox"]');
        checkbox.addEventListener('change', () => {
            this.updateSelectedCount(this.currentTab);
        });

        return row;
    }

    getActionButtons(order) {
        const currentUrl = encodeURIComponent(window.location.href);
        
        if (this.currentTab === 'pending') {
            return `
                <a href="../../../app/controllers/OrderController.php?action=single_confirm&id=${order.order_id}&tab=pending&redirect_url=${currentUrl}" 
                   class="btn-small pm-action-btn pm-approve" title="Confirm">
                   <i class="fa-solid fa-check"></i> Confirm
                </a>
                <a href="../../../app/controllers/OrderController.php?action=single_cancel&id=${order.order_id}&tab=pending&redirect_url=${currentUrl}" 
                   class="btn-small pm-action-btn pm-delete" title="Cancel" onclick="return confirm('Are you sure?')">
                   <i class="fa-solid fa-ban"></i> Cancel
                </a>
                <button type="button" class="btn-small pm-action-btn btn-view" onclick="viewOrderDetail(${order.order_id})" title="View Details">
                    <i class="fas fa-eye"></i> Details
                </button>
            `;
        }
        
        // Add other tab action buttons as needed
        return `
            <button type="button" class="btn-small pm-action-btn btn-view" onclick="viewOrderDetail(${order.order_id})" title="View Details">
                <i class="fas fa-eye"></i> Details
            </button>
        `;
    }

    updatePagination(data) {
        const activeTab = document.querySelector('.pm-tab-content.pm-active');
        if (!activeTab) return;

        const pagination = activeTab.querySelector('.pm-pagination');
        if (!pagination) return;

        const currentPage = data.currentPage;
        const totalPages = data.totalPages;

        pagination.innerHTML = `
            <a href="#" class="pm-page-btn page-btn ${currentPage <= 1 ? 'disabled' : ''}" 
               onclick="orderSearchFilter.goToPage(${Math.max(1, currentPage - 1)}); return false;">
                <i class="fa-solid fa-angle-left"></i> Previous
            </a>
            <span class="page-info">Page ${currentPage} of ${totalPages}</span>
            <a href="#" class="pm-page-btn page-btn ${currentPage >= totalPages ? 'disabled' : ''}" 
               onclick="orderSearchFilter.goToPage(${Math.min(totalPages, currentPage + 1)}); return false;">
                Next <i class="fa-solid fa-angle-right"></i>
            </a>
        `;
    }

    async goToPage(page) {
        const searchParams = this.getSearchParameters();
        searchParams.page = page;

        this.isLoading = true;
        this.showLoading();

        try {
            const response = await fetch('../../../app/controllers/OrderController.php?action=ajax_search', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(searchParams)
            });

            const result = await response.json();

            if (result.success) {
                this.updateOrderTable(result.data);
                this.updatePagination(result.data);
            }
        } catch (error) {
            console.error('Pagination error:', error);
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    switchTab(tabName) {
        this.currentTab = tabName;
        
        // Update hidden tab input
        const tabInput = document.querySelector('input[name="tab"]');
        if (tabInput) {
            tabInput.value = tabName;
        }

        // Perform search for new tab
        this.performSearch();
    }

    // Utility functions
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
    }

    formatMoney(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + ' VNĐ';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    capitalizeFirst(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    getStatusBadgeClass(status) {
        const statusMap = {
            'pending': 'status-pending',
            'processing': 'status-approved',
            'shipped': 'status-shipped',
            'delivered': 'status-completed',
            'cancelled': 'status-refused'
        };
        return statusMap[status] || 'status-pending';
    }

    updateSelectedCount(tabName) {
        const checkboxes = document.querySelectorAll(`.post-checkbox-${tabName}:checked`);
        const countElement = document.querySelector(`#selected-count-${tabName}`);
        if (countElement) {
            countElement.textContent = checkboxes.length;
        }
    }

    updateCountBadges() {
        // This would need to be implemented based on your specific requirements
        // You might want to make another AJAX call to get updated counts
    }

    showLoading() {
        const activeTab = document.querySelector('.pm-tab-content.pm-active');
        if (!activeTab) return;

        let loadingElement = activeTab.querySelector('.search-loading');
        if (!loadingElement) {
            loadingElement = document.createElement('div');
            loadingElement.className = 'search-loading';
            loadingElement.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            `;
            loadingElement.innerHTML = `
                <div style="text-align: center;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #007cba; margin-bottom: 10px;"></i>
                    <p style="color: #666;">Đang tìm kiếm...</p>
                </div>
            `;
            activeTab.style.position = 'relative';
            activeTab.appendChild(loadingElement);
        }
        loadingElement.style.display = 'flex';
    }

    hideLoading() {
        const loadingElement = document.querySelector('.search-loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }

    showError(message) {
        // Create a simple error notification
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-notification';
        errorDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 10000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        `;
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle"></i> ${message}
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; float: right; margin-left: 10px; cursor: pointer;">&times;</button>
        `;
        document.body.appendChild(errorDiv);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 5000);
    }

    extractTabFromOnclick(onclickStr) {
        if (!onclickStr) return null;
        const match = onclickStr.match(/showOrderTab\('([^']+)'\)/);
        return match ? match[1] : null;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.orderSearchFilter = new OrderSearchFilter();
});

// Export functionality
function exportFilteredOrders() {
    const searchParams = window.orderSearchFilter.getSearchParameters();
    const queryString = new URLSearchParams({
        action: 'export',
        status: searchParams.status,
        search: searchParams.search || '',
        sort_by: searchParams.sort_by,
        date_from: searchParams.filters.date_from || '',
        date_to: searchParams.filters.date_to || '',
        price_from: searchParams.filters.price_from || '',
        price_to: searchParams.filters.price_to || ''
    });

    window.location.href = `../../../app/controllers/OrderController.php?${queryString}`;
}