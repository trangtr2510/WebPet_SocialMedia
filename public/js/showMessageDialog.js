/**
 * Reusable Message Dialog Component
 * Usage: showMessageDialog(message, type, title)
 * Types: 'success', 'error', 'warning', 'info'
 */

// Enhanced message dialog function - REUSABLE VERSION
window.showMessageDialog = function(message, type = 'info', title = '') {
    console.log('showMessageDialog called:', message, type, title);
    
    // Remove existing dialog if any
    const existingDialog = document.querySelector('.message-dialog-overlay');
    if (existingDialog) {
        existingDialog.remove();
    }

    // Set default titles based on type
    const defaultTitles = {
        success: 'Thành công',
        error: 'Lỗi',
        warning: 'Cảnh báo',
        info: 'Thông báo'
    };

    const dialogTitle = title || defaultTitles[type] || 'Thông báo';
    const iconClass = type === 'success' ? 'fa-check' : 
                     type === 'error' ? 'fa-times' : 
                     type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info';

    // Create dialog HTML với tất cả !important
    const dialogHTML = `
        <div class="message-dialog-overlay" id="messageDialog" style="
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(0, 0, 0, 0.5) !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            z-index: 10000 !important;
            opacity: 0 !important;
            transition: opacity 0.3s ease !important;
        ">
            <div class="message-dialog" style="
                background: white !important;
                border-radius: 12px !important;
                padding: 24px !important;
                max-width: 400px !important;
                width: 90% !important;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
                transform: scale(0.8) !important;
                transition: transform 0.3s ease !important;
                position: relative !important;
            ">
                <div class="message-dialog-header" style="
                    display: flex !important;
                    align-items: center !important;
                    margin-bottom: 16px !important;
                ">
                    <div class="message-dialog-icon ${type}" style="
                        width: 40px !important;
                        height: 40px !important;
                        border-radius: 50% !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        margin-right: 12px !important;
                        background: ${type === 'success' ? '#10B981' : 
                                    type === 'error' ? '#EF4444' : 
                                    type === 'warning' ? '#F59E0B' : '#3B82F6'} !important;
                        color: white !important;
                    ">
                        <i class="fa-solid ${iconClass}"></i>
                    </div>
                    <h3 class="message-dialog-title" style="
                        margin: 0 !important;
                        font-size: 18px !important;
                        font-weight: 600 !important;
                        color: #1F2937 !important;
                    ">${dialogTitle}</h3>
                </div>
                <div class="message-dialog-content">
                    <p class="message-dialog-message" style="
                        margin: 0 0 20px 0 !important;
                        color: #6B7280 !important;
                        line-height: 1.5 !important;
                        font-size: 14px !important;
                    ">${message}</p>
                    <div class="message-dialog-actions" style="
                        display: flex !important;
                        justify-content: flex-end !important;
                    ">
                        <button class="message-dialog-btn primary" onclick="closeMessageDialog()" style="
                            background: #3B82F6 !important;
                            color: white !important;
                            border: none !important;
                            padding: 8px 16px !important;
                            border-radius: 6px !important;
                            cursor: pointer !important;
                            font-weight: 500 !important;
                            transition: background 0.2s ease !important;
                        " onmouseover="this.style.background='#2563EB'" onmouseout="this.style.background='#3B82F6'">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add dialog to body
    document.body.insertAdjacentHTML('beforeend', dialogHTML);

    // Get dialog elements
    const dialog = document.getElementById('messageDialog');
    const dialogContent = dialog.querySelector('.message-dialog');
    
    // Force reflow để đảm bảo CSS được apply
    dialog.offsetHeight;
    
    // Show dialog with animation using setTimeout để đảm bảo timing
    setTimeout(() => {
        dialog.style.opacity = '1';
        dialogContent.style.transform = 'scale(1)';
    }, 10);

    // Auto close after 5 seconds for success messages
    if (type === 'success') {
        setTimeout(() => {
            closeMessageDialog();
        }, 5000);
    }
};

// Close message dialog function
window.closeMessageDialog = function() {
    const dialog = document.getElementById('messageDialog');
    if (dialog) {
        const dialogContent = dialog.querySelector('.message-dialog');
        dialog.style.opacity = '0';
        dialogContent.style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            dialog.remove();
        }, 300);
    }
};

// Event listeners for closing dialog
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('message-dialog-overlay')) {
        closeMessageDialog();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMessageDialog();
    }
});

// Test function để kiểm tra dialog
window.testMessageDialog = function() {
    showMessageDialog('Đây là thông báo thử nghiệm!', 'success', 'Test');
};

// Utility functions for common use cases
window.showSuccessDialog = function(message, title = '') {
    showMessageDialog(message, 'success', title);
};

window.showErrorDialog = function(message, title = '') {
    showMessageDialog(message, 'error', title);
};

window.showWarningDialog = function(message, title = '') {
    showMessageDialog(message, 'warning', title);
};

window.showInfoDialog = function(message, title = '') {
    showMessageDialog(message, 'info', title);
};