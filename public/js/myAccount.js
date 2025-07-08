// Address modal functions
function openAddressModal() {
    document.getElementById('addressModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAddressModal() {
    document.getElementById('addressModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking on overlay
document.getElementById('addressModal').addEventListener('click', function (e) {
    if (e.target === this) {
        closeAddressModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.getElementById('addressModal').classList.contains('active')) {
        closeAddressModal();
    }
});

// Handle form submission
document.getElementById('addressForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const customerName = document.getElementById('customerName').value;
    const phoneNumber = document.getElementById('phoneNumber').value;
    const detailAddress = document.getElementById('detailAddress').value;
    const province = document.getElementById('province').selectedOptions[0].text;
    const district = document.getElementById('district').selectedOptions[0].text;
    const ward = document.getElementById('ward').selectedOptions[0].text;

    // Validate required fields
    if (!customerName || !phoneNumber || !detailAddress ||
        !document.getElementById('province').value ||
        !document.getElementById('district').value ||
        !document.getElementById('ward').value) {
        showErrorDialog('Vui lòng điền đầy đủ thông tin bắt buộc!');
        return;
    }

    // Update the address display
    const customerNameElement = document.querySelector('.customer-name');
    const addressElement = document.querySelector('.address');

    if (customerNameElement) customerNameElement.textContent = `${customerName} | ${phoneNumber}`;
    if (addressElement) addressElement.textContent = `${detailAddress}, ${ward}, ${district}, ${province}`;

    // Close modal
    closeAddressModal();

    // Show success message
    showSuccessDialog('Địa chỉ đã được cập nhật thành công!');
});

// Province/District/Ward cascading dropdown simulation
document.getElementById('province').addEventListener('change', function () {
    const district = document.getElementById('district');
    const ward = document.getElementById('ward');

    // Reset district and ward
    district.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
    ward.innerHTML = '<option value="">Chọn Phường/Xã</option>';

    if (this.value === 'hanoi') {
        district.innerHTML = `
            <option value="">Chọn Quận/Huyện</option>
            <option value="bactuliem">Bắc Từ Liêm</option>
            <option value="namtuliem">Nam Từ Liêm</option>
            <option value="caugiay">Cầu Giấy</option>
            <option value="dongda">Đống Đa</option>
        `;
    } else if (this.value === 'hcm') {
        district.innerHTML = `
            <option value="">Chọn Quận/Huyện</option>
            <option value="quan1">Quận 1</option>
            <option value="quan2">Quận 2</option>
            <option value="quan3">Quận 3</option>
            <option value="quan4">Quận 4</option>
        `;
    }
});

document.getElementById('district').addEventListener('change', function () {
    const ward = document.getElementById('ward');

    // Reset ward
    ward.innerHTML = '<option value="">Chọn Phường/Xã</option>';

    if (this.value === 'bactuliem') {
        ward.innerHTML = `
            <option value="">Chọn Phường/Xã</option>
            <option value="conhue2">Cổ Nhuế 2</option>
            <option value="conhue1">Cổ Nhuế 1</option>
            <option value="phucdinh">Phúc Dinh</option>
            <option value="xuanhoa">Xuân Hòa</option>
        `;
    } else if (this.value === 'caugiay') {
        ward.innerHTML = `
            <option value="">Chọn Phường/Xã</option>
            <option value="dichvong">Dịch Vọng</option>
            <option value="dichvonghau">Dịch Vọng Hậu</option>
            <option value="mailam">Mai Lâm</option>
            <option value="quantan">Quan Tan</option>
        `;
    }
});

// Avatar update function
function updateAvatar(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showErrorDialog('Chỉ chấp nhận file JPG, PNG, GIF!');
        return;
    }

    // Validate file size (max 5MB)
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        showErrorDialog('Kích thước file không được vượt quá 5MB!');
        return;
    }
    
    // Hiển thị preview ảnh ngay lập tức
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('avatarPreview').src = e.target.result;
    };
    reader.readAsDataURL(file);
    
    // Upload ảnh lên server
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('action', 'update_avatar');
    
    // Show loading state
    const avatarBtn = document.querySelector('.edit-avatar-btn');
    const originalContent = avatarBtn.innerHTML;
    avatarBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    avatarBtn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Network response was not ok');
        }
        return res.json();
    })
    .then(data => {
        // Restore button state
        avatarBtn.innerHTML = originalContent;
        avatarBtn.disabled = false;
        
        if (data.success) {
            // Cập nhật src của ảnh với tên file mới từ server
            document.getElementById('avatarPreview').src = '../../public/uploads/avatar/' + data.avatar;
            showSuccessDialog(data.message || 'Cập nhật avatar thành công!');
        } else {
            // Nếu lỗi, khôi phục ảnh cũ
            location.reload();
            showErrorDialog(data.message || 'Có lỗi khi cập nhật ảnh.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Restore button state
        avatarBtn.innerHTML = originalContent;
        avatarBtn.disabled = false;
        
        location.reload();
        showErrorDialog('Lỗi kết nối! Vui lòng thử lại.');
    });
}

// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const menuItems = document.querySelectorAll('.menu-item');
    const tabContents = document.querySelectorAll('.tab-content');
    
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            if (this.classList.contains('logout-btn')) {
                showLogoutConfirmDialog();
                return;
            }
            
            const tabId = this.dataset.tab;
            
            // Remove active class from all menu items and tab contents
            menuItems.forEach(mi => mi.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));
            
            // Add active class to clicked item and corresponding tab
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });

    // Profile form submission handler
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_profile');
            
            // Show loading state
            const submitBtn = this.querySelector('.update-btn');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang cập nhật...';
            submitBtn.disabled = true;
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                return res.json();
            })
            .then(data => {
                // Restore button state
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                
                console.log('Response from server:', data);
                
                if (data.success) {
                    showSuccessDialog(data.message || 'Cập nhật thông tin thành công!');
                } else {
                    showErrorDialog(data.message || 'Có lỗi xảy ra khi cập nhật thông tin!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Restore button state
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
                
                showErrorDialog('Lỗi kết nối! Vui lòng thử lại.');
            });
        });
    }
});

