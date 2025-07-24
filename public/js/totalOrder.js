class DashboardManager {
    constructor() {
        this.apiUrl = '../../app/controllers/totalOrder.php';
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
        
        // Export Excel button
        // document.getElementById('exportExcel').addEventListener('click', () => {
        //     this.exportToExcel();
        // });
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
        });
    }
    
    /**
     * Populate days dựa trên tháng được chọn
     */
    populateDays() {
        const monthSelect = document.getElementById('selectMonth');
        const daySelect = document.getElementById('selectDay');
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
        document.getElementById('selectMonth').value = this.currentMonth;
        document.getElementById('selectMonthOnly').value = this.currentMonth;
        
        // Set quý hiện tại
        const currentQuarter = Math.ceil(this.currentMonth / 3);
        document.getElementById('selectQuarter').value = currentQuarter;
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
        const timePeriod = document.getElementById('timePeriod').value;
        let params = {
            action: 'getSalesStats',
            timePeriod: timePeriod
        };
        
        switch(timePeriod) {
            case 'day':
                params.selectMonth = document.getElementById('selectMonth').value;
                params.selectDay = document.getElementById('selectDay').value;
                break;
            case 'month':
                params.selectMonthOnly = document.getElementById('selectMonthOnly').value;
                params.selectYearForMonth = document.getElementById('selectYearForMonth').value;
                break;
            case 'quarter':
                params.selectQuarter = document.getElementById('selectQuarter').value;
                params.selectYearForQuarter = document.getElementById('selectYearForQuarter').value;
                break;
            case 'year':
                params.selectYear = document.getElementById('selectYear').value;
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
            console.error('Error updating stats:', error);
            this.showError('Không thể kết nối đến server');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Cập nhật giao diện với dữ liệu mới
     */
    updateUI(data) {
        // Cập nhật tổng tiền
        const totalElement = document.querySelector('.sales-total h3');
        if (totalElement) {
            let total = parseInt(data.current_total.replace(/,/g, '')); // bỏ dấu phẩy và chuyển thành số
            let formattedTotal = total >= 1000 ? `${Math.round(total / 1000)}k` : `${total}`;
            totalElement.textContent = `$${formattedTotal}`;
        }
        
        // Cập nhật phần trăm thay đổi
        const changeElement = document.querySelector('.sales-total span');
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
        const titleElement = document.querySelector('.sales-total p');
        if(titleElement && data.selected_period) {
            titleElement.textContent = `Total Sales - ${data.selected_period}`;
        }
        
        // Trigger custom event để các component khác có thể lắng nghe
        document.dispatchEvent(new CustomEvent('statsUpdated', {
            detail: data
        }));
    }
    
    /**
     * Hiển thị loading state
     */
    showLoading(show) {
        const totalElement = document.querySelector('.sales-total h3');
        const changeElement = document.querySelector('.sales-total span');
        
        if(show) {
            if(totalElement) totalElement.textContent = 'Loading...';
            if(changeElement) changeElement.textContent = 'Đang tải...';
        }
    }
    
    /**
     * Hiển thị lỗi
     */
    showError(message) {
        const totalElement = document.querySelector('.sales-total h3');
        const changeElement = document.querySelector('.sales-total span');
        
        if(totalElement) totalElement.textContent = 'Error';
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
     * Export dữ liệu ra Excel
     */
    async exportToExcel() {
        try {
            const params = this.getApiParameters();
            params.export = 'excel';
            
            const queryString = new URLSearchParams(params).toString();
            
            // Tạo link download
            const link = document.createElement('a');
            link.href = `${this.apiUrl}?${queryString}`;
            link.download = `sales_statistics_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Hiển thị thông báo thành công
            this.showNotification('Đã xuất file Excel thành công!', 'success');
            
        } catch (error) {
            console.error('Error exporting Excel:', error);
            this.showNotification('Có lỗi khi xuất file Excel', 'error');
        }
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
        const totalElement = document.querySelector('.sales-total h3');
        const changeElement = document.querySelector('.sales-total span');
        
        return {
            total: totalElement ? totalElement.textContent : null,
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
const dashboardStyles = `
<style>
.sales-total span.positive {
    color: #4CAF50;
}

.sales-total span.negative {
    color: #f44336;
}

.sales-total span.error {
    color: #ff9800;
    font-style: italic;
}

.stats-select {
    margin: 0 5px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
}

.excel-btn {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: 10px;
}

.excel-btn:hover {
    background: #45a049;
}

.date-selection {
    display: inline-block;
    margin: 0 10px;
}

.statistics-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .statistics-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .date-selection {
        margin: 5px 0;
    }
}
</style>
`;

// Thêm CSS vào head
document.head.insertAdjacentHTML('beforeend', dashboardStyles);

// Khởi tạo Dashboard Manager khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    window.dashboardManager = new DashboardManager();
    
    // Expose một số method để sử dụng từ bên ngoài
    window.refreshDashboard = () => window.dashboardManager.refresh();
    window.getCurrentStats = () => window.dashboardManager.getCurrentStats();
});

// Auto-refresh mỗi 5 phút (tùy chọn)
setInterval(() => {
    if(window.dashboardManager && document.visibilityState === 'visible') {
        window.dashboardManager.refresh();
    }
}, 5 * 60 * 1000);