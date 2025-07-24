// Active page navigation
document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.querySelectorAll('.nav_link');
    const mobileNavLinks = document.querySelectorAll('.mobile_nav_link');

    // Desktop navigation
    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active'));
            // Add active class to clicked link
            this.classList.add('active');
        });
    });

    // Mobile navigation
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            // Skip if this is the category link (handled separately)
            if (this.textContent.trim().includes('Category')) return;

            // Remove active class from all mobile links
            mobileNavLinks.forEach(l => l.classList.remove('active'));
            // Add active class to clicked link
            this.classList.add('active');
        });
    });
});

// Mobile menu functions
// Fixed Mobile menu functions
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    const overlay = document.querySelector('.mobile_overlay');
    
    console.log('Toggle - mobileMenu:', mobileMenu);
    console.log('Toggle - overlay:', overlay);
    
    if (mobileMenu && overlay) {
        // Reset any transform styles that might interfere
        mobileMenu.style.transform = '';
        mobileMenu.style.left = '';
        
        // Toggle active classes
        mobileMenu.classList.toggle('active');
        overlay.classList.toggle('active');
        
        // Toggle body class for additional control
        document.body.classList.toggle('mobile-menu-open');
        
        console.log('Menu active:', mobileMenu.classList.contains('active'));
    } else {
        console.error('Elements not found!');
    }
}

function closeMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    const overlay = document.querySelector('.mobile_overlay');
    
    console.log('Close - mobileMenu:', mobileMenu);
    console.log('Close - overlay:', overlay);
    
    if (mobileMenu && overlay) {
        // Reset any transform styles that might interfere
        mobileMenu.style.transform = '';
        mobileMenu.style.left = '';
        
        // Remove active classes
        mobileMenu.classList.remove('active');
        overlay.classList.remove('active');
        
        // Remove body class
        document.body.classList.remove('mobile-menu-open');
        
        console.log('Menu closed');
    } else {
        console.error('Elements not found in closeMobileMenu!');
    }
}


// Mobile category dropdown toggle
function toggleMobileCategory(e) {
    e.preventDefault();
    const dropdown = document.getElementById('mobileCategoryDropdown');
    const chevron = e.target.closest('.mobile_nav_link').querySelector('i');

    dropdown.classList.toggle('active');

    // Rotate chevron icon
    if (dropdown.classList.contains('active')) {
        chevron.style.transform = 'rotate(180deg)';
    } else {
        chevron.style.transform = 'rotate(0deg)';
    }
}

// Handle chevron click specifically
document.addEventListener('DOMContentLoaded', function () {
    const chevronIcon = document.querySelector('.mobile_nav_link i.fa-chevron-down');
    if (chevronIcon) {
        chevronIcon.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMobileCategory(e);
        });
    }
});

// Close mobile menu when clicking outside
document.addEventListener('click', function (e) {
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuBtn = document.querySelector('.mobile_menu_btn');

    if (mobileMenu && mobileMenuBtn) {
        if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
            closeMobileMenu();
        }
    }
});

// Handle window resize
window.addEventListener('resize', function () {
    if (window.innerWidth > 768) {
        closeMobileMenu();
    }
});

// Account dropdown functionality
const accountBtn = document.querySelector('.account_btn');
const notifyBox = document.querySelector('.notify_box_login_register');

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

if (cartBtn) {
    cartBtn.addEventListener('click', function (e) {
        e.preventDefault();

        // Nếu đang ở trang con như /views/pages, chuyển đến cart.php tương đối:
        window.location.href = "/WebsitePet/views/pages/cart.php";

        // Hoặc tốt hơn: luôn dùng đường dẫn tuyệt đối từ gốc:
        // window.location.href = "/WebsitePet/views/pages/cart.php";
    });
}


// Auth buttons functionality
const loginBtn = document.querySelector('.auth_btn.login');
const registerBtn = document.querySelector('.auth_btn.register');
const headerElement = document.querySelector('header');

// Kiểm tra xem header có class "header_category" không
const isCategoryPage = headerElement && headerElement.classList.contains('header_category');

// Xác định đường dẫn dựa theo trang hiện tại
const loginUrl = isCategoryPage
    ? "../../views/auth/login_register.php?action=signin"
    : "views/auth/login_register.php?action=signin";

const signupUrl = isCategoryPage
    ? "../../views/auth/login_register.php?action=signup"
    : "views/auth/login_register.php?action=signup";

// Gắn sự kiện cho nút đăng nhập
if (loginBtn) {
    loginBtn.addEventListener('click', (e) => {
        e.preventDefault();
        window.location.href = loginUrl;
    });
}

// Gắn sự kiện cho nút đăng ký
if (registerBtn) {
    registerBtn.addEventListener('click', (e) => {
        e.preventDefault();
        window.location.href = signupUrl;
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