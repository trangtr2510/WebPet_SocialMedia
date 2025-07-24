class DashboardProductsSold {
    constructor() {
        this.apiUrl = '../../app/controllers/ProductsSoldController.php';
        this.currentYear = new Date().getFullYear();
        this.currentMonth = new Date().getMonth() + 1;
        this.currentDay = new Date().getDate();
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.populateSelectors();
        this.loadInitialData();
    }
    
    /**
     * Thiết lập event listeners
     */
    setupEventListeners() {
        // Lắng nghe thay đổi time period
        document.getElementById('timePeriod').addEventListener('change', (e) => {
            this.handleTimePeriodChange(e.target.value);
        });
        
        // Lắng nghe thay đổi các selector
        document.getElementById('selectMonth').addEventListener('change', () => {
            this.populateDays();
            this.updateStats();
        });
        
        document.getElementById('selectDay').addEventListener('change', () => {
            this.updateStats();
        });
        
        document.getElementById('selectMonthOnly').addEventListener('change', () => {
            this.updateStats();
        });
        
        document.getElementById('selectYearForMonth').addEventListener('change', () => {
            this.updateStats();
        });
        
        document.getElementById('selectQuarter').addEventListener('change', () => {
            this.updateStats();
        });
        
        document.getElementById('selectYearForQuarter').addEventListener('change', () => {
            this.updateStats();
        });
        
        document.getElementById('selectYear').addEventListener('change', () => {
            this.updateStats();
        });
    }
    
    /**
     * Xử lý thay đổi time period
     */
    handleTimePeriodChange(period) {
        // Ẩn tất cả các selection
        document.getElementById('daySelection').style.display = 'none';
        document.getElementById('monthSelection').style.display = 'none';
        document.getElementById('quarterSelection').style.display = 'none';
        document.getElementById('yearSelection').style.display = 'none';
        
        // Hiện selection tương ứng
        switch(period) {
            case 'day':
                document.getElementById('daySelection').style.display = 'block';
                break;
            case 'month':
                document.getElementById('monthSelection').style.display = 'block';
                break;
            case 'quarter':
                document.getElementById('quarterSelection').style.display = 'block';
                break;
            case 'year':
                document.getElementById('yearSelection').style.display = 'block';
                break;
        }
        
        // Cập nhật thống kê
        this.updateStats();
    }
    
    /**
     * Populate các selector với dữ liệu
     */
    populateSelectors() {
        this.populateYears();
        this.populateDays();
        this.setDefaultValues();
    }
    
    /**
     * Populate years cho các selector
     */
    populateYears() {
        const yearSelectors = [
            'selectYearForMonth',
            'selectYearForQuarter', 
            'selectYear'
        ];
        
        const startYear = 2020; // Năm bắt đầu
        const endYear = this.currentYear + 1; // Năm hiện tại + 1
        
        yearSelectors.forEach(selectorId => {
            const selector = document.getElementById(selectorId);
            if (selector) {
                selector.innerHTML = '';
                
                for(let year = endYear; year >= startYear; year--) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = `Năm ${year}`;
                    if(year === this.currentYear) {
                        option.selected = true;
                    }
                    selector.appendChild(option);
                }
            }
        });
    }
    
    /**
     * Populate days dựa trên tháng được chọn
     */
    populateDays() {
        const monthSelect = document.getElementById('selectMonth');
        const daySelect = document.getElementById('selectDay');
        
        if (!monthSelect || !daySelect) return;
        
        const selectedMonth = parseInt(monthSelect.value);
        
        // Xóa các option cũ
        daySelect.innerHTML = '';
        
        // Lấy số ngày trong tháng
        const daysInMonth = new Date(this.currentYear, selectedMonth, 0).getDate();
        
        for(let day = 1; day <= daysInMonth; day++) {
            const option = document.createElement('option');
            option.value = day;
            option.textContent = `Ngày ${day}`;
            if(day === this.currentDay && selectedMonth === this.currentMonth) {
                option.selected = true;
            }
            daySelect.appendChild(option);
        }
    }
    
    /**
     * Thiết lập giá trị mặc định
     */
    setDefaultValues() {
        // Set tháng hiện tại
        const monthSelect = document.getElementById('selectMonth');
        const monthOnlySelect = document.getElementById('selectMonthOnly');
        
        if (monthSelect) monthSelect.value = this.currentMonth;
        if (monthOnlySelect) monthOnlySelect.value = this.currentMonth;
        
        // Set quý hiện tại
        const quarterSelect = document.getElementById('selectQuarter');
        if (quarterSelect) {
            const currentQuarter = Math.ceil(this.currentMonth / 3);
            quarterSelect.value = currentQuarter;
        }
    }
    
    /**
     * Load dữ liệu ban đầu
     */
    loadInitialData() {
        this.updateStats();
    }
    
    /**
     * Lấy parameters cho API call
     */
    getApiParameters() {
        const timePeriodElement = document.getElementById('timePeriod');
        const timePeriod = timePeriodElement ? timePeriodElement.value : 'day';
        
        let params = {
            action: 'getProductsSoldStats',
            timePeriod: timePeriod
        };
        
        switch(timePeriod) {
            case 'day':
                const monthSelect = document.getElementById('selectMonth');
                const daySelect = document.getElementById('selectDay');
                if (monthSelect) params.selectMonth = monthSelect.value;
                if (daySelect) params.selectDay = daySelect.value;
                break;
            case 'month':
                const monthOnlySelect = document.getElementById('selectMonthOnly');
                const yearForMonthSelect = document.getElementById('selectYearForMonth');
                if (monthOnlySelect) params.selectMonthOnly = monthOnlySelect.value;
                if (yearForMonthSelect) params.selectYearForMonth = yearForMonthSelect.value;
                break;
            case 'quarter':
                const quarterSelect = document.getElementById('selectQuarter');
                const yearForQuarterSelect = document.getElementById('selectYearForQuarter');
                if (quarterSelect) params.selectQuarter = quarterSelect.value;
                if (yearForQuarterSelect) params.selectYearForQuarter = yearForQuarterSelect.value;
                break;
            case 'year':
                const yearSelect = document.getElementById('selectYear');
                if (yearSelect) params.selectYear = yearSelect.value;
                break;
        }
        
        return params;
    }
    
    /**
     * Cập nhật thống kê
     */
    async updateStats() {
        try {
            this.showLoading(true);
            
            const params = this.getApiParameters();
            const queryString = new URLSearchParams(params).toString();
            
            const response = await fetch(`${this.apiUrl}?${queryString}`);
            const result = await response.json();
            
            if(result.success) {
                this.updateUI(result.data);
            } else {
                this.showError(result.error || 'Có lỗi xảy ra khi tải dữ liệu');
            }
        } catch (error) {
            console.error('Error updating products sold stats:', error);
            this.showError('Không thể kết nối đến server');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Cập nhật giao diện với dữ liệu mới
     */
    updateUI(data) {
        // Cập nhật số lượng sản phẩm đã bán
        const quantityElement = document.querySelector('.sales-products h3');
        if(quantityElement) {
            quantityElement.textContent = data.current_total;
        }
        
        // Cập nhật phần trăm thay đổi
        const changeElement = document.querySelector('.sales-products span');
        if(changeElement) {
            changeElement.textContent = `${data.change_percent}% ${data.period_text}`;
            
            // Thêm class cho màu sắc
            changeElement.classList.remove('positive', 'negative');
            if(data.change_percent.startsWith('+')) {
                changeElement.classList.add('positive');
            } else if(data.change_percent.startsWith('-')) {
                changeElement.classList.add('negative');
            }
        }
        
        // Cập nhật title nếu cần
        const titleElement = document.querySelector('.sales-products p');
        if(titleElement && data.selected_period) {
            titleElement.textContent = `Products Sold - ${data.selected_period}`;
        }
        
        // Trigger custom event để các component khác có thể lắng nghe
        document.dispatchEvent(new CustomEvent('productsSoldUpdated', {
            detail: data
        }));
    }
    
    /**
     * Hiển thị loading state
     */
    showLoading(show) {
        const quantityElement = document.querySelector('.sales-products h3');
        const changeElement = document.querySelector('.sales-products span');
        
        if(show) {
            if(quantityElement) quantityElement.textContent = 'Loading...';
            if(changeElement) changeElement.textContent = 'Đang tải...';
        }
    }
    
    /**
     * Hiển thị lỗi
     */
    showError(message) {
        const quantityElement = document.querySelector('.sales-products h3');
        const changeElement = document.querySelector('.sales-products span');
        
        if(quantityElement) quantityElement.textContent = 'Error';
        if(changeElement) {
            changeElement.textContent = message;
            changeElement.classList.add('error');
        }
        
        // Tự động ẩn lỗi sau 5 giây
        setTimeout(() => {
            if(changeElement) {
                changeElement.classList.remove('error');
            }
            this.updateStats(); // Thử lại
        }, 5000);
    }
    
    /**
     * Hiển thị notification
     */
    showNotification(message, type = 'info') {
        // Tạo notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        // Style cho notification
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        
        // Màu sắc theo type
        const colors = {
            success: '#4CAF50',
            error: '#f44336',
            info: '#2196F3'
        };
        notification.style.backgroundColor = colors[type] || colors.info;
        
        // Thêm vào DOM
        document.body.appendChild(notification);
        
        // Hiển thị với animation
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 100);
        
        // Tự động ẩn sau 3 giây
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if(notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    /**
     * Lấy dữ liệu thống kê hiện tại
     */
    getCurrentStats() {
        const quantityElement = document.querySelector('.sales-products h3');
        const changeElement = document.querySelector('.sales-products span');
        
        return {
            quantity: quantityElement ? quantityElement.textContent : null,
            change: changeElement ? changeElement.textContent : null
        };
    }
    
    /**
     * Refresh dữ liệu
     */
    refresh() {
        this.updateStats();
    }
}

// CSS cho các class được sử dụng
const productsSoldStyles = `
<style>
.sales-products span.positive {
    color: #4CAF50;
}

.sales-products span.negative {
    color: #f44336;
}

.sales-products span.error {
    color: #ff9800;
    font-style: italic;
}

.sales-products {
    transition: all 0.3s ease;
}

.sales-products:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(242, 200, 237, 0.3);
}

.sales-products h3 {
    font-size: 2.5em;
    font-weight: bold;
    margin: 10px 0;
    color: #333;
}

.sales-products p {
    color: #666;
    font-weight: 500;
    margin: 8px 0;
}

.sales-products span {
    font-size: 0.9em;
    font-weight: 500;
}

.sales-products svg {
    margin-bottom: 10px;
}
</style>
`;

// Thêm CSS vào head
document.head.insertAdjacentHTML('beforeend', productsSoldStyles);

// Khởi tạo Dashboard Products Sold khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    window.dashboardProductsSold = new DashboardProductsSold();
    
    // Expose một số method để sử dụng từ bên ngoài
    window.refreshProductsSold = () => window.dashboardProductsSold.refresh();
    window.getCurrentProductsSoldStats = () => window.dashboardProductsSold.getCurrentStats();
});

// Auto-refresh mỗi 5 phút (tùy chọn)
setInterval(() => {
    if(window.dashboardProductsSold && document.visibilityState === 'visible') {
        window.dashboardProductsSold.refresh();
    }
}, 5 * 60 * 1000);