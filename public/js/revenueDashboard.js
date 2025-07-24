class RevenueDashboard {
    constructor() {
        this.currentType = 'quarter'; // quarter hoặc year
        this.currentYear = new Date().getFullYear();
        this.currentQuarter = Math.ceil((new Date().getMonth() + 1) / 3);
        
        this.initElements();
        this.initEvents();
        this.loadData();
    }

    initElements() {
        this.chartWrapper = document.querySelector('.chart.level-chart');
        this.chartContainer = document.querySelector('.chart.level-chart .chart-container');
        this.chartHeader = document.querySelector('.chart.level-chart header h2');
        
        // Tạo container cho biểu đồ có trục
        this.chartWrapper.innerHTML = `
            <header>
                <h2>Doanh thu</h2>
            </header>
            <div class="chart-with-axis">
                <div class="y-axis"></div>
                <div class="chart-container"></div>
            </div>
            <div class="labels">
                <div class="label">
                    <div class="active"></div>
                    <p>Doanh thu</p>
                </div>
            </div>
        `;
        
        this.chartContainer = this.chartWrapper.querySelector('.chart-container');
        this.yAxis = this.chartWrapper.querySelector('.y-axis');
        
        // Các select control
        this.selectYearForMonth = document.getElementById('selectYearForMonth');
        this.selectMonthOnly = document.getElementById('selectMonthOnly');
        this.selectMonth = document.getElementById('selectMonth');
        this.selectYear = document.getElementById('selectYear');
        this.selectQuarter = document.getElementById('selectQuarter');
    }

    initEvents() {
        if (this.selectYear) {
            this.selectYear.addEventListener('change', () => {
                this.currentYear = this.selectYear.value;
                this.loadData();
            });
        }

        if (this.selectQuarter) {
            this.selectQuarter.addEventListener('change', () => {
                this.currentQuarter = this.selectQuarter.value;
                this.loadData();
            });
        }
    }

    loadData() {
        const params = new URLSearchParams();
        params.append('action', 'getRevenue');
        params.append('type', this.currentType);
        params.append('year', this.currentYear);
        
        if (this.currentType === 'quarter') {
            params.append('quarter', this.currentQuarter);
        }

        fetch(`../../app/controllers/revenueDashboard.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateChart(data.data);
                } else {
                    console.error('Error loading revenue data:', data.error);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
            });
    }

    updateChart(data) {
        // Xóa nội dung cũ
        this.chartContainer.innerHTML = '';
        this.yAxis.innerHTML = '';
        
        // Cập nhật tiêu đề
        this.chartHeader.textContent = `Doanh thu ${data.label}`;
        
        // Tìm giá trị lớn nhất để tính tỷ lệ phần trăm
        const maxValue = Math.max(...data.data.map(item => item.total), 1);
        
        // Tạo các mốc cho trục Y (5 mốc)
        const yAxisSteps = 5;
        const stepValue = maxValue / yAxisSteps;
        
        // Tạo trục Y
        for (let i = yAxisSteps; i >= 0; i--) {
            const value = stepValue * i;
            const axisLabel = document.createElement('div');
            axisLabel.className = 'y-axis-label';
            axisLabel.textContent = this.formatShortCurrency(value);
            this.yAxis.appendChild(axisLabel);
        }
        
        // Tạo các cột biểu đồ
        data.data.forEach(item => {
            const monthName = this.getMonthName(item.month);
            const heightPercent = (item.total / maxValue) * 100;
            
            const barContainer = document.createElement('div');
            barContainer.className = 'bar-container';
            
            const bar = document.createElement('div');
            bar.className = 'bar';
            bar.style.height = `${heightPercent}%`;
            
            const barLabel = document.createElement('span');
            barLabel.className = 'bar-label';
            barLabel.textContent = monthName;
            
            barContainer.appendChild(bar);
            barContainer.appendChild(barLabel);
            this.chartContainer.appendChild(barContainer);
            
            // Thêm tooltip hiển thị giá trị chính xác
            bar.addEventListener('mouseover', (e) => {
                const tooltip = document.createElement('div');
                tooltip.className = 'chart-tooltip';
                tooltip.textContent = this.formatCurrency(item.total);
                tooltip.style.position = 'absolute';
                tooltip.style.bottom = `${heightPercent + 5}%`;
                tooltip.style.left = '50%';
                tooltip.style.transform = 'translateX(-50%)';
                
                barContainer.appendChild(tooltip);
                
                // Xóa tooltip khi di chuột ra
                bar.addEventListener('mouseout', () => {
                    barContainer.removeChild(tooltip);
                }, { once: true });
            });
        });
    }

    getMonthName(monthNumber) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[monthNumber - 1];
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', { 
            style: 'currency', 
            currency: 'VND' 
        }).format(amount);
    }

    formatShortCurrency(amount) {
        if (amount >= 1000000) {
            return `${(amount / 1000000).toFixed(1)}M`;
        } else if (amount >= 1000) {
            return `${(amount / 1000).toFixed(0)}k`;
        }
        return amount.toFixed(0);
    }
}

// Khởi tạo khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
    new RevenueDashboard();
});