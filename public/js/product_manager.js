document.addEventListener('DOMContentLoaded', function () {
    // Lấy các phần tử DOM cần thiết
    const addProductBtn = document.querySelector('.add-product-btn');
    const backArrow = document.querySelector('.fa-arrow-left');
    const formContainer = document.querySelector('.form-container_add_newProduct');

    // Global array để lưu tất cả files
    let allFiles = [];
    let primaryImageIndex = 0; // Index của ảnh chính

    // Thêm sự kiện click cho nút Add Product
    if (addProductBtn) {
        addProductBtn.addEventListener('click', function () {
            formContainer.style.display = 'grid';
            formContainer.style.position = 'fixed';
            formContainer.style.top = '50%';
            formContainer.style.left = '50%';
            formContainer.style.width = '80%';
            formContainer.style.height = '90%';
            formContainer.style.transform = 'translate(-50%, -50%)';
            formContainer.style.zIndex = '1000';
            document.body.style.overflow = 'hidden';
        });
    }

    // Lấy các phần tử DOM cần thiết
    const editProductBtn = document.querySelector('.edit-product');
    const formEditContainer = document.querySelector('.form-container_edit_product');

    // Thêm sự kiện click cho nút Add Product
    if (editProductBtn) {
        editProductBtn.addEventListener('click', function () {
            formEditContainer.style.display = 'grid';
            formEditContainer.style.position = 'fixed';
            formEditContainer.style.top = '50%';
            formEditContainer.style.left = '50%';
            formEditContainer.style.width = '80%';
            formEditContainer.style.height = '80%';
            formEditContainer.style.transform = 'translate(-50%, -50%)';
            formEditContainer.style.zIndex = '1000';
            document.body.style.overflow = 'hidden';
        });
    }

    // Thêm sự kiện click cho icon mũi tên quay lại
    if (backArrow) {
        backArrow.addEventListener('click', function () {
            formContainer.style.display = 'none';
            document.body.style.overflow = '';
        });
    }

    // Hàm cập nhật input file với tất cả files
    function updateFileInput() {
        const fileInput = document.getElementById('product_images_input');
        const dataTransfer = new DataTransfer();

        // Thêm tất cả files vào DataTransfer
        allFiles.forEach(file => {
            dataTransfer.items.add(file);
        });

        // Cập nhật input
        fileInput.files = dataTransfer.files;

        // Cập nhật primary image name
        if (allFiles.length > 0) {
            document.getElementById('primary_image_name').value = allFiles[primaryImageIndex].name;
        }
    }

    // Hàm cập nhật UI hiển thị ảnh
    function updateImageDisplay() {
        const imageSlots = document.querySelectorAll('.image-slot_add_newProduct');
        const previewImg = document.querySelector('.image-preview_add_newProduct img');

        // Cập nhật ảnh chính (preview)
        if (allFiles.length > 0) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
            };
            reader.readAsDataURL(allFiles[primaryImageIndex]);
        }

        // Cập nhật image slots
        imageSlots.forEach((slot, index) => {
            if (index < allFiles.length) {
                // Có file cho slot này
                if (index === primaryImageIndex) {
                    // Slot này là ảnh chính
                    slot.innerHTML = `
                        <img src="${URL.createObjectURL(allFiles[index])}" 
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                        <div class="primary-badge" style="position: absolute; top: 2px; right: 2px; background: #007bff; color: white; font-size: 10px; padding: 2px 4px; border-radius: 2px;">CHÍNH</div>
                    `;
                } else {
                    // Slot này là ảnh phụ
                    slot.innerHTML = `
                        <img src="${URL.createObjectURL(allFiles[index])}" 
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                        <button type="button" class="remove-image" style="position: absolute; top: 2px; right: 2px; background: #dc3545; color: white; border: none; border-radius: 2px; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                        <button type="button" class="set-primary" style="position: absolute; bottom: 2px; right: 2px; background: #28a745; color: white; border: none; border-radius: 2px; font-size: 8px; padding: 2px 4px; cursor: pointer;">Phụ</button>
                    `;
                }
                slot.style.position = 'relative';
            } else {
                // Slot trống
                slot.innerHTML = '<span>+</span>';
                slot.style.position = 'static';
            }
        });
    }

    // Xử lý upload area
    document.querySelector('.upload-area_add_newProduct').addEventListener('click', function () {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = true;
        input.onchange = function (e) {
            const files = Array.from(e.target.files);

            // Nếu chưa có file nào, file đầu tiên sẽ là ảnh chính
            if (allFiles.length === 0 && files.length > 0) {
                primaryImageIndex = 0;
            }

            // Thêm files vào mảng
            allFiles.push(...files);

            // Cập nhật UI và input
            updateImageDisplay();
            updateFileInput();
        };
        input.click();
    });

    // Xử lý image slots
    document.querySelectorAll('.image-slot_add_newProduct').forEach((slot, index) => {
        slot.addEventListener('click', function (e) {
            // Kiểm tra nếu click vào button
            if (e.target.classList.contains('remove-image')) {
                // Xóa ảnh
                allFiles.splice(index, 1);

                // Điều chỉnh primary index nếu cần
                if (index === primaryImageIndex) {
                    primaryImageIndex = 0; // Chuyển về ảnh đầu tiên
                } else if (index < primaryImageIndex) {
                    primaryImageIndex--;
                }

                updateImageDisplay();
                updateFileInput();
                return;
            }

            if (e.target.classList.contains('set-primary')) {
                // Đặt làm ảnh chính
                primaryImageIndex = index;
                updateImageDisplay();
                updateFileInput();
                return;
            }

            // Nếu slot trống, cho phép chọn file
            if (index >= allFiles.length) {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.onchange = function (e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Nếu chưa có file nào, file này sẽ là ảnh chính
                        if (allFiles.length === 0) {
                            primaryImageIndex = 0;
                        }

                        allFiles.push(file);
                        updateImageDisplay();
                        updateFileInput();
                    }
                };
                input.click();
            }
        });
    });

    // Xử lý Size Selection - CẢI TIẾN
    const sizeOptions = document.querySelectorAll('.size-option_add_newProduct');
    const selectedSizesInput = document.getElementById('selected_sizes');
    let selectedSizes = [];

    sizeOptions.forEach(option => {
        option.addEventListener('click', function () {
            const size = this.dataset.size;

            // Toggle selection
            if (this.classList.contains('selected')) {
                // Remove selection
                this.classList.remove('selected');
                selectedSizes = selectedSizes.filter(s => s !== size);

                // Remove checkmark
                const checkmark = this.querySelector('.checkmark');
                if (checkmark) {
                    checkmark.style.display = 'none';
                }
            } else {
                // Add selection
                this.classList.add('selected');
                selectedSizes.push(size);

                // Show checkmark
                const checkmark = this.querySelector('.checkmark');
                if (checkmark) {
                    checkmark.style.display = 'block';
                }
            }

            // Update hidden input
            selectedSizesInput.value = selectedSizes.join(',');

            console.log('Selected sizes:', selectedSizes); // Debug log
        });
    });

    // Phần code category và validation của bạn...
    const categoryParentSelect = document.getElementById('category_parent');
    const categoryChildSelect = document.getElementById('category_child');
    const productFields = document.getElementById('product-fields');
    const genderSection = document.getElementById('gender_section');
    const ageSection = document.getElementById('age_section');
    const colorSection = document.getElementById('color_section');
    const weightSection = document.getElementById('weight_section');

    const addForm = document.querySelector('.form-container_add_newProduct');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate form fields
            const productName = document.getElementById('product_name');
            if (!productName.value.trim()) {
                showErrorDialog('Vui lòng nhập tên sản phẩm');
                productName.focus();
                return;
            }

            const categoryChild = document.getElementById('category_child');
            if (!categoryChild.value) {
                showErrorDialog('Vui lòng chọn danh mục con');
                return;
            }

            const productWeight = document.getElementById('product_weight');
            if (!productWeight.value || parseFloat(productWeight.value) <= 0) {
                showErrorDialog('Vui lòng nhập cân nặng hợp lệ');
                productWeight.focus();
                return;
            }

            const productPrice = document.getElementById('product_price');
            if (!productPrice.value || parseFloat(productPrice.value) <= 0) {
                showErrorDialog('Vui lòng nhập giá hợp lệ');
                productPrice.focus();
                return;
            }

            if (allFiles.length === 0) {
                showErrorDialog('Vui lòng chọn ít nhất một ảnh cho sản phẩm');
                return;
            }

            // Tạo FormData object
            const formData = new FormData(addForm);

            // Thêm các files vào FormData
            for (let i = 0; i < allFiles.length; i++) {
                formData.append('product_images[]', allFiles[i]);
            }

            // SỬA URL GỬI REQUEST
            fetch('/WebsitePet/app/controllers/ProductAjaxController.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    // Kiểm tra content-type trước khi parse JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // Nếu không phải JSON, đọc text để debug
                        return response.text().then(text => {
                            console.error('Response is not JSON:', text);
                            throw new Error('Server returned non-JSON response');
                        });
                    }
                })
                .then(data => {
                    if (data.success) {
                        showSuccessDialog(data.message);
                        // Chuyển hướng sau 3 giây
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 3000);
                    } else {
                        showErrorDialog(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorDialog('Đã xảy ra lỗi khi gửi dữ liệu');
                });
        });
    }

    function toggleFieldsByParentName(parentName) {
        if (!parentName) {
            if (productFields) productFields.style.visibility = 'visible';
            if (genderSection) genderSection.style.visibility = 'visible';
            if (ageSection) ageSection.style.visibility = 'visible';
            if (colorSection) colorSection.style.visibility = 'visible';
            if (weightSection) weightSection.style.visibility = 'visible';
            return;
        }

        const lowerCaseName = parentName.toLowerCase();

        if (lowerCaseName.includes('pet')) {
            if (productFields) productFields.style.display = 'none';
            if (genderSection) genderSection.style.display = 'block';
            if (ageSection) ageSection.style.display = 'block';
            if (weightSection) weightSection.style.display = 'block';
            if (colorSection) colorSection.style.display = 'block';
        } else if (lowerCaseName.includes('product')) {
            if (productFields) productFields.style.display = 'block';
            if (genderSection) genderSection.style.display = 'none';
            if (ageSection) ageSection.style.display = 'none';
            if (weightSection) weightSection.style.display = 'block';
            if (colorSection) colorSection.style.display = 'block';
        } else {
            if (productFields) productFields.style.display = 'block';
            if (genderSection) genderSection.style.display = 'block';
            if (ageSection) ageSection.style.display = 'block';
            if (weightSection) weightSection.style.display = 'block';
            if (colorSection) colorSection.style.display = 'block';
        }
    }

    function fetchParentCategoryName(categoryId) {
        return fetch(`../../../app/controllers/ProductController.php?action=getParentCategoryName&category_id=${categoryId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                return data.parent_name || data.category_name || null;
            })
            .catch(error => {
                console.error('Error fetching parent name:', error);
                return null;
            });
    }

    // Xử lý khi chọn danh mục cha
    if (categoryParentSelect) {
        categoryParentSelect.addEventListener('change', function () {
            const parentId = this.value;
            categoryChildSelect.innerHTML = '<option value="">Chọn danh mục con</option>';

            if (parentId) {
                fetch(`../../../app/controllers/ProductController.php?action=getChildCategories&parent_id=${parentId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            data.forEach(child => {
                                const option = document.createElement('option');
                                option.value = child.category_id;
                                option.textContent = child.category_name;
                                categoryChildSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi khi lấy danh mục con:', error);
                    });

                fetchParentCategoryName(parentId).then(parentName => {
                    toggleFieldsByParentName(parentName);
                });
            } else {
                toggleFieldsByParentName(null);
            }
        });
    }

    // Khi load trang, nếu đã chọn sẵn category parent
    if (categoryParentSelect && categoryParentSelect.value) {
        fetchParentCategoryName(categoryParentSelect.value).then(parentName => {
            toggleFieldsByParentName(parentName);
        });
    }

    // Debug: Monitor gender select changes
    document.querySelectorAll('select[name^="main_gender"], select[name^="image_gender_"]').forEach(select => {
        select.addEventListener('change', function () {
            console.log(`Gender changed: ${this.name} = ${this.value}`);
        });
    });

    if (categoryParentSelect && categoryParentSelect.value) {
        fetchParentCategoryName(categoryParentSelect.value).then(parentName => {
            toggleFieldsByParentName(parentName);
        });
    }

    // Debug: Monitor gender select changes
    document.querySelectorAll('select[name^="main_gender"], select[name^="image_gender_"]').forEach(select => {
        select.addEventListener('change', function () {
            console.log(`Gender changed: ${this.name} = ${this.value}`);
        });
    });

    // Thêm real-time validation cho các trường input
    const productName = document.getElementById('product_name');
    const productPrice = document.getElementById('product_price');
    const productWeight = document.getElementById('product_weight');

    if (productName) {
        productName.addEventListener('blur', function () {
            if (this.value.trim() && this.value.trim().length < 3) {
                showWarningDialog('Tên sản phẩm nên có ít nhất 3 ký tự!');
            }
        });
    }

    if (productPrice) {
        productPrice.addEventListener('blur', function () {
            if (this.value && parseFloat(this.value) <= 0) {
                showWarningDialog('Giá sản phẩm phải lớn hơn 0!');
            }
        });
    }

    if (productWeight) {
        productWeight.addEventListener('blur', function () {
            if (this.value && parseFloat(this.value) <= 0) {
                showWarningDialog('Cân nặng phải lớn hơn 0!');
            }
        });
    }

    // Xử lý nút Hủy với dialog xác nhận
    const cancelBtn = document.querySelector('.btn-danger_add_newProduct[type="reset"]');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function (e) {
            e.preventDefault(); // Ngăn chặn reset form mặc định

            // Kiểm tra xem có dữ liệu đã được nhập hay không
            const hasData = checkFormHasData();

            if (hasData) {
                // Hiển thị dialog xác nhận
                showConfirmDialog(
                    'Xác nhận hủy',
                    'Bạn có chắc chắn muốn hủy? Tất cả dữ liệu đã nhập sẽ bị mất!',
                    function () {
                        // Callback khi người dùng xác nhận
                        resetFormAndClose();
                        document.body.style.overflow = '';
                        showErrorDialog('Không thể xóa sản phẩm vì đang có đơn hàng ở trạng thái "đang xử lý" chứa sản phẩm này.');
                    },
                    function () {
                        // Callback khi người dùng hủy (không làm gì)
                        console.log('Người dùng đã hủy việc reset form');
                        document.body.style.overflow = '';
                    }
                );
            } else {
                // Nếu chưa có dữ liệu, đóng form luôn
                resetFormAndClose();
            }
        });
    }

    // Hàm kiểm tra xem form có dữ liệu hay không
    function checkFormHasData() {
        const form = document.querySelector('.form-container_add_newProduct');
        const inputs = form.querySelectorAll('input[type="text"], input[type="number"], textarea, select');

        // Kiểm tra các input text, number, textarea, select
        for (let input of inputs) {
            if (input.value.trim() !== '') {
                return true;
            }
        }

        // Kiểm tra xem có file ảnh được chọn hay không
        if (allFiles && allFiles.length > 0) {
            return true;
        }

        // Kiểm tra xem có size nào được chọn hay không
        if (selectedSizes && selectedSizes.length > 0) {
            return true;
        }

        return false;
    }

    // Hàm reset form và đóng popup
    function resetFormAndClose() {
        const form = document.querySelector('.form-container_add_newProduct');
        const formContainer = document.querySelector('.form-container_add_newProduct');

        // Reset form
        form.reset();

        // Reset các biến global
        allFiles = [];
        selectedSizes = [];
        primaryImageIndex = 0;

        // Reset selected sizes UI
        const sizeOptions = document.querySelectorAll('.size-option_add_newProduct');
        sizeOptions.forEach(option => {
            option.classList.remove('selected');
            const checkmark = option.querySelector('.checkmark');
            if (checkmark) {
                checkmark.style.display = 'none';
            }
        });

        // Reset image display
        updateImageDisplay();

        // Reset các input ẩn
        document.getElementById('selected_sizes').value = '';
        document.getElementById('primary_image_name').value = '';

        // Đóng popup
        formContainer.style.display = 'none';
        document.body.style.overflow = '';

        // Hiển thị thông báo
        // showInfoDialog('Đã hủy thành công!');
    }

    // Hàm hiển thị dialog xác nhận (bạn cần thêm vào file dialog của mình)
    function showConfirmDialog(title, message, onConfirm, onCancel) {
        // Tạo dialog HTML
        const dialogHTML = `
        <div class="dialog-overlay" id="confirmDialog" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        ">
            <div class="dialog-content" style="
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                max-width: 400px;
                width: 90%;
                text-align: center;
            ">
                <div class="dialog-icon" style="
                    margin-bottom: 20px;
                ">
                    <i class="fas fa-exclamation-triangle" style="
                        font-size: 48px;
color: #ff6b6b;
                    "></i>
                </div>
                <h3 style="
                    margin: 0 0 15px 0;
                    color: #333;
                    font-size: 20px;
                ">${title}</h3>
                <p style="
                    margin: 0 0 25px 0;
                    color: #666;
                    line-height: 1.5;
                ">${message}</p>
                <div class="dialog-buttons" style="
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                ">
                    <button type="button" class="confirm-btn" style="
                        background: #dc3545;
                        color: white;
                        border: none;
                        padding: 12px 24px;
                        border-radius: 5px;
                        cursor: pointer;
                        font-size: 14px;
                        transition: background 0.3s;
                    ">
                        <i class="fas fa-check"></i> Xác nhận
                    </button>
                    <button type="button" class="cancel-btn" style="
                        background: #6c757d;
                        color: white;
                        border: none;
                        padding: 12px 24px;
                        border-radius: 5px;
                        cursor: pointer;
                        font-size: 14px;
                        transition: background 0.3s;
                    ">
                        <i class="fas fa-times"></i> Hủy bỏ
                    </button>
                </div>
            </div>
        </div>
    `;

        // Thêm dialog vào DOM
        document.body.insertAdjacentHTML('beforeend', dialogHTML);

        const dialog = document.getElementById('confirmDialog');
        const confirmBtn = dialog.querySelector('.confirm-btn');
        const cancelBtn = dialog.querySelector('.cancel-btn');

        // Xử lý sự kiện
        confirmBtn.addEventListener('click', function () {
            dialog.remove();
            if (onConfirm) onConfirm();
        });

        cancelBtn.addEventListener('click', function () {
            dialog.remove();
            if (onCancel) onCancel();
        });

        // Đóng dialog khi click overlay
        dialog.addEventListener('click', function (e) {
            if (e.target === dialog) {
                dialog.remove();
                if (onCancel) onCancel();
            }
        });

        // Đóng dialog khi nhấn ESC
        const escHandler = function (e) {
            if (e.key === 'Escape') {
                dialog.remove();
                document.removeEventListener('keydown', escHandler);
                if (onCancel) onCancel();
            }
        };
        document.addEventListener('keydown', escHandler);

        // Thêm hover effect cho buttons
        confirmBtn.addEventListener('mouseenter', function () {
            this.style.background = '#c82333';
        });
        confirmBtn.addEventListener('mouseleave', function () {
            this.style.background = '#dc3545';
        });

        cancelBtn.addEventListener('mouseenter', function () {
            this.style.background = '#5a6268';
        });
        cancelBtn.addEventListener('mouseleave', function () {
            this.style.background = '#6c757d';
        });
    }
});

