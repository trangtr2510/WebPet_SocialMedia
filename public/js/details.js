var swiper = new Swiper(".mySwiper", {
    loop: true,
    spaceBetween: 10,
    slidesPerView: 4,
    freeMode: true,
    watchSlidesProgress: true,
});
var swiper2 = new Swiper(".mySwiper2", {
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
            updateProductInfo(this.realIndex);
        }
    }
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