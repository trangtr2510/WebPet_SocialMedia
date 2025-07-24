class CountProductManager {
    constructor() {
        this.apiUrl = '../../app/controllers/dashboardProductcontroller.php';
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
            this.updateProductStats();
        });
        
        document.getElementById('selectDay').addEventListener('change', () => {
            this.updateProductStats();
        });
        
        document.getElementById('selectMonthOnly').addEventListener('change', () => {
            this.updateProductStats();
        });
        
        document.getElementById('selectYearForMonth').addEventListener('change', () => {
            this.updateProductStats();
        });
        
        document.getElementById('selectQuarter').addEventListener('change', () => {
            this.updateProductStats();
        });
        
        document.getElementById('selectYearForQuarter').addEventListener('change', () => {
            this.updateProductStats();
        });
        
        document.getElementById('selectYear').addEventListener('change', () => {
            this.updateProductStats();
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
        this.updateProductStats();
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
        const selectedYear = document.getElementById('selectYearForMonth') ? 
            parseInt(document.getElementById('selectYearForMonth').value) : this.currentYear;
        
        // Xóa các option cũ
        daySelect.innerHTML = '';
        
        // Lấy số ngày trong tháng
        const daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();
        
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
        if(document.getElementById('selectMonth')) {
            document.getElementById('selectMonth').value = this.currentMonth;
        }
        if(document.getElementById('selectMonthOnly')) {
            document.getElementById('selectMonthOnly').value = this.currentMonth;
        }
        
        // Set quý hiện tại
        const currentQuarter = Math.ceil(this.currentMonth / 3);
        const selectQuarter = document.getElementById('selectQuarter');
        if(selectQuarter) selectQuarter.value = currentQuarter;
    }
    
    /**
     * Load dữ liệu ban đầu
     */
    loadInitialData() {
        this.updateProductStats();
    }
    
    /**
     * Lấy parameters cho API call
     */
    getApiParameters() {
        const timePeriodElement = document.getElementById('timePeriod');
        const timePeriod = timePeriodElement ? timePeriodElement.value : 'day';
        
        let params = {
            action: 'getTopSellingProductsStats',
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
     * Cập nhật thống kê sản phẩm
     */
    async updateProductStats() {
        try {
            this.showLoading(true);
            
            const params = this.getApiParameters();
            const queryString = new URLSearchParams(params).toString();
            
            const response = await fetch(`${this.apiUrl}?${queryString}`);
            const result = await response.json();
            
            if(result.success) {
                this.updateProductUI(result.data);
            } else {
                this.showError(result.error || 'Có lỗi xảy ra khi tải dữ liệu sản phẩm');
            }
        } catch (error) {
            console.error('Error updating product stats:', error);
            this.showError('Không thể kết nối đến server');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Cập nhật giao diện với dữ liệu sản phẩm mới
     */
    updateProductUI(data) {
        const tableBody = document.querySelector('.top-products-details tbody');
        if (!tableBody) return;
        
        // Xóa các hàng cũ
        tableBody.innerHTML = '';
        
        // Thêm các hàng mới
        if (data.top_products && data.top_products.length > 0) {
            data.top_products.forEach((product, index) => {
                const row = document.createElement('tr');
                
                // Tạo các ô dữ liệu
                const rankCell = document.createElement('td');
                rankCell.textContent = String(index + 1).padStart(2, '0');
                
                const nameCell = document.createElement('td');
                nameCell.textContent = product.product_name;
                
                const popularityCell = document.createElement('td');
                const rangeDiv = document.createElement('div');
                rangeDiv.className = 'range';
                const rangeInnerDiv = document.createElement('div');
                rangeInnerDiv.className = `range-${index + 1}`;
                rangeInnerDiv.style.width = `${product.percentage}%`;
                rangeDiv.appendChild(rangeInnerDiv);
                popularityCell.appendChild(rangeDiv);
                
                const salesCell = document.createElement('td');
                const salesVolumeDiv = document.createElement('div');
                salesVolumeDiv.className = `sales-volume sv-${index + 1}`;
                salesVolumeDiv.textContent = `${product.percentage}%`;
                salesCell.appendChild(salesVolumeDiv);
                
                // Thêm các ô vào hàng
                row.appendChild(rankCell);
                row.appendChild(nameCell);
                row.appendChild(popularityCell);
                row.appendChild(salesCell);
                
                // Thêm hàng vào bảng
                tableBody.appendChild(row);
            });
        } else {
            // Hiển thị thông báo khi không có dữ liệu
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.textContent = 'Không có dữ liệu sản phẩm nào trong khoảng thời gian này';
            cell.style.textAlign = 'center';
            cell.style.padding = '20px';
            row.appendChild(cell);
            tableBody.appendChild(row);
        }
        
        // Cập nhật tiêu đề nếu cần
        const headerElement = document.querySelector('.top-products-details header h2');
        if (headerElement && data.period_text) {
            headerElement.textContent = `Top Products - ${data.period_text}`;
        }
        
        // Trigger custom event để các component khác có thể lắng nghe
        document.dispatchEvent(new CustomEvent('productStatsUpdated', {
            detail: data
        }));
    }
    
    /**
     * Hiển thị loading state
     */
    showLoading(show) {
        const tableBody = document.querySelector('.top-products-details tbody');
        if (!tableBody) return;
        
        if (show) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px;">
                        Đang tải dữ liệu...
                    </td>
                </tr>
            `;
        }
    }
    
    /**
     * Hiển thị lỗi
     */
    showError(message) {
        const tableBody = document.querySelector('.top-products-details tbody');
        if (!tableBody) return;
        
        tableBody.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; padding: 20px; color: #f44336;">
                    ${message}
                </td>
            </tr>
        `;
        
        // Tự động ẩn lỗi sau 5 giây
        setTimeout(() => {
            this.updateProductStats(); // Thử lại
        }, 5000);
    }
    
    /**
     * Export dữ liệu sản phẩm ra Excel
     */
    async exportToExcel() {
        try {
            const params = this.getApiParameters();
            params.export = 'excel';
            
            const queryString = new URLSearchParams(params).toString();
            
            // Tạo link download
            const link = document.createElement('a');
            link.href = `${this.apiUrl}?${queryString}`;
            link.download = `product_statistics_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Hiển thị thông báo thành công
            this.showNotification('Đã xuất file Excel thống kê sản phẩm thành công!', 'success');
            
        } catch (error) {
            console.error('Error exporting product Excel:', error);
            this.showNotification('Có lỗi khi xuất file Excel thống kê sản phẩm', 'error');
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
     * Refresh dữ liệu sản phẩm
     */
    refresh() {
        this.updateProductStats();
    }
}

// CSS cho các class được sử dụng
const productCountStyles = `
<style>
.top-products-details {
    background: #f5f5f5;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    margin-top: 20px;
}

.top-products-details header {
    margin-bottom: 20px;
}

.top-products-details header h2 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.top-products-details table {
    width: 100%;
    border-collapse: collapse;
}

.top-products-details th {
    text-align: left;
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    font-weight: 500;
    color: #666;
}

.top-products-details td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.range {
    width: 100%;
    height: 8px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
}

.range-1 {
    height: 100%;
    background: #4CAF50;
    border-radius: 4px;
}

.range-2 {
    height: 100%;
    background: #2196F3;
    border-radius: 4px;
}

.range-3 {
    height: 100%;
    background: #FFC107;
    border-radius: 4px;
}

.range-4 {
    height: 100%;
    background: #FF5722;
    border-radius: 4px;
}

.sales-volume {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    color: white;
    text-align: center;
    min-width: 50px;
    display: inline-block;
}

.sv-1 {
    background: #4CAF50;
}

.sv-2 {
    background: #2196F3;
}

.sv-3 {
    background: #FFC107;
}

.sv-4 {
    background: #FF5722;
}

.product-stats-select {
    margin: 0 5px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
}

.product-excel-btn {
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

.product-excel-btn:hover {
    background: #1976D2;
}

.product-date-selection {
    display: inline-block;
    margin: 0 10px;
}

.product-statistics-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .product-statistics-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .product-date-selection {
        margin: 5px 0;
    }
}
</style>
`;

// Thêm CSS vào head
document.head.insertAdjacentHTML('beforeend', productCountStyles);

// Khởi tạo Count Product Manager khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    window.countProductManager = new CountProductManager();
    
    // Expose một số method để sử dụng từ bên ngoài
    window.refreshProductStats = () => window.countProductManager.refresh();
});

// Auto-refresh mỗi 5 phút (tùy chọn)
setInterval(() => {
    if(window.countProductManager && document.visibilityState === 'visible') {
        window.countProductManager.refresh();
    }
}, 5 * 60 * 1000);