// Add some debugging
document.querySelector('form').addEventListener('submit', function (e) {
    console.log('Form submitted');
    console.log('Form data:', new FormData(this));
    const submitter = document.activeElement;
    // Nếu là nút cập nhật kho, bỏ qua kiểm tra
    if (submitter && submitter.name === 'skip-validation') {
        console.log('Skip validation for warehouse update button');
        return;
    }

    // Check if required fields are filled
    const requiredFields = ['product_name', 'category_child', 'product_price', 'product_weight'];
    for (let field of requiredFields) {
        const input = this.querySelector(`[name="${field}"]`);
        if (!input || !input.value.trim()) {
            console.error(`Missing required field: ${field}`);
            alert(`Vui lòng nhập ${field}`);
            e.preventDefault();
            return;
        }
    }
});

// 1. Event listener cho nút Edit
document.addEventListener('DOMContentLoaded', function () {
    // Thêm event listener cho các nút edit
    document.querySelectorAll('.edit-product').forEach(button => {
        button.addEventListener('click', function () {
            const productId = this.getAttribute('data-product-id');
            editProduct(productId);
        });
    });
});

// 1. Thêm function để load danh mục cha
function loadParentCategories(targetSelectId, selectedParentId = null) {
    const select = document.getElementById(targetSelectId);
    select.innerHTML = '<option value="">Đang tải...</option>';

    fetch(`../../../app/controllers/ProductController.php?action=getParentCategoryListAjax`)
        .then(response => response.text()) // <-- xử lý dạng text
        .then(text => {
            try {
                const data = JSON.parse(text);
                select.innerHTML = '<option value="">Chọn danh mục chính</option>';

                if (data && data.length > 0) {
                    data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.category_id;
                        option.textContent = category.category_name;
                        if (selectedParentId && category.category_id == selectedParentId) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                }
                console.log("Parsed data:", data);
            } catch (e) {
            console.error("❌ Không thể parse JSON. Response thô:", text);
            }
        })
        .catch(error => {
            console.error('Error loading parent categories:', error);
            select.innerHTML = '<option value="">Lỗi khi tải danh mục</option>';
        });
}

