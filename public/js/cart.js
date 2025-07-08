function updateQuantity(button, change) {
    const quantityInput = button.parentElement.querySelector('.quantity-input');
    const currentValue = parseInt(quantityInput.value);
    const newValue = Math.max(1, Math.min(10, currentValue + change));
    quantityInput.value = newValue;

    // Update total price for this item
    const cartItem = button.closest('.cart-item');
    const priceElement = cartItem.querySelector('.current-price');
    const totalElement = cartItem.querySelector('.total-price');
    const unitPrice = parseInt(priceElement.textContent.replace(/[^\d]/g, ''));
    const totalPrice = unitPrice * newValue;
    totalElement.textContent = totalPrice.toLocaleString('vi-VN') + '₫';

    updateCartSummary();
}

function removeItem(button) {
    const cartItem = button.closest('.cart-item');
    cartItem.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => {
        cartItem.remove();
        updateCartSummary();
        updateCartHeader();
    }, 300);
}

function updateCartSummary() {
    const checkedItems = document.querySelectorAll('.item-checkbox:checked');
    let totalAmount = 0;
    let itemCount = 0;

    checkedItems.forEach(checkbox => {
        const cartItem = checkbox.closest('.cart-item');
        const quantity = parseInt(cartItem.querySelector('.quantity-input').value);
        const price = parseInt(cartItem.querySelector('.current-price').textContent.replace(/[^\d]/g, ''));
        totalAmount += price * quantity;
        itemCount += quantity;
    });

    // Update summary
    const summaryValue = document.querySelector('.summary-value');
    const totalValue = document.querySelector('.total-value');
    const checkoutBtn = document.querySelector('.checkout-btn');

    summaryValue.textContent = totalAmount.toLocaleString('vi-VN') + '₫';

    if (totalAmount > 0) {
        totalValue.textContent = totalAmount.toLocaleString('vi-VN') + '₫';
        checkoutBtn.textContent = `Mua Hàng (${itemCount})`;
        checkoutBtn.disabled = false;
    } else {
        totalValue.textContent = 'Vui lòng chọn sản phẩm';
        checkoutBtn.textContent = 'Mua Hàng (0)';
        checkoutBtn.disabled = true;
    }
}

function updateCartHeader() {
    const allItems = document.querySelectorAll('.cart-item');
    const headerCheckbox = document.querySelector('.select-all-checkbox');
    const headerLabel = headerCheckbox.nextElementSibling;

    headerLabel.textContent = `Tất cả (${allItems.length} sản phẩm)`;
}

// Select all functionality
document.querySelector('.select-all-checkbox').addEventListener('change', function () {
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    itemCheckboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateCartSummary();
});

// Individual item checkbox functionality
document.querySelectorAll('.item-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function () {
        const allCheckboxes = document.querySelectorAll('.item-checkbox');
        const checkedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
        const selectAllCheckbox = document.querySelector('.select-all-checkbox');

        selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
        updateCartSummary();
    });
});

// Initialize
updateCartSummary();

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
        alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
        return;
    }

    // Update the address display
    const customerNameElement = document.querySelector('.customer-name');
    const addressElement = document.querySelector('.address');

    customerNameElement.textContent = `${customerName} | ${phoneNumber}`;
    addressElement.textContent = `${detailAddress}, ${ward}, ${district}, ${province}`;

    // Close modal
    closeAddressModal();

    // Show success message
    alert('Địa chỉ đã được cập nhật thành công!');
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