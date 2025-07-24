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

    // Thêm sự kiện click cho icon mũi tên quay lại
    if (backArrow) {
        backArrow.addEventListener('click', function () {
            formContainer.style.display = 'none';
            document.body.style.overflow = '';
        });
    }
});