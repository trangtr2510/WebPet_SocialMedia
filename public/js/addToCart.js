function addToCart(productId, maxStock) {
    const quantityInput = document.getElementById(`quantity_${productId}`);
    const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
    const selectedSize = typeof getSelectedSize === 'function' ? getSelectedSize() : null;

    if (quantity > maxStock) {
        alert('Not enough stock available!');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    if (selectedSize) {
        formData.append('size', selectedSize);
    }

    const tryUrls = [
        './app/controllers/CartController.php',
        '../../app/controllers/CartController.php'
    ];

    // Try URLs in order until one succeeds
    function tryFetch(urlIndex = 0) {
        if (urlIndex >= tryUrls.length) {
            showNotification('Error adding product to cart! (URL fallback failed)', 'error');
            return;
        }

        fetch(tryUrls[urlIndex], {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('HTTP error');
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                showNotification(data.message || 'Product added to cart successfully!', 'success');
                updateCartCount();
                if (quantityInput) quantityInput.value = 1;
            } else {
                showNotification(data.message || 'Error adding product to cart!', 'error');
            }
        })
        .catch(() => {
            tryFetch(urlIndex + 1); // Try next URL
        });
    }

    tryFetch(); // Start with first URL
}

// Function để lấy size đã chọn
function getSelectedSize() {
    const selectedSizeElement = document.querySelector('.size-option.selected');
    return selectedSizeElement ? selectedSizeElement.dataset.size : null;
}

// Function để cập nhật số lượng sản phẩm trong giỏ hàng (nếu cần)
function updateCartQuantity(productId, quantity) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    fetch('../../app/controllers/CartController.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification(data.message || 'Cart updated successfully!', 'success');
                updateCartCount();
            } else {
                showNotification(data.message || 'Error updating cart!', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error updating cart!', 'error');
        });
}
// Function để hiển thị thông báo
function showNotification(message, type = 'success') {
    // Tạo element thông báo
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

    // Thêm CSS cho notification
    notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            ${type === 'success' ? 'background-color: #28a745;' : 'background-color: #dc3545;'}
        `;

    document.body.appendChild(notification);

    // Hiển thị notification
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Ẩn notification sau 3 giây
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Function để cập nhật số lượng giỏ hàng
function updateCartCount() {
    // Gửi GET request để lấy số lượng sản phẩm
    fetch('./app/controllers/CartController.php?action=count', {
        method: 'GET'
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Cập nhật số lượng trên icon giỏ hàng (nếu có)
                const cartCountElement = document.querySelector('.cart-count');
                if (cartCountElement) {
                    cartCountElement.textContent = data.count;
                }
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}
