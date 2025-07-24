/**
 * Filter.js - Xử lý lọc và sắp xếp sản phẩm
 * Tương tác với FilterController.php
 */

class ProductFilter {
    constructor() {
        this.currentFilters = {
            gender: [],
            color: [],
            min_price: null,
            max_price: null,
            breed: [],
            category_id: null,
            sort: 'popular',
            page: 1,
            limit: 12
        };
        
        this.isLoading = false;
        this.debounceTimer = null;
        this.hasUserInteraction = false; // Thêm flag để track user interaction
        
        this.init();
    }

    /**
     * Khởi tạo event listeners
     */
    init() {
        this.bindEvents();
        this.loadInitialData();
    }

    /**
     * Bind các sự kiện
     */
    bindEvents() {
        // Gender checkboxes
        document.querySelectorAll('input[type="checkbox"][id="male"], input[type="checkbox"][id="female"]').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.hasUserInteraction = true; // Đánh dấu user đã tương tác
                this.handleGenderChange(e);
            });
        });

        // Color filters
        document.querySelectorAll('.filter_option').forEach(option => {
            const colorDot = option.querySelector('.color_dot');
            if (colorDot) {
                option.addEventListener('click', (e) => {
                    this.hasUserInteraction = true; // Đánh dấu user đã tương tác
                    this.handleColorChange(e, option);
                });
            }
        });

        // Price filters
        const priceInputs = document.querySelectorAll('.price_filter_input');
        priceInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.hasUserInteraction = true; // Đánh dấu user đã tương tác
                this.handlePriceChange(e);
            });
        });

        // Breed/Size checkboxes
        document.querySelectorAll('input[type="checkbox"][id="small"], input[type="checkbox"][id="medium"], input[type="checkbox"][id="large"]').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.hasUserInteraction = true; // Đánh dấu user đã tương tác
                this.handleBreedChange(e);
            });
        });

        // Sort dropdown
        const sortDropdown = document.querySelector('.sort_dropdown');
        if (sortDropdown) {
            sortDropdown.addEventListener('change', (e) => {
                this.hasUserInteraction = true; // Đánh dấu user đã tương tác
                this.handleSortChange(e);
            });
        }

        // Reset filters button (nếu có)
        const resetBtn = document.querySelector('.reset_filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                this.hasUserInteraction = true; // Reset cũng là tương tác
                this.resetFilters();
            });
        }
    }

    /**
     * Xử lý thay đổi gender filter
     */
    handleGenderChange(e) {
        const value = e.target.id === 'male' ? 'Male' : 'female';
        
        if (e.target.checked) {
            if (!this.currentFilters.gender.includes(value)) {
                this.currentFilters.gender.push(value);
            }
        } else {
            this.currentFilters.gender = this.currentFilters.gender.filter(g => g !== value);
        }
        
        this.applyFilters();
    }

    /**
     * Xử lý thay đổi color filter
     */
    handleColorChange(e, option) {
        e.preventDefault();
        
        const label = option.querySelector('label');
        if (!label) return;
        
        const color = label.textContent.trim();
        const isSelected = option.classList.contains('selected');
        
        if (isSelected) {
            option.classList.remove('selected');
            this.currentFilters.color = this.currentFilters.color.filter(c => c !== color);
        } else {
            option.classList.add('selected');
            if (!this.currentFilters.color.includes(color)) {
                this.currentFilters.color.push(color);
            }
        }
        
        this.applyFilters();
    }

    /**
     * Xử lý thay đổi price filter
     */
    handlePriceChange(e) {
        clearTimeout(this.debounceTimer);
        
        this.debounceTimer = setTimeout(() => {
            const minInput = document.querySelector('.price_filter_input[placeholder="Min"]');
            const maxInput = document.querySelector('.price_filter_input[placeholder="Max"]');
            
            const minValue = minInput ? parseFloat(minInput.value) || null : null;
            const maxValue = maxInput ? parseFloat(maxInput.value) || null : null;
            
            this.currentFilters.min_price = minValue;
            this.currentFilters.max_price = maxValue;
            
            this.applyFilters();
        }, 500); // Debounce 500ms
    }

    /**
     * Xử lý thay đổi breed filter
     */
    handleBreedChange(e) {
        const value = e.target.id.charAt(0).toUpperCase() + e.target.id.slice(1); // small -> Small
        
        if (e.target.checked) {
            if (!this.currentFilters.breed.includes(value)) {
                this.currentFilters.breed.push(value);
            }
        } else {
            this.currentFilters.breed = this.currentFilters.breed.filter(b => b !== value);
        }
        
        this.applyFilters();
    }

    /**
     * Xử lý thay đổi sort
     */
    handleSortChange(e) {
        const sortValue = e.target.value;
        let sortKey = 'popular';
        
        switch (sortValue) {
            case 'Price: Low to High':
                sortKey = 'price_low_high';
                break;
            case 'Price: High to Low':
                sortKey = 'price_high_low';
                break;
            case 'Newest':
                sortKey = 'newest';
                break;
            case 'Sort by: Popular':
            default:
                sortKey = 'popular';
                break;
        }
        
        this.currentFilters.sort = sortKey;
        this.currentFilters.page = 1; // Reset về trang 1
        this.applyFilters();
    }

    /**
     * Áp dụng filters và gọi API
     */
    async applyFilters() {
        // Chỉ gọi API khi user đã có tương tác hoặc load lần đầu
        if (this.isLoading || (!this.hasUserInteraction && this.currentFilters.page === 1)) {
            return;
        }
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            const response = await fetch('../../app/controllers/FilterController.php?action=filter', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(this.currentFilters)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.renderProducts(result.data);
                this.updateProductCount(result.total);
                this.updatePagination(result.total);
            } else {
                this.showError(result.message || 'Có lỗi xảy ra khi lọc sản phẩm');
            }
            
        } catch (error) {
            console.error('Filter error:', error);
            this.showError('Không thể kết nối đến server. Vui lòng thử lại.');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    /**
     * Render danh sách sản phẩm
     */
    renderProducts(products) {
        const container = document.querySelector('.products_grid');
        if (!container) return;
        
        if (products.length === 0) {
            container.innerHTML = `
                <div class="no_products">
                    <p>Không tìm thấy sản phẩm nào phù hợp với tiêu chí lọc.</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        products.forEach(product => {
            html += this.createProductCard(product);
        });
        
        container.innerHTML = html;
        
        // Bind events cho các sản phẩm mới
        this.bindProductEvents();
    }

    /**
     * Tạo HTML cho một sản phẩm
     */
    createProductCard(product) {
        // FIX: Sử dụng image_url thay vì image_path
        const imagePath = product.image_url || 'default-product.jpg';
        const price = parseFloat(product.price).toLocaleString('vi-VN');
        
        return `
            <div class="product_card">
                <img class="img_product" src="../../public/uploads/product/${imagePath}" alt="${product.product_name}">
                <i class="fa-solid fa-eye eye-icon" onclick="viewProduct(${product.product_id})"></i>
                <div class="product_details">
                    <h3 class="product_code">
                        ${product.product_code || product.product_name}
                    </h3>
                    <div class="product_meta">
                        <span class="product_gender">Genre: ${product.gender || 'N/A'}</span>
                        <span class="product_age">Age: ${product.age || 'N/A'} months</span>
                    </div>
                    <p class="product_price">${price} VND</p>
                    
                    ${product.parent_category_id == 8 ? `
                        ${product.stock_quantity > 0 ? `
                            <div class="card_body_gift" onclick="addToCart(${product.product_id}, ${product.stock_quantity})">
                                <img src="../../public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                <p class="card_body_gift_p">Add to cart</p>
                            </div>
                        ` : `
                            <div class="card_body_gift disabled">
                                <img src="../../public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                <p class="card_body_gift_p">Out of Stock</p>
                            </div>
                        `}
                    ` : ''}
                </div>
            </div>
        `;
    }

    /**
     * Cập nhật số lượng sản phẩm
     */
    updateProductCount(total) {
        const countElement = document.querySelector('.products_count');
        if (countElement) {
            countElement.textContent = `${total} products`;
        }
    }

    /**
     * Cập nhật pagination
     */
    updatePagination(total) {
        const totalPages = Math.ceil(total / this.currentFilters.limit);
        const currentPage = this.currentFilters.page;
        
        // Tạo pagination nếu chưa có
        let paginationContainer = document.querySelector('.pagination');
        if (!paginationContainer) {
            paginationContainer = document.createElement('div');
            paginationContainer.className = 'pagination';
            document.querySelector('.products_container').appendChild(paginationContainer);
        }
        
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Previous button
        if (currentPage > 1) {
            html += `<button class="page_btn" data-page="${currentPage - 1}">‹ Trước</button>`;
        }
        
        // Page numbers
        for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
            html += `<button class="page_btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }
        
        // Next button
        if (currentPage < totalPages) {
            html += `<button class="page_btn" data-page="${currentPage + 1}">Sau ›</button>`;
        }
        
        paginationContainer.innerHTML = html;
        
        // Bind pagination events
        paginationContainer.querySelectorAll('.page_btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const page = parseInt(e.target.dataset.page);
                this.hasUserInteraction = true; // Pagination cũng là user interaction
                this.goToPage(page);
            });
        });
    }

    /**
     * Chuyển trang
     */
    goToPage(page) {
        this.currentFilters.page = page;
        this.applyFilters();
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /**
     * Reset tất cả filters
     */
    resetFilters() {
        // Reset form controls
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        document.querySelectorAll('.price_filter_input').forEach(input => input.value = '');
        document.querySelectorAll('.filter_option.selected').forEach(option => option.classList.remove('selected'));
        
        const sortDropdown = document.querySelector('.sort_dropdown');
        if (sortDropdown) {
            sortDropdown.value = 'Sort by: Popular';
        }
        
        // Reset filters object
        this.currentFilters = {
            gender: [],
            color: [],
            min_price: null,
            max_price: null,
            breed: [],
            category_id: null,
            sort: 'popular',
            page: 1,
            limit: 12
        };
        
        this.applyFilters();
    }

    /**
     * Load dữ liệu ban đầu - CHỈ hiển thị giao diện, không gọi API
     */
    loadInitialData() {
        // Chỉ setup giao diện ban đầu, không gọi API
        console.log('Filter initialized. Waiting for user interaction...');
    }

    /**
     * Load dữ liệu lần đầu khi cần thiết (gọi từ bên ngoài)
     */
    forceLoadData() {
        this.hasUserInteraction = true;
        this.applyFilters();
    }

    /**
     * Bind events cho các sản phẩm
     */
    bindProductEvents() {
        // Quick view buttons
        document.querySelectorAll('.btn_quick_view').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = e.target.dataset.productId;
                this.showQuickView(productId);
            });
        });
        
        // Add to cart buttons
        document.querySelectorAll('.btn_add_cart').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = e.target.dataset.productId;
                this.addToCart(productId);
            });
        });
    }

    /**
     * Hiển thị loading
     */
    showLoading() {
        const container = document.querySelector('.products_grid');
        if (container) {
            container.classList.add('loading');
        }
        
        // Thêm loading overlay nếu chưa có
        if (!document.querySelector('.loading_overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'loading_overlay';
            overlay.innerHTML = '<div class="spinner">Đang tải...</div>';
            container.appendChild(overlay);
        }
    }

    /**
     * Ẩn loading
     */
    hideLoading() {
        const container = document.querySelector('.products_grid');
        if (container) {
            container.classList.remove('loading');
        }
        
        const overlay = document.querySelector('.loading_overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * Hiển thị lỗi
     */
    showError(message) {
        const container = document.querySelector('.products_grid');
        if (container) {
            container.innerHTML = `
                <div class="error_message">
                    <p>⚠️ ${message}</p>
                    <button onclick="location.reload()">Thử lại</button>
                </div>
            `;
        }
    }

    /**
     * Quick view sản phẩm
     */
    showQuickView(productId) {
        // TODO: Implement quick view modal
        console.log('Quick view product:', productId);
    }

    /**
     * Thêm vào giỏ hàng
     */
    addToCart(productId) {
        // TODO: Implement add to cart functionality
        console.log('Add to cart:', productId);
    }

    /**
     * Set category filter (gọi từ bên ngoài)
     */
    setCategoryFilter(categoryId) {
        this.currentFilters.category_id = categoryId;
        this.currentFilters.page = 1;
        this.hasUserInteraction = true; // Đây cũng là user interaction
        this.applyFilters();
    }

    /**
     * Get current filters (để sử dụng ở nơi khác)
     */
    getCurrentFilters() {
        return { ...this.currentFilters };
    }
}

// Khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    window.productFilter = new ProductFilter();
});

// Export để sử dụng ở module khác
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProductFilter;
}