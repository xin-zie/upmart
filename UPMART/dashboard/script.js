/**
 * UPMart Marketplace - Integrated Logic
 * Combined Marketplace Management, Messaging, and Notifications
 */

// 1. GLOBAL FUNCTIONS (Defined for direct HTML access)
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

    // Inject hidden product_id
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
        closeOverlay: document.querySelector('.close-overlay'),

        // Messaging Elements
        msgModal: document.getElementById('msg-modal'),
        msgConversations: document.getElementById('msg-conversations'),
        msgThreadView: document.getElementById('msg-thread-view'),
        msgTitle: document.getElementById('msg-title'),
        msgInput: document.getElementById('msg-input'),
        msgSendBtn: document.getElementById('msg-send'),
        msgFloatBtn: document.getElementById('msg-float-btn'),
        closeMsg: document.getElementById('close-msg'),
        msgBack: document.getElementById('msg-back')
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
            elements.greet.innerText = "Welcome to UPMart!"; // Cleaned up name for generic use
            elements.subgreet.innerText = "Start exploring our marketplace and discover amazing products!";
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

    /**
     * 5. MESSAGING & NOTIFICATION LOGIC
     */

    // Notification Click Handler
    window.handleNotifClick = function(type, targetId, senderName) {
        // Close notification drawer
        const drawer = document.getElementById('notifDrawer');
        if (drawer) drawer.classList.remove('open');

        // Redirect or Open Chat based on type
        if (type === 'message' || type === 'wish_match') {
            window.openChatUI(0, targetId, senderName);
        } else if (type === 'approval') {
            window.location.href = 'marketplace.php';
        }
    };

    window.openChatUI = function(productId, otherId, title) {
        const msgModal = document.getElementById('msg-modal');
        const threadContainer = document.getElementById('msg-thread');
        
        msgModal.classList.add('open');
        document.getElementById('msg-conversations').style.display = 'none';
        document.getElementById('msg-thread-view').style.display = 'flex';
        document.getElementById('msg-title').innerText = title;

        // Set attributes for the 'Send' button logic
        msgModal.setAttribute('data-active-other', otherId);
        msgModal.setAttribute('data-active-product', productId);

        threadContainer.innerHTML = '<p style="text-align:center; padding:20px; color:#888;">Loading history...</p>';

        fetch(`handle_actions.php?get_messages=1&other_user=${otherId}&product_id=${productId}`)
            .then(res => res.json())
            .then(data => {
                threadContainer.innerHTML = ''; // Clear "Loading" text
                
                if (data.length === 0) {
                    threadContainer.innerHTML = '<p style="text-align:center; margin-top:20px; color:#bbb;">No previous messages.</p>';
                }

                data.forEach(m => {
                    const div = document.createElement('div');
                    // Adds 'mine' class if sent by current user, 'theirs' otherwise
                    div.className = m.is_mine ? 'msg-bubble mine' : 'msg-bubble theirs';
                    div.innerHTML = `
                        <div class="bubble-content">
                            <p>${m.message}</p>
                            <small style="display:block; font-size:0.6rem; margin-top:4px; opacity:0.7;">
                                ${m.sent_at}
                            </small>
                        </div>
                    `;
                    threadContainer.appendChild(div);
                });
                
                // Auto-scroll to the bottom (the most recent message)
                threadContainer.scrollTop = threadContainer.scrollHeight;
            })
            .catch(err => {
                console.error("History Error:", err);
                threadContainer.innerHTML = '<p style="color:red; text-align:center;">Failed to load history.</p>';
            });
    };

    // Load Conversations List
    function loadConversations() {
        if (!elements.msgConversations) return;
        elements.msgConversations.innerHTML = '<p style="padding:20px; color:#888;">Loading chats...</p>';

        fetch('handle_actions.php?get_conversations=1')
            .then(res => res.json())
            .then(data => {
                elements.msgConversations.innerHTML = '';
                if (data.length === 0) {
                    elements.msgConversations.innerHTML = '<p style="padding:20px; color:#888;">No messages yet.</p>';
                    return;
                }
                data.forEach(convo => {
                    const div = document.createElement('div');
                    div.className = 'convo-item';
                    div.innerHTML = `
                        <div style="display:flex; align-items:center; gap:10px; padding:15px; border-bottom:1px solid #eee; cursor:pointer;">
                            <img src="${convo.profile_pic}" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                            <div style="flex:1; overflow:hidden;">
                                <strong style="display:block;">${convo.other_user_name}</strong>
                                <small style="color:maroon;">${convo.product_name}</small>
                                <p style="margin:0; font-size:0.8rem; color:#666; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${convo.last_message}</p>
                            </div>
                        </div>`;
                    div.onclick = () => window.openChatUI(convo.product_id, convo.other_id, convo.other_user_name);
                    elements.msgConversations.appendChild(div);
                });
            });
    }

    // Send Message Handler
    function sendMessage() {
        const message = elements.msgInput.value.trim();
        if (!message) return;

        const otherId = elements.msgModal.getAttribute('data-active-other');
        const productId = elements.msgModal.getAttribute('data-active-product');

        const fd = new FormData();
        fd.append('send_message', '1');
        fd.append('receiver_id', otherId);
        fd.append('product_id', productId);
        fd.append('message', message);

        fetch('handle_actions.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    elements.msgInput.value = '';
                    window.openChatUI(productId, otherId, elements.msgTitle.innerText);
                }
            });
    }

    /**
     * 6. EVENT LISTENERS
     */

    // Buying Logic (Event Delegation)
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('buy-btn')) {
            const productId = e.target.getAttribute('data-id');
            const sellerId = e.target.getAttribute('data-seller');
            const productTitle = e.target.getAttribute('data-title');

            const formData = new FormData();
            formData.append('place_order', '1');
            formData.append('product_id', productId);
            formData.append('seller_id', sellerId);

            fetch('handle_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.openChatUI(productId, sellerId, productTitle);
                    } else {
                        alert(data.message || "Failed to place order.");
                    }
                });
        }
    });

    // Marketplace Switching
    elements.btnBuyer.addEventListener('click', () => switchMode(false));
    elements.btnSeller.addEventListener('click', () => switchMode(true));

    // Search & Filtering
    if (elements.productSearch) elements.productSearch.addEventListener('input', updateFeed);
    
    filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
            filterPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            updateFeed();
        });
    });

    // Sidebar Navigation
    if (elements.dynamicLink) {
        elements.dynamicLink.addEventListener('click', (e) => {
            e.preventDefault();
            hideAllViews();
            if (elements.btnSeller.classList.contains('active')) {
                elements.viewTransactions.style.display = 'block';
            } else {
                elements.viewCart.style.display = 'block';
            }
            elements.dynamicLink.parentElement.classList.add('active');
        });
    }

    // Modal Control (Messages)
    if (elements.msgFloatBtn) {
        elements.msgFloatBtn.onclick = () => {
            elements.msgModal.classList.add('open');
            elements.msgConversations.style.display = 'block';
            elements.msgThreadView.style.display = 'none';
            loadConversations();
        };
    }

    if (elements.closeMsg) elements.closeMsg.onclick = () => elements.msgModal.classList.remove('open');

    if (elements.msgBack) {
        elements.msgBack.onclick = () => {
            elements.msgThreadView.style.display = 'none';
            elements.msgConversations.style.display = 'block';
            loadConversations();
        };
    }

    if (elements.msgSendBtn) elements.msgSendBtn.onclick = sendMessage;
    if (elements.msgInput) {
        elements.msgInput.onkeypress = (e) => { if (e.key === 'Enter') sendMessage(); };
    }

    // Image & Lightbox Logic
    if (elements.uploadArea) elements.uploadArea.onclick = () => elements.fileInput.click();
    
    if (elements.fileInput) {
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

    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('clickable-img')) {
            elements.overlayImg.src = e.target.src;
            elements.overlay.style.display = 'flex';
        }
    });

    if (elements.closeOverlay) elements.closeOverlay.onclick = () => elements.overlay.style.display = 'none';
});