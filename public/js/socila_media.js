let swiper = new Swiper(".mySwiper", {
    slidesPerView: 6,
    spaceBetween: 5,
})

// ----Window scroll
window.addEventListener('scroll', () => {
    document.querySelector('.add-post-popup').style.display = 'none'
    document.querySelector('.theme-customize').style.display = 'none'
})

// start aside

let menuItem = document.querySelectorAll('.menu-item');

// active class remove
const removeActive = () => {
    menuItem.forEach(item => {
        item.classList.remove('active')
    })
}
const notifyBox_media = document.querySelector('#Notify-box');
const notificationBox = document.querySelector('.notification-box');
const counter = document.querySelector('#ntCounter');

menuItem.forEach(item => {
    item.addEventListener('click', () => {
        removeActive();
        item.classList.add('active')
        // notificationBox.style.display = 'none'
    })
})

let isShown = false;

// notifyBox_media.addEventListener('click', () => {
//     if (!isShown) {
//         notificationBox.style.display = 'block';
//         counter.style.display = 'none';
//     } else {
//         notificationBox.style.display = 'none';
//     }

//     isShown = !isShown;
// });

// -------- Close
document.querySelectorAll('.close').forEach(AllClose => {
    AllClose.addEventListener('click', () => {
        document.querySelector('.add-post-popup').style.display = 'none'
        document.querySelector('.theme-customize').style.display = 'none'
    })
})

// ----popup add post
document.querySelector('#create-lg').addEventListener('click', () => {
    document.querySelector('.add-post-popup').style.display = 'flex'
})
document.querySelector('#feed-pic-upload').addEventListener('change', () => {
    document.querySelector('#postIMG').src = URL.createObjectURL(document.querySelector('#feed-pic-upload').files[0])
})

// Updated like functionality with AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Like button click handler
    document.querySelectorAll('.like-btn').forEach(likeBtn => {
        likeBtn.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const heartIcon = this.querySelector('i');
            const feed = this.closest('.feed');
            const likeCountSpan = feed.querySelector('.like-count');
            
            // Send AJAX request
            fetch('../../app/controllers/PostController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=like&post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    if (data.liked) {
                        heartIcon.classList.add('liked');
                    } else {
                        heartIcon.classList.remove('liked');
                    }
                    
                    // Update like count
                    likeCountSpan.textContent = data.like_count;
                } else {
                    console.error('Error:', data.message);
                    // Optionally show error message to user
                    alert('Error: ' + (data.message || 'Something went wrong'));
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                alert('Network error occurred. Please try again.');
            });
        });
    });
});

// Optional: Add CSS animation for like effect
function addLikeAnimation(element) {
    element.style.transform = 'scale(1.2)';
    element.style.transition = 'transform 0.1s ease';
    
    setTimeout(() => {
        element.style.transform = 'scale(1)';
    }, 100);
}

let currentPage = 1;
let totalPages = 1;
let isLoading = false;

// Show liked posts overlay
function showLikedPostsOverlay() {
    document.getElementById('like_post-management-overlay').style.display = 'flex';
    loadLikedPosts(1);
}

// Hide liked posts overlay
function hideLikedPostsOverlay() {
    document.getElementById('like_post-management-overlay').style.display = 'none';
}

// Load liked posts
async function loadLikedPosts(page = 1) {
    if (isLoading) return;
    
    isLoading = true;
    currentPage = page;
    
    // Show loading state
    showState('loading');
    
    try {
        const response = await fetch(`../../app/controllers/PostController.php?action=get_liked_posts&page=${page}&limit=12`);
        const data = await response.json();
        
        if (data.success) {
            if (data.posts.length === 0 && page === 1) {
                showState('empty');
            } else {
                displayLikedPosts(data.posts, data.pagination);
                showState('content');
            }
        } else {
            showState('error');
        }
        
    } catch (error) {
        console.error('Error loading liked posts:', error);
        showState('error');
    } finally {
        isLoading = false;
    }
}

// Display liked posts
function displayLikedPosts(posts, pagination) {
    const grid = document.getElementById('liked-posts-grid');
    const statsBar = document.getElementById('liked-posts-stats');
    
    // Update stats
    document.getElementById('total-liked-count').textContent = pagination.total_count;
    document.getElementById('showing-count').textContent = posts.length;
    
    // Clear previous content
    grid.innerHTML = '';
    
    // Create post cards
    posts.forEach(post => {
        const postCard = createLikedPostCard(post);
        grid.appendChild(postCard);
    });
    
    // Update pagination
    updatePagination(pagination);
}

// Create liked post card
function createLikedPostCard(post) {
    const card = document.createElement('div');
    card.className = 'liked-post-card';
    card.innerHTML = `
        <div class="liked-post-header">
            <div class="post-author">
                <img src="${post.author_avatar_path}" alt="Author" class="author-avatar">
                <div class="author-info">
                    <h4>${escapeHtml(post.author_name)}</h4>
                </div>
            </div>
            <div class="post-dates">
                <div>Published: ${post.published_at_formatted}</div>
                <div class="liked-date">❤️ Liked: ${post.liked_at_formatted}</div>
            </div>
        </div>
        
        <div class="liked-post-content">
            ${post.title ? `<h3 class="post-title">${escapeHtml(post.title)}</h3>` : ''}
            
            ${post.featured_image ? `<img src="${post.featured_image_path}" alt="Post image" class="post-image">` : ''}
            
            ${post.content_preview ? `<p class="post-preview">${escapeHtml(post.content_preview)}</p>` : ''}
            
            ${post.tags ? createTagsHTML(post.tags) : ''}
            
            <div class="post-meta">
                <span class="like-count">
                    <i class="fa fa-heart"></i> ${post.like_count} likes
                </span>
                <button class="unlike-btn" onclick="unlikePost(${post.post_id})" data-post-id="${post.post_id}">
                    <i class="fa fa-heart-o"></i> Unlike
                </button>
            </div>
        </div>
    `;
    
    return card;
}

// Create tags HTML
function createTagsHTML(tags) {
    if (!tags) return '';
    
    const tagArray = tags.split(',');
    const tagElements = tagArray.map(tag => 
        `<span class="tag">#${escapeHtml(tag.trim())}</span>`
    ).join('');
    
    return `<div class="post-tags">${tagElements}</div>`;
}

// Unlike a post
async function unlikePost(postId) {
    try {
        const response = await fetch('../../app/controllers/PostController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=like&post_id=${postId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Reload current page to update the list
            loadLikedPosts(currentPage);
            
            // Also update the main feed if visible
            const mainFeedLikeBtn = document.querySelector(`[data-post-id="${postId}"] .like-btn i`);
            if (mainFeedLikeBtn) {
                mainFeedLikeBtn.classList.remove('liked');
                const likeCountSpan = document.querySelector(`[data-post-id="${postId}"] .like-count`);
                if (likeCountSpan) {
                    likeCountSpan.textContent = data.like_count;
                }
            }
        } else {
            alert('Error unliking post: ' + (data.message || 'Unknown error'));
        }
        
    } catch (error) {
        console.error('Error unliking post:', error);
        alert('Network error occurred. Please try again.');
    }
}

// Show different states
function showState(state) {
    const states = ['loading', 'error', 'empty', 'content'];
    
    states.forEach(s => {
        const element = document.getElementById(`liked-posts-${s}`);
        if (element) {
            element.style.display = s === state ? 'block' : 'none';
        }
    });
    
    // Show/hide content elements
    const contentElements = ['liked-posts-grid', 'liked-posts-stats', 'liked-posts-pagination'];
    contentElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.style.display = state === 'content' ? 'block' : 'none';
        }
    });
}

// Update pagination
function updatePagination(pagination) {
    totalPages = pagination.total_pages;
    currentPage = pagination.current_page;
    
    document.getElementById('current-page').textContent = currentPage;
    document.getElementById('total-pages').textContent = totalPages;
    
    // Update button states
    const prevBtn = document.getElementById('prev-page-btn');
    const nextBtn = document.getElementById('next-page-btn');
    
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
}

// Navigation functions
function goToPrevPage() {
    if (currentPage > 1) {
        loadLikedPosts(currentPage - 1);
    }
}