// Custom logout confirmation dialog
function showLogoutConfirmDialog() {
    // Remove existing dialog if any
    const existingDialog = document.querySelector('.logout-dialog-overlay');
    if (existingDialog) {
        existingDialog.remove();
    }

    const dialogHTML = `
        <div class="logout-dialog-overlay" id="logoutDialog" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
        ">
            <div class="logout-dialog" style="
                background: white;
                border-radius: 12px;
                padding: 24px;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
                transform: scale(0.8);
                transition: transform 0.3s ease;
            ">
                <div class="logout-dialog-header" style="
                    display: flex;
                    align-items: center;
                    margin-bottom: 16px;
                ">
                    <div class="logout-dialog-icon" style="
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-right: 12px;
                        background: #F59E0B;
                        color: white;
                    ">
                        <i class="fa-solid fa-sign-out-alt"></i>
                    </div>
                    <h3 style="
                        margin: 0;
                        font-size: 18px;
                        font-weight: 600;
                        color: #1F2937;
                    ">Xác nhận đăng xuất</h3>
                </div>
                <div class="logout-dialog-content">
                    <p style="
                        margin: 0 0 20px 0;
                        color: #6B7280;
                        line-height: 1.5;
                    ">Bạn có chắc chắn muốn đăng xuất khỏi tài khoản?</p>
                    <div style="
                        display: flex;
                        justify-content: flex-end;
                        gap: 12px;
                    ">
                        <button onclick="closeLogoutDialog()" style="
                            background: #E5E7EB;
                            color: #374151;
                            border: none;
                            padding: 8px 16px;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 500;
                            transition: background 0.2s ease;
                        " onmouseover="this.style.background='#D1D5DB'" onmouseout="this.style.background='#E5E7EB'">
                            Hủy
                        </button>
                        <button onclick="confirmLogout()" style="
                            background: #EF4444;
                            color: white;
                            border: none;
                            padding: 8px 16px;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 500;
                            transition: background 0.2s ease;
                        " onmouseover="this.style.background='#DC2626'" onmouseout="this.style.background='#EF4444'">
                            Đăng xuất
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', dialogHTML);

    const dialog = document.getElementById('logoutDialog');
    const dialogContent = dialog.querySelector('.logout-dialog');
    
    requestAnimationFrame(() => {
        dialog.style.opacity = '1';
        dialogContent.style.transform = 'scale(1)';
    });
}

function closeLogoutDialog() {
    const dialog = document.getElementById('logoutDialog');
    if (dialog) {
        const dialogContent = dialog.querySelector('.logout-dialog');
        dialog.style.opacity = '0';
        dialogContent.style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            dialog.remove();
        }, 300);
    }
}

function confirmLogout() {
    // Redirect to logout controller
    window.location.href = '../../app/controllers/LogoutController.php';
}

document.addEventListener('DOMContentLoaded', function() {
    // Function to switch tabs
    function switchTab(tabName) {
        // Remove active class from all menu items and tab contents
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Add active class to selected menu item and tab content
        const menuItem = document.querySelector(`[data-tab="${tabName}"]`);
        const tabContent = document.getElementById(tabName);
        
        if (menuItem && tabContent) {
            menuItem.classList.add('active');
            tabContent.classList.add('active');
        }
    }
    
    // Check URL parameters on page load
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    
    if (tabParam) {
        switchTab(tabParam);
    }
    
    // Add click event listeners to menu items
    document.querySelectorAll('.menu-item[data-tab]').forEach(menuItem => {
        menuItem.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            switchTab(tabName);
            
            // Update URL without page reload
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('tab', tabName);
            window.history.pushState({}, '', newUrl);
        });
    });
});

// Redirect to my orders function
function redirectToMyOrders() {
    window.location.href = '../../views/pages/myAccount.php?tab=my-orders';
}