// 2. Cập nhật function populateEditForm
function populateEditForm(data) {
    const product = data.product;
    const images = data.images;
    const category = data.category;
    const parentCategory = data.parentCategory;
    const isPet = data.isPet;

    // Reset form trước khi điền dữ liệu
    resetEditForm();

    // Điền thông tin cơ bản
    document.getElementById('edit_product_id').value = product.product_id;
    document.getElementById('edit_product_name').value = product.product_name;
    document.getElementById('edit_business_description').value = product.description || '';
    document.getElementById('edit_product_price').value = product.price;
    document.getElementById('edit_product_weight').value = product.weight;

    // Load danh mục cha và chọn đúng giá trị
    if (parentCategory) {
        // Nếu có danh mục cha, load danh mục cha và chọn nó
        loadParentCategories('edit_category_parent', parentCategory.category_id);
        // Sau đó load danh mục con
        setTimeout(() => {
            loadChildCategories(parentCategory.category_id, 'edit_category_child', category.category_id);
        }, 500);
    } else {
        // Nếu không có danh mục cha, có nghĩa là category hiện tại là danh mục cha
        loadParentCategories('edit_category_parent', category.category_id);
        // Reset danh mục con
        setTimeout(() => {
            const categoryChildSelect = document.getElementById('edit_category_child');
            categoryChildSelect.innerHTML = '<option value="">Không có danh mục con</option>';
            categoryChildSelect.value = '';
        }, 500);
    }

    // Hiển thị/ẩn fields theo loại sản phẩm
    toggleFieldsForEdit(isPet);

    if (isPet) {
        document.getElementById('edit_main_age').value = product.age || '';
        // Normalize image data trước khi sử dụng
        const normalizedImages = normalizeImageData(images);
        
        // Điền thông tin từ ảnh
        normalizedImages.forEach((image, index) => {
            if (image.is_primary) {
                document.getElementById('edit_main_gender').value = image.gender || '';
                document.getElementById('edit_main_color').value = image.color || '';
                if (image.age) {
                    document.getElementById('edit_main_age').value = image.age;
                }
            } else {
                const displayOrder = image.display_order;
                if (displayOrder >= 1 && displayOrder <= 5) {
                    const imageIndex = displayOrder - 1;
                    
                    if (imageIndex >= 1 && imageIndex <= 4) {
                        const genderSelect = document.getElementById(`edit_image_gender_${imageIndex}`);
                        const colorInput = document.getElementById(`edit_image_color_${imageIndex}`);
                        const ageInput = document.getElementById(`edit_image_age_${imageIndex}`);

                        if (genderSelect) genderSelect.value = image.gender || '';
                        if (colorInput) colorInput.value = image.color || '';
                        if (ageInput) ageInput.value = image.age || '';
                    }
                }
            }
        });
    } else {
        document.getElementById('edit_product_material').value = product.material || '';
        document.getElementById('edit_selected_sizes').value = product.size || '';
        
        if (product.size) {
            const selectedSizes = product.size.split(',');
            selectedSizes.forEach(size => {
                const sizeOption = document.querySelector(`#editProductForm .size-option_edit_product[data-size="${size.trim()}"]`);
                if (sizeOption) {
                    sizeOption.classList.add('selected');
                }
            });
        }
    }

    // Hiển thị ảnh hiện tại
    displayCurrentImages(images);

    // Hiển thị section thay thế ảnh
    document.getElementById('replace_images_section').style.display = 'block';
}

// 3. Cập nhật event listener cho edit_category_parent
document.addEventListener('DOMContentLoaded', function () {
    // Xử lý thay đổi danh mục cha
    document.getElementById('edit_category_parent').addEventListener('change', function () {
        const parentId = this.value;
        const childSelect = document.getElementById('edit_category_child');

        if (parentId) {
            loadChildCategories(parentId, 'edit_category_child');

            // Kiểm tra xem có phải Pet không để toggle fields
            fetch(`../../../app/controllers/ProductController.php?action=getParentCategoryName&category_id=${parentId}`)
                .then(response => response.json())
                .then(data => {
                    const isPet = data.parent_name && data.parent_name.toLowerCase() === 'pet';
                    toggleFieldsForEdit(isPet);
                })
                .catch(error => {
                    console.error('Error checking category type:', error);
                });
        } else {
            childSelect.innerHTML = '<option value="">Chọn danh mục con</option>';
            // Reset fields khi không chọn danh mục
            toggleFieldsForEdit(false);
        }
    });
});