function goToNextPage() {
    if (currentPage < totalPages) {
        loadLikedPosts(currentPage + 1);
    }
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Close overlay events
    document.getElementById('close-liked-posts-overlay').addEventListener('click', hideLikedPostsOverlay);
    
    // Close when clicking outside
    document.getElementById('like_post-management-overlay').addEventListener('click', function(e) {
        if (e.target === this) {
            hideLikedPostsOverlay();
        }
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        const overlay = document.getElementById('like_post-management-overlay');
        if (overlay.style.display === 'flex') {
            if (e.key === 'Escape') {
                hideLikedPostsOverlay();
            } else if (e.key === 'ArrowLeft') {
                goToPrevPage();
            } else if (e.key === 'ArrowRight') {
                goToNextPage();
            }
        }
    });
});

// Add button to show liked posts overlay (add this to your main navigation)
function addLikedPostsButton() {
    // You can add this button to your navigation menu
    const button = document.createElement('button');
    button.innerHTML = '<i class="fa fa-heart"></i> My Likes';
    button.onclick = showLikedPostsOverlay;
    button.className = 'liked-posts-btn';
    
    // Add to your navigation area
    // Example: document.querySelector('.navigation').appendChild(button);
}

document.addEventListener('DOMContentLoaded', () => {
    const bookmarkBtn = document.getElementById('bookmark-button');
    if (bookmarkBtn) {
        bookmarkBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Ngăn chuyển trang nếu có href
            showLikedPostsOverlay();
        });
    }
});



// theme customize
document.querySelector('#theme').addEventListener('click', () => {
    document.querySelector('.theme-customize').style.display = 'flex'
})

// font size
let fontSize = document.querySelectorAll('.choose-size span');

const removeSelectorActive = () => {
    fontSize.forEach(size => {
        size.classList.remove('active')
    })
}

fontSize.forEach(size => {
    size.addEventListener('click', () => {
        let fontSize;
        removeSelectorActive();
        size.classList.toggle('active');

        if (size.classList.contains('font-size-1')) {
            fontSize = '10px';
        } else if (size.classList.contains('font-size-2')) {
            fontSize = '12px';
        } else if (size.classList.contains('font-size-3')) {
            fontSize = '14px';
        } else if (size.classList.contains('font-size-4')) {
            fontSize = '16px';
        } else if (size.classList.contains('font-size-5')) {
            fontSize = '18px';
        }
        // Html fontsize change\
        document.querySelector('html').style.fontSize = fontSize;
        document.querySelector('.wrapper2').style.fontSize = fontSize;
    })
})

// primary color
let colorpallete = document.querySelectorAll('.choose-color span');
var root = document.querySelector(':root');

const removeActiveColor = () => {
    colorpallete.forEach(color => {
        color.classList.remove('active')
    })
}

colorpallete.forEach(color => {
    color.addEventListener('click', () => {
        let primaryHue;
        removeActiveColor();
        color.classList.add('active');

        if (color.classList.contains('color-1')) {
            Hue = 203;
        } else if (color.classList.contains('color-2')) {
            Hue = 52;
        } else if (color.classList.contains('color-3')) {
            Hue = 352;
        } else if (color.classList.contains('color-4')) {
            Hue = 152;
        } else if (color.classList.contains('color-5')) {
            Hue = 202;
        }
        root.style.setProperty('--primary-color-hue-1', Hue)
    })
})

// Background change
let bg1 = document.querySelector('.bg1');
let bg2 = document.querySelector('.bg2');

const changeBg = () => {
    root.style.setProperty('--color-dark-light-theme', darkColorLightTheme);
    root.style.setProperty('--color-light-light-theme', lightColorLightTheme);
    root.style.setProperty('--color-white-light-theme', whiteColorLightTheme);
}

let lightColorLightTheme;
let whiteColorLightTheme;
let darkColorLightTheme;

bg2.addEventListener('click', () => {
    darkColorLightTheme = '95%';
    lightColorLightTheme = '5%';
    whiteColorLightTheme = '10%';

    bg2.classList.add('active');
    bg1.classList.remove('active');

    bgicon();
    changeBg();
})
bg1.addEventListener('click', () => {
    bg1.classList.add('active');
    bg2.classList.remove('active');
    window.location.reload();
})

let menuItemImg = document.querySelectorAll('.menu-item span img');

const bgicon = () => {
    menuItemImg.forEach(icon => {
        icon.classList.add('icon-bg');
    })
}

document.addEventListener('DOMContentLoaded', function () {
    const usersSection = document.getElementById('users-section');
    const chatArea = document.getElementById('chat-area');
    const backBtn = document.getElementById('back-btn');
    const usersList = document.querySelector(".users-list");

    // Function to show chat area
    function showChatArea() {
        if (usersSection && chatArea) {
            usersSection.style.display = 'none';
            chatArea.style.display = 'block';
        }
    }

    // Function to hide chat area
    function hideChatArea() {
        if (usersSection && chatArea) {
            chatArea.style.display = 'none';
            usersSection.style.display = 'block';
        }
    }

    // Click handler for user list
    if (usersList) {
        usersList.addEventListener("click", function (e) {
            const target = e.target.closest("a[data-user]");
            // Chat area info
            const chatAvatar = document.getElementById('chat-avatar');
            const chatName = document.getElementById('chat-name');

            // Khi click vào 1 user
            if (target) {
                e.preventDefault();

                const userId = target.dataset.user;
                const userName = target.querySelector('.details span')?.textContent || '';
                const avatarSrc = target.querySelector('img')?.getAttribute('src') || '';

                console.log("User clicked:", userId);
                const incomingInput = document.querySelector(".recipient_id");
                if (incomingInput) {
                    incomingInput.value = userId;
                }
                // Cập nhật khung chat
                chatName.textContent = userName;
                chatAvatar.setAttribute('src', avatarSrc);

                showChatArea();
            }
        });
    }

    // Back button
    if (backBtn) {
        backBtn.addEventListener('click', function (e) {
            e.preventDefault();
            hideChatArea();
        });
    }

    // Hide chat area ban đầu
    if (chatArea) {
        chatArea.style.display = 'none';
    }
});

