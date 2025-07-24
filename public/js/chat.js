
// JavaScript
const searchBar = document.querySelector(".users .search input");
const searchBtn = document.querySelector(".users .search button");
const usersList = document.querySelector("#users-list");

searchBtn.onclick = () => {
    // Nếu input đã có class active
    if (searchBar.classList.contains("active")) {
        // Nếu người dùng đang xóa sạch input -> xem như muốn đóng
        if (searchBar.value.trim() === "") {
            searchBar.classList.remove("active");
            searchBtn.classList.remove("active");
        } else {
            // Nếu vẫn có chữ thì chỉ clear input
            searchBar.value = "";
            searchBar.focus();
        }
    } else {
        // Nếu input chưa hiển thị thì hiển thị ra
        searchBar.classList.add("active");
        searchBtn.classList.add("active");
        searchBar.focus();
    }
};

searchBar.onkeyup = ()=>{
    let searchTerm = searchBar.value;
    if(searchTerm != ""){
        searchBar.classList.add("active");
    }
    else{
        searchBar.classList.remove("active");
    }
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "/WebsitePet/app/controllers/SearchController.php", true);
    xhr.onload = ()=>{
        if(xhr.readyState === XMLHttpRequest.DONE){
            if(xhr.status === 200){
                let data = xhr.response;
                usersList.innerHTML = data;
            }
        }
    }
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
    xhr.send("searchTerm=" + encodeURIComponent(searchTerm)); 
}

setInterval(()=>{
    let xhr = new XMLHttpRequest();
    xhr.open("GET", "/WebsitePet/app/controllers/SocialController.php", true);

    xhr.onload = ()=>{
        if(xhr.readyState === XMLHttpRequest.DONE){
            if(xhr.status === 200){
                let data = xhr.response;
                if(!searchBar.classList.contains("active")){
                    usersList.innerHTML = data;
                }
            }
        }
    }
    xhr.send();
}, 500);

const form = document.querySelector(".typing-area"),
inputField = form.querySelector(".input-field"),
sendBtn = form.querySelector("button"),
chatBox = document.querySelector('.chat-box');

form.onsubmit = (e)=>{
    e.preventDefault();
}

sendBtn.onclick = ()=>{
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "/WebsitePet/app/controllers/MessageController.php", true);

    xhr.onload = ()=>{
        if(xhr.readyState === XMLHttpRequest.DONE){
            if(xhr.status === 200){
                inputField.value = "";
            }
        }
    }
    let formData = new FormData(form);
    xhr.send(formData);
}

chatBox.onmouseenter = ()=>{
    chatBox.classList.add("active");
}

chatBox.onmouseenter = ()=>{
    chatBox.classList.remove("active");
}

setInterval(()=>{
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "/WebsitePet/app/controllers/GetChatController.php", true);

    xhr.onload = ()=>{
        if(xhr.readyState === XMLHttpRequest.DONE){
            if(xhr.status === 200){
                let data = xhr.response;
                chatBox.innerHTML = data;
                if(chatBox.classList.contains("active")){
                    scrollToBottom();
                }
            }
        }
    }
    let formData = new FormData(form);
    xhr.send(formData);
}, 500);