// 4. Cập nhật resetEditForm để load lại danh mục cha
function resetEditForm() {
    document.getElementById('editProductForm').reset();

    // Reset size selection
    document.querySelectorAll('#editProductForm .size-option_edit_product').forEach(option => {
        option.classList.remove('selected');
    });

    // Reset image grid
    const imageGrid = document.querySelector('#editProductForm .image-grid_edit_product');
    imageGrid.innerHTML = '';
    for (let i = 0; i < 5; i++) {
        const slot = document.createElement('div');
        slot.className = 'image-slot_edit_product';
        slot.dataset.slot = i;
        slot.innerHTML = '<span>+</span>';
        imageGrid.appendChild(slot);
    }

    // Reset preview image
    const previewImage = document.querySelector('#editProductForm #preview_image');
    previewImage.src = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjMzMzIiByeD0iOCIvPgo8cGF0aCBkPSJNMzAgNDBMMzUgMzVMMzUgNjVMMzAgNjBWNDBaIiBmaWxsPSIjNjY2Ii8+CjxwYXRoIGQ9Ik03MCA0MEw2NSAzNUw2NSA2NUw3MCA2MFY0MFoiIGZpbGw9IiM2NjYiLz4KPGF0aCBkPSJNNDAgMzBMNDUgMjVMNTUgMjVMNjAgMzBWNzBMNTUgNzVMNDUgNzVMNDAgNzBWMzBaIiBmaWxsPSIjNjY2Ii8+Cjwvc3ZnPgo=";

    // Reset primary image name
    document.getElementById('edit_primary_image_name').value = '';

    // Hide replace images section
    document.getElementById('replace_images_section').style.display = 'none';

    // Load lại danh mục cha khi reset
    loadParentCategories('edit_category_parent');
    
    // Reset danh mục con
    const categoryChildSelect = document.getElementById('edit_category_child');
    categoryChildSelect.innerHTML = '<option value="">Chọn danh mục con</option>';
}

// 1. Updated validation function to handle both alt_text and original_name
function validateImageData(images) {
    if (!Array.isArray(images)) {
        console.error('Images should be an array');
        return false;
    }
    
    for (let image of images) {
        // Kiểm tra các trường bắt buộc
        if (!image.hasOwnProperty('image_url')) {
            console.error('Missing image_url field in image data');
            return false;
        }
        
        // Kiểm tra xem có original_name hoặc alt_text không
        if (!image.hasOwnProperty('original_name') && !image.hasOwnProperty('alt_text')) {
            console.error('Missing original_name or alt_text field in image data');
            return false;
        }
        
        if (!image.hasOwnProperty('is_primary')) {
            console.error('Missing is_primary field in image data');
            return false;
        }
        
        if (!image.hasOwnProperty('display_order')) {
            console.error('Missing display_order field in image data');
            return false;
        }
    }
    
    return true;
}

// 2. Updated function to normalize image data
function normalizeImageData(images) {
    return images.map(image => {
        // Đảm bảo có original_name
        if (!image.original_name) {
            image.original_name = image.alt_text || image.image_url;
        }
        
        // Đảm bảo các trường pet info tồn tại
        if (!image.gender) image.gender = '';
        if (!image.color) image.color = '';
        if (!image.age) image.age = '';
        
        return image;
    });
}

// 3. Updated displayCurrentImages function
function displayCurrentImages(images) {
    const imageGrid = document.querySelector('#editProductForm .image-grid_edit_product');
    const previewImage = document.querySelector('#editProductForm #preview_image');

    // Reset image grid
    imageGrid.innerHTML = '';
    for (let i = 0; i < 5; i++) {
        const slot = document.createElement('div');
        slot.className = 'image-slot_edit_product';
        slot.dataset.slot = i;
        slot.innerHTML = '<span>+</span>';
        imageGrid.appendChild(slot);
    }

    // Normalize image data trước khi sử dụng
    const normalizedImages = normalizeImageData(images);
    
    // Sắp xếp ảnh theo display_order
    normalizedImages.sort((a, b) => a.display_order - b.display_order);

    // Hiển thị ảnh hiện tại theo display_order
    normalizedImages.forEach((image) => {
        const slotIndex = image.display_order - 1; // Convert to 0-based index
        const slot = imageGrid.children[slotIndex];
        if (slot) {
            slot.innerHTML = `
                <img src="../../../public/uploads/product/${image.image_url}" alt="${image.original_name}" title="${image.original_name}">
                <div class="image-overlay_edit_product">
                    <button type="button" onclick="removeCurrentImage(${image.image_id})" class="remove-btn" title="Xóa ảnh">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            slot.classList.add('has-image');
            slot.dataset.imageId = image.image_id;
        }

        // Set primary image preview
        if (image.is_primary && previewImage) {
            previewImage.src = `../../../public/uploads/product/${image.image_url}`;
            previewImage.title = image.original_name;
            document.getElementById('edit_primary_image_name').value = image.original_name;
        }
    });
}

// 5. Updated function để kiểm tra ProductImage model
function checkImageDataStructure(images) {
    console.log('Image data structure:', images);
    
    // Kiểm tra xem images có chứa các trường cần thiết không
    images.forEach((image, index) => {
        console.log(`Image ${index}:`, {
            image_id: image.image_id,
            image_url: image.image_url,
            original_name: image.original_name || image.alt_text,
            alt_text: image.alt_text,
            is_primary: image.is_primary,
            display_order: image.display_order,
            gender: image.gender || '',
            color: image.color || '',
            age: image.age || ''
        });
    });
}

// 6. Updated editProduct function
function editProduct(productId) {
    showLoading();

    fetch(`../../../app/controllers/ProductController.php?action=getProductForEdit&product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                // Normalize image data trước khi validate
                if (data.images) {
                    data.images = normalizeImageData(data.images);
                }
                
                // Validate image data
                if (data.images && !validateImageData(data.images)) {
                    console.error('Invalid image data structure');
                    showAlert('error', 'Dữ liệu ảnh không hợp lệ');
                    return;
                }
                
                // Debug: Kiểm tra cấu trúc dữ liệu ảnh
                checkImageDataStructure(data.images);
                
                populateEditForm(data);
                showEditForm();
            } else {
                showAlert('error', 'Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showAlert('error', 'Có lỗi xảy ra khi tải dữ liệu sản phẩm');
        });
}

// 7. Helper function to get image display name
function getImageDisplayName(image) {
    return image.original_name || image.alt_text || image.image_url;
}

// 6. Hàm xóa ảnh hiện tại
function removeCurrentImage(imageId) {
    if (confirm('Bạn có chắc muốn xóa ảnh này?')) {
        showLoading();

        fetch(`../../../app/controllers/ProductController.php?action=removeImage&image_id=${imageId}`, {
            method: 'POST'
        })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    // Reload current images
                    const productId = document.getElementById('edit_product_id').value;
                    editProduct(productId);
                    showAlert('success', 'Xóa ảnh thành công!');
                } else {
                    showAlert('error', 'Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showAlert('error', 'Có lỗi xảy ra khi xóa ảnh');
            });
    }
}

// 7. Hàm load danh mục con (đã cải thiện)
function loadChildCategories(parentId, targetSelectId, selectedChildId = null) {
    const select = document.getElementById(targetSelectId);
    select.innerHTML = '<option value="">Đang tải...</option>';

    fetch(`../../../app/controllers/ProductController.php?action=getChildCategories&parent_id=${parentId}`)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">Chọn danh mục con</option>';

            if (data && data.length > 0) {
                data.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.category_id;
                    option.textContent = category.category_name;
                    if (selectedChildId && category.category_id == selectedChildId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="">Không có danh mục con</option>';
            }
        })
        .catch(error => {
            console.error('Error loading child categories:', error);
            select.innerHTML = '<option value="">Lỗi khi tải danh mục con</option>';
        });
}

// 8. Hàm toggle fields theo loại sản phẩm
function toggleFieldsForEdit(isPet) {
    const petFields = document.getElementById('pet_fields');
    const productFields = document.getElementById('product_fields');
    
    if (isPet) {
        // Hiển thị pet-specific fields
        petFields.style.display = 'block';
        
        // Ẩn product-specific fields NGOẠI TRỪ price và weight
        const materialSection = productFields.querySelector('.form-group_edit_product'); // Material section
        const sizeSection = productFields.querySelector('.section_edit_product:nth-child(2)'); // Size section
        
        // Ẩn material và size sections
        if (materialSection) materialSection.style.display = 'none';
        if (sizeSection) sizeSection.style.display = 'none';
        
        // Đảm bảo price và weight sections luôn hiển thị
        const priceSection = productFields.querySelector('.section_edit_product:nth-child(3)');
        const weightSection = productFields.querySelector('.section_edit_product:nth-child(4)');
        
        if (priceSection) priceSection.style.display = 'block';
        if (weightSection) weightSection.style.display = 'block';
        
        // Hoặc có thể hiển thị toàn bộ productFields nhưng chỉ ẩn material và size
        productFields.style.display = 'block';
        
    } else {
        // Ẩn pet-specific fields
        petFields.style.display = 'none';
        
        // Hiển thị tất cả product-specific fields
        productFields.style.display = 'block';
        
        // Đảm bảo tất cả sections trong product fields đều hiển thị
        const allSections = productFields.querySelectorAll('.section_edit_product, .form-group_edit_product');
        allSections.forEach(section => {
            section.style.display = 'block';
        });
    }
}

// 9. Hàm hiển thị form edit
function showEditForm() {
    document.body.style.overflow = 'hidden';
    document.getElementById('editProductForm').style.display = 'block';

    // Scroll to top of form
    document.getElementById('editProductForm').scrollIntoView({ behavior: 'smooth' });
}

// 10. Hàm đóng form edit
function closeEditForm() {
    document.body.style.overflow = '';
    document.getElementById('editProductForm').style.display = 'none';
    resetEditForm();
}