// Mobile Message Navigation Handler
document.addEventListener('DOMContentLoaded', function () {
    const messageNavItem = document.querySelector('.mobile_nav_item.message_nav_item a');
    const messagesDiv = document.querySelector('.messages');
    const headerNav = document.querySelector('.header');
    const mainLeft = document.querySelector('.main-left');
    const mainMiddle = document.querySelector('.main-middle');
    const mainRight = document.querySelector('.main-right');
    const mobileOverlay = document.querySelector('.mobile_overlay');
    const mobileMenu = document.querySelector('.mobile_menu');

    // Create back button for mobile message header
    function createMobileMessageHeader() {
        const existingMobileHeader = document.querySelector('.mobile-message-header');
        if (existingMobileHeader) return existingMobileHeader;

        const mobileHeader = document.createElement('div');
        mobileHeader.className = 'mobile-message-header';
        mobileHeader.innerHTML = `
            <div class="mobile-message-header-content">
                <button class="mobile-back-btn">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div class="mobile-message-title">
                    <span>Messages</span>
                </div>
            </div>
        `;

        // Insert after the main header
        headerNav.insertAdjacentElement('afterend', mobileHeader);
        return mobileHeader;
    }

    // Show mobile message view
    function showMobileMessages() {
        console.log('Showing mobile messages...');

        // Create mobile header first
        const mobileHeader = createMobileMessageHeader();
        mobileHeader.style.display = 'block';

        // Update body class for mobile message view
        document.body.classList.add('mobile-message-view');

        // Hide sidebar and posts, show messages
        if (mainLeft) mainLeft.style.display = 'none';
        if (mainMiddle) mainMiddle.style.display = 'none';
        if (mainRight) {
            mainRight.style.display = 'block';
            mainRight.style.width = '100%';
        }

        // Show messages
        if (messagesDiv) {
            messagesDiv.style.display = 'block';
        }

        // Close mobile menu
        closeMobileMenuMessage();
    }

    // Hide mobile message view and return to normal
    function hideMobileMessages() {
        console.log('Hiding mobile messages...');

        // Hide mobile header
        const mobileHeader = document.querySelector('.mobile-message-header');
        if (mobileHeader) {
            mobileHeader.style.display = 'none';
        }

        // Remove body class
        document.body.classList.remove('mobile-message-view');

        // Reset styles for mobile view
        if (window.innerWidth <= 768) {
            if (mainLeft) mainLeft.style.display = 'block';
            if (mainMiddle) mainMiddle.style.display = 'block';
            if (mainRight) mainRight.style.display = 'none';
            if (messagesDiv) messagesDiv.style.display = 'none';
        } else {
            // Desktop view
            if (mainLeft) mainLeft.style.display = 'block';
            if (mainMiddle) mainMiddle.style.display = 'block';
            if (mainRight) mainRight.style.display = 'block';
            if (messagesDiv) messagesDiv.style.display = 'block';
        }
    }

    // Close mobile menu function
    // Fixed Close mobile menu function in message handler
    function closeMobileMenuMessage() {
        const mobileMenu = document.getElementById('mobileMenu') || document.querySelector('.mobile_menu');
        const mobileOverlay = document.querySelector('.mobile_overlay');

        if (mobileMenu) {
            // Reset styles and use the same approach as your CSS
            mobileMenu.style.transform = '';
            mobileMenu.style.left = '';
            mobileMenu.classList.remove('active');
        }
        if (mobileOverlay) {
            mobileOverlay.classList.remove('active');
            // Reset any inline styles
            mobileOverlay.style.opacity = '';
            mobileOverlay.style.visibility = '';
        }
        document.body.classList.remove('mobile-menu-open');
    }

    // Handle message nav item click
    if (messageNavItem) {
        messageNavItem.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('Message nav item clicked');

            // Check if we're in mobile view
            if (window.innerWidth <= 768) {
                showMobileMessages();
            } else {
                // Desktop view - maybe scroll to messages or do something else
                if (messagesDiv) {
                    messagesDiv.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    }

    // Handle back button click
    document.addEventListener('click', function (e) {
        if (e.target.matches('.mobile-back-btn') || e.target.closest('.mobile-back-btn')) {
            e.preventDefault();
            e.stopPropagation();
            hideMobileMessages();
        }
    });

    // Handle window resize to manage mobile/desktop views
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            // Desktop view - reset everything
            hideMobileMessages();
        } else {
            // Mobile view - check if we're in message view
            if (document.body.classList.contains('mobile-message-view')) {
                // Keep message view active
                showMobileMessages();
            }
        }
    });

    // Expose functions globally for potential external use
    window.showMobileMessages = showMobileMessages;
    window.hideMobileMessages = hideMobileMessages;
    window.closeMobileMenuMessage = closeMobileMenuMessage;
});

document.getElementById('post-image').addEventListener('change', function (e) {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';

    if (this.files && this.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            preview.appendChild(img);
        }

        reader.readAsDataURL(this.files[0]);
    }
});

document.getElementById('feed-pic-upload').addEventListener('change', function (e) {
    const preview = document.getElementById('image-preview_popup');
    preview.innerHTML = '';

    if (this.files && this.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            preview.appendChild(img);
        }

        reader.readAsDataURL(this.files[0]);
    }
});


function confirmLogout() {
    // Redirect to logout controller
    window.location.href = '../../app/controllers/LogoutController.php';
}

document.getElementById('post_input').addEventListener('submit', function (e) {
    e.preventDefault(); // Ngăn form reload

    const form = e.target;
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(res => {
            // Kiểm tra loại phản hồi trả về
            const contentType = res.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return res.json(); // OK
            } else {
                return res.text().then(text => {
                    throw new Error("Server did not return JSON:\n" + text);
                });
            }
        })
        .then(data => {
            if (data.success) {
                showSuccessDialog(data.message || 'Tạo bài viết thành công');
                form.reset(); // Xoá nội dung form nếu cần
                document.getElementById('image-preview').innerHTML = '';
            } else {
                showErrorDialog(data.message || 'Tạo bài viết thất bại');
            }
        })
        .catch(err => {
            console.error(err);
            showErrorDialog('Lỗi hệ thống. Vui lòng thử lại sau.');
        });
});

document.addEventListener('DOMContentLoaded', function () {
    const postForm = document.getElementById('postForm');
    const messageDiv = document.getElementById('messageDiv');
    const loadingDiv = document.getElementById('loadingDiv');
    const submitBtn = document.getElementById('submitBtn');
    const popup = document.getElementById('addPostPopup');
    const closeBtn = document.getElementById('closePostPopup');
    const imageUpload = document.getElementById('feed-pic-upload');
    const imagePreview = document.getElementById('image-preview_popup');
    const postIMG = document.getElementById('postIMG');

    // Image preview functionality
    imageUpload.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                postIMG.src = e.target.result;
                postIMG.style.display = 'block';
                postIMG.style.width = '100%';
                postIMG.style.maxWidth = '300px';
                postIMG.style.height = 'auto';
                postIMG.style.borderRadius = '8px';
                postIMG.style.marginTop = '10px';

                imagePreview.innerHTML = '';
                imagePreview.appendChild(postIMG);
            };
            reader.readAsDataURL(file);
        }
    });

    // Close popup
    closeBtn.addEventListener('click', function () {
        popup.style.display = 'none';
        resetForm();
    });

    // Form submission
    postForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(postForm);
        formData.append('action', 'create'); // Đảm bảo gửi action=create

        // Show loading
        loadingDiv.style.display = 'block';
        submitBtn.disabled = true;
        messageDiv.innerHTML = ''; // Optional: nếu không dùng nữa, có thể bỏ dòng này

        // Gửi dữ liệu đến controller
        fetch('../../app/controllers/PostController.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                loadingDiv.style.display = 'none';
                submitBtn.disabled = false;

                if (data.success) {
                    showSuccessDialog(data.message || 'Post created successfully');
                    setTimeout(() => {
                        resetForm();
                        popup.style.display = 'none';
                        if (typeof loadPosts === 'function') {
                            loadPosts();
                        }
                    }, 1500);
                } else {
                    showErrorDialog(data.message || 'Error creating post');
                }
            })
            .catch(error => {
                loadingDiv.style.display = 'none';
                submitBtn.disabled = false;
                console.error('Error:', error);
                showErrorDialog('Network error. Please try again.');
            });
    });


    function resetForm() {
        postForm.reset();
        postIMG.style.display = 'none';
        postIMG.src = '';
        imagePreview.innerHTML = '';
        messageDiv.innerHTML = '';
        loadingDiv.style.display = 'none';
        submitBtn.disabled = false;
    }

    // Show popup function (call this to open the popup)
    window.showAddPostPopup = function () {
        popup.style.display = 'block';
    };
});

