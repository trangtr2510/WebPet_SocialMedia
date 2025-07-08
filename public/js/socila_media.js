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

menuItem.forEach(item => {
    item.addEventListener('click', () => {
        removeActive();
        item.classList.add('active')
        document.querySelector('.notification-box').style.display = 'none'
    })
})

document.querySelector('#Notify-box').addEventListener('click', () => {
    document.querySelector('.notification-box').style.display = 'block'
    document.querySelector('#ntCounter').style.display = 'none'
})

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

// like
document.querySelectorAll('.action-button span:first-child i').forEach(liked => {
    liked.addEventListener('click', () => {
        liked.classList.toggle('liked');
    })
})

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
            fontSize = '13px';
        } else if (size.classList.contains('font-size-3')) {
            fontSize = '16px';
        } else if (size.classList.contains('font-size-4')) {
            fontSize = '19px';
        } else if (size.classList.contains('font-size-5')) {
            fontSize = '22px';
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
        root.style.setProperty('--primary-color-hue', Hue)
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