document.addEventListener('DOMContentLoaded', function () {
    // Xử lý thay đổi danh mục cha
    document.getElementById('edit_category_parent').addEventListener('change', function () {
        const parentId = this.value;
        const childSelect = document.getElementById('edit_category_child');

        if (parentId) {
            loadChildCategories(parentId, 'edit_category_child');

            // Kiểm tra xem có phải Pet không để toggle fields
            fetch(`../../../app/controllers/ProductController.php?action=getParentCategoryName&category_id=${parentId}`)
                .then(response => response.json())
                .then(data => {
                    const isPet = data.parent_name && data.parent_name.toLowerCase() === 'pet';
                    toggleFieldsForEdit(isPet);
                })
                .catch(error => {
                    console.error('Error checking category type:', error);
                });
        } else {
            childSelect.innerHTML = '<option value="">Chọn danh mục con</option>';
        }
    });

    // Xử lý size selection
    document.querySelectorAll('#editProductForm .size-option_edit_product').forEach(option => {
        option.addEventListener('click', function () {
            this.classList.toggle('selected');

            // Cập nhật hidden input
            const selectedSizes = Array.from(document.querySelectorAll('#editProductForm .size-option_edit_product.selected'))
                .map(el => el.dataset.size);
            document.getElementById('edit_selected_sizes').value = selectedSizes.join(',');
        });
    });

    // Xử lý upload ảnh mới
    document.getElementById('edit_product_images_input').addEventListener('change', function (e) {
        const files = Array.from(e.target.files);
        const imageGrid = document.querySelector('#editProductForm .image-grid_edit_product');
        const previewImage = document.querySelector('#editProductForm #preview_image');
        const replaceAllCheckbox = document.getElementById('replace_all_images');

        // Nếu chọn thay thế tất cả ảnh, clear existing images
        if (replaceAllCheckbox.checked) {
            clearExistingImages();
        }

        files.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const emptySlot = imageGrid.querySelector('.image-slot_edit_product:not(.has-image)');
                if (emptySlot) {
                    emptySlot.innerHTML = `
                        <img src="${e.target.result}" alt="New image" title="${file.name}">
                        <div class="image-overlay_edit_product">
                            <button type="button" onclick="removeNewImage(this)" class="remove-btn" title="Xóa ảnh">
                                <i class="fas fa-times"></i>
                            </button>
                            <button type="button" onclick="setPrimaryImage(this)" class="primary-btn" title="Chọn làm ảnh chính">
                                <i class="fas fa-star"></i>
                            </button>
                        </div>
                        <div class="image-info">${file.name}</div>
                    `;
                    emptySlot.classList.add('has-image');
                    emptySlot.classList.add('new-image');
                    emptySlot.dataset.fileName = file.name;

                    // Set as primary if it's the first image and no primary exists
                    if (index === 0 && !document.querySelector('.image-slot_edit_product.primary-image')) {
                        setPrimaryImageSlot(emptySlot);
                    }
                }
            };
            reader.readAsDataURL(file);
        });
    });

    // Xử lý checkbox thay thế tất cả ảnh
    document.getElementById('replace_all_images').addEventListener('change', function() {
        const replaceSection = document.querySelector('.replace-images-section');
        if (this.checked) {
            replaceSection.style.borderColor = '#e74c3c';
            replaceSection.style.backgroundColor = '#fdf2f2';
        } else {
            replaceSection.style.borderColor = '#ddd';
            replaceSection.style.backgroundColor = '#f8f9fa';
        }
    });

    // Xử lý drag & drop
    const editUploadArea = document.querySelector('#editProductForm .upload-area_edit_product');

    editUploadArea.addEventListener('click', function () {
        document.getElementById('edit_product_images_input').click();
    });

    editUploadArea.addEventListener('dragover', function (e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });

    editUploadArea.addEventListener('dragleave', function (e) {
        e.preventDefault();
        this.classList.remove('drag-over');
    });

    editUploadArea.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.remove('drag-over');

        const files = Array.from(e.dataTransfer.files);
        const input = document.getElementById('edit_product_images_input');

        // Tạo FileList mới
        const dt = new DataTransfer();
        files.forEach(file => dt.items.add(file));
        input.files = dt.files;

        // Trigger change event
        input.dispatchEvent(new Event('change'));
    });

    // Xử lý submit form edit
    document.getElementById('editProductForm').addEventListener('submit', function (e) {
        e.preventDefault();

        // Validation
        if (!validateEditForm()) {
            return;
        }

        const formData = new FormData(this);
        formData.append('action', 'update_product');

        // Hiển thị loading
        showLoading();

        fetch('../../../app/controllers/ProductController.php?action=update_product', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showAlert('success', data.message);
                    closeEditForm();
                    // Refresh product list hoặc redirect
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    } else {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    showAlert('error', 'Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showAlert('error', 'Có lỗi xảy ra khi cập nhật sản phẩm');
            });
    });
});

// Utility functions
function clearExistingImages() {
    const imageGrid = document.querySelector('#editProductForm .image-grid_edit_product');
    const slots = imageGrid.querySelectorAll('.image-slot_edit_product');
    
    slots.forEach(slot => {
        slot.innerHTML = '<span>+</span>';
        slot.classList.remove('has-image', 'new-image', 'primary-image');
        slot.removeAttribute('data-image-id');
        slot.removeAttribute('data-file-name');
    });
}

function removeNewImage(button) {
    const slot = button.closest('.image-slot_edit_product');
    const wasprimary = slot.classList.contains('primary-image');
    
    slot.innerHTML = '<span>+</span>';
    slot.classList.remove('has-image', 'new-image', 'primary-image');
    slot.removeAttribute('data-file-name');
    
    // Nếu đây là ảnh chính, tìm ảnh khác làm ảnh chính
    if (wasPrimary) {
        const otherImages = document.querySelectorAll('.image-slot_edit_product.has-image');
        if (otherImages.length > 0) {
            setPrimaryImageSlot(otherImages[0]);
        }
    }
}

function setPrimaryImage(button) {
    const slot = button.closest('.image-slot_edit_product');
    setPrimaryImageSlot(slot);
}

function setPrimaryImageSlot(slot) {
    // Remove primary class from all images
    document.querySelectorAll('.image-slot_edit_product.primary-image').forEach(img => {
        img.classList.remove('primary-image');
    });
    
    // Add primary class to selected image
    slot.classList.add('primary-image');
    
    // Update preview image
    const img = slot.querySelector('img');
    const previewImage = document.querySelector('#editProductForm #preview_image');
    
    if (img && previewImage) {
        previewImage.src = img.src;
        previewImage.title = slot.dataset.fileName || img.alt;
    }
    
    // Update hidden input
    const fileName = slot.dataset.fileName || slot.querySelector('.image-info')?.textContent;
    if (fileName) {
        document.getElementById('edit_primary_image_name').value = fileName;
    }
}

function validateEditForm() {
    const requiredFields = [
        'edit_product_name',
        'edit_category_parent',
        'edit_product_price',
        'edit_product_weight'
    ];

    for (let fieldId of requiredFields) {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
            const label = field.previousElementSibling;
            const fieldName = label ? label.textContent.replace('*', '').trim() : fieldId;
            showAlert('error', `Vui lòng điền ${fieldName}`);
            field.focus();
            return false;
        }
    }

    // Kiểm tra category con nếu có
    const categoryChild = document.getElementById('edit_category_child');
    if (categoryChild.options.length > 1 && !categoryChild.value &&
        !categoryChild.options[0].text.includes('Không có danh mục con')) {
        showAlert('error', 'Vui lòng chọn danh mục con');
        categoryChild.focus();
        return false;
    }

    // Kiểm tra giá phải là số dương
    const price = parseFloat(document.getElementById('edit_product_price').value);
    if (isNaN(price) || price <= 0) {
        showAlert('error', 'Giá sản phẩm phải là số dương');
        document.getElementById('edit_product_price').focus();
        return false;
    }

    // Kiểm tra cân nặng phải là số dương
    const weight = parseFloat(document.getElementById('edit_product_weight').value);
    if (isNaN(weight) || weight <= 0) {
        showAlert('error', 'Cân nặng phải là số dương');
        document.getElementById('edit_product_weight').focus();
        return false;
    }

    return true;
}

function showLoading() {
    const existingLoading = document.getElementById('loading-overlay');
    if (existingLoading) {
        existingLoading.remove();
    }

    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loading-overlay';
    loadingDiv.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    loadingDiv.innerHTML = `
        <div class="loading-spinner" style="
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        ">
            <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 10px;"></i>
            <div>Đang xử lý...</div>
        </div>
    `;
    document.body.appendChild(loadingDiv);
}