// Main Feed JavaScript với Hover Fix
document.addEventListener('DOMContentLoaded', function() {
    
    // Function to show notification
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">
                    ${type === 'success' ? '<i class="fa fa-check-circle"></i>' : 
                      type === 'error' ? '<i class="fa fa-exclamation-circle"></i>' : 
                      '<i class="fa fa-info-circle"></i>'}
                </span>
                <span class="notification-message">${message}</span>
            </div>
        `;
        
        // Add styles if not already present
        if (!document.getElementById('notification-styles')) {
            const styles = document.createElement('style');
            styles.id = 'notification-styles';
            styles.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    padding: 15px 20px;
                    z-index: 10000;
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                    max-width: 400px;
                }
                .notification.show {
                    transform: translateX(0);
                }
                .notification-success {
                    border-left: 4px solid #28a745;
                }
                .notification-error {
                    border-left: 4px solid #dc3545;
                }
                .notification-info {
                    border-left: 4px solid #17a2b8;
                }
                .notification-content {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .notification-icon {
                    font-size: 18px;
                }
                .notification-success .notification-icon {
                    color: #28a745;
                }
                .notification-error .notification-icon {
                    color: #dc3545;
                }
                .notification-info .notification-icon {
                    color: #17a2b8;
                }
            `;
            document.head.appendChild(styles);
        }
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    async function deletePost(postId) {
        console.log('Deleting post with ID:', postId);
        
        try {
            // Show loading state
            const feedElement = document.querySelector(`.management-feed[data-post-id="${postId}"]`);
            if (feedElement) {
                feedElement.style.opacity = '0.5';
                feedElement.style.pointerEvents = 'none';
            }
            
            // Call delete API
            const url = `../../app/controllers/PostController.php?action=single_Customer_delete&id=${postId}`;
            console.log('Delete URL:', url);
            
            const response = await fetch(url, {
                method: 'GET', // Since your controller uses GET with action parameter
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('Delete response status:', response.status);
            
            if (response.ok) {
                // Post deleted successfully
                console.log('Post deleted successfully');
                
                // Remove the post from DOM with animation
                if (feedElement) {
                    feedElement.style.transition = 'all 0.3s ease';
                    feedElement.style.transform = 'translateX(-100%)';
                    feedElement.style.opacity = '0';
                    
                    setTimeout(() => {
                        feedElement.remove();
                        
                        // Check if there are no more posts and show empty state
                        const remainingPosts = document.querySelectorAll('.management-feed');
                        if (remainingPosts.length === 0) {
                            const activeTab = document.querySelector('.tab-button.active');
                            const currentStatus = activeTab ? activeTab.getAttribute('data-status') : 'đã xuất bản';
                            
                            if (postContent) {
                                postContent.innerHTML = `
                                    <div class="no-posts">
                                        <h3>Không có bài viết nào</h3>
                                        <p>Không có bài viết nào trong trạng thái "${currentStatus}"</p>
                                    </div>
                                `;
                            }
                        }
                    }, 300);
                }
                
                // Show success message
                showNotification('Đã xóa bài viết thành công!', 'success');
                
            } else {
                // Handle error response
                const errorText = await response.text();
                console.error('Delete error:', errorText);
                throw new Error('Không thể xóa bài viết');
            }
            
        } catch (error) {
            console.error('Error deleting post:', error);
            
            // Restore post appearance
            const feedElement = document.querySelector(`.management-feed[data-post-id="${postId}"]`);
            if (feedElement) {
                feedElement.style.opacity = '1';
                feedElement.style.pointerEvents = 'auto';
            }
            
            // Show error message
            showNotification('Có lỗi xảy ra khi xóa bài viết: ' + error.message, 'error');
        }
    }


    // Function để init main feed functionality
    function initMainFeedFunctionality() {
        console.log('Initializing main feed functionality...');
        
        // Chỉ target các feed trong main-middle, KHÔNG phải management feeds
        const mainFeeds = document.querySelectorAll('.main-middle .feed:not(.management-feed)');
        console.log('Found main feeds:', mainFeeds.length);
        
        mainFeeds.forEach(feed => {
            const editSpan = feed.querySelector('.edit');
            const editMenu = feed.querySelector('.edit-menu');
            
            if (!editSpan || !editMenu) return;
            
            // Xóa tất cả event listeners cũ (nếu có)
            editSpan.replaceWith(editSpan.cloneNode(true));
            const newEditSpan = feed.querySelector('.edit');
            const newEditMenu = newEditSpan.querySelector('.edit-menu');
            
            // Click handler cho three dots - CHỈ toggle menu, KHÔNG block hover
            newEditSpan.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Main feed three dots clicked');
                
                // Close other menus
                document.querySelectorAll('.main-middle .edit-menu').forEach(menu => {
                    if (menu !== newEditMenu) {
                        menu.style.display = 'none';
                        menu.classList.remove('show');
                    }
                });
                
                // Toggle current menu - sử dụng class thay vì style để không conflict với CSS hover
                if (newEditMenu.classList.contains('show')) {
                    newEditMenu.classList.remove('show');
                    newEditMenu.style.display = 'none';
                } else {
                    newEditMenu.classList.add('show');
                    newEditMenu.style.display = 'block';
                }
            });
            
            // Edit button handler
            const editBtn = newEditMenu.querySelector('.edit-post-btn');
            if (editBtn) {
                editBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Edit button clicked in main feed');
                    const postId = feed.dataset.postId;
                    if (postId) {
                        openEditPostPopup(postId);
                    }
                });
            }
            
            // Delete button handler
            const deleteBtn = newEditMenu.querySelector('.delete-post-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Delete button clicked in main feed');
                    const postId = this.dataset.postId;
                    if (postId && confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
                        deletePost(postId);
                    }
                });
            }
        });
        
        // Close menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.edit')) {
                document.querySelectorAll('.main-middle .edit-menu').forEach(menu => {
                    menu.style.display = 'none';
                    menu.classList.remove('show');
                });
            }
        });
        
        console.log('Main feed functionality initialized');
    }
    
    // Chạy ngay khi DOM ready
    initMainFeedFunctionality();
    
    // Chạy lại khi có feed mới được load
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('feed')) {
                        console.log('New feed detected, reinitializing...');
                        initMainFeedFunctionality();
                    }
                });
            }
        });
    });
    
    const feedsContainer = document.querySelector('.main-middle .feeds');
    if (feedsContainer) {
        observer.observe(feedsContainer, {
            childList: true,
            subtree: true
        });
    }
    
    // Make function available globally
    window.initMainFeedFunctionality = initMainFeedFunctionality;
});

