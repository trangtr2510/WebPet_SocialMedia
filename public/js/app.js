const openBtn = document.querySelector(".open-btn");
const closeBtn = document.querySelector(".close-btn");
const navLinks = document.querySelectorAll(".nav-links a");
const fulfillmentCtx = document
  .querySelector(".fulfillment-chart canvas")
  .getContext("2d");
const VisitorsCtx = document
  .querySelector(".visitors-chart canvas")
  .getContext("2d");

// control active nav-link
navLinks.forEach((navLink) => {
  navLink.addEventListener("click", function () {
    navLinks.forEach((l) => l.classList.remove("active"));
    this.classList.add("active");
  });
});

// customer fulfillment chart
// create linear gradient for first dataset
const gradient1 = fulfillmentCtx.createLinearGradient(0, 0, 0, 200);
gradient1.addColorStop(0, "#f2c8ed");
gradient1.addColorStop(1, "#21222d");

// create linear gradient for second dataset
const gradient2 = fulfillmentCtx.createLinearGradient(0, 0, 0, 200);
gradient2.addColorStop(0, "#a9dfd8");
gradient2.addColorStop(1, "#21222d");

async function initFulfillmentChart() {
  try {
    // Lấy giá trị từ các select box
    const selectMonth = document.getElementById('selectMonthOnly')?.value;
    const selectYear = document.getElementById('selectYearForMonth')?.value;
    
    // Tạo URL với các tham số lọc
    let url = '../../app/controllers/revenue2monthDashboard.php?action=getRevenue2Month';
    if (selectYear) {
      url += `&selectYear=${selectYear}`;
    }
    if (selectMonth) {
      url += `&selectMonth=${selectMonth}`;
    }

    // Gọi API để lấy dữ liệu
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    
    const result = await response.json();
    
    if (!result.success) {
      throw new Error('API request failed');
    }
    
    const data = result.data;
    
    // Tính tổng tiền của 2 tháng
    const totalLastMonth = data.previous_month.reduce((sum, value) => sum + value, 0);
    const totalThisMonth = data.current_month.reduce((sum, value) => sum + value, 0);
    
    // Định dạng số tiền thành dạng $1,234
    const formatCurrency = (amount) => {
      return '$' + amount.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    };
    
    // Cập nhật tổng tiền vào HTML
    const labelsContainer = document.querySelector('.fulfillment-chart .labels');
    labelsContainer.querySelector('.last-month-span').textContent = formatCurrency(totalLastMonth);
    labelsContainer.querySelector('.this-month-span').textContent = formatCurrency(totalThisMonth);
    
    // Cập nhật nhãn tháng
    labelsContainer.querySelector('.last-month-p').textContent = data.previous_month_label;
    labelsContainer.querySelector('.this-month-p').textContent = data.selected_month;
    
    // Tạo gradient cho biểu đồ
    const fulfillmentCtx = document
      .querySelector(".fulfillment-chart canvas")
      .getContext("2d");
    const gradient1 = fulfillmentCtx.createLinearGradient(0, 0, 0, 400);
    gradient1.addColorStop(0, "rgba(242, 200, 237, 0.5)");
    gradient1.addColorStop(1, "rgba(242, 200, 237, 0)");
    
    const gradient2 = fulfillmentCtx.createLinearGradient(0, 0, 0, 400);
    gradient2.addColorStop(0, "rgba(169, 223, 216, 0.5)");
    gradient2.addColorStop(1, "rgba(169, 223, 216, 0)");
    
    // Tạo biểu đồ với dữ liệu từ API
    const fulfillmentChart = new Chart(fulfillmentCtx, {
      type: "line",
      data: {
        labels: data.labels,
        datasets: [
          {
            label: data.selected_month,
            data: data.current_month,
            borderColor: "#f2c8ed",
            backgroundColor: gradient1,
            fill: true,
            pointRadius: 3,
          },
          {
            label: data.previous_month_label,
            data: data.previous_month,
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
          },
        },
        plugins: {
          legend: {
            display: false,
          },
        },
      },
    });
    
    return fulfillmentChart; // Trả về biểu đồ để có thể cập nhật sau này
    
  } catch (error) {
    console.error('Error loading fulfillment chart data:', error);
    // Có thể hiển thị thông báo lỗi cho người dùng ở đây
  }
}