function hideLoading() {
    const loadingDiv = document.getElementById('loading-overlay');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

function showAlert(type, message) {
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());

    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        console.error('Alert container not found');
        return;
    }

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.cssText = `
        padding: 15px;
        margin: 10px 0;
        border-radius: 4px;
        border: 1px solid;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease-in-out;
        ${type === 'success' ?
            'background: #d4edda; color: #155724; border-color: #c3e6cb;' :
            'background: #f8d7da; color: #721c24; border-color: #f5c6cb;'
        }
    `;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span style="flex: 1;">${message}</span>
        <button type="button" class="close-alert" onclick="this.parentElement.remove()" style="
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: inherit;
        ">
            <i class="fas fa-times"></i>
        </button>
    `;

    alertContainer.innerHTML = '';
    alertContainer.appendChild(alertDiv);

    // Auto hide after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 5000);
}

// 12. Utility functions
function removeNewImage(button) {
    const slot = button.closest('.image-slot_edit_product');
    slot.innerHTML = '<span>+</span>';
    slot.classList.remove('has-image', 'new-image');
}

// Cải tiến function loadEditProduct để đổ dữ liệu gender
function loadEditProduct(productId) {
    if (!productId) {
        showAlert('error', 'ID sản phẩm không hợp lệ');
        return;
    }
    
    showLoading();
    
    fetch(`../../../app/controllers/ProductController.php?action=getProductForEdit&product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Điền thông tin cơ bản
                fillBasicProductInfo(data.product);
                
                // Điền thông tin danh mục
                fillCategoryInfo(data);
                
                // Điền thông tin ảnh và đổ dữ liệu gender
                fillProductImages(data.images, data.isPet);
                
                // Điền thông tin pet-specific hoặc product-specific
                if (data.isPet) {
                    fillPetSpecificInfo(data.images);
                    showPetFields();
                } else {
                    fillProductSpecificInfo(data.product, data.selectedSizes);
                    showProductFields();
                }
                
                // Set primary image
                if (data.primaryImageName) {
                    document.getElementById('edit_primary_image_name').value = data.primaryImageName;
                }
                
                showEditForm();
            } else {
                showAlert('error', data.message || 'Không thể tải thông tin sản phẩm');
            }
        })
        .catch(error => {
            console.error('Error loading product:', error);
            showAlert('error', 'Có lỗi xảy ra khi tải thông tin sản phẩm');
        })
        .finally(() => {
            hideLoading();
        });
}

// Function để điền thông tin ảnh và đổ dữ liệu gender
function fillProductImages(images, isPet) {
    const imageGrid = document.getElementById('image_grid');
    const previewImage = document.getElementById('preview_image');
    
    // Reset image grid
    const slots = imageGrid.querySelectorAll('.image-slot_edit_product');
    slots.forEach(slot => {
        slot.innerHTML = '<span>+</span>';
        slot.classList.remove('has-image', 'primary-image');
        slot.removeAttribute('data-file-name');
        slot.removeAttribute('data-image-id');
    });
    
    // Sắp xếp ảnh theo display_order
    images.sort((a, b) => a.display_order - b.display_order);
    
    images.forEach((image, index) => {
        if (index < slots.length) {
            const slot = slots[index];
            
            // Tạo HTML cho ảnh
            const imageHtml = `
                <img src="public/uploads/product/${image.image_url}" 
                     alt="${image.alt_text || image.original_name}" 
                     style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                <div class="image-overlay_edit_product">
                    <button type="button" class="btn-set-primary" onclick="setPrimaryImage(this)" 
                            title="Đặt làm ảnh chính">
                        <i class="fas fa-star"></i>
                    </button>
                    <button type="button" class="btn-remove-image" onclick="removeExistingImage(this)" 
                            title="Xóa ảnh">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="image-info" style="display: none;">${image.original_name}</div>
            `;
            
            slot.innerHTML = imageHtml;
            slot.classList.add('has-image');
            slot.setAttribute('data-file-name', image.original_name);
            slot.setAttribute('data-image-id', image.image_id);
            slot.setAttribute('data-display-order', image.display_order);
            
            // Đánh dấu ảnh chính
            if (image.is_primary) {
                slot.classList.add('primary-image');
                previewImage.src = `public/uploads/product/${image.image_url}`;
                previewImage.title = image.original_name;
            }
        }
    });
}

// Function để điền thông tin pet-specific và đổ dữ liệu gender
function fillPetSpecificInfo(images) {
    // Reset tất cả select gender, color, age
    resetPetFields();
    
    // Sắp xếp ảnh theo display_order
    images.sort((a, b) => a.display_order - b.display_order);
    
    images.forEach((image, index) => {
        if (image.is_primary) {
            // Đổ dữ liệu cho ảnh chính
            setSelectValue('edit_main_gender', image.gender);
            setInputValue('edit_main_color', image.color);
            setInputValue('edit_main_age', image.age);
        } else {
            // Đổ dữ liệu cho ảnh phụ
            // Tính toán index cho ảnh phụ (bỏ qua ảnh chính)
            const auxiliaryIndex = index + 1; // +1 vì ảnh phụ bắt đầu từ 1
            
            if (auxiliaryIndex <= 4) {
                setSelectValue(`edit_image_gender_${auxiliaryIndex}`, image.gender);
                setInputValue(`edit_image_color_${auxiliaryIndex}`, image.color);
                setInputValue(`edit_image_age_${auxiliaryIndex}`, image.age);
            }
        }
    });
}

// Helper function để reset pet fields
function resetPetFields() {
    // Reset gender selects
    const genderSelects = [
        'edit_main_gender',
        'edit_image_gender_1',
        'edit_image_gender_2', 
        'edit_image_gender_3',
        'edit_image_gender_4'
    ];
    
    genderSelects.forEach(selectId => {
        const select = document.getElementById(selectId);
        if (select) select.value = '';
    });
    
    // Reset color inputs
    const colorInputs = [
        'edit_main_color',
        'edit_image_color_1',
        'edit_image_color_2',
        'edit_image_color_3', 
        'edit_image_color_4'
    ];
    
    colorInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) input.value = '';
    });
    
    // Reset age inputs
    const ageInputs = [
        'edit_main_age',
        'edit_image_age_1',
        'edit_image_age_2',
        'edit_image_age_3',
        'edit_image_age_4'
    ];
    
    ageInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) input.value = '';
    });
}

// Helper function để set giá trị cho select
function setSelectValue(selectId, value) {
    const select = document.getElementById(selectId);
    if (select && value) {
        select.value = value;
    }
}

// Helper function để set giá trị cho input
function setInputValue(inputId, value) {
    const input = document.getElementById(inputId);
    if (input && value) {
        input.value = value;
    }
}

// Function để clear pet fields khi xóa ảnh
function clearPetFieldsForImage(displayOrder) {
    const order = parseInt(displayOrder);
    
    if (order === 1) {
        // Ảnh chính
        setSelectValue('edit_main_gender', '');
        setInputValue('edit_main_color', '');
        setInputValue('edit_main_age', '');
    } else {
        // Ảnh phụ
        const auxiliaryIndex = order;
        if (auxiliaryIndex <= 4) {
            setSelectValue(`edit_image_gender_${auxiliaryIndex}`, '');
            setInputValue(`edit_image_color_${auxiliaryIndex}`, '');
            setInputValue(`edit_image_age_${auxiliaryIndex}`, '');
        }
    }
}

// Cải tiến function updateExistingImages trong PHP để sử dụng display_order đúng cách
function getImageUpdateData(isPet) {
    const updateData = {};
    
    if (isPet) {
        // Lấy dữ liệu cho ảnh chính
        updateData.main = {
            gender: document.getElementById('edit_main_gender')?.value || null,
            color: document.getElementById('edit_main_color')?.value || null,
            age: document.getElementById('edit_main_age')?.value || null
        };
        
        // Lấy dữ liệu cho ảnh phụ
        for (let i = 1; i <= 4; i++) {
            const gender = document.getElementById(`edit_image_gender_${i}`)?.value || null;
            const color = document.getElementById(`edit_image_color_${i}`)?.value || null;
            const age = document.getElementById(`edit_image_age_${i}`)?.value || null;
            
            updateData[`auxiliary_${i}`] = { gender, color, age };
        }
    }
    
    return updateData;
}

// Event listener để theo dõi thay đổi gender/color/age
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for pet fields
    const petFields = [
        'edit_main_gender', 'edit_main_color', 'edit_main_age',
        'edit_image_gender_1', 'edit_image_color_1', 'edit_image_age_1',
        'edit_image_gender_2', 'edit_image_color_2', 'edit_image_age_2',
        'edit_image_gender_3', 'edit_image_color_3', 'edit_image_age_3',
        'edit_image_gender_4', 'edit_image_color_4', 'edit_image_age_4'
    ];
    
    petFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', function() {
                // Có thể thêm logic để validate hoặc update UI
                console.log(`Field ${fieldId} changed to: ${this.value}`);
            });
        }
    });
});

// Function để validate pet fields
function validatePetFields() {
    const slots = document.querySelectorAll('.image-slot_edit_product.has-image');
    
    slots.forEach((slot, index) => {
        const displayOrder = slot.getAttribute('data-display-order');
        const order = parseInt(displayOrder);
        
        if (order === 1) {
            // Ảnh chính - kiểm tra có đầy đủ thông tin không
            const gender = document.getElementById('edit_main_gender')?.value;
            const color = document.getElementById('edit_main_color')?.value;
            const age = document.getElementById('edit_main_age')?.value;
            
            if (!gender || !color || !age) {
                showAlert('warning', 'Vui lòng điền đầy đủ thông tin cho ảnh chính');
            }
        }
    });
}

