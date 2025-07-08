document.addEventListener('DOMContentLoaded', function() {
    const itemsPerPage = 9;
    let currentPage = 1;
    
    // Get all product cards from HTML
    function getAllProductCards() {
        return document.querySelectorAll('.product_card');
    }
    
    function displayProducts(page = 1) {
        const productCards = getAllProductCards();
        const startIndex = (page - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        
        // Hide all products first
        productCards.forEach(card => {
            card.style.display = 'none';
        });
        
        // Show only products for current page
        for (let i = startIndex; i < endIndex && i < productCards.length; i++) {
            productCards[i].style.display = 'block';
        }
    }
    
    function createPagination() {
        const productCards = getAllProductCards();
        const totalPages = Math.ceil(productCards.length / itemsPerPage);
        const pagination = document.getElementById('pagination');
        
        // Only show pagination if there are more than 9 products
        if (productCards.length <= 9) {
            if (pagination) {
                pagination.innerHTML = '';
            }
            return;
        }
        
        if (!pagination) return;
        
        pagination.innerHTML = '';
        
        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '←';
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                displayProducts(currentPage);
                createPagination();
            }
        };
        pagination.appendChild(prevBtn);
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = i === currentPage ? 'active' : '';
                pageBtn.onclick = () => {
                    currentPage = i;
                    displayProducts(currentPage);
                    createPagination();
                };
                pagination.appendChild(pageBtn);
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'ellipsis';
                ellipsis.textContent = '...';
                pagination.appendChild(ellipsis);
            }
        }
        
        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '→';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.onclick = () => {
            if (currentPage < totalPages) {
                currentPage++;
                displayProducts(currentPage);
                createPagination();
            }
        };
        pagination.appendChild(nextBtn);
    }
    
    function updateProductCount() {
        const productsCount = document.getElementById('productsCount');
        if (productsCount) {
            const productCards = getAllProductCards();
            productsCount.textContent = `${productCards.length} puppies`;
        }
    }
    
    // Initialize the page
    displayProducts(1);
    createPagination();
    updateProductCount();
});