// Biến lưu trữ biểu đồ hiện tại
let currentFulfillmentChart = null;

// Hàm cập nhật biểu đồ khi select thay đổi
function updateFulfillmentChart() {
  // Nếu đã có biểu đồ trước đó, hủy nó đi
  if (currentFulfillmentChart) {
    currentFulfillmentChart.destroy();
  }
  
  // Tạo biểu đồ mới
  initFulfillmentChart().then(chart => {
    currentFulfillmentChart = chart;
  });
}

// Gọi hàm khởi tạo biểu đồ lần đầu
updateFulfillmentChart();

// Thêm event listeners cho các select box
document.getElementById('selectYearForMonth')?.addEventListener('change', updateFulfillmentChart);
document.getElementById('selectMonthOnly')?.addEventListener('change', updateFulfillmentChart);

// create linear gradient for first dataset
const visitorsGradient = fulfillmentCtx.createLinearGradient(0, 0, 0, 400);
visitorsGradient.addColorStop(0, "#a9dfd8");
visitorsGradient.addColorStop(1, "#21222d");

const visitorsChart = new Chart(VisitorsCtx, {
  type: "line",
  data: {
    labels: [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ],
    datasets: [
      {
        label: "New Visitors",
        data: [60, 95, 450, 250, 350, 500, 280, 420, 380, 270, 120, 320],
        borderColor: "#a9dfd8",
        backgroundColor: visitorsGradient,
        fill: true,
        pointRadius: 0,
      },
    ],
  },
  options: {
    responsive: true,
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          color: "#ccc",
        },
      },
      x: {
        ticks: {
          color: "#ccc",
        },
      },
    },
    plugins: {
      legend: {
        display: false,
      },
    },
  },
});



// js giao dien
document.addEventListener('DOMContentLoaded', function () {
  // Get elements
  const timePeriodSelect = document.getElementById('timePeriod');
  const daySelection = document.getElementById('daySelection');
  const monthSelection = document.getElementById('monthSelection');
  const quarterSelection = document.getElementById('quarterSelection');
  const yearSelection = document.getElementById('yearSelection');

  // Populate year dropdowns
  const currentYear = new Date().getFullYear();
  const yearSelects = [
    document.getElementById('selectYearForMonth'),
    document.getElementById('selectYearForQuarter'),
    document.getElementById('selectYear')
  ];

  for (let i = currentYear; i >= currentYear - 10; i--) {
    yearSelects.forEach(select => {
      const option = document.createElement('option');
      option.value = i;
      option.textContent = i;
      select.appendChild(option);
    });
  }

  // Populate days based on selected month
  function updateDays() {
    const monthSelect = document.getElementById('selectMonth');
    const daySelect = document.getElementById('selectDay');
    const year = document.getElementById('selectYearForMonth').value || currentYear;
    const month = monthSelect.value;
    const daysInMonth = new Date(year, month, 0).getDate();

    daySelect.innerHTML = '';
    for (let i = 1; i <= daysInMonth; i++) {
      const option = document.createElement('option');
      option.value = i;
      option.textContent = i;
      daySelect.appendChild(option);
    }
  }

  document.getElementById('selectMonth').addEventListener('change', updateDays);
  document.getElementById('selectYearForMonth').addEventListener('change', updateDays);

  // Initialize days
  updateDays();

  // Handle time period change
  timePeriodSelect.addEventListener('change', function () {
    const value = this.value;

    // Hide all selections first
    daySelection.style.display = 'none';
    monthSelection.style.display = 'none';
    quarterSelection.style.display = 'none';
    yearSelection.style.display = 'none';

    // Show the appropriate selection
    if (value === 'day') {
      daySelection.style.display = 'flex';
    } else if (value === 'month') {
      monthSelection.style.display = 'flex';
    } else if (value === 'quarter') {
      quarterSelection.style.display = 'flex';
    } else if (value === 'year') {
      yearSelection.style.display = 'flex';
    }
  });

  // Initialize with day selection visible
  daySelection.style.display = 'flex';

  // Excel export button functionality
  document.getElementById('exportExcel').addEventListener('click', function () {
    // Add your export to Excel functionality here
    alert('Xuất Excel sẽ được thực hiện với các tham số đã chọn');
  });
});