// Export functions nếu cần
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        loadEditProduct,
        fillProductImages,
        fillPetSpecificInfo,
        removeExistingImage,
        validatePetFields
    };
}

// 4. JAVASCRIPT - Xử lý toggle switch và modal
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('disableProductModal');
    const reasonTextarea = document.getElementById('disableReason');
    const confirmBtn = document.getElementById('confirmDisable');
    const cancelBtn = document.getElementById('cancelDisable');
    const closeBtn = document.querySelector('.close');
    
    let currentToggle = null;
    let currentProductId = null;
    
    // Toggle switch functionality
    const toggleSwitches = document.querySelectorAll('.toggle-switch input');
    toggleSwitches.forEach(toggle => {
        toggle.addEventListener('change', function () {
            const card = this.closest('.product-card');
            const statusSpan = card.querySelector('.product-status');
            const productId = this.dataset.productId;
            
            if (this.checked) {
                // Enable sản phẩm
                updateProductStatus(productId, 1, null, this, statusSpan);
            } else {
                // Disable sản phẩm - hiển thị modal
                currentToggle = this;
                currentProductId = productId;
                showDisableModal();
            }
        });
    });
    
    // Hiển thị modal
    function showDisableModal() {
        modal.style.display = 'block';
        reasonTextarea.value = '';
        reasonTextarea.focus();
    }
    
    // Ẩn modal
    function hideDisableModal() {
        modal.style.display = 'none';
        if (currentToggle) {
            currentToggle.checked = true; // Reset toggle về trạng thái ban đầu
        }
        currentToggle = null;
        currentProductId = null;
    }
    
    // Xử lý confirm disable
    confirmBtn.addEventListener('click', function() {
        const reason = reasonTextarea.value.trim();
        if (!reason) {
            alert('Please provide a reason for disabling this product.');
            return;
        }
        
        const card = currentToggle.closest('.product-card');
        const statusSpan = card.querySelector('.product-status');
        
        updateProductStatus(currentProductId, 0, reason, currentToggle, statusSpan);
        hideDisableModal();
    });
    
    // Xử lý cancel
    cancelBtn.addEventListener('click', hideDisableModal);
    closeBtn.addEventListener('click', hideDisableModal);
    
    // Đóng modal khi click outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            hideDisableModal();
        }
    });
    
    // Hàm cập nhật trạng thái sản phẩm
    function updateProductStatus(productId, isActive, reason, toggle, statusSpan) {
        // Hiển thị loading
        const originalText = statusSpan.textContent;
        statusSpan.textContent = 'Updating...';
        
        const data = {
            product_id: productId,
            is_active: isActive,
            reason: reason
        };
        console.log('Sending:', data);
        fetch('../../../app/controllers/ProductController.php?action=toggleProduct', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (isActive == 1) {
                    statusSpan.textContent = 'Active';
                    statusSpan.className = 'product-status status-active';
                    toggle.checked = true;
                } else {
                    statusSpan.textContent = 'Draft';
                    statusSpan.className = 'product-status status-draft';
                    toggle.checked = false;
                }
                
                // Hiển thị thông báo thành công
                showNotification('Product updated successfully!', 'success');
            } else {
                // Revert lại trạng thái ban đầu
                statusSpan.textContent = originalText;
                toggle.checked = !toggle.checked;
                
                // Hiển thị thông báo lỗi
                showNotification(data.message || 'Failed to update product', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusSpan.textContent = originalText;
            toggle.checked = !toggle.checked;
            showNotification('Network error occurred', 'error');
        });
    }
    
    // Hàm hiển thị thông báo
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
            ${type === 'success' ? 'background-color: #28a745;' : 'background-color: #dc3545;'}
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 100);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-product-btn');
    const deleteDialog = document.getElementById('productDeleteConfirmDialog');
    const productNameSpan = document.getElementById('productDeleteName');
    const confirmBtn = document.getElementById('confirmProductDelete');
    const cancelBtn = document.getElementById('cancelProductDelete');
    const closeBtn = document.querySelector('.product-delete-dialog-close');
    const overlay = document.querySelector('.product-delete-dialog-overlay');
    
    let currentProductId = null;

    // Mở dialog xác nhận
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            currentProductId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            
            productNameSpan.textContent = productName;
            deleteDialog.style.display = 'block';
        });
    });

    // Đóng dialog
    function closeDialog() {
        deleteDialog.style.display = 'none';
        currentProductId = null;
    }

    cancelBtn.addEventListener('click', closeDialog);
    closeBtn.addEventListener('click', closeDialog);
    overlay.addEventListener('click', closeDialog);

    // Xác nhận xóa
    confirmBtn.addEventListener('click', function() {
        if (currentProductId) {
            // Hiển thị loading
            confirmBtn.textContent = 'Deleting...';
            confirmBtn.disabled = true;

            // Gửi request xóa sản phẩm
            fetch('../../../app/controllers/ProductController.php?action=delete&id=' + currentProductId, {
                method: 'GET'
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('successfully')) {
                    // Xóa thành công, reload trang
                    location.reload();
                } else {
                    alert('Error deleting product: ' + data);
                    confirmBtn.textContent = 'Delete';
                    confirmBtn.disabled = false;
                }
            })
            .catch(error => {
                alert('Error deleting product: ' + error);
                confirmBtn.textContent = 'Delete';
                confirmBtn.disabled = false;
            });
        }
    });

    // Đóng dialog khi nhấn ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && deleteDialog.style.display === 'block') {
            closeDialog();
        }
    });
});

