<?php
include(__DIR__ . '/../../config/config.php');
require_once(__DIR__ .'/../../app/models/User.php');

session_start();

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$user_type = $is_logged_in ? $_SESSION['user_type'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../views/auth/login_register.php");
    exit;
}

// Lấy thông tin người dùng
$user = $userModel->getUserById($_SESSION['user_id']);

// Kiểm tra vai trò
if (!$userModel->isAdmin($user) && !$userModel->isEmployee($user)) {
    // Nếu không phải admin hoặc employee thì chuyển về login_register
    header("Location: ../../../views/auth/login_register.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management Interface</title>
    <link rel="stylesheet" href="../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/admin/admin.css">
    <script type="text/javascript" src="../../public/js/admin.js" defer></script>
</head>

<body>
    <nav id="sidebar" class="sidebar">
        <ul>
            <!-- Toggle Button at Top -->
            <li class="toggle-section">
                <div class="toggle-container">
                    <button onclick="toggleSidebar()" id="toggle-btn" class="toggle-btn" data-tooltip="Toggle Menu">
                        <i class="fas fa-angle-double-left icon_rotate"></i>
                    </button>
                    <div class="collapsed-avatar">
                        <img src="../../public/uploads/avatar/<?php echo htmlspecialchars($img); ?>" alt="User Avatar" class="user-avatar-collapsed">
                    </div>
                </div>
                <div class="logo">
                    <img src="../../public/uploads/avatar/<?php echo htmlspecialchars($img); ?>" alt="User Avatar" class="user-avatar">
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($user_type); ?></span>
                    </div>
                </div>
            </li>
            <!-- Navigation Items -->
            <li  class="active">
                <a href="./dashboard.php" data-tooltip="Dashboard">
                    <i class="fas fa-chart-line icon_nav"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="./category/index.php" data-tooltip="Danh mục">
                    <i class="fa-solid fa-list icon_nav"></i>
                    <span>Danh mục</span>
                </a>
            </li>
            <li>
                <a href="./products/product_manager.php" data-tooltip="Shop">
                    <i class="fa-solid fa-paw icon_nav"></i>
                    <span>Shop</span>
                </a>
            </li>
            <li>
                <a href="./post/post_manager.php" data-tooltip="Diễn đàn">
                    <i class="fa-solid fa-share-nodes icon_nav"></i>
                    <span>Diễn đàn</span>
                </a>
            </li>
            <li>
                <button onclick=toggleSubMenu(this) class="dropdown-btn">
                  <i class="fa-solid fa-user-group icon_nav"></i>
                  <span>Quản lý tài khoản</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>
                <ul class="sub-menu">
                  <div>
                    <li><a href="./users/quanlynhanvien/quanlynhanvien.php">Quản lý nhân viên</a></li>
                    <li><a href="./users/quanlykhachhang/quanlykhachhang.php#">Quản lý khách hàng</a></li>
                  </div>
                </ul>
            </li>
            <li>
                <a href="./orders/order_manager.php" data-tooltip="Order">
                    <i class="fa-solid fa-truck"></i>
                    <span>Quản lý đơn hàng</span>
                </a>
            </li>
             <li>
                <a href="../../app/controllers/LogoutController.php" data-tooltip="Order">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Đăng xuất</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <main class="main_admin">
        <header class="main-header">
            
            <!-- statistics controls -->
            <div class="statistics-controls">
                <!-- Time period selection -->
                <select id="timePeriod" class="stats-select">
                    <option value="day">Thống kê theo ngày</option>
                    <option value="month">Thống kê theo tháng</option>
                    <option value="quarter">Thống kê theo quý</option>
                    <option value="year">Thống kê theo năm</option>
                </select>
                
                <!-- Day selection (visible only when "day" is selected) -->
                <div id="daySelection" class="date-selection">
                    <select id="selectMonth" class="stats-select">
                        <option value="1">Tháng 1</option>
                        <option value="2">Tháng 2</option>
                        <option value="3">Tháng 3</option>
                        <option value="4">Tháng 4</option>
                        <option value="5">Tháng 5</option>
                        <option value="6">Tháng 6</option>
                        <option value="7">Tháng 7</option>
                        <option value="8">Tháng 8</option>
                        <option value="9">Tháng 9</option>
                        <option value="10">Tháng 10</option>
                        <option value="11">Tháng 11</option>
                        <option value="12">Tháng 12</option>
                    </select>
                    <select id="selectDay" class="stats-select">
                        <!-- Days will be populated via JavaScript -->
                    </select>
                </div>
                
                <!-- Month selection (visible only when "month" is selected) -->
                <div id="monthSelection" class="date-selection" style="display:none;">
                    <select id="selectMonthOnly" class="stats-select">
                        <option value="1">Tháng 1</option>
                        <option value="2">Tháng 2</option>
                        <option value="3">Tháng 3</option>
                        <option value="4">Tháng 4</option>
                        <option value="5">Tháng 5</option>
                        <option value="6">Tháng 6</option>
                        <option value="7">Tháng 7</option>
                        <option value="8">Tháng 8</option>
                        <option value="9">Tháng 9</option>
                        <option value="10">Tháng 10</option>
                        <option value="11">Tháng 11</option>
                        <option value="12">Tháng 12</option>
                    </select>
                    <select id="selectYearForMonth" class="stats-select">
                        <!-- Years will be populated via JavaScript -->
                    </select>
                </div>
                
                <!-- Quarter selection (visible only when "quarter" is selected) -->
                <div id="quarterSelection" class="date-selection" style="display:none;">
                    <select id="selectQuarter" class="stats-select">
                        <option value="1">Quý 1</option>
                        <option value="2">Quý 2</option>
                        <option value="3">Quý 3</option>
                        <option value="4">Quý 4</option>
                    </select>
                    <select id="selectYearForQuarter" class="stats-select">
                        <!-- Years will be populated via JavaScript -->
                    </select>
                </div>
                
                <!-- Year selection (visible only when "year" is selected) -->
                <div id="yearSelection" class="date-selection" style="display:none;">
                    <select id="selectYear" class="stats-select">
                        <!-- Years will be populated via JavaScript -->
                    </select>
                </div>
                
                <!-- Excel export button -->
                <button id="exportExcel" class="excel-btn">
                    <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <path d="M12 18v-6"></path>
                        <path d="M9 15h6"></path>
                    </svg>
                    Xuất Excel
                </button>
            </div>
        </header>

        <section class="dashboard">
          <!-- card 1 -->
          <div class="card sales-summary">
            <div class="sales-details">
              <header>
                <h2>Tóm tắt doanh số </h2>
                <p>bán hàng hôm nay</p>
              </header>

              <!-- sales details container -->
              <div>
                <!-- item 1 -->
                <div class="sales-detail sales-total">
                  <svg width="1em" height="1em" viewBox="0 0 23 26" fill="none">
                    <path
                      d="M21.7034 6.95138L11.7277 1.08333L1.75201 6.95138V18.6875L11.7277 24.5555L21.7034 18.6875V6.95138Z"
                      stroke="#FEB95A"
                      stroke-width="1.95"
                      stroke-linejoin="round"
                    />
                    <path
                      d="M7.03326 13.993V16.3403M11.7277 11.6458V16.3403V11.6458ZM16.4222 9.2986V16.3403V9.2986Z"
                      stroke="#FEB95A"
                      stroke-width="1.95"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                  <h3>$5k</h3>
                  <p>Total Sales</p>
                  <span>+10% from yesterday</span>
                </div>

                <!-- item 2 -->
                <div class="sales-detail sales-orders">
                  <svg width="1em" height="1em" viewBox="0 0 21 25" fill="none">
                    <path
                      d="M15.4987 2.69315H18.2645C18.5609 2.69315 18.8451 2.81204 19.0547 3.02367C19.2643 3.2353 19.382 3.52233 19.382 3.82163V22.4415C19.382 22.7408 19.2643 23.0279 19.0547 23.2395C18.8451 23.4511 18.5609 23.57 18.2645 23.57H2.61951C2.32313 23.57 2.03889 23.4511 1.82932 23.2395C1.61975 23.0279 1.50201 22.7408 1.50201 22.4415V3.82163C1.50201 3.52233 1.61975 3.2353 1.82932 3.02367C2.03889 2.81204 2.32313 2.69315 2.61951 2.69315H6.53076V4.38587H14.3533V2.69315H15.4987Z"
                      stroke="#A9DFD8"
                      stroke-width="1.5"
                      stroke-linejoin="round"
                    />
                    <path
                      d="M12.1187 9.4636L7.64867 13.9781H13.2384L8.76617 18.492M6.53117 1H14.3537V4.38544H6.53117V1Z"
                      stroke="#A9DFD8"
                      stroke-width="1.5"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                  <h3>500</h3>
                  <p>Total Orders</p>
                  <span>+8% from yesterday</span>
                </div>

                <!-- item 3 -->
                <div class="sales-detail sales-products">
                  <svg width="1em" height="1em" viewBox="0 0 23 25" fill="none">
                    <path
                      d="M17.3006 6.24477H20.9059C21.1239 6.24499 21.3342 6.32613 21.4958 6.47249C21.6574 6.61885 21.759 6.81999 21.7808 7.03696L22.3177 12.4062H20.5468L20.1067 8.00519H17.3006V10.6458C17.3006 10.8793 17.2078 11.1031 17.0427 11.2682C16.8777 11.4333 16.6538 11.526 16.4203 11.526C16.1869 11.526 15.963 11.4333 15.7979 11.2682C15.6329 11.1031 15.5401 10.8793 15.5401 10.6458V8.00519H8.49847V10.6458C8.49847 10.8793 8.40574 11.1031 8.24066 11.2682C8.07559 11.4333 7.85171 11.526 7.61826 11.526C7.38482 11.526 7.16093 11.4333 6.99586 11.2682C6.83079 11.1031 6.73805 10.8793 6.73805 10.6458V8.00519H3.93019L2.52186 22.0885H12.0193V23.8489H1.54835C1.42541 23.8488 1.30385 23.8229 1.19152 23.773C1.07919 23.723 0.97857 23.6501 0.896143 23.5589C0.813717 23.4677 0.751314 23.3602 0.712953 23.2434C0.674592 23.1266 0.661124 23.003 0.673419 22.8807L2.25779 7.03696C2.2796 6.81999 2.38116 6.61885 2.54281 6.47249C2.70445 6.32613 2.91466 6.24499 3.13272 6.24477H6.73805V5.63039C6.73805 2.57782 9.08997 0.083313 12.0193 0.083313C14.9486 0.083313 17.3006 2.57782 17.3006 5.63039V6.24653V6.24477ZM15.5401 6.24477V5.63039C15.5401 3.52845 13.9522 1.84373 12.0193 1.84373C10.0864 1.84373 8.49847 3.52845 8.49847 5.63039V6.24653H15.5401V6.24477ZM21.0802 19.3423C21.1605 19.2549 21.2576 19.1847 21.3657 19.1358C21.4739 19.0869 21.5907 19.0604 21.7094 19.0579C21.828 19.0554 21.9459 19.0768 22.0561 19.121C22.1662 19.1652 22.2662 19.2312 22.3502 19.3151C22.4342 19.3989 22.5003 19.4989 22.5447 19.6089C22.589 19.719 22.6106 19.8369 22.6083 19.9555C22.6059 20.0741 22.5796 20.1911 22.5308 20.2993C22.4821 20.4075 22.412 20.5047 22.3248 20.5851L18.8039 24.106C18.6389 24.271 18.415 24.3637 18.1816 24.3637C17.9482 24.3637 17.7244 24.271 17.5593 24.106L14.0385 20.5851C13.9544 20.5039 13.8874 20.4068 13.8412 20.2994C13.7951 20.192 13.7708 20.0765 13.7698 19.9597C13.7688 19.8428 13.7911 19.7269 13.8353 19.6187C13.8796 19.5105 13.9449 19.4122 14.0276 19.3296C14.1102 19.247 14.2085 19.1816 14.3167 19.1373C14.4249 19.0931 14.5408 19.0708 14.6576 19.0718C14.7745 19.0728 14.89 19.0971 14.9974 19.1433C15.1048 19.1894 15.2019 19.2564 15.2831 19.3405L17.3006 21.3597V15.0469C17.3006 14.8134 17.3933 14.5895 17.5584 14.4245C17.7234 14.2594 17.9473 14.1666 18.1808 14.1666C18.4142 14.1666 18.6381 14.2594 18.8032 14.4245C18.9682 14.5895 19.061 14.8134 19.061 15.0469V21.3597L21.0802 19.3405V19.3423Z"
                      fill="#F2C8ED"
                    />
                  </svg>
                  <h3>9</h3>
                  <p>Products Sold</p>
                  <span>+2% from yesterday</span>
                </div>

                <!-- item 4 -->
                <div class="sales-detail sales-customers">
                  <svg width="1em" height="1em" viewBox="0 0 23 24" fill="none">
                    <path
                      d="M10.3482 12C13.3821 12 15.8416 9.57487 15.8416 6.58332C15.8416 3.59178 13.3821 1.16666 10.3482 1.16666C7.31422 1.16666 4.85472 3.59178 4.85472 6.58332C4.85472 9.57487 7.31422 12 10.3482 12Z"
                      stroke="#20AEF3"
                      stroke-width="1.5"
                    />
                    <path
                      d="M14.7432 16.3333H21.3353M15.8419 22.8333H2.94987C2.63826 22.8334 2.33021 22.7681 2.04614 22.6418C1.76208 22.5155 1.5085 22.3311 1.30225 22.1008C1.096 21.8704 0.941783 21.5995 0.849842 21.3059C0.7579 21.0124 0.730334 20.7029 0.768973 20.398L1.19746 17.0136C1.29711 16.2274 1.68464 15.5041 2.28718 14.9798C2.88972 14.4555 3.66575 14.1663 4.46936 14.1666H4.855L15.8419 22.8333ZM18.0393 13.0833V19.5833V13.0833Z"
                      stroke="#20AEF3"
                      stroke-width="1.5"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                  <h3>12</h3>
                  <p>New Customers</p>
                  <span>+3% from yesterday</span>
                </div>
              </div>
            </div>

            <!-- bar chart -->
            <div class="chart level-chart">
                <header>
                    <h2>Level</h2>
                </header>

                <div class="chart-container">
                  <div class="bar-container">
                        <div class="bar" style="height: 50%;"></div>
                        <span class="bar-label">Jan</span>
                    </div>
                    <div class="bar-container">
                        <div class="bar" style="height: 60%;"></div>
                        <span class="bar-label">Feb</span>
                    </div>
                    <div class="bar-container">
                        <div class="bar" style="height: 45%;"></div>
                        <span class="bar-label">Mar</span>
                    </div>
                    <div class="bar-container">
                        <div class="bar" style="height: 20%;"></div>
                        <span class="bar-label">Apr</span>
                    </div>
                    <div class="bar-container">
                        <div class="bar" style="height: 45%;"></div>
                        <span class="bar-label">May</span>
                    </div>
                    <div class="bar-container">
                        <div class="bar" style="height: 56%;"></div>
                        <span class="bar-label">Jun</span>
                    </div>
                    <div class="bar-container">
                        <div class="bar" style="height: 50%;"></div>
                        <span class="bar-label">Jan</span>
                    </div>
                    <div class="bar-container">
                        <div class="bar" style="height: 60%;"></div>
                        <span class="bar-label">Feb</span>
                    </div>
                    <div class="bar-container">
                        <div class="bar" style="height: 45%;"></div>
                        <span class="bar-label">Mar</span>
                    </div>
                    <div class="bar-container">
                        <div class="bar" style="height: 20%;"></div>
                        <span class="bar-label">Apr</span>
                    </div>
                    <div class="bar-container">
                        <div class="bar" style="height: 45%;"></div>
                        <span class="bar-label">May</span>
                    </div>
                    <div class="bar-container">
                        <div class="bar" style="height: 56%;"></div>
                        <span class="bar-label">Jun</span>
                    </div>
                </div>

                <div class="labels">
                    <div class="label">
                        <div class="active"></div>
                        <p>Doanh thu</p>
                    </div>
                </div>
            </div>
          </div>

          <!-- card 2 -->
          <div class="card top-products">
            <!-- product details container -->
            <div class="top-products-details">
              <header>
                <h2>Top Products</h2>
              </header>

              <table>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Popularity</th>
                    <th>Sales</th>
                  </tr>
                </thead>

                <tbody>
                  <tr>
                    <td>01</td>
                    <td>Home Decore Range</td>
                    <td>
                      <div class="range"><div class="range-1"></div></div>
                    </td>
                    <td><div class="sales-volume sv-1">46%</div></td>
                  </tr>

                  <tr>
                    <td>02</td>
                    <td>Disney Princess Dress</td>
                    <td>
                      <div class="range"><div class="range-2"></div></div>
                    </td>
                    <td><div class="sales-volume sv-2">17%</div></td>
                  </tr>

                  <tr>
                    <td>03</td>
                    <td>Bathroom Essentials</td>
                    <td>
                      <div class="range"><div class="range-3"></div></div>
                    </td>
                    <td><div class="sales-volume sv-3">19%</div></td>
                  </tr>

                  <tr>
                    <td>04</td>
                    <td>Apple Smartwatch</td>
                    <td>
                      <div class="range"><div class="range-4"></div></div>
                    </td>
                    <td><div class="sales-volume sv-4">29%</div></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- fulfillment chart -->
            <div class="chart fulfillment-chart">
              <header><h2>Doanh thu giữa 2 tháng</h2></header>

              <div class="chart-container"><canvas></canvas></div>

              <div class="labels">
                <div class="">
                  <div class="label">
                    <div class="last-month"></div>
                    <p class="last-month-p">Last Month</p>
                  </div>
                  <span class="last-month-span">$4,087</span>
                </div>

                <div class="divider"></div>

                <div class="">
                  <div class="label">
                    <div class="this-month"></div>
                    <p class="this-month-p">This Month</p>
                  </div>
                  <span class="this-month-span">$5,506</span>
                </div>
              </div>
            </div>
          </div>

          <!-- card 3 -->
          <div class="card earnings">
            <div class="chart orders-chart">
              <header>
                <h2>Trạng thái đơn hàng</h2>
                <p> Phân phối các đơn hàng hiện tại</p>
              </header>

              <div class="chart-container">
                <div class="pie-chart">
                  <div class="pie-chart-slice pending"></div>
                  <div class="pie-chart-slice processing"></div>
                  <div class="pie-chart-slice shipped"></div>
                  <div class="pie-chart-slice delivered"></div>
                  <div class="pie-chart-slice cancelled"></div>
                  <div class="pie-chart-center"></div>
                </div>
                
                <div class="legend">
                  <div class="legend-item">
                    <span class="color pending"></span>
                    <span>Pending (25%)</span>
                  </div>
                  <div class="legend-item">
                    <span class="color processing"></span>
                    <span>Processing (20%)</span>
                  </div>
                  <div class="legend-item">
                    <span class="color shipped"></span>
                    <span>Shipped (15%)</span>
                  </div>
                  <div class="legend-item">
                    <span class="color delivered"></span>
                    <span>Delivered (30%)</span>
                  </div>
                  <div class="legend-item">
                    <span class="color cancelled"></span>
                    <span>Cancelled (10%)</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- visitors chart -->
            <div class="chart visitors-chart">
              <header>
                <h2>Visitors Insight</h2>
              </header>

              <canvas></canvas>
            </div>
          </div>
        </section>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../public/js/app.js"></script>
    <script src="../../public/js/totalOrder.js"></script>
    <script src="../../public/js/countOrder.js"></script>
    <script src="../../public/js/DashboardProductsSold.js"></script>
    <script src="../../public/js/DashboardNewCustomer.js"></script>
    <script src="../../public/js/CountProductManager.js"></script>
    <script src="../../public/js/revenueDashboard.js"></script>
    <script src="../../public/js/OrderStatusChartManager.js"></script>
    <script src="../../public/js/exportExcel.js"></script>

</body>
</html>