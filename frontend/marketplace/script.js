/**
 * UPMart Marketplace - Main Logic
 */

// 1. GLOBAL FUNCTIONS (Defined outside so HTML onclick can find them)
function fillEditForm(product) {
    const form = document.querySelector('.form-section form');
    if (!form) return;

    // Map data to inputs
    form.querySelector('[name="title"]').value = product.title;
    form.querySelector('[name="price"]').value = product.price;
    form.querySelector('[name="description"]').value = product.description;
    form.querySelector('[name="category_id"]').value = product.category_id;
    
    // Change the button to "Update" mode
    const submitBtn = form.querySelector('.post-btn');
    submitBtn.innerText = "Update Listing";
    submitBtn.name = "update_post";

    // Inject hidden product_id so the backend knows which one to fix
    let idInput = form.querySelector('[name="product_id"]');
    if (!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'product_id';
        form.appendChild(idInput);
    }
    idInput.value = product.product_id;

    // Scroll smoothly to form
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', () => {
    /**
     * 2. SELECTORS & INITIALIZATION
     */
    const elements = {
        // Mode Switchers
        btnBuyer: document.getElementById('mode-buyer'),
        btnSeller: document.getElementById('mode-seller'),
        
        // Views
        viewBuyer: document.getElementById('view-buyer'),
        viewSeller: document.getElementById('view-seller'),
        viewCart: document.getElementById('view-cart'),
        viewTransactions: document.getElementById('view-transactions'),
        
        // Navigation & Global UI
        dynamicLink: document.getElementById('dynamic-link'),
        navMarketplace: document.querySelector('.nav-links li:nth-child(2)'),
        navLinks: document.querySelectorAll('.nav-links li'),
        productSearch: document.getElementById('product-search'),
        socialFeed: document.querySelector('.social-feed'),
        
        // Text/Header Elements
        greet: document.getElementById('greeting'),
        subgreet: document.getElementById('sub-greeting'),
        categoryNav: document.getElementById('category-nav'),
        welcomeSection: document.querySelector('.welcome'),
        
        // Product Listing Form
        categorySelect: document.getElementById('category-select'),
        otherCategoryContainer: document.getElementById('other-category-container'),
        uploadArea: document.getElementById('dropzone-area'),
        fileInput: document.getElementById('file-input'),
        previewContainer: document.getElementById('image-preview-container'),
        
        // Lightbox
        overlay: document.getElementById('image-overlay'),
        overlayImg: document.getElementById('overlay-img'),
        closeOverlay: document.querySelector('.close-overlay')
    };

    /**
     * 3. VIEW MANAGEMENT
     */
    function hideAllViews() {
        const views = [
            elements.viewBuyer, elements.viewSeller, 
            elements.viewCart, elements.viewTransactions, 
            elements.welcomeSection, elements.categoryNav
        ];
        views.forEach(el => { if(el) el.style.display = 'none'; });
        elements.navLinks.forEach(li => li.classList.remove('active'));
    }

    function switchMode(isSeller) {
        hideAllViews();
        if (elements.welcomeSection) elements.welcomeSection.style.display = 'block';
        if (elements.navMarketplace) elements.navMarketplace.classList.add('active');

        if (isSeller) {
            if (elements.viewSeller) elements.viewSeller.style.display = 'block';
            if (elements.categoryNav) elements.categoryNav.style.display = 'none'; 
            elements.greet.innerText = "Seller Mode";
            elements.subgreet.innerText = "Manage your shop and list new products.";
            elements.dynamicLink.innerHTML = '<span class="icon">📈</span> My Transactions';
            elements.btnSeller.classList.add('active');
            elements.btnBuyer.classList.remove('active');
        } else {
            if (elements.viewBuyer) elements.viewBuyer.style.display = 'block';
            if (elements.categoryNav) elements.categoryNav.style.display = 'flex'; 
            elements.greet.innerText = "Buyer Mode";
            elements.subgreet.innerText = "Browse and find the products you need!";
            elements.dynamicLink.innerHTML = '<span class="icon">🛍️</span> My Cart';
            elements.btnBuyer.classList.add('active');
            elements.btnSeller.classList.remove('active');
        }
    }

    /**
     * 4. LIVE FEED LOGIC (SEARCH & FILTER)
     */
    function updateFeed() {
        const activePill = document.querySelector('.filter-pill.active');
        const categoryId = activePill ? activePill.getAttribute('data-category') : 'all';
        const searchTerm = elements.productSearch ? elements.productSearch.value : '';
        
        fetch(`fetch_products.php?category=${categoryId}&search=${encodeURIComponent(searchTerm)}`)
            .then(res => res.text())
            .then(data => {
                if (elements.socialFeed) elements.socialFeed.innerHTML = data;
            });
    }

    // Initialize Filter Pills
    const filterPills = document.querySelectorAll('.filter-pill');
    filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
            filterPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            updateFeed();
        });
    });

    if (elements.productSearch) {
        elements.productSearch.addEventListener('input', updateFeed);
    }

    /**
     * 5. UI INTERACTION & MEDIA
     */

    // Sidebar Navigation
    if (elements.dynamicLink) {
        elements.dynamicLink.addEventListener('click', (e) => {
            e.preventDefault();
            hideAllViews();
            const isSellerMode = elements.btnSeller.classList.contains('active');
            if (isSellerMode) {
                elements.viewTransactions.style.display = 'block';
            } else {
                elements.viewCart.style.display = 'block';
            }
            elements.dynamicLink.parentElement.classList.add('active');
        });
    }

    if (elements.navMarketplace) {
        elements.navMarketplace.addEventListener('click', (e) => {
            e.preventDefault();
            switchMode(elements.btnSeller.classList.contains('active'));
        });
    }

    elements.btnBuyer.addEventListener('click', () => switchMode(false));
    elements.btnSeller.addEventListener('click', () => switchMode(true));

    // Form: Category "Others" Toggle
    if (elements.categorySelect) {
        elements.categorySelect.addEventListener('change', function() {
            elements.otherCategoryContainer.style.display = (this.value === "4") ? 'block' : 'none';
        });
    }

    // Form: Image Selection Preview
    if (elements.uploadArea) {
        elements.uploadArea.onclick = () => elements.fileInput.click();
        elements.fileInput.onchange = (e) => {
            elements.previewContainer.innerHTML = ''; 
            Array.from(e.target.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    const img = document.createElement('img');
                    img.src = ev.target.result;
                    img.className = 'preview-img'; 
                    elements.previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        };
    }

    // Lightbox Functionality
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('clickable-img')) {
            elements.overlayImg.src = e.target.src;
            elements.overlay.style.display = 'flex';
        }
    });

    if (elements.closeOverlay) elements.closeOverlay.onclick = () => elements.overlay.style.display = 'none';
    if (elements.overlay) elements.overlay.onclick = (e) => {
        if (e.target === elements.overlay) elements.overlay.style.display = 'none';
    };
});