// Product Filtering System
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const searchBox = document.querySelector('.search-box');
    const parentCategorySelect = document.querySelector('.Category_Parent');
    const childCategorySelect = document.querySelector('.Category_Child');
    const statusSelect = document.querySelector('.status_filter');
    const searchButton = document.querySelector('.filter-group_btn');
    const productsGrid = document.querySelector('.products-grid');
    
    let allProducts = []; // Store all products for filtering
    let filteredProducts = []; // Store filtered results
    
    // Initialize
    init();
    
    function init() {
        // Load initial data
        loadParentCategories();
        loadAllProducts();
        setupEventListeners();
        setupStatusOptions();
    }
    
    // Setup Event Listeners
    function setupEventListeners() {
        // Search input (debounced)
        let searchTimeout;
        searchBox.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterProducts();
            }, 300);
        });
        
        // Category filters
        parentCategorySelect.addEventListener('change', function() {
            loadChildCategories(this.value);
            filterProducts();
        });
        
        childCategorySelect.addEventListener('change', function() {
            filterProducts();
        });
        
        statusSelect.addEventListener('change', function() {
            filterProducts();
        });
        
        // Search button
        searchButton.addEventListener('click', function() {
            filterProducts();
        });
        
        // Enter key in search box
        searchBox.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterProducts();
            }
        });
    }
    
    // Load Parent Categories
    function loadParentCategories() {
        fetch('../../../app/controllers/ProductController.php?action=getParentCategoryListAjax')
            .then(response => response.json())
            .then(categories => {
                populateParentCategories(categories);
            })
            .catch(error => {
                console.error('Error loading parent categories:', error);
            });
    }
    
    // Populate Parent Categories Dropdown
    function populateParentCategories(categories) {
        parentCategorySelect.innerHTML = '<option value="">All Categories</option>';
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.category_id;
            option.textContent = category.category_name;
            parentCategorySelect.appendChild(option);
        });
    }
    
    // Load Child Categories
    function loadChildCategories(parentId) {
        childCategorySelect.innerHTML = '<option value="">All Sub-categories</option>';
        
        if (!parentId) {
            return;
        }
        
        fetch(`../../../app/controllers/ProductController.php?action=getChildCategories&parent_id=${parentId}`)
            .then(response => response.json())
            .then(categories => {
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.category_id;
                    option.textContent = category.category_name;
                    childCategorySelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading child categories:', error);
            });
    }
    
    // Setup Status Options
    function setupStatusOptions() {
        statusSelect.innerHTML = `
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Draft</option>
        `;
    }
    
    // Load All Products (extract from current DOM)
    function loadAllProducts() {
        const productCards = document.querySelectorAll('.product-card');
        allProducts = Array.from(productCards).map(card => {
            const toggle = card.querySelector('.toggle-switch input');
            const nameElement = card.querySelector('.product-name');
            const categoryTag = card.querySelector('.tag');
            const statusElement = card.querySelector('.product-status');
            
            return {
                element: card,
                id: toggle ? toggle.dataset.productId : '',
                name: nameElement ? nameElement.textContent.toLowerCase() : '',
                category: categoryTag ? categoryTag.textContent : '',
                status: toggle ? (toggle.checked ? '1' : '0') : '0',
                statusText: statusElement ? statusElement.textContent : ''
            };
        });
        filteredProducts = [...allProducts];
    }
    
    // Main Filter Function
    function filterProducts() {
        const searchTerm = searchBox.value.toLowerCase().trim();
        const selectedParentCategory = parentCategorySelect.value;
        const selectedChildCategory = childCategorySelect.value;
        const selectedStatus = statusSelect.value;
        
        filteredProducts = allProducts.filter(product => {
            // Search filter
            const matchesSearch = !searchTerm || 
                product.name.includes(searchTerm) ||
                product.category.toLowerCase().includes(searchTerm) ||
                product.id.includes(searchTerm);
            
            // Status filter
            const matchesStatus = !selectedStatus || product.status === selectedStatus;
            
            // Category filter (simplified - you may need to enhance this based on your data structure)
            let matchesCategory = true;
            if (selectedChildCategory) {
                // If child category is selected, match against it
                matchesCategory = product.category.toLowerCase().includes(getSelectedCategoryText(childCategorySelect).toLowerCase());
            } else if (selectedParentCategory) {
                // If only parent category is selected, match against it
                matchesCategory = product.category.toLowerCase().includes(getSelectedCategoryText(parentCategorySelect).toLowerCase());
            }
            
            return matchesSearch && matchesStatus && matchesCategory;
        });
        
        displayFilteredProducts();
        updateResultsCount();
    }
    
    // Get selected category text
    function getSelectedCategoryText(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        return selectedOption ? selectedOption.textContent : '';
    }
    
    // Display Filtered Products
    function displayFilteredProducts() {
        // Hide all products first
        allProducts.forEach(product => {
            product.element.style.display = 'none';
        });
        
        // Show filtered products
        filteredProducts.forEach(product => {
            product.element.style.display = 'block';
        });
        
        // Show "No results" message if needed
        showNoResultsMessage(filteredProducts.length === 0);
    }
    
    // Show/Hide No Results Message
    function showNoResultsMessage(show) {
        let noResultsMsg = document.querySelector('.no-results-message');
        
        if (show) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'no-results-message';
                noResultsMsg.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your search criteria or filters</p>
                    </div>
                `;
                productsGrid.appendChild(noResultsMsg);
            }
            noResultsMsg.style.display = 'block';
        } else {
            if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        }
    }
    
    // Update Results Count
    function updateResultsCount() {
        let countElement = document.querySelector('.results-count');
        
        if (!countElement) {
            countElement = document.createElement('div');
            countElement.className = 'results-count';
            countElement.style.cssText = `
                margin: 20px 0;
                color: #666;
                font-size: 14px;
            `;
            
            const header = document.querySelector('.products-header');
            if (header) {
                header.parentNode.insertBefore(countElement, header.nextSibling);
            }
        }
        
        const total = allProducts.length;
        const filtered = filteredProducts.length;
        
        if (filtered === total) {
            countElement.textContent = `Showing all ${total} products`;
        } else {
            countElement.textContent = `Showing ${filtered} of ${total} products`;
        }
    }
    
    // Clear All Filters
    function clearAllFilters() {
        searchBox.value = '';
        parentCategorySelect.value = '';
        childCategorySelect.value = '';
        statusSelect.value = '';
        childCategorySelect.innerHTML = '<option value="">All Sub-categories</option>';
        
        filteredProducts = [...allProducts];
        displayFilteredProducts();
        updateResultsCount();
    }
    
    // Add Clear Filters Button
    function addClearFiltersButton() {
        const clearButton = document.createElement('button');
        clearButton.textContent = 'Clear Filters';
        clearButton.className = 'clear-filters-btn';
        clearButton.style.cssText = `
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px 16px;
            border-radius: 4px;
            color: #6c757d;
            cursor: pointer;
            font-size: 14px;
        `;
        
        clearButton.addEventListener('click', clearAllFilters);
        
        const filtersSection = document.querySelector('.filters-section');
        if (filtersSection) {
            filtersSection.appendChild(clearButton);
        }
    }
    
    // Initialize clear filters button
    addClearFiltersButton();
    
    // Advanced Search Function (for future enhancement)
    function advancedSearch(criteria) {
        // This function can be enhanced to handle more complex search criteria
        // like price range, stock level, date ranges, etc.
        console.log('Advanced search criteria:', criteria);
    }
    
    // Export functions for external use if needed
    window.ProductFilter = {
        filterProducts,
        clearAllFilters,
        loadAllProducts,
        advancedSearch
    };
});

// Additional utility functions for product filtering

// Debounce function for search input
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Format product data for filtering
function formatProductForFilter(productElement) {
    const toggle = productElement.querySelector('.toggle-switch input');
    const nameElement = productElement.querySelector('.product-name');
    const categoryTag = productElement.querySelector('.tag');
    const statusElement = productElement.querySelector('.product-status');
    const priceElement = productElement.querySelector('.price-value');
    const stockElement = productElement.querySelector('.stock-text');
    
    return {
        element: productElement,
        id: toggle ? toggle.dataset.productId : '',
        name: nameElement ? nameElement.textContent.toLowerCase() : '',
        category: categoryTag ? categoryTag.textContent : '',
        status: toggle ? (toggle.checked ? '1' : '0') : '0',
        statusText: statusElement ? statusElement.textContent : '',
        price: priceElement ? parseFloat(priceElement.textContent.replace('$', '')) : 0,
        stock: stockElement ? parseInt(stockElement.textContent.match(/\d+/)?.[0] || '0') : 0
    };
}

// Enhanced filtering with multiple criteria
function applyAdvancedFilter(products, criteria) {
    return products.filter(product => {
        let matches = true;
        
        // Text search
        if (criteria.search) {
            const searchTerm = criteria.search.toLowerCase();
            matches = matches && (
                product.name.includes(searchTerm) ||
                product.category.toLowerCase().includes(searchTerm) ||
                product.id.includes(searchTerm)
            );
        }
        
        // Status filter
        if (criteria.status !== undefined && criteria.status !== '') {
            matches = matches && (product.status === criteria.status);
        }
        
        // Price range filter
        if (criteria.minPrice !== undefined) {
            matches = matches && (product.price >= criteria.minPrice);
        }
        
        if (criteria.maxPrice !== undefined) {
            matches = matches && (product.price <= criteria.maxPrice);
        }
        
        // Stock level filter
        if (criteria.inStock !== undefined) {
            if (criteria.inStock) {
                matches = matches && (product.stock > 0);
            } else {
                matches = matches && (product.stock === 0);
            }
        }
        
        return matches;
    });
}

// JavaScript xử lý form nhập kho với tên riêng biệt
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý click button "Nhập kho"
    document.querySelectorAll('.warehouse-entry-product-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            openWarehouseEntryModal(productId);
        });
    });
    
    // Xử lý thay đổi số lượng nhập
    const importQuantityInput = document.getElementById('warehouse_entry_import_quantity');
    if (importQuantityInput) {
        importQuantityInput.addEventListener('input', function() {
            calculateWarehouseEntryNewTotal();
        });
    }
    
    // Xử lý submit form
    const warehouseEntryForm = document.getElementById('warehouseEntryForm');
    if (warehouseEntryForm) {
        warehouseEntryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateWarehouseEntry();
        });
    }
});

function openWarehouseEntryModal(productId) {
    // Lấy thông tin sản phẩm hiện tại
    fetchWarehouseEntryProductInfo(productId);
    document.getElementById('warehouse_entry_product_id').value = productId;
    document.getElementById('warehouseEntryModal').style.display = 'block';
}

function closeWarehouseEntryModal() {
    document.getElementById('warehouseEntryModal').style.display = 'none';
    document.getElementById('warehouseEntryForm').reset();
}

function fetchWarehouseEntryProductInfo(productId) {
    console.log('Fetching product info with ID:', productId);
    
    fetch('../../../app/controllers/get_product_info.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('warehouse_entry_product_name').value = data.product.product_name;
            document.getElementById('warehouse_entry_current_stock').value = data.product.stock_quantity;
        } else {
            alert('Không thể lấy thông tin sản phẩm: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi lấy thông tin sản phẩm');
    });
}

function calculateWarehouseEntryNewTotal() {
    const currentStock = parseInt(document.getElementById('warehouse_entry_current_stock').value) || 0;
    const importQuantity = parseInt(document.getElementById('warehouse_entry_import_quantity').value) || 0;
    const newTotal = currentStock + importQuantity;
    document.getElementById('warehouse_entry_new_total').value = newTotal;
}

function updateWarehouseEntry() {
    const formData = new FormData(document.getElementById('warehouseEntryForm'));
    
    // Simulate successful update
    setTimeout(() => {
        alert('Cập nhật kho thành công!');
        closeWarehouseEntryModal();
        location.reload(); // Uncomment khi sử dụng thực tế
    }, 500);
    
    fetch('../../../app/controllers/update_warehouse.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Cập nhật kho thành công!');
            closeWarehouseEntryModal();
            // Reload trang để cập nhật dữ liệu
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi cập nhật kho');
    });
}

// Đóng modal khi click outside
window.onclick = function(event) {
    const modal = document.getElementById('warehouseEntryModal');
    if (event.target === modal) {
        closeWarehouseEntryModal();
    }
}