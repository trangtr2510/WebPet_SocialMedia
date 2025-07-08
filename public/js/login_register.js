[
    { selector: '.switch-login', url: './login_register.php?action=signin' },
    { selector: '.switch-register', url: './login_register.php?action=signup' }
].forEach(({ selector, url }) => {
  const el = document.querySelector(selector);
  if (el) {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = url;
    });
  }
});


const container = document.getElementById('container');
const overlayCon = document.getElementById('overlayCon');
const overlayBtn = document.getElementById('overlayBtn');

overlayBtn.addEventListener('click', () => {
    container.classList.toggle('right-panel-active');

    overlayBtn.classList.toggle('btnScaled');
    window.requestAnimationFrame(() => {
        overlayBtn.classList.add('btnScaled');
    });
});

// Lấy giá trị action từ URL
const params = new URLSearchParams(window.location.search);
const action = params.get('action');

// Kích hoạt đúng form
if (action === 'signup') {
    container.classList.add('right-panel-active'); // Kích hoạt form đăng ký
} else {
    container.classList.remove('right-panel-active'); // Mặc định là đăng nhập
}

function slugify(str) {
    // Bỏ dấu tiếng Việt
    str = str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    // Chuyển thành chữ thường, bỏ ký tự đặc biệt
    return str.toLowerCase().replace(/[^a-z0-9]/g, '');
}

const fullNameInput = document.getElementById('fullNameInput');
const usernameInput = document.getElementById('username');
const generateBtn = document.getElementById('generateUsername');

// Tự động cập nhật username khi người dùng gõ họ tên
fullNameInput.addEventListener('input', function () {
    const name = fullNameInput.value;
    if (name.trim() !== '') {
        usernameInput.value = slugify(name);
    }
});

// Khi người dùng nhấn nút "Đổi" → cập nhật lại username
generateBtn.addEventListener('click', function () {
    const name = fullNameInput.value;
    if (!name.trim()) {
        alert("Vui lòng nhập họ tên trước.");
        return;
    }
    usernameInput.value = slugify(name);
});