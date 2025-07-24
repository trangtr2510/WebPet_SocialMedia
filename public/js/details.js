document.addEventListener("DOMContentLoaded", function () {
    const swiper = new Swiper(".mySwiper", {
        loop: true,
        spaceBetween: 10,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesProgress: true,
    });

    const swiper2 = new Swiper(".mySwiper2", {
        loop: true,
        spaceBetween: 10,
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        thumbs: {
            swiper: swiper,
        },
        on: {
            slideChange: function () {
                console.log("Switched to slide:", this.realIndex);
                updateProductInfo?.(this.realIndex); // nếu bạn có hàm này
            }
        }
    });
});

// Initialize Reviews Swiper
document.addEventListener('DOMContentLoaded', function () {
    // Xử lý ảnh bị lỗi trước khi khởi tạo Swiper
    function handleImageErrors() {
        const reviewImages = document.querySelectorAll('.review-image img');
        reviewImages.forEach(img => {
            img.onerror = function () {
                // Ẩn img và thêm class no-image cho container
                this.style.display = 'none';
                this.parentElement.classList.add('no-image');
            };

            // Kiểm tra src có hợp lệ không
            if (!img.src || img.src === '' || img.src.includes('undefined')) {
                img.style.display = 'none';
                img.parentElement.classList.add('no-image');
            }
        });

        // Xử lý các review-image không có img
        const emptyImageContainers = document.querySelectorAll('.review-image:empty, .review-image:not(:has(img))');
        emptyImageContainers.forEach(container => {
            container.classList.add('no-image');
        });
    }

    // Gọi hàm xử lý ảnh
    handleImageErrors();

    var reviewsSwiper = new Swiper(".mySwiper_reviews", {
        // Basic settings
        loop: true,
        slidesPerView: 1,
        spaceBetween: 20,
        centeredSlides: false, // Tắt centeredSlides để hiển thị 3.5 slides

        // Autoplay
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },

        // Pagination
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
            dynamicBullets: true,
        },

        // Navigation buttons
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },

        // Responsive breakpoints
        breakpoints: {
            // when window width is >= 320px
            320: {
                slidesPerView: 1,
                spaceBetween: 15
            },
            // when window width is >= 480px
            480: {
                slidesPerView: 1.2,
                spaceBetween: 20
            },
            // when window width is >= 640px
            640: {
                slidesPerView: 1.8,
                spaceBetween: 20
            },
            // when window width is >= 768px
            768: {
                slidesPerView: 2.2,
                spaceBetween: 20
            },
            // when window width is >= 1024px
            1024: {
                slidesPerView: 2.8,
                spaceBetween: 25
            },
            // when window width is >= 1200px
            1200: {
                slidesPerView: 3.5, // Hiển thị 3.5 slides
                spaceBetween: 25
            },
            // when window width is >= 1400px
            1400: {
                slidesPerView: 3.5,
                spaceBetween: 30
            }
        },

        // Effects
        effect: 'slide',
        speed: 600,

        // Touch settings
        touchRatio: 1,
        touchAngle: 45,
        grabCursor: true,

        // Lazy loading
        lazy: {
            loadPrevNext: true,
        },

        // Callbacks
        on: {
            init: function () {
                console.log('Reviews swiper initialized');
                // Xử lý lại ảnh sau khi Swiper được khởi tạo
                setTimeout(handleImageErrors, 100);
            },
            slideChange: function () {
                // Xử lý ảnh cho slides mới
                setTimeout(handleImageErrors, 100);
            }
        }
    });

    // Optional: Add custom controls
    const reviewsContainer = document.querySelector('.swiper_reviews');
    if (reviewsContainer) {
        // Add custom prev/next buttons if needed
        const customPrevBtn = reviewsContainer.querySelector('.custom-prev');
        const customNextBtn = reviewsContainer.querySelector('.custom-next');

        if (customPrevBtn) {
            customPrevBtn.addEventListener('click', () => {
                reviewsSwiper.slidePrev();
            });
        }

        if (customNextBtn) {
            customNextBtn.addEventListener('click', () => {
                reviewsSwiper.slideNext();
            });
        }
    }

    // Xử lý resize window
    window.addEventListener('resize', function () {
        setTimeout(handleImageErrors, 100);
    });
});


