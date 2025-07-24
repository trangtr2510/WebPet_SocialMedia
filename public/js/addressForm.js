// Address modal functions
function openAddressModal() {
    document.getElementById('addressModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAddressModal() {
    document.getElementById('addressModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking on overlay
document.getElementById('addressModal').addEventListener('click', function (e) {
    if (e.target === this) {
        closeAddressModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.getElementById('addressModal').classList.contains('active')) {
        closeAddressModal();
    }
});
