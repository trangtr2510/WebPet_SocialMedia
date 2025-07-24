class Revenue2MonthDashboard {
    constructor() {
        this.apiUrl = '../../app/controllers/revenue2monthDashboard.php';
        this.currentYear = new Date().getFullYear();
        this.currentMonth = new Date().getMonth() + 1;
        
        this.fulfillmentChart = null;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.populateSelectors();
        this.loadInitialData();
        this.initFulfillmentChart();
    }

    /**
     * Thiết lập event listeners
     */
    setupEventListeners() {
        // Lắng nghe thay đổi các selector
        document.getElementById('selectMonthOnly')?.addEventListener('change', () => {
            this.updateRevenueStats();
        });

        document.getElementById('selectYearForMonth')?.addEventListener('change', () => {
            this.updateRevenueStats();
        });
    }

    /**
     * Populate các selector với dữ liệu
     */
    populateSelectors() {
        this.populateYears();
        this.populateMonths();
        this.setDefaultValues();
    }

    /**
     * Populate years cho selector
     */
    populateYears() {
        const yearSelector = document.getElementById('selectYearForMonth');
        if (!yearSelector) return;

        const startYear = 2020;
        const endYear = this.currentYear + 1;

        yearSelector.innerHTML = '';

        for (let year = endYear; year >= startYear; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = `Năm ${year}`;
            if (year === this.currentYear) {
                option.selected = true;
            }
            yearSelector.appendChild(option);
        }
    }

    /**
     * Populate months cho selector
     */
    populateMonths() {
        const monthSelector = document.getElementById('selectMonthOnly');
        if (!monthSelector) return;

        monthSelector.innerHTML = '';

        for (let month = 1; month <= 12; month++) {
            const option = document.createElement('option');
            option.value = month;
            option.textContent = `Tháng ${month}`;
            if (month === this.currentMonth) {
                option.selected = true;
            }
            monthSelector.appendChild(option);
        }
    }

    /**
     * Thiết lập giá trị mặc định
     */
    setDefaultValues() {
        if (document.getElementById('selectMonthOnly')) {
            document.getElementById('selectMonthOnly').value = this.currentMonth;
        }
        if (document.getElementById('selectYearForMonth')) {
            document.getElementById('selectYearForMonth').value = this.currentYear;
        }
    }

    /**
     * Load dữ liệu ban đầu
     */
    loadInitialData() {
        this.updateRevenueStats();
    }

    /**
     * Khởi tạo biểu đồ fulfillment
     */
    initFulfillmentChart() {
        const fulfillmentCanvas = document.querySelector(".fulfillment-chart canvas");
        if (!fulfillmentCanvas) return;

        if (this.fulfillmentChart) {
            this.fulfillmentChart.destroy();
        }

        const fulfillmentCtx = fulfillmentCanvas.getContext("2d");

        // Tạo gradient cho biểu đồ
        const gradient1 = fulfillmentCtx.createLinearGradient(0, 0, 0, 200);
        gradient1.addColorStop(0, "#f2c8ed");
        gradient1.addColorStop(1, "#21222d");

        const gradient2 = fulfillmentCtx.createLinearGradient(0, 0, 0, 200);
        gradient2.addColorStop(0, "#a9dfd8");
        gradient2.addColorStop(1, "#21222d");

        this.fulfillmentChart = new Chart(fulfillmentCtx, {
            type: "line",
            data: {
                labels: ["1", "4", "7", "10", "13", "16", "19", "22", "25", "28", "31"],
                datasets: [
                    {
                        label: "This month",
                        data: [],
                        borderColor: "#f2c8ed",
                        backgroundColor: gradient1,
                        fill: true,
                        pointRadius: 3,
                    },
                    {
                        label: "Last month",
                        data: [],
                        borderColor: "#a9dfd8",
                        backgroundColor: gradient2,
                        fill: true,
                        pointRadius: 3,
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': $' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
            },
        });
    }

    /**
     * Cập nhật thống kê doanh thu
     */
    async updateRevenueStats() {
        try {
            this.showLoading(true);

            const params = {
                action: 'getRevenue2Month',
                selectMonth: document.getElementById('selectMonthOnly')?.value || this.currentMonth,
                selectYear: document.getElementById('selectYearForMonth')?.value || this.currentYear
            };

            const queryString = new URLSearchParams(params).toString();
            const response = await fetch(`${this.apiUrl}?${queryString}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                this.updateRevenueUI(result.data);
            } else {
                this.showError(result.error || 'Có lỗi xảy ra khi tải dữ liệu doanh thu');
            }
        } catch (error) {
            console.error('Error updating revenue stats:', error);
            this.showError('Không thể kết nối đến server');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Cập nhật giao diện với dữ liệu doanh thu mới
     */
    updateRevenueUI(data) {
        // Cập nhật biểu đồ fulfillment
        if (this.fulfillmentChart) {
            this.fulfillmentChart.data.labels = data.labels;
            this.fulfillmentChart.data.datasets[0].data = data.current_month;
            this.fulfillmentChart.data.datasets[1].data = data.previous_month;
            this.fulfillmentChart.update();
        }

        // Cập nhật labels và tổng doanh thu
        const labelsContainer = document.querySelector('.fulfillment-chart .labels');
        if (labelsContainer) {
            // Tính tổng doanh thu tháng trước
            const lastMonthTotal = data.previous_month.reduce((a, b) => a + b, 0);
            // Tính tổng doanh thu tháng này
            const currentMonthTotal = data.current_month.reduce((a, b) => a + b, 0);

            // Cập nhật label tháng trước
            const lastMonthLabel = labelsContainer.querySelector('.last-month + p');
            const lastMonthValue = labelsContainer.querySelector('.labels > div:first-child span');
            if (lastMonthLabel && lastMonthValue) {
                lastMonthLabel.textContent = data.previous_month_label || 'Last Month';
                lastMonthValue.textContent = '$' + this.formatMoney(lastMonthTotal);
            }

            // Cập nhật label tháng này
            const thisMonthLabel = labelsContainer.querySelector('.this-month + p');
            const thisMonthValue = labelsContainer.querySelector('.labels > div:last-child span');
            if (thisMonthLabel && thisMonthValue) {
                thisMonthLabel.textContent = data.selected_month || 'This Month';
                thisMonthValue.textContent = '$' + this.formatMoney(currentMonthTotal);
            }
        }
    }

    /**
     * Định dạng tiền tệ
     */
    formatMoney(amount) {
        return amount.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    /**
     * Hiển thị loading state
     */
    showLoading(show) {
        const chartContainer = document.querySelector('.fulfillment-chart .chart-container');
        const labelsContainer = document.querySelector('.fulfillment-chart .labels');

        if (show) {
            if (chartContainer) {
                if (!chartContainer.dataset.originalContent) {
                    chartContainer.dataset.originalContent = chartContainer.innerHTML;
                }
                chartContainer.innerHTML = '<p>Đang tải dữ liệu...</p>';
            }
            if (labelsContainer) {
                labelsContainer.style.opacity = '0.5';
            }
        } else {
            if (chartContainer) {
                if (chartContainer.dataset.originalContent) {
                    chartContainer.innerHTML = chartContainer.dataset.originalContent;
                    delete chartContainer.dataset.originalContent;
                }
            }
            if (labelsContainer) {
                labelsContainer.style.opacity = '1';
            }
        }
    }

    /**
     * Hiển thị lỗi
     */
    showError(message) {
        const chartContainer = document.querySelector('.fulfillment-chart .chart-container');
        if (chartContainer) {
            chartContainer.innerHTML = `<p class="error">${message}</p>`;
        }

        // Tự động ẩn lỗi sau 5 giây
        setTimeout(() => {
            this.updateRevenueStats(); // Thử lại
        }, 5000);
    }

    /**
     * Refresh dữ liệu doanh thu
     */
    refresh() {
        this.updateRevenueStats();
    }

    /**
     * Destroy all charts - cleanup method
     */
    destroy() {
        if (this.fulfillmentChart) {
            this.fulfillmentChart.destroy();
            this.fulfillmentChart = null;
        }
    }
}

// CSS cho các class được sử dụng
const revenueChartStyles = `
<style>
.fulfillment-chart{
    position: relative;
}

.fulfillment-chart .error {
    color: #f44336;
    text-align: center;
    padding: 20px;
}

.fulfillment-chart .labels {
    display: flex;
    justify-content: space-around;
    align-items: center;
    margin-top: 20px;
}

.fulfillment-chart .labels > div {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.fulfillment-chart .label {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}

.fulfillment-chart .last-month {
    width: 12px;
    height: 12px;
    background-color: #a9dfd8;
    margin-right: 8px;
    border-radius: 3px;
}

.fulfillment-chart .this-month {
    width: 12px;
    height: 12px;
    background-color: #f2c8ed;
    margin-right: 8px;
    border-radius: 3px;
}

.fulfillment-chart .divider {
    width: 1px;
    height: 40px;
    background-color: #eee;
}

.fulfillment-chart span {
    font-weight: bold;
    font-size: 1.1em;
}
</style>
`;

// Thêm CSS vào head
document.head.insertAdjacentHTML('beforeend', revenueChartStyles);

// Khởi tạo Revenue Dashboard Manager khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', function () {
    if (window.revenue2MonthDashboard) {
        window.revenue2MonthDashboard.destroy();
    }
    
    window.revenue2MonthDashboard = new Revenue2MonthDashboard();
    window.refreshRevenueStats = () => window.revenue2MonthDashboard.refresh();
});

// Auto-refresh mỗi 5 phút (tùy chọn)
setInterval(() => {
    if (window.revenue2MonthDashboard && document.visibilityState === 'visible') {
        window.revenue2MonthDashboard.refresh();
    }
}, 5 * 60 * 1000);

// Thêm event listener để cleanup khi trang đóng
window.addEventListener('beforeunload', () => {
    if (window.revenue2MonthDashboard) {
        window.revenue2MonthDashboard.destroy();
    }
});