// Tab switching functionality
const tabButtons = document.querySelectorAll('.tab-button');
const tabContents = document.querySelectorAll('.tab-content');

tabButtons.forEach(button => {
    button.addEventListener('click', () => {
        // Remove active class from all buttons and contents
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));

        // Add active class to clicked button
        button.classList.add('active');

        // Show corresponding content
        const targetTab = button.getAttribute('data-tab');
        document.getElementById(targetTab).classList.add('active');
    });
});

// Rating input functionality
const ratingStars = document.querySelectorAll('.rating-input .star');
let currentRating = 0;

ratingStars.forEach((star, index) => {
    star.addEventListener('click', () => {
        currentRating = index + 1;
        updateRatingDisplay();
    });

    star.addEventListener('mouseenter', () => {
        highlightStars(index + 1);
    });
});

document.querySelector('.rating-input').addEventListener('mouseleave', () => {
    updateRatingDisplay();
});

function highlightStars(rating) {
    ratingStars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

function updateRatingDisplay() {
    highlightStars(currentRating);
}

// File upload functionality
const fileUpload = document.querySelector('.file-upload');
const browseButton = fileUpload.querySelector('button');

browseButton.addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,video/*';
    input.multiple = true;
    input.click();

    input.addEventListener('change', (e) => {
        const files = e.target.files;
        if (files.length > 0) {
            fileUpload.innerHTML = `
                        <i class="fas fa-check-circle" style="color: #28a745;"></i>
                        <div>${files.length} file(s) selected</div>
                    `;
        }
    });
});

// Form submission
document.querySelector('.add-review-form').addEventListener('submit', (e) => {
    e.preventDefault();
    alert('Review submitted successfully!');
});

// Function to update product info based on current slide
function updateProductInfo(index) {
    // Đảm bảo index nằm trong phạm vi hợp lệ
    if (imageData && imageData.length > 0) {
        // Sử dụng modulo để đảm bảo index luôn trong phạm vi
        const safeIndex = index % imageData.length;
        const currentImage = imageData[safeIndex];

        if (currentImage) {
            // Update Gender
            const genderItem = document.getElementById('gender-item');
            const genderValue = document.getElementById('gender-value');
            if (currentImage.gender && currentImage.gender.trim() !== '') {
                genderValue.textContent = currentImage.gender;
                genderItem.style.display = 'flex';
            } else {
                genderItem.style.display = 'none';
            }

            // Update Color
            const colorItem = document.getElementById('color-item');
            const colorValue = document.getElementById('color-value');
            if (currentImage.color && currentImage.color.trim() !== '') {
                colorValue.textContent = currentImage.color;
                colorItem.style.display = 'flex';
            } else {
                colorItem.style.display = 'none';
            }
        }
    }
}


// Initialize with first image data
document.addEventListener('DOMContentLoaded', function () {
    updateProductInfo(0);
});

// Contact function
function contactUs() {
    // Có thể redirect đến trang contact hoặc mở modal
    alert('Redirecting to contact page...');
    // window.location.href = '../../views/pages/contact.php';
}

// Search functionality
document.querySelector('.search_input').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
        const searchTerm = this.value.trim();
        if (searchTerm) {
            window.location.href = `../../views/pages/search.php?q=${encodeURIComponent(searchTerm)}`;
        }
    }
});

document.querySelector('.search_icon').addEventListener('click', function () {
    const searchTerm = document.querySelector('.search_input').value.trim();
    if (searchTerm) {
        window.location.href = `../../views/pages/search.php?q=${encodeURIComponent(searchTerm)}`;
    }
});

// Chỉ chạy các function này nếu là cart interface
if (isCartInterface) {
    // Function để giảm số lượng
    function decreaseQuantity() {
        const quantityInput = document.getElementById('quantity');
        const currentQuantity = parseInt(quantityInput.value);

        if (currentQuantity > 1) {
            quantityInput.value = currentQuantity - 1;
            updateTotalPrice();
        }

        // Cập nhật trạng thái button
        updateButtonStates();
    }

    // Function để tăng số lượng
    function increaseQuantity() {
        const quantityInput = document.getElementById('quantity');
        const currentQuantity = parseInt(quantityInput.value);

        if (currentQuantity < maxStock) {
            quantityInput.value = currentQuantity + 1;
            updateTotalPrice();
        }

        // Cập nhật trạng thái button
        updateButtonStates();
    }

    // Function để cập nhật tổng giá
    function updateTotalPrice() {
        const quantityInput = document.getElementById('quantity');
        const quantity = parseInt(quantityInput.value);
        const totalPrice = originalPrice * quantity;

        document.getElementById('total-price').textContent = formatPrice(totalPrice);
    }

    // Function để format giá tiền
    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price) + ' VND';
    }

    // Function để cập nhật trạng thái button
    function updateButtonStates() {
        const quantityInput = document.getElementById('quantity');
        const quantity = parseInt(quantityInput.value);
        const decreaseBtn = document.getElementById('decrease-btn');
        const increaseBtn = document.getElementById('increase-btn');

        // Disable nút decrease nếu quantity = 1
        decreaseBtn.disabled = quantity <= 1;

        // Disable nút increase nếu quantity = maxStock
        increaseBtn.disabled = quantity >= maxStock;
    }

    // Function để xử lý thêm vào giỏ hàng
    function addToCart() {
        const quantity = parseInt(document.getElementById('quantity').value);
        const selectedSize = getSelectedSize();

        // Kiểm tra nếu sản phẩm có size và chưa chọn size
        if (productSize && productSize.trim() !== '' && !selectedSize) {
            alert('Please select a size before adding to cart!');
            return;
        }

        // Kiểm tra số lượng tồn kho
        if (quantity > maxStock) {
            alert('Not enough stock available!');
            return;
        }

        // Tạo FormData để gửi dữ liệu POST
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        if (selectedSize) {
            formData.append('size', selectedSize);
        }

        // Gửi AJAX request để thêm vào giỏ hàng
        fetch('../../app/controllers/CartController.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Hiển thị thông báo thành công
                    showNotification(data.message || 'Product added to cart successfully!', 'success');

                    // Cập nhật số lượng giỏ hàng trên header (nếu có)
                    updateCartCount();

                    // Reset form về trạng thái ban đầu
                    document.getElementById('quantity').value = 1;
                    updateTotalPrice();
                    updateButtonStates();

                    // Reset size selection
                    resetSizeSelection();
                } else {
                    showNotification(data.message || 'Error adding product to cart!', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error adding product to cart!', 'error');
            });
    }

    // Function để cập nhật số lượng giỏ hàng
    function updateCartCount() {
        // Gửi GET request để lấy số lượng sản phẩm
        fetch('../../app/controllers/CartController.php?action=count', {
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

    // Function để xóa sản phẩm khỏi giỏ hàng (nếu cần)
    function removeFromCart(productId) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('product_id', productId);

        fetch('../../app/controllers/CartController.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message || 'Product removed from cart!', 'success');
                    updateCartCount();
                } else {
                    showNotification(data.message || 'Error removing product!', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error removing product!', 'error');
            });
    }

    // Function để lấy size đã chọn
    function getSelectedSize() {
        const selectedSizeElement = document.querySelector('.size-option.selected');
        return selectedSizeElement ? selectedSizeElement.dataset.size : null;
    }

    // Function để reset size selection
    function resetSizeSelection() {
        document.querySelectorAll('.size-option').forEach(option => {
            option.classList.remove('selected');
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
    // function updateCartCount() {
    //     fetch('../../app/controllers/CartController.php', {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/json',
    //             'X-Requested-With': 'XMLHttpRequest'
    //         },
    //         body: JSON.stringify({
    //             action: 'get_cart_count'
    //         })
    //     })
    //         .then(response => response.json())
    //         .then(data => {
    //             if (data.success) {
    //                 // Cập nhật số lượng trên icon giỏ hàng (nếu có)
    //                 const cartCountElement = document.querySelector('.cart-count');
    //                 if (cartCountElement) {
    //                     cartCountElement.textContent = data.count;
    //                 }
    //             }
    //         })
    //         .catch(error => console.error('Error updating cart count:', error));
    // }

    // Function để xử lý chọn size
    function selectSize(sizeElement) {
        // Bỏ chọn tất cả size khác
        document.querySelectorAll('.size-option').forEach(option => {
            option.classList.remove('selected');
        });

        // Chọn size hiện tại
        sizeElement.classList.add('selected');
    }

    // Function để tạo size selector
    function createSizeSelector() {
        if (!productSize || productSize.trim() === '') {
            return;
        }

        // Parse sizes từ chuỗi (ví dụ: "M,L,XL" hoặc "M, L, XL")
        const sizes = productSize.split(',').map(size => size.trim()).filter(size => size);

        if (sizes.length === 0) {
            return;
        }

        // Tạo HTML cho size selector
        const sizeSelector = document.createElement('div');
        sizeSelector.className = 'size-selector';
        sizeSelector.innerHTML = `
            <label class="size-label">Size:</label>
            <div class="size-options">
                ${sizes.map(size => `
                    <div class="size-option" data-size="${size}" onclick="selectSize(this)">
                        ${size}
                    </div>
                `).join('')}
            </div>
        `;

        // Thêm CSS cho size selector
        const style = document.createElement('style');
        style.textContent = `
            .size-selector {
                margin: 20px 0;
            }
            
            .size-label {
                display: block;
                font-weight: 600;
                color: #333;
                margin-bottom: 10px;
            }
            
            .size-options {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            
            .size-option {
                padding: 10px 15px;
                border: 2px solid #ddd;
                border-radius: 5px;
                cursor: pointer;
                transition: all 0.3s ease;
                background: white;
                min-width: 50px;
                text-align: center;
                font-weight: 500;
            }
            
            .size-option:hover {
                border-color: #007bff;
                background: #f8f9ff;
            }
            
            .size-option.selected {
                border-color: #007bff;
                background: #007bff;
                color: white;
            }
            
            .size-option.selected:hover {
                background: #0056b3;
            }
        `;

        document.head.appendChild(style);

        // Chèn size selector vào vị trí thích hợp
        const quantitySelector = document.querySelector('.quantity-selector');
        if (quantitySelector) {
            quantitySelector.parentNode.insertBefore(sizeSelector, quantitySelector);
        }
    }

    // Khởi tạo khi DOM đã load
    document.addEventListener('DOMContentLoaded', function () {
        // Tạo size selector nếu sản phẩm có size
        createSizeSelector();

        // Cập nhật trạng thái button ban đầu
        updateButtonStates();

        // Thêm event listener cho input quantity
        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantityInput.addEventListener('input', function () {
                const value = parseInt(this.value);
                if (value < 1) {
                    this.value = 1;
                } else if (value > maxStock) {
                    this.value = maxStock;
                }
                updateTotalPrice();
                updateButtonStates();
            });
        }
    });
} else {
    // Giao diện cho parent category = 1 (pets)
    function contactUs() {
        // Hiển thị thông tin liên hệ hoặc chuyển đến trang liên hệ
        const contactInfo = `
            <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #333; margin-bottom: 15px;">Contact Information</h3>
                <p><strong>Phone:</strong> +84 123 456 789</p>
                <p><strong>Email:</strong> info@monito.com</p>
                <p><strong>Address:</strong> 123 Pet Street, Hanoi, Vietnam</p>
                <p style="margin-top: 15px; color: #666;">
                    We're here to help you find the perfect pet! Contact us for more information about this adorable companion.
                </p>
            </div>
        `;

        // Tạo modal để hiển thị thông tin liên hệ
        showModal('Contact Us', contactInfo);
    }

    // Function để hiển thị modal
    function showModal(title, content) {
        // Tạo modal element
        const modal = document.createElement('div');
        modal.className = 'contact-modal';
        modal.innerHTML = `
            <div class="modal-overlay" onclick="closeModal()">
                <div class="modal-content" onclick="event.stopPropagation()">
                    <div class="modal-header">
                        <h2>${title}</h2>
                        <button class="modal-close" onclick="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        ${content}
                    </div>
                </div>
            </div>
        `;

        // Thêm CSS cho modal
        const style = document.createElement('style');
        style.textContent = `
            .contact-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
            }
            
            .modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .modal-content {
                background: white;
                border-radius: 8px;
                max-width: 500px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
                animation: modalSlideIn 0.3s ease;
            }
            
            @keyframes modalSlideIn {
                from { transform: translateY(-50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            
            .modal-header {
                padding: 20px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .modal-header h2 {
                margin: 0;
                color: #333;
            }
            
            .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .modal-close:hover {
                color: #333;
            }
            
            .modal-body {
                padding: 20px;
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(modal);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    // Function để đóng modal
    function closeModal() {
        const modal = document.querySelector('.contact-modal');
        if (modal) {
            modal.remove();
            document.body.style.overflow = 'auto';
        }
    }
}

// Common functions for both interfaces

// Function để xem chi tiết sản phẩm
function viewProduct(productId) {
    window.location.href = `product_detail.php?id=${productId}`;
}

// Swiper initialization
document.addEventListener('DOMContentLoaded', function () {
    // Initialize thumbnail swiper
    var swiper = new Swiper(".mySwiper", {
        spaceBetween: 10,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesProgress: true,
    });

    // Initialize main swiper
    var swiper2 = new Swiper(".mySwiper2", {
        spaceBetween: 10,
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
        thumbs: {
            swiper: swiper,
        },
    });

    // Update gender and color info when slide changes
    swiper2.on('slideChange', function () {
        const activeSlide = document.querySelector('.mySwiper2 .swiper-slide-active');
        if (activeSlide) {
            const gender = activeSlide.getAttribute('data-gender');
            const color = activeSlide.getAttribute('data-color');

            updateProductInfo(gender, color);
        }
    });

    // Initialize with first slide data
    const firstSlide = document.querySelector('.mySwiper2 .swiper-slide');
    if (firstSlide) {
        const gender = firstSlide.getAttribute('data-gender');
        const color = firstSlide.getAttribute('data-color');
        updateProductInfo(gender, color);
    }
});

// Function để cập nhật thông tin sản phẩm
function updateProductInfo(gender, color) {
    const genderItem = document.getElementById('gender-item');
    const colorItem = document.getElementById('color-item');
    const genderValue = document.getElementById('gender-value');
    const colorValue = document.getElementById('color-value');

    if (gender && gender.trim() !== '') {
        genderItem.style.display = 'flex';
        genderValue.textContent = gender;
    } else {
        genderItem.style.display = 'none';
    }

    if (color && color.trim() !== '') {
        colorItem.style.display = 'flex';
        colorValue.textContent = color;
    } else {
        colorItem.style.display = 'none';
    }
}

// Tab functionality
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetTab = this.getAttribute('data-tab');

            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
});

// Rating functionality for review form
document.addEventListener('DOMContentLoaded', function () {
    const ratingStars = document.querySelectorAll('.rating-input .star');
    let currentRating = 0;

    ratingStars.forEach(star => {
        star.addEventListener('click', function () {
            currentRating = parseInt(this.getAttribute('data-rating'));
            updateRatingDisplay();
        });

        star.addEventListener('mouseenter', function () {
            const hoverRating = parseInt(this.getAttribute('data-rating'));
            highlightStars(hoverRating);
        });
    });

    // Reset highlight when mouse leaves rating area
    document.querySelector('.rating-input').addEventListener('mouseleave', function () {
        highlightStars(currentRating);
    });

    function highlightStars(rating) {
        ratingStars.forEach((star, index) => {
            if (index < rating) {
                star.style.color = '#ffc107';
            } else {
                star.style.color = '#e0e0e0';
            }
        });
    }

    function updateRatingDisplay() {
        highlightStars(currentRating);
    }
});

// Handle review form submission
document.addEventListener('DOMContentLoaded', function () {
    const reviewForm = document.querySelector('.add-review-form');
    if (reviewForm) {
        const submitBtn = reviewForm.querySelector('.submit-btn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function (e) {
                e.preventDefault();

                // Get form data
                const name = document.getElementById('name').value;
                const email = document.getElementById('email').value;
                const title = document.getElementById('review-title').value;
                const text = document.getElementById('review-text').value;

                // Validate form
                if (!name || !email || !title || !text || currentRating === 0) {
                    alert('Please fill in all required fields and select a rating.');
                    return;
                }

                // Here you would normally send the data to your server
                // For now, just show a success message
                alert('Thank you for your review! It will be published after moderation.');

                // Reset form
                reviewForm.reset();
                currentRating = 0;
                highlightStars(0);
            });
        }
    }
});