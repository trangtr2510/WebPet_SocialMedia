class CountOrderManager {
    constructor() {
        this.apiUrl = '../../app/controllers/countDeliveredOrders.php';
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
            this.updateOrderStats();
        });
        
        document.getElementById('selectDay').addEventListener('change', () => {
            this.updateOrderStats();
        });
        
        document.getElementById('selectMonthOnly').addEventListener('change', () => {
            this.updateOrderStats();
        });
        
        document.getElementById('selectYearForMonth').addEventListener('change', () => {
            this.updateOrderStats();
        });
        
        document.getElementById('selectQuarter').addEventListener('change', () => {
            this.updateOrderStats();
        });
        
        document.getElementById('selectYearForQuarter').addEventListener('change', () => {
            this.updateOrderStats();
        });
        
        document.getElementById('selectYear').addEventListener('change', () => {
            this.updateOrderStats();
        });
        
        // Export Excel button
        // document.getElementById('exportExcel').addEventListener('click', () => {
        //     this.updateOrderStats();
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
        this.updateOrderStats();
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
            if(selector) {
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
        
        if(!monthSelect || !daySelect) return;
        
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
        
        if(selectMonth) selectMonth.value = this.currentMonth;
        if(selectMonthOnly) selectMonthOnly.value = this.currentMonth;
        
        // Set quý hiện tại
        const currentQuarter = Math.ceil(this.currentMonth / 3);
        const selectQuarter = document.getElementById('selectQuarter');
        if(selectQuarter) selectQuarter.value = currentQuarter;
    }
    
    /**
     * Load dữ liệu ban đầu
     */
    loadInitialData() {
        this.updateOrderStats();
    }
    
    /**
     * Lấy parameters cho API call
     */
    getApiParameters() {
        const timePeriodElement = document.getElementById('timePeriod');
        const timePeriod = timePeriodElement ? timePeriodElement.value : 'day';
        
        let params = {
            action: 'getDeliveredOrdersStats',
            timePeriod: timePeriod
        };
        
        switch(timePeriod) {
            case 'day':
                const selectMonth = document.getElementById('selectMonth');
                const selectDay = document.getElementById('selectDay');
                if(selectMonth && selectDay) {
                    params.selectMonth = selectMonth.value;
                    params.selectDay = selectDay.value;
                }
                break;
            case 'month':
                const selectMonthOnly = document.getElementById('selectMonthOnly');
                const selectYearForMonth = document.getElementById('selectYearForMonth');
                if(selectMonthOnly && selectYearForMonth) {
                    params.selectMonthOnly = selectMonthOnly.value;
                    params.selectYearForMonth = selectYearForMonth.value;
                }
                break;
            case 'quarter':
                const selectQuarter = document.getElementById('selectQuarter');
                const selectYearForQuarter = document.getElementById('selectYearForQuarter');
                if(selectQuarter && selectYearForQuarter) {
                    params.selectQuarter = selectQuarter.value;
                    params.selectYearForQuarter = selectYearForQuarter.value;
                }
                break;
            case 'year':
                const selectYear = document.getElementById('selectYear');
                if(selectYear) {
                    params.selectYear = selectYear.value;
                }
                break;
        }
        
        return params;
    }
    
    /**
     * Cập nhật thống kê đơn hàng
     */
    async updateOrderStats() {
        try {
            this.showLoading(true);
            
            const params = this.getApiParameters();
            const queryString = new URLSearchParams(params).toString();
            
            const response = await fetch(`${this.apiUrl}?${queryString}`);
            const text = await response.text();
            console.log('Raw response:', text); // Debug quan trọng
            const result = JSON.parse(text);    // Giữ nguyên logic cũ sau khi parse
            
            if(result.success) {
                this.updateOrderUI(result.data);
            } else {
                this.showError(result.error || 'Có lỗi xảy ra khi tải dữ liệu đơn hàng');
            }
        } catch (error) {
            console.error('Error updating order stats:', error);
            this.showError('Không thể kết nối đến server');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Cập nhật giao diện với dữ liệu đơn hàng mới
     */
    updateOrderUI(data) {
        // Cập nhật tổng số đơn hàng
        const totalElement = document.querySelector('.sales-orders h3');
        if(totalElement) {
            totalElement.textContent = data.current_total || '0';
        }
        
        // Cập nhật phần trăm thay đổi
        const changeElement = document.querySelector('.sales-orders span');
        if(changeElement) {
            changeElement.textContent = `${data.change_percent}% ${data.period_text}`;
            
            // Thêm class cho màu sắc
            changeElement.classList.remove('positive', 'negative');
            if(data.change_percent && data.change_percent.toString().startsWith('+')) {
                changeElement.classList.add('positive');
            } else if(data.change_percent && data.change_percent.toString().startsWith('-')) {
                changeElement.classList.add('negative');
            }
        }
        
        // Cập nhật title nếu cần
        const titleElement = document.querySelector('.sales-orders p');
        if(titleElement && data.selected_period) {
            titleElement.textContent = `Total Orders - ${data.selected_period}`;
        }
        
        // Trigger custom event để các component khác có thể lắng nghe
        document.dispatchEvent(new CustomEvent('orderStatsUpdated', {
            detail: data
        }));
    }
    
    /**
     * Hiển thị loading state
     */
    showLoading(show) {
        const totalElement = document.querySelector('.sales-orders h3');
        const changeElement = document.querySelector('.sales-orders span');
        
        if(show) {
            if(totalElement) totalElement.textContent = 'Loading...';
            if(changeElement) changeElement.textContent = 'Đang tải...';
        }
    }
    
    /**
     * Hiển thị lỗi
     */
    showError(message) {
        const totalElement = document.querySelector('.sales-orders h3');
        const changeElement = document.querySelector('.sales-orders span');
        
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
            this.updateOrderStats(); // Thử lại
        }, 5000);
    }
    
    /**
     * Export dữ liệu đơn hàng ra Excel
     */
    async exportToExcel() {
        try {
            const params = this.getApiParameters();
            params.export = 'excel';
            
            const queryString = new URLSearchParams(params).toString();
            
            // Tạo link download
            const link = document.createElement('a');
            link.href = `${this.apiUrl}?${queryString}`;
            link.download = `order_statistics_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Hiển thị thông báo thành công
            this.showNotification('Đã xuất file Excel thống kê đơn hàng thành công!', 'success');
            
        } catch (error) {
            console.error('Error exporting order Excel:', error);
            this.showNotification('Có lỗi khi xuất file Excel thống kê đơn hàng', 'error');
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
     * Lấy dữ liệu thống kê đơn hàng hiện tại
     */
    getCurrentOrderStats() {
        const totalElement = document.querySelector('.sales-orders h3');
        const changeElement = document.querySelector('.sales-orders span');
        
        return {
            total: totalElement ? totalElement.textContent : null,
            change: changeElement ? changeElement.textContent : null
        };
    }
    
    /**
     * Refresh dữ liệu đơn hàng
     */
    refresh() {
        this.updateOrderStats();
    }
}

// CSS cho các class được sử dụng (tương tự như DashboardManager)
const orderCountStyles = `
<style>
.sales-orders span.positive {
    color: #4CAF50;
}

.sales-orders span.negative {
    color: #f44336;
}

.sales-orders span.error {
    color: #ff9800;
    font-style: italic;
}

.order-stats-select {
    margin: 0 5px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
}

.order-excel-btn {
    background: #2196F3;
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

.order-excel-btn:hover {
    background: #1976D2;
}

.order-date-selection {
    display: inline-block;
    margin: 0 10px;
}

.order-statistics-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .order-statistics-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .order-date-selection {
        margin: 5px 0;
    }
}
</style>
`;

// Thêm CSS vào head
document.head.insertAdjacentHTML('beforeend', orderCountStyles);

// Khởi tạo Count Order Manager khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    window.countOrderManager = new CountOrderManager();
    
    // Expose một số method để sử dụng từ bên ngoài
    window.refreshOrderStats = () => window.countOrderManager.refresh();
    window.getCurrentOrderStats = () => window.countOrderManager.getCurrentOrderStats();
});

// Auto-refresh mỗi 5 phút (tùy chọn)
setInterval(() => {
    if(window.countOrderManager && document.visibilityState === 'visible') {
        window.countOrderManager.refresh();
    }
}, 5 * 60 * 1000);