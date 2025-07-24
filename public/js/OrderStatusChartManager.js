class OrderStatusChartManager {
    constructor() {
        this.apiUrl = '../../app/controllers/OrderStatusPercentageController.php';
        this.currentYear = new Date().getFullYear();
        this.currentMonth = new Date().getMonth() + 1;
        this.currentDay = new Date().getDate();
        
        // Định nghĩa màu sắc cho các trạng thái
        this.statusColors = {
            pending: 'var(--clr-orange)',
            processing: 'var(--clr-blue)',
            shipped: 'var(--clr-purple)',
            delivered: 'var(--clr-light-green)',
            cancelled: 'var(--clr-red)'
        };
        
        // Định nghĩa nhãn tiếng Việt
        this.statusLabels = {
            pending: 'Chờ xử lý',
            processing: 'Đang xử lý',
            shipped: 'Đã gửi',
            delivered: 'Đã giao',
            cancelled: 'Đã hủy'
        };
        
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
        const timePeriodElement = document.getElementById('timePeriod');
        if (timePeriodElement) {
            timePeriodElement.addEventListener('change', (e) => {
                this.handleTimePeriodChange(e.target.value);
            });
        }
        
        // Lắng nghe thay đổi các selector
        const selectors = [
            'selectMonth', 'selectDay', 'selectMonthOnly', 
            'selectYearForMonth', 'selectQuarter', 'selectYearForQuarter', 'selectYear'
        ];
        
        selectors.forEach(selectorId => {
            const element = document.getElementById(selectorId);
            if (element) {
                element.addEventListener('change', () => {
                    if (selectorId === 'selectMonth') {
                        this.populateDays();
                    }
                    this.updateOrderStatusChart();
                });
            }
        });
    }
    
    /**
     * Xử lý thay đổi time period
     */
    handleTimePeriodChange(period) {
        // Cập nhật biểu đồ khi thay đổi period
        this.updateOrderStatusChart();
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
        
        const startYear = 2020;
        const endYear = this.currentYear + 1;
        
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
        
        daySelect.innerHTML = '';
        
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
        const selectMonth = document.getElementById('selectMonth');
        const selectMonthOnly = document.getElementById('selectMonthOnly');
        
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
        this.updateOrderStatusChart();
    }
    
    /**
     * Lấy parameters cho API call
     */
    getApiParameters() {
        const timePeriodElement = document.getElementById('timePeriod');
        const timePeriod = timePeriodElement ? timePeriodElement.value : 'day';
        
        let params = {
            action: 'getOrderStatusStats',
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
     * Cập nhật biểu đồ trạng thái đơn hàng
     */
    async updateOrderStatusChart() {
        try {
            this.showLoading(true);
            
            const params = this.getApiParameters();
            const queryString = new URLSearchParams(params).toString();
            
            const response = await fetch(`${this.apiUrl}?${queryString}`);
            const text = await response.text();
            console.log('Raw response:', text);
            const result = JSON.parse(text);
            
            if(result.success) {
                this.updateChartUI(result.data);
            } else {
                this.showError(result.error || 'Có lỗi xảy ra khi tải dữ liệu trạng thái đơn hàng');
            }
        } catch (error) {
            console.error('Error updating order status chart:', error);
            this.showError('Không thể kết nối đến server');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Cập nhật giao diện biểu đồ với dữ liệu mới
     */
    updateChartUI(data) {
        const statusDistribution = data.status_distribution;
        const totalOrders = data.total_orders;
        
        // Cập nhật biểu đồ tròn
        this.updatePieChart(statusDistribution);
        
        // Cập nhật legend
        this.updateLegend(statusDistribution);
        
        // Cập nhật center text
        this.updateCenterText(totalOrders);
        
        // Trigger custom event
        document.dispatchEvent(new CustomEvent('orderStatusChartUpdated', {
            detail: data
        }));
    }
    
    /**
     * Cập nhật biểu đồ tròn
     */
    updatePieChart(statusDistribution) {
        const pieChart = document.querySelector('.pie-chart');
        if (!pieChart) return;
        
        // Tạo conic-gradient dựa trên dữ liệu
        let gradientStops = [];
        let currentAngle = 0;
        
        Object.keys(this.statusColors).forEach(status => {
            const percentage = statusDistribution[status]?.percentage || 0;
            const angle = (percentage / 100) * 360;
            
            if (percentage > 0) {
                const color = this.statusColors[status];
                gradientStops.push(`${color} ${currentAngle}deg ${currentAngle + angle}deg`);
                currentAngle += angle;
            }
        });
        
        // Nếu không có dữ liệu, hiển thị màu xám
        if (gradientStops.length === 0) {
            pieChart.style.background = '#e0e0e0';
        } else {
            // Fill phần còn lại bằng màu transparent nếu cần
            if (currentAngle < 360) {
                gradientStops.push(`transparent ${currentAngle}deg 360deg`);
            }
            
            const gradient = `conic-gradient(from 0deg, ${gradientStops.join(', ')})`;
            pieChart.style.background = gradient;
        }
    }
    
    /**
     * Cập nhật legend
     */
    updateLegend(statusDistribution) {
        const legend = document.querySelector('.legend');
        if (!legend) return;
        
        legend.innerHTML = '';
        
        Object.keys(this.statusColors).forEach(status => {
            const data = statusDistribution[status] || { count: 0, percentage: 0 };
            const legendItem = document.createElement('div');
            legendItem.className = 'legend-item';
            
            legendItem.innerHTML = `
                <span class="color ${status}"></span>
                <span>${this.statusLabels[status]} (${data.percentage}% - ${data.count})</span>
            `;
            
            legend.appendChild(legendItem);
        });
    }
    
    /**
     * Cập nhật text ở giữa biểu đồ
     */
    updateCenterText(totalOrders) {
        const centerElement = document.querySelector('.pie-chart-center');
        if (centerElement) {
            centerElement.innerHTML = `
                <div style="text-align: center;">
                    <div style="font-size: 1.2em; font-weight: bold; color: #333;">${totalOrders}</div>
                    <div style="font-size: 0.8em; opacity: 0.7; color: #333;">Tổng đơn</div>
                </div>
            `;
        }
    }
    
    /**
     * Hiển thị loading state
     */
    showLoading(show) {
        const centerElement = document.querySelector('.pie-chart-center');
        const legend = document.querySelector('.legend');
        
        if (show) {
            if (centerElement) {
                centerElement.innerHTML = '<div style="font-size: 0.9em;">Đang tải...</div>';
            }
            if (legend) {
                legend.innerHTML = '<div style="text-align: center; color: #999;">Đang tải dữ liệu...</div>';
            }
        }
    }
    
    /**
     * Hiển thị lỗi
     */
    showError(message) {
        const centerElement = document.querySelector('.pie-chart-center');
        const legend = document.querySelector('.legend');
        
        if (centerElement) {
            centerElement.innerHTML = '<div style="font-size: 0.8em; color: #f44336;">Lỗi</div>';
        }
        if (legend) {
            legend.innerHTML = `<div style="text-align: center; color: #f44336; font-size: 0.9em;">${message}</div>`;
        }
        
        // Tự động thử lại sau 5 giây
        setTimeout(() => {
            this.updateOrderStatusChart();
        }, 5000);
    }
    
    /**
     * Hiển thị notification
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
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
        
        const colors = {
            success: '#4CAF50',
            error: '#f44336',
            info: '#2196F3'
        };
        notification.style.backgroundColor = colors[type] || colors.info;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 100);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    /**
     * Lấy dữ liệu hiện tại của biểu đồ
     */
    getCurrentChartData() {
        const centerElement = document.querySelector('.pie-chart-center');
        const legendItems = document.querySelectorAll('.legend-item');
        
        return {
            total: centerElement ? centerElement.textContent.trim() : null,
            legend: Array.from(legendItems).map(item => item.textContent.trim())
        };
    }
    
    /**
     * Refresh dữ liệu biểu đồ
     */
    refresh() {
        this.updateOrderStatusChart();
    }
    
    /**
     * Export dữ liệu biểu đồ
     */
    async exportChartData() {
        try {
            const params = this.getApiParameters();
            params.export = 'excel';
            
            const queryString = new URLSearchParams(params).toString();
            
            const link = document.createElement('a');
            link.href = `${this.apiUrl}?${queryString}`;
            link.download = `order_status_chart_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showNotification('Đã xuất dữ liệu biểu đồ trạng thái đơn hàng thành công!', 'success');
            
        } catch (error) {
            console.error('Error exporting chart data:', error);
            this.showNotification('Có lỗi khi xuất dữ liệu biểu đồ', 'error');
        }
    }
}

// CSS bổ sung cho biểu đồ
const orderStatusChartStyles = `
<style>
.pie-chart-center {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.legend-item {
    transition: opacity 0.3s ease;
}

.legend-item:hover {
    opacity: 0.8;
}

.notification {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.orders-chart .chart-container {
    min-height: 300px;
}

.orders-chart .pie-chart {
    transition: transform 0.3s ease;
}

.orders-chart .pie-chart:hover {
    transform: scale(1.02);
}

@media screen and (max-width: 768px) {
    .orders-chart .pie-chart {
        width: 180px;
        height: 180px;
    }
    
    .orders-chart .legend {
        font-size: 0.8rem;
    }
}
</style>
`;

// Thêm CSS vào head
document.head.insertAdjacentHTML('beforeend', orderStatusChartStyles);

// Khởi tạo Order Status Chart Manager khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra xem có biểu đồ trạng thái đơn hàng trên trang không
    if (document.querySelector('.orders-chart .pie-chart')) {
        window.orderStatusChartManager = new OrderStatusChartManager();
        
        // Expose methods để sử dụng từ bên ngoài
        window.refreshOrderStatusChart = () => window.orderStatusChartManager.refresh();
        window.getCurrentChartData = () => window.orderStatusChartManager.getCurrentChartData();
        // window.exportOrderStatusChart = () => window.orderStatusChartManager.exportChartData();
    }
});

// Lắng nghe sự kiện từ CountOrderManager để đồng bộ dữ liệu
document.addEventListener('orderStatsUpdated', function(event) {
    if (window.orderStatusChartManager) {
        // Có thể đồng bộ hoặc refresh biểu đồ khi order stats thay đổi
        setTimeout(() => {
            window.orderStatusChartManager.refresh();
        }, 500);
    }
});

// Auto-refresh mỗi 5 phút (tùy chọn)
setInterval(() => {
    if (window.orderStatusChartManager && document.visibilityState === 'visible') {
        window.orderStatusChartManager.refresh();
    }
}, 5 * 60 * 1000);