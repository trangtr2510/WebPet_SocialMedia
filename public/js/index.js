// Account dropdown functionality
const accountBtn = document.querySelector('.account_btn');
const notifyBox = document.querySelector('.notify_box');

accountBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    notifyBox.classList.toggle('active');
});

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
    if (!accountBtn.contains(e.target) && !notifyBox.contains(e.target)) {
        notifyBox.classList.remove('active');
    }
});

// Sticky navigation on scroll - FIXED VERSION
const nav = document.querySelector('.nav');
const breadcrumb = document.querySelector('.breadcrumb');
const header = document.querySelector('.header');
let isSticky = false;
let navHeight = 0;

// Get navigation height once DOM is loaded
window.addEventListener('load', () => {
    navHeight = nav.offsetHeight;
});

window.addEventListener('scroll', () => {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    // Check if header is category type (short) or main header (full height)
    const isCategory = header.classList.contains('header_category');

    // Different trigger points for different header types
    const triggerPoint = isCategory ? navHeight : header.offsetHeight - navHeight;

    if (scrollTop > triggerPoint) {
        if (!isSticky) {
            nav.classList.add('sticky');
            // Add padding to prevent content jump
            document.body.style.paddingTop = navHeight + 'px';
            isSticky = true;

            // Handle breadcrumb sticky
            if (breadcrumb) {
                breadcrumb.classList.add('sticky');
                // Add extra padding for breadcrumb
                document.body.style.paddingTop = (navHeight + breadcrumb.offsetHeight) + 'px';
            }
        }
    } else {
        if (isSticky) {
            nav.classList.remove('sticky');
            // Remove padding completely
            document.body.style.paddingTop = '0';
            isSticky = false;

            if (breadcrumb) {
                breadcrumb.classList.remove('sticky');
            }
        }
    }
});

// Search functionality
const searchInput = document.querySelector('.search_input');
const searchIcon = document.querySelector('.search_icon');

searchIcon.addEventListener('click', () => {
    const searchValue = searchInput.value.trim();
    if (searchValue) {
        alert(`Tìm kiếm: ${searchValue}`);
    }
});

searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        const searchValue = searchInput.value.trim();
        if (searchValue) {
            alert(`Tìm kiếm: ${searchValue}`);
        }
    }
});

// Cart functionality
const cartBtn = document.querySelector('.cart_btn');
cartBtn.addEventListener('click', () => {
    alert('Mở giỏ hàng');
});

// Auth buttons functionality
const loginBtn = document.querySelector('.auth_btn.login');
const registerBtn = document.querySelector('.auth_btn.register');

if (loginBtn) {
    loginBtn.addEventListener('click', (e) => {
        e.preventDefault();
        // Chuyển đến form đăng nhập
        window.location.href = "views/auth/login_register.php?action=signin";
    });
}

if (registerBtn) {
    registerBtn.addEventListener('click', (e) => {
        e.preventDefault();
        // Chuyển đến form đăng ký
        window.location.href = "views/auth/login_register.php?action=signup";
    });
}

function viewProduct(productId) {
    // Chuyển đến trang chi tiết sản phẩm
    window.location.href = '../../../WebsitePet/views/pages/details.php?id=' + productId;
}

// Ensure DOM is loaded before executing
document.addEventListener('DOMContentLoaded', function () {
    // Mobile menu functions
    window.toggleMobileMenu = function () {
        const mobileMenu = document.getElementById('mobileMenu');
        const overlay = document.querySelector('.mobile_overlay');

        if (mobileMenu && overlay) {
            mobileMenu.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    };

    window.closeMobileMenu = function () {
        const mobileMenu = document.getElementById('mobileMenu');
        const overlay = document.querySelector('.mobile_overlay');

        if (mobileMenu && overlay) {
            mobileMenu.classList.remove('active');
            overlay.classList.remove('active');
        }
    };

    // Add event listeners for hamburger button
    const hamburgerBtn = document.querySelector('.hamburger_btn');
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', function (e) {
            e.preventDefault();
            toggleMobileMenu();
        });
    }

    // Add event listeners for close button
    const closeBtn = document.querySelector('.close_btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeMobileMenu();
        });
    }

    // Add event listeners for overlay
    const overlay = document.querySelector('.mobile_overlay');
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            e.preventDefault();
            closeMobileMenu();
        });
    }

    // Close mobile menu when clicking on nav links
    document.querySelectorAll('.mobile_nav_link').forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });

    // Close mobile menu when pressing Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
        }
    });
});