// Fixed Post Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const postManagementOverlay = document.getElementById('post-management-overlay');
    const postManagementTrigger = document.getElementById('post-management-trigger');
    const closePostManagement = document.getElementById('close-post-management');
    const tabButtons = document.querySelectorAll('.tab-button');
    const postContent = document.getElementById('post-manager-content');

    const editPostPopup = document.getElementById('editPostPopup');
    const closeEditPostPopup = document.getElementById('closeEditPostPopup');
    const editPostForm = document.getElementById('editPostForm');
    const editLoadingDiv = document.getElementById('editLoadingDiv');
    const editMessageDiv = document.getElementById('editMessageDiv');
    
    if (editPostPopup) {
        editPostPopup.style.zIndex = '10000'; // Cao hơn post management overlay (thường là 9999)
    }

    // Đóng popup
    closeEditPostPopup.addEventListener('click', function() {
        editPostPopup.style.display = 'none';
        resetEditForm();
    });

    // Đóng popup khi click outside
    editPostPopup.addEventListener('click', function(e) {
        if (e.target === editPostPopup) {
            editPostPopup.style.display = 'none';
            resetEditForm();
        }
    });

    // Event listener cho các nút Edit
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-menu li:first-child') || 
            e.target.closest('.edit-post-btn')) {
            e.preventDefault();
            
            // Lấy post ID từ data attribute hoặc từ element gần nhất
            const feedElement = e.target.closest('.feed');
            const postId = feedElement ? feedElement.dataset.postId : null;
            
            if (postId) {
                openEditPostPopup(postId);
            }
        }
    });

    // Preview ảnh khi chọn file mới
    document.getElementById('edit-feed-pic-upload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('edit-image-preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                    <div class="image-preview-item">
                        <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                        <button type="button" class="remove-image-btn" onclick="removeEditPreviewImage()">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        }
    });

    // Submit form
    editPostForm.addEventListener('submit', function(e) {
        e.preventDefault();
        submitEditPost();
    });

    // Function để mở popup và load dữ liệu post
    async function openEditPostPopup(postId) {
        try {
            editLoadingDiv.style.display = 'flex';
            editPostPopup.style.display = 'flex';
            
            // Fetch post data
            const response = await fetch(`../../app/controllers/PostController.php?action=get&id=${postId}`);
            const result = await response.json();
            
            if (result.success) {
                const post = result.data;
                
                // Populate form với dữ liệu post
                document.getElementById('edit-post-id').value = post.post_id;
                document.getElementById('edit-post-title').value = post.title || '';
                document.getElementById('edit-post-content').value = post.content || '';
                
                // Hiển thị ảnh hiện tại nếu có
                const editImagePreview = document.getElementById('edit-image-preview');
                if (post.featured_image) {
                    editImagePreview.innerHTML = `
                        <div class="current-image">
                            <p style="margin-bottom: 10px; color: #666;">Current image:</p>
                            <img src="../../public/uploads/post/${post.featured_image}" 
                                 alt="Current image" 
                                 style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                        </div>
                    `;
                } else {
                    editImagePreview.innerHTML = '';
                }
                
                editLoadingDiv.style.display = 'none';
            } else {
                throw new Error(result.message || 'Failed to load post data');
            }
        } catch (error) {
            editLoadingDiv.style.display = 'none';
            showNotification('Lỗi khi tải dữ liệu bài viết: ' + error.message, 'error');
        }
    }

    // Function để submit form edit
    async function submitEditPost() {
        try {
            editLoadingDiv.style.display = 'flex';
            const submitBtn = document.getElementById('editSubmitBtn');
            submitBtn.disabled = true;

            const formData = new FormData(editPostForm);
            const postId = document.getElementById('edit-post-id').value;
            
            // Thêm trạng thái "chờ xác nhận" vào form data
            formData.append('status', 'pending');

            const response = await fetch(`../../app/controllers/PostController.php?action=update&id=${postId}`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Bài viết đã được cập nhật và đang chờ xác nhận!', 'success');
                setTimeout(() => {
                    editPostPopup.style.display = 'none';
                    resetEditForm();
                    // Reload trang để hiển thị thay đổi
                    location.reload();
                }, 2000);
            } else {
                throw new Error(result.message || 'Failed to update post');
            }
        } catch (error) {
            showNotification('Lỗi: ' + error.message, 'error');
        } finally {
            editLoadingDiv.style.display = 'none';
            document.getElementById('editSubmitBtn').disabled = false;
        }
    }

    // Function để reset form
    function resetEditForm() {
        editPostForm.reset();
        document.getElementById('edit-image-preview').innerHTML = '';
        editMessageDiv.style.display = 'none';
        editLoadingDiv.style.display = 'none';
    }

    // Function để remove preview image
    window.removeEditPreviewImage = function() {
        document.getElementById('edit-image-preview').innerHTML = '';
        document.getElementById('edit-feed-pic-upload').value = '';
    }

    // Debug: Check if elements exist
    console.log('Elements found:', {
        overlay: !!postManagementOverlay,
        trigger: !!postManagementTrigger,
        close: !!closePostManagement,
        tabs: tabButtons.length,
        content: !!postContent
    });

    // Open post management overlay
    postManagementTrigger?.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Opening post management overlay...');
        
        if (postManagementOverlay) {
            // Fix: Set display first, then add classes
            postManagementOverlay.style.display = 'flex';
            postManagementOverlay.style.visibility = 'visible';
            postManagementOverlay.style.opacity = '1';
            
            // Add show class after a brief delay
            setTimeout(() => {
                postManagementOverlay.classList.add('show');
            }, 10);
            
            // Load default tab (published posts)
            loadUserPosts('đã xuất bản');
        }
    });

    // Close post management overlay
    closePostManagement?.addEventListener('click', function() {
        console.log('Closing post management overlay...');
        if (postManagementOverlay) {
            postManagementOverlay.classList.remove('show');
            postManagementOverlay.style.opacity = '0';
            setTimeout(() => {
                postManagementOverlay.style.display = 'none';
                postManagementOverlay.style.visibility = 'hidden';
            }, 300);
        }
    });

    // Close when clicking outside
    postManagementOverlay?.addEventListener('click', function(e) {
        if (e.target === postManagementOverlay) {
            this.classList.remove('show');
            this.style.opacity = '0';
            setTimeout(() => {
                this.style.display = 'none';
                this.style.visibility = 'hidden';
            }, 300);
        }
    });

    // Tab switching
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            console.log('Switching tab to:', this.getAttribute('data-status'));
            
            // Remove active class from all buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Load posts for selected status
            const status = this.getAttribute('data-status');
            loadUserPosts(status);
        });
    });

    // Function to load user posts by status
    async function loadUserPosts(status) {
        console.log('Loading posts for status:', status);
        
        try {
            if (postContent) {
                postContent.innerHTML = '<div class="loading">Đang tải bài viết...</div>';
            }
            
            // Fix: Use correct URL format
            const url = `../../app/controllers/PostController.php?action=getUserPostsByStatus&status=${encodeURIComponent(status)}`;
            console.log('Fetching URL:', url);
            
            const response = await fetch(url);
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Response error text:', errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            // Try to parse JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', responseText);
                throw new Error('Invalid JSON response');
            }
            
            console.log('Parsed API Response:', result);
            
            if (result.success) {
                console.log('Posts data:', result.posts);
                displayPosts(result.posts, status);
            } else {
                throw new Error(result.message || 'Failed to load posts');
            }
        } catch (error) {
            console.error('Error loading posts:', error);
            if (postContent) {
                postContent.innerHTML = `
                    <div class="error-message">
                        <p>Có lỗi xảy ra khi tải bài viết: ${error.message}</p>
                        <button onclick="window.loadUserPosts('${status}')" class="retry-btn">Thử lại</button>
                    </div>
                `;
            }
        }
    }

    // Function to display posts in feed format
    function displayPosts(posts, status) {
        console.log('Displaying posts:', posts?.length || 0, 'posts for status:', status);
        
        if (!postContent) {
            console.error('Post content element not found');
            return;
        }

        if (!posts || posts.length === 0) {
            postContent.innerHTML = `
                <div class="no-posts">
                    <h3>Không có bài viết nào</h3>
                    <p>Không có bài viết nào trong trạng thái "${status}"</p>
                </div>
            `;
            return;
        }

        let postsHtml = '<div class="feeds management-feeds">';
        
        posts.forEach((post, index) => {
            console.log(`Processing post ${index}:`, post);
            
            const postTime = formatDate(post.published_at || post.created_at);
            const authorAvatar = post.author_avatar || 'default.jpg';
            const authorName = post.author_name || 'Unknown';
            const statusBadge = getStatusBadge(post.status);
            const isPublished = status === 'đã xuất bản';
            
            // Tạo menu items dựa trên trạng thái
            let menuItems = '';
            if (isPublished) {
                // Chỉ hiển thị Delete cho bài đã xuất bản
                menuItems = `
                    <li class="delete-post-btn" data-post-id="${post.post_id}">
                        <i class="fa-solid fa-trash"></i> Delete
                    </li>
                `;
            } else {
                // Hiển thị cả Edit và Delete cho bài chờ duyệt và bị từ chối
                menuItems = `
                    <li class="edit-post-btn">
                        <i class="fa fa-pen"></i> Edit
                    </li>
                    <li class="delete-post-btn" data-post-id="${post.post_id}">
                        <i class="fa-solid fa-trash"></i> Delete
                    </li>
                `;
            }
            
            postsHtml += `
                <div class="feed management-feed" data-post-id="${post.post_id}">
                    <div class="feed-top">
                        <div class="user">
                            <div class="profile-picture">
                                <img src="../../public/uploads/avatar/${authorAvatar}" alt="Avatar" onerror="this.src='../../public/uploads/avatar/default.jpg'">
                            </div>
                            <div class="info">
                                <h3>${escapeHtml(authorName)}</h3>
                                <div class="time text-gry">
                                    <small>${postTime}</small>
                                    ${statusBadge}
                                </div>
                            </div>
                        </div>
                        <span class="edit">
                            <img src="../../public/svg/three-dots.svg" alt="">
                            <ul class="edit-menu">
                                ${menuItems}
                            </ul>
                        </span>
                    </div>
                    
                    ${post.title ? `
                        <div class="feed-title">
                            <h3>${escapeHtml(post.title)}</h3>
                        </div>
                    ` : ''}
                    
                    ${post.content ? `
                        <div class="feed-content">
                            <p>${escapeHtml(post.content).replace(/\n/g, '<br>')}</p>
                        </div>
                    ` : ''}
                    
                    ${post.featured_image ? `
                        <div class="feed-img">
                            <img src="../../public/uploads/post/${post.featured_image}" alt="" loading="lazy" onerror="this.style.display='none'">
                        </div>
                    ` : ''}
                    
                    <div class="action-button">
                        <div class="interaction-button">
                            <span><i class="fa fa-heart"></i></span>
                            <span><i class="fa fa-comment-dots"></i></span>
                            <span><i class="fa-solid fa-share"></i></span>
                        </div>
                        <div class="bookmark">
                            <i class="fa fa-bookmark"></i>
                        </div>
                    </div>
                    
                    <div class="liked-by">
                        <p><b>${post.like_count || 0} people</b> liked this</p>
                    </div>
                    
                    ${post.tags ? `
                        <div class="tags">
                            ${post.tags.split(',').map(tag => 
                                `<span class="hashtag">#${escapeHtml(tag.trim())}</span>`
                            ).join('')}
                        </div>
                    ` : ''}
                    
                    <div class="comments text-gry">
                        View all comments
                    </div>
                </div>
            `;
        });
        
        postsHtml += '</div>';
        console.log('Generated HTML length:', postsHtml.length);
        postContent.innerHTML = postsHtml;
        
        console.log('Posts HTML inserted successfully');
        
        // Initialize edit functionality for newly loaded posts
        initializeEditFunctionality();
    }

    // Function to get status badge
    function getStatusBadge(status) {
        const badges = {
            'đã xuất bản': '<span class="status-badge status-published">Đã xuất bản</span>',
            'chờ xác nhận': '<span class="status-badge status-pending">Chờ duyệt</span>',
            'từ chối': '<span class="status-badge status-rejected">Từ chối</span>'
        };
        return badges[status] || '';
    }

    // Function to format date
    function formatDate(dateString) {
        if (!dateString) return '';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            console.error('Date formatting error:', error);
            return dateString;
        }
    }

    // Function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Function to initialize edit functionality for management posts
    function initializeEditFunctionality() {
        console.log('Initializing edit functionality...');
        
        // Edit post functionality - chỉ hoạt động nếu button tồn tại
        document.querySelectorAll('.management-feed .edit-post-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Ngăn chặn event bubbling
                console.log('Edit button clicked');
                const feedElement = this.closest('.feed');
                const postId = feedElement.dataset.postId;
                if (postId) {
                    console.log('Opening edit for post:', postId);
                    openEditPostPopup(postId);
                }
            });
        });

        // Delete post functionality
        document.querySelectorAll('.management-feed .delete-post-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Ngăn chặn event bubbling
                console.log('Delete button clicked');
                const postId = this.dataset.postId;
                if (postId && confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
                    console.log('Deleting post:', postId);
                    deletePost(postId);
                }
            });
        });

        // Three dots menu toggle - đảm bảo hover effect vẫn hoạt động
        document.querySelectorAll('.management-feed .edit').forEach(editBtn => {
            editBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Ngăn chặn event bubbling
                console.log('Three dots menu clicked');
                
                // Close other menus
                document.querySelectorAll('.edit-menu').forEach(menu => {
                    if (menu !== this.querySelector('.edit-menu')) {
                        menu.style.display = 'none';
                    }
                });
                
                const menu = this.querySelector('.edit-menu');
                if (menu) {
                    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                }
            });

            // Đảm bảo hover effect vẫn hoạt động
            editBtn.addEventListener('mouseenter', function(e) {
                // CSS hover sẽ tự động xử lý, không cần JavaScript
            });

            editBtn.addEventListener('mouseleave', function(e) {
                // CSS hover sẽ tự động xử lý, không cần JavaScript
            });
        });

        // Close menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.edit')) {
                document.querySelectorAll('.edit-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
        
        console.log('Edit functionality initialized');
    }

    // Make loadUserPosts available globally
    window.loadUserPosts = loadUserPosts;

    // Additional debugging function
    window.debugPostManagement = function() {
        console.log('=== Post Management Debug Info ===');
        console.log('Overlay element:', postManagementOverlay);
        console.log('Overlay display:', postManagementOverlay?.style.display);
        console.log('Overlay classes:', postManagementOverlay?.className);
        console.log('Content element:', postContent);
        console.log('Content HTML length:', postContent?.innerHTML?.length || 0);
        console.log('Tab buttons:', tabButtons.length);
        console.log('Active tab:', document.querySelector('.tab-button.active')?.textContent);
    };

    
    // Function to show notification
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">
                    ${type === 'success' ? '<i class="fa fa-check-circle"></i>' : 
                      type === 'error' ? '<i class="fa fa-exclamation-circle"></i>' : 
                      '<i class="fa fa-info-circle"></i>'}
                </span>
                <span class="notification-message">${message}</span>
            </div>
        `;
        
        // Add styles if not already present
        if (!document.getElementById('notification-styles')) {
            const styles = document.createElement('style');
            styles.id = 'notification-styles';
            styles.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    padding: 15px 20px;
                    z-index: 10000;
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                    max-width: 400px;
                }
                .notification.show {
                    transform: translateX(0);
                }
                .notification-success {
                    border-left: 4px solid #28a745;
                }
                .notification-error {
                    border-left: 4px solid #dc3545;
                }
                .notification-info {
                    border-left: 4px solid #17a2b8;
                }
                .notification-content {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .notification-icon {
                    font-size: 18px;
                }
                .notification-success .notification-icon {
                    color: #28a745;
                }
                .notification-error .notification-icon {
                    color: #dc3545;
                }
                .notification-info .notification-icon {
                    color: #17a2b8;
                }
            `;
            document.head.appendChild(styles);
        }
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    async function deletePost(postId) {
        console.log('Deleting post with ID:', postId);
        
        try {
            // Show loading state
            const feedElement = document.querySelector(`.management-feed[data-post-id="${postId}"]`);
            if (feedElement) {
                feedElement.style.opacity = '0.5';
                feedElement.style.pointerEvents = 'none';
            }
            
            // Call delete API
            const url = `../../app/controllers/PostController.php?action=single_Customer_delete&id=${postId}`;
            console.log('Delete URL:', url);
            
            const response = await fetch(url, {
                method: 'GET', // Since your controller uses GET with action parameter
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('Delete response status:', response.status);
            
            if (response.ok) {
                // Post deleted successfully
                console.log('Post deleted successfully');
                
                // Remove the post from DOM with animation
                if (feedElement) {
                    feedElement.style.transition = 'all 0.3s ease';
                    feedElement.style.transform = 'translateX(-100%)';
                    feedElement.style.opacity = '0';
                    
                    setTimeout(() => {
                        feedElement.remove();
                        
                        // Check if there are no more posts and show empty state
                        const remainingPosts = document.querySelectorAll('.management-feed');
                        if (remainingPosts.length === 0) {
                            const activeTab = document.querySelector('.tab-button.active');
                            const currentStatus = activeTab ? activeTab.getAttribute('data-status') : 'đã xuất bản';
                            
                            if (postContent) {
                                postContent.innerHTML = `
                                    <div class="no-posts">
                                        <h3>Không có bài viết nào</h3>
                                        <p>Không có bài viết nào trong trạng thái "${currentStatus}"</p>
                                    </div>
                                `;
                            }
                        }
                    }, 300);
                }
                
                // Show success message
                showNotification('Đã xóa bài viết thành công!', 'success');
                
            } else {
                // Handle error response
                const errorText = await response.text();
                console.error('Delete error:', errorText);
                throw new Error('Không thể xóa bài viết');
            }
            
        } catch (error) {
            console.error('Error deleting post:', error);
            
            // Restore post appearance
            const feedElement = document.querySelector(`.management-feed[data-post-id="${postId}"]`);
            if (feedElement) {
                feedElement.style.opacity = '1';
                feedElement.style.pointerEvents = 'auto';
            }
            
            // Show error message
            showNotification('Có lỗi xảy ra khi xóa bài viết: ' + error.message, 'error');
        }
    }

});

class PostSearch {
    constructor() {
        this.searchInput = document.getElementById('post-search-input');
        this.searchBtn = document.getElementById('search-btn');
        this.clearBtn = document.getElementById('clear-search');
        this.searchInfo = document.getElementById('search-info');
        this.searchResultsText = document.getElementById('search-results-text');
        this.currentKeyword = '';
        this.searchTimeout = null;
        
        this.init();
    }
    
    init() {
        // Tìm kiếm khi nhấn nút search
        this.searchBtn.addEventListener('click', () => {
            this.performSearch();
        });
        
        // Tìm kiếm khi nhấn Enter
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.performSearch();
            }
        });
        
        // Tìm kiếm tự động khi gõ (debounced)
        this.searchInput.addEventListener('input', () => {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.performSearch();
            }, 500); // Đợi 500ms sau khi ngừng gõ
        });
        
        // Xóa tìm kiếm
        this.clearBtn.addEventListener('click', () => {
            this.clearSearch();
        });
        
        // Cập nhật search khi chuyển tab
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                setTimeout(() => {
                    if (this.currentKeyword) {
                        this.performSearch();
                    }
                }, 100);
            });
        });
    }
    
    performSearch() {
        const keyword = this.searchInput.value.trim();
        this.currentKeyword = keyword;
        
        // Hiện/ẩn nút clear
        this.clearBtn.style.display = keyword ? 'flex' : 'none';
        
        // Lấy trạng thái hiện tại
        const activeTab = document.querySelector('.tab-button.active');
        const status = activeTab ? activeTab.dataset.status : 'đã xuất bản';
        
        // Gọi API search
        this.fetchSearchResults(keyword, status, 1);
    }
    
    fetchSearchResults(keyword, status, page = 1) {
        const params = new URLSearchParams({
            action: 'search',
            keyword: keyword,
            status: status,
            page: page
        });
        
        // Hiện loading
        const content = document.getElementById('post-manager-content');
        content.innerHTML = '<div class="loading">Đang tìm kiếm...</div>';
        
        fetch(`../../app/controllers/PostController.php?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.displaySearchResults(data);
                    this.updateSearchInfo(data.search_info, data.pagination);
                } else {
                    content.innerHTML = '<div class="error">Có lỗi xảy ra khi tìm kiếm</div>';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                content.innerHTML = '<div class="error">Có lỗi xảy ra khi tìm kiếm</div>';
            });
    }
    
    displaySearchResults(data) {
        const content = document.getElementById('post-manager-content');
        
        if (data.posts.length === 0) {
            content.innerHTML = '<div class="no-posts">Không tìm thấy bài viết nào</div>';
            return;
        }
        
        // Render posts (sử dụng hàm renderPosts hiện có)
        if (typeof window.renderPosts === 'function') {
            window.renderPosts(data.posts, data.pagination);
        } else {
            // Fallback rendering nếu không có hàm renderPosts
            let postsHtml = '<div class="posts-grid">';
            data.posts.forEach(post => {
                postsHtml += this.renderPostCard(post);
            });
            postsHtml += '</div>';
            
            // Add pagination
            if (data.pagination.total_pages > 1) {
                postsHtml += this.renderPagination(data.pagination);
            }
            
            content.innerHTML = postsHtml;
        }
    }
    
    renderPostCard(post) {
        // Helper function to escape HTML
        const escapeHtml = (text) => {
            if (!text) return '';
            return text.toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#39;");
        };

        // Get author info (assuming post has author data)
        const authorAvatar = post.author_avatar || 'default.jpg';
        const authorName = post.author_name || 'Unknown Author';
        
        // Format post time
        const postTime = post.created_at || '';
        
        // Create status badge
        const statusBadge = post.status ? 
            `<span class="status-badge status-${post.status.replace(/\s+/g, '-')}">${post.status}</span>` : '';
        
        // Menu items (you can customize based on your needs)
        const menuItems = `
            <li><a href="#" onclick="editPost(${post.post_id})">Edit</a></li>
            <li><a href="#" onclick="deletePost(${post.post_id})">Delete</a></li>
        `;

        return `
            <div class="feed management-feed" data-post-id="${post.post_id}">
                <div class="feed-top">
                    <div class="user">
                        <div class="profile-picture">
                            <img src="../../public/uploads/avatar/${authorAvatar}" alt="Avatar" onerror="this.src='../../public/uploads/avatar/default.jpg'">
                        </div>
                        <div class="info">
                            <h3>${escapeHtml(authorName)}</h3>
                            <div class="time text-gry">
                                <small>${postTime}</small>
                                ${statusBadge}
                            </div>
                        </div>
                    </div>
                    <span class="edit">
                        <img src="../../public/svg/three-dots.svg" alt="">
                        <ul class="edit-menu">
                            ${menuItems}
                        </ul>
                    </span>
                </div>
                
                ${post.title ? `
                    <div class="feed-title">
                        <h3>${this.highlightKeyword(escapeHtml(post.title))}</h3>
                    </div>
                ` : ''}
                
                ${(post.content || post.summary) ? `
                    <div class="feed-content">
                        <p>${this.highlightKeyword(escapeHtml(post.content || post.summary)).replace(/\n/g, '<br>')}</p>
                    </div>
                ` : ''}
                
                ${(post.featured_image || post.image) ? `
                    <div class="feed-img">
                        <img src="../../public/uploads/post/${post.featured_image || post.image}" alt="" loading="lazy" onerror="this.style.display='none'">
                    </div>
                ` : ''}
                
                <div class="action-button">
                    <div class="interaction-button">
                        <span><i class="fa fa-heart"></i></span>
                        <span><i class="fa fa-comment-dots"></i></span>
                        <span><i class="fa-solid fa-share"></i></span>
                    </div>
                    <div class="bookmark">
                        <i class="fa fa-bookmark"></i>
                    </div>
                </div>
                
                <div class="liked-by">
                    <p><b>${post.like_count || 0} people</b> liked this</p>
                </div>
                
                ${post.tags ? `
                    <div class="tags">
                        ${post.tags.split(',').map(tag => 
                            `<span class="hashtag">#${escapeHtml(tag.trim())}</span>`
                        ).join('')}
                    </div>
                ` : ''}
                
                <div class="comments text-gry">
                    View all comments
                </div>
            </div>
        `;
    }
    
    renderPagination(pagination) {
        let paginationHtml = '<div class="pagination">';
        
        if (pagination.has_prev) {
            paginationHtml += `<button onclick="postSearch.goToPage(${pagination.current_page - 1})">‹ Trước</button>`;
        }
        
        paginationHtml += `<span>Trang ${pagination.current_page} / ${pagination.total_pages}</span>`;
        
        if (pagination.has_next) {
            paginationHtml += `<button onclick="postSearch.goToPage(${pagination.current_page + 1})">Sau ›</button>`;
        }
        
        paginationHtml += '</div>';
        return paginationHtml;
    }
    
    highlightKeyword(text) {
        if (!this.currentKeyword || !text) return text;
        
        const regex = new RegExp(`(${this.escapeRegex(this.currentKeyword)})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    updateSearchInfo(searchInfo, pagination) {
        if (searchInfo.keyword) {
            this.searchResultsText.textContent = 
                `Tìm thấy ${pagination.total_posts} kết quả cho "${searchInfo.keyword}" trong ${searchInfo.status}`;
            this.searchInfo.style.display = 'block';
        } else {
            this.searchInfo.style.display = 'none';
        }
    }
    
    clearSearch() {
        this.searchInput.value = '';
        this.currentKeyword = '';
        this.clearBtn.style.display = 'none';
        this.searchInfo.style.display = 'none';
        
        // Reload posts without search
        const activeTab = document.querySelector('.tab-button.active');
        if (activeTab) {
            activeTab.click();
        }
    }
    
    goToPage(page) {
        const activeTab = document.querySelector('.tab-button.active');
        const status = activeTab ? activeTab.dataset.status : 'đã xuất bản';
        this.fetchSearchResults(this.currentKeyword, status, page);
    }
}

// Khởi tạo search khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    window.postSearch = new PostSearch();
    
    // Function để khởi tạo edit functionality cho tất cả các feed
    function initializeAllEditFunctionality() {
        console.log('Initializing edit functionality for all feeds...');
        
        // Xóa tất cả event listeners cũ để tránh duplicate
        document.querySelectorAll('.management-feed .edit').forEach(editBtn => {
            // Clone node để remove tất cả event listeners
            const newEditBtn = editBtn.cloneNode(true);
            editBtn.parentNode.replaceChild(newEditBtn, editBtn);
        });
        
        // Khởi tạo lại functionality cho tất cả các feed (bao gồm cả search results)
        document.querySelectorAll('.management-feed .edit').forEach(editBtn => {
            editBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Ngăn chặn event bubbling
                console.log('Three dots menu clicked');
               
                // Close other menus
                document.querySelectorAll('.edit-menu').forEach(menu => {
                    if (menu !== this.querySelector('.edit-menu')) {
                        menu.style.display = 'none';
                    }
                });
               
                const menu = this.querySelector('.edit-menu');
                if (menu) {
                    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                }
            });

            // Hover effects
            editBtn.addEventListener('mouseenter', function(e) {
                // CSS hover sẽ tự động xử lý
            });

            editBtn.addEventListener('mouseleave', function(e) {
                // CSS hover sẽ tự động xử lý
            });
        });

        // Edit post functionality
        document.querySelectorAll('.management-feed .edit-post-btn').forEach(btn => {
            // Remove existing listeners by cloning
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Edit button clicked');
                const feedElement = this.closest('.feed');
                const postId = feedElement.dataset.postId;
                if (postId) {
                    console.log('Opening edit for post:', postId);
                    // Trigger edit post popup
                    openEditPostForSearch(postId);
                }
            });
        });

        // Delete post functionality
        document.querySelectorAll('.management-feed .delete-post-btn').forEach(btn => {
            // Remove existing listeners by cloning
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Delete button clicked');
                const postId = this.dataset.postId;
                if (postId && confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
                    console.log('Deleting post:', postId);
                    // Call delete function
                    deletePostForSearch(postId);
                }
            });
        });

        // Close menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.edit')) {
                document.querySelectorAll('.edit-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
        
        console.log('Edit functionality initialized for all feeds');
    }

    // Khởi tạo ban đầu
    initializeAllEditFunctionality();
    
    // Khởi tạo main feed functionality
    if (typeof initMainFeedFunctionality === 'function') {
        initMainFeedFunctionality();
    }

    // Observer để theo dõi thay đổi DOM
    const observer = new MutationObserver(function(mutations) {
        let shouldReinit = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        // Kiểm tra nếu có feed mới được thêm vào
                        if (node.classList && node.classList.contains('feed')) {
                            shouldReinit = true;
                        }
                        // Hoặc nếu có container chứa feeds
                        else if (node.querySelector && node.querySelector('.feed')) {
                            shouldReinit = true;
                        }
                    }
                });
            }
        });
        
        if (shouldReinit) {
            console.log('New feed content detected, reinitializing edit functionality...');
            // Delay một chút để đảm bảo DOM đã render xong
            setTimeout(() => {
                initializeAllEditFunctionality();
                if (typeof initMainFeedFunctionality === 'function') {
                    initMainFeedFunctionality();
                }
            }, 100);
        }
    });

    // Theo dõi thay đổi trong post content container
    const postContent = document.getElementById('post-manager-content');
    if (postContent) {
        observer.observe(postContent, {
            childList: true,
            subtree: true
        });
    }

    // Theo dõi thay đổi trong main feeds container (nếu có)
    const mainFeedsContainer = document.querySelector('.feeds');
    if (mainFeedsContainer) {
        observer.observe(mainFeedsContainer, {
            childList: true,
            subtree: true
        });
    }

    // Function để edit post (duplicate của function trong post management)
    async function openEditPostForSearch(postId) {
        try {
            const editPostPopup = document.getElementById('editPostPopup');
            const editLoadingDiv = document.getElementById('editLoadingDiv');
            const editPostForm = document.getElementById('editPostForm');
            
            if (!editPostPopup || !editLoadingDiv || !editPostForm) {
                console.error('Edit post popup elements not found');
                showNotificationForSearch('Không tìm thấy form chỉnh sửa bài viết', 'error');
                return;
            }
            
            editLoadingDiv.style.display = 'flex';
            editPostPopup.style.display = 'flex';
            
            // Fetch post data
            const response = await fetch(`../../app/controllers/PostController.php?action=get&id=${postId}`);
            const result = await response.json();
            
            if (result.success) {
                const post = result.data;
                
                // Populate form với dữ liệu post
                document.getElementById('edit-post-id').value = post.post_id;
                document.getElementById('edit-post-title').value = post.title || '';
                document.getElementById('edit-post-content').value = post.content || '';
                
                // Hiển thị ảnh hiện tại nếu có
                const editImagePreview = document.getElementById('edit-image-preview');
                if (post.featured_image) {
                    editImagePreview.innerHTML = `
                        <div class="current-image">
                            <p style="margin-bottom: 10px; color: #666;">Current image:</p>
                            <img src="../../public/uploads/post/${post.featured_image}" 
                                 alt="Current image" 
                                 style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                        </div>
                    `;
                } else {
                    editImagePreview.innerHTML = '';
                }
                
                editLoadingDiv.style.display = 'none';
            } else {
                throw new Error(result.message || 'Failed to load post data');
            }
        } catch (error) {
            const editLoadingDiv = document.getElementById('editLoadingDiv');
            if (editLoadingDiv) editLoadingDiv.style.display = 'none';
            showNotificationForSearch('Lỗi khi tải dữ liệu bài viết: ' + error.message, 'error');
        }
    }

    // Function để delete post (duplicate của function trong post management)
    async function deletePostForSearch(postId) {
        console.log('Deleting post with ID:', postId);
        
        try {
            // Show loading state
            const feedElement = document.querySelector(`.management-feed[data-post-id="${postId}"]`);
            if (feedElement) {
                feedElement.style.opacity = '0.5';
                feedElement.style.pointerEvents = 'none';
            }
            
            // Call delete API
            const url = `../../app/controllers/PostController.php?action=single_Customer_delete&id=${postId}`;
            console.log('Delete URL:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('Delete response status:', response.status);
            
            if (response.ok) {
                // Post deleted successfully
                console.log('Post deleted successfully');
                
                // Remove the post from DOM with animation
                if (feedElement) {
                    feedElement.style.transition = 'all 0.3s ease';
                    feedElement.style.transform = 'translateX(-100%)';
                    feedElement.style.opacity = '0';
                    
                    setTimeout(() => {
                        feedElement.remove();
                        
                        // Check if there are no more posts and show empty state
                        const remainingPosts = document.querySelectorAll('.management-feed');
                        if (remainingPosts.length === 0) {
                            const postContent = document.getElementById('post-manager-content');
                            if (postContent) {
                                postContent.innerHTML = `
                                    <div class="no-posts">
                                        <h3>Không có bài viết nào</h3>
                                        <p>Không có bài viết nào được tìm thấy</p>
                                    </div>
                                `;
                            }
                        }
                    }, 300);
                }
                
                // Show success message
                showNotificationForSearch('Đã xóa bài viết thành công!', 'success');
                
            } else {
                // Handle error response
                const errorText = await response.text();
                console.error('Delete error:', errorText);
                throw new Error('Không thể xóa bài viết');
            }
            
        } catch (error) {
            console.error('Error deleting post:', error);
            
            // Restore post appearance
            const feedElement = document.querySelector(`.management-feed[data-post-id="${postId}"]`);
            if (feedElement) {
                feedElement.style.opacity = '1';
                feedElement.style.pointerEvents = 'auto';
            }
            
            // Show error message
            showNotificationForSearch('Có lỗi xảy ra khi xóa bài viết: ' + error.message, 'error');
        }
    }

    // Function để show notification (duplicate từ post management)
    function showNotificationForSearch(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">
                    ${type === 'success' ? '<i class="fa fa-check-circle"></i>' : 
                      type === 'error' ? '<i class="fa fa-exclamation-circle"></i>' : 
                      '<i class="fa fa-info-circle"></i>'}
                </span>
                <span class="notification-message">${message}</span>
            </div>
        `;
        
        // Add styles if not already present
        if (!document.getElementById('notification-styles')) {
            const styles = document.createElement('style');
            styles.id = 'notification-styles';
            styles.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    padding: 15px 20px;
                    z-index: 10000;
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                    max-width: 400px;
                }
                .notification.show {
                    transform: translateX(0);
                }
                .notification-success {
                    border-left: 4px solid #28a745;
                }
                .notification-error {
                    border-left: 4px solid #dc3545;
                }
                .notification-info {
                    border-left: 4px solid #17a2b8;
                }
                .notification-content {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .notification-icon {
                    font-size: 18px;
                }
                .notification-success .notification-icon {
                    color: #28a745;
                }
                .notification-error .notification-icon {
                    color: #dc3545;
                }
                .notification-info .notification-icon {
                    color: #17a2b8;
                }
            `;
            document.head.appendChild(styles);
        }
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Make function available globally cho PostSearch có thể gọi
    window.reinitializeEditFunctionality = initializeAllEditFunctionality;
});