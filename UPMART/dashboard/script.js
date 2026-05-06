/**
 * UPMart Marketplace — Main Logic
 */
document.addEventListener('click', e => {
    const btn = e.target.closest('.buy-btn');
    if (!btn) return;

    e.preventDefault();

    const productId = btn.dataset.productId;
    const sellerId = btn.dataset.sellerId;
    const productName = btn.dataset.productName;

    if (window.openChatUI) {
        window.openChatUI(productId, sellerId, productName);
    }
});

// ─────────────────────────────────────────────
// 1. GLOBAL: fillEditForm (called from inline onclick)
// ─────────────────────────────────────────────
function fillEditForm(product) {
    const form = document.getElementById('product-form');
    if (!form) return;

    form.querySelector('#f-title').value         = product.title       || '';
    form.querySelector('#f-price').value         = product.price       || '';
    form.querySelector('#f-desc').value          = product.description || '';
    form.querySelector('#category-select').value = product.category_id || '';

    // Switch to Edit mode
    const submitBtn = document.getElementById('form-submit-btn');
    const formTitle = document.getElementById('form-title');
    submitBtn.textContent = 'Update Post';
    submitBtn.name        = 'update_post';
    formTitle.textContent = 'Edit Post';

    let idInput = form.querySelector('[name="product_id"]');
    if (!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'product_id';
        form.appendChild(idInput);
    }
    idInput.value = product.product_id;

    document.querySelector('.form-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}


// ─────────────────────────────────────────────
// 2. DOM READY
// ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // ── Selectors ──────────────────────────────
    const el = {
        btnBuyer: document.getElementById('mode-buyer'),
        btnSeller: document.getElementById('mode-seller'),
        viewBuyer: document.getElementById('view-buyer'),
        viewSeller: document.getElementById('view-seller'),
        viewCart: document.getElementById('view-cart'),
        viewTxn: document.getElementById('view-transactions'),
        dynamicLink: document.getElementById('dynamic-link'),
        navMarketplace: document.querySelector('#nav-marketplace'),
        navLinks: document.querySelectorAll('.nav-links li'),
        productSearch: document.getElementById('product-search'),
        socialFeed: document.querySelector('.social-feed'),
        greet: document.getElementById('greeting'),
        subgreet: document.getElementById('sub-greeting'),
        categoryNav: document.getElementById('category-nav'),
        welcomeSection: document.querySelector('.welcome'),
        categorySelect: document.getElementById('category-select'),
        otherCat: document.getElementById('other-category-container'),
        uploadArea: document.getElementById('dropzone-area'),
        fileInput: document.getElementById('file-input'),
        previewCont: document.getElementById('image-preview-container'),
        overlay: document.getElementById('image-overlay'),
        overlayImg: document.getElementById('overlay-img'),
        closeOverlay: document.querySelector('.close-overlay'),
        // Messaging
        msgFloat: document.getElementById('msg-float-btn'),
        msgModal: document.getElementById('msg-modal'),
        closeMsg: document.getElementById('close-msg'),
        msgTitle: document.getElementById('msg-title'),
        msgConvos: document.getElementById('msg-conversations'),
        msgThreadView: document.getElementById('msg-thread-view'),
        msgThread: document.getElementById('msg-thread'),
        msgInput: document.getElementById('msg-input'),
        msgSend: document.getElementById('msg-send'),
        msgBack: document.getElementById('msg-back'),
    };

    // Active messaging context
    let activeProductId = null;
    let activeSellerId = null;
    let msgPollTimer = null;

    // ─────────────────────────────────────────────
    // 3. VIEW MANAGEMENT
    // ─────────────────────────────────────────────
    function hideAllViews() {
        [el.viewBuyer, el.viewSeller, el.viewCart, el.viewTxn,
        el.welcomeSection, el.categoryNav].forEach(v => { if (v) v.style.display = 'none'; });
        el.navLinks.forEach(li => li.classList.remove('active'));
    }

    function switchMode(isSeller) {
        hideAllViews();
        if (el.welcomeSection) el.welcomeSection.style.display = 'block';
        if (el.navMarketplace) el.navMarketplace.classList.add('active');

        if (isSeller) {
            if (el.viewSeller) el.viewSeller.style.display = 'block';
            if (el.categoryNav) el.categoryNav.style.display = 'none';
            // el.greet.innerText    = "Seller Mode";
            el.subgreet.innerText = "Manage your shop and list new products.";
            el.dynamicLink.innerHTML = '<span class="icon">📈</span> My Transactions';
            el.btnSeller.classList.add('active');
            el.btnBuyer.classList.remove('active');
        } else {
            if (el.viewBuyer) el.viewBuyer.style.display = 'block';
            if (el.categoryNav) el.categoryNav.style.display = 'flex';
            // el.greet.innerText    = "Buyer Mode";
            el.subgreet.innerText = "Browse and find the products you need!";
            el.dynamicLink.innerHTML = '<span class="icon">🛍️</span> My Orders';
            el.btnBuyer.classList.add('active');
            el.btnSeller.classList.remove('active');
        }
    }

    // ─────────────────────────────────────────────
    // 4. LIVE FEED (search + filter)
    // ─────────────────────────────────────────────
    function updateFeed() {
        const activePill = document.querySelector('.filter-pill.active');
        const category = activePill ? activePill.getAttribute('data-category') : 'all';
        const search = el.productSearch ? el.productSearch.value : '';

        fetch(`fetch_products.php?category=${category}&search=${encodeURIComponent(search)}`)
            .then(r => r.text())
            .then(html => { if (el.socialFeed) el.socialFeed.innerHTML = html; });
    }

    document.querySelectorAll('.filter-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            updateFeed();
        });
    });

    if (el.productSearch) el.productSearch.addEventListener('input', updateFeed);

    // ─────────────────────────────────────────────
    // 5. BUY ITEM BUTTON (delegated — works after AJAX reload)
    // ─────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.buy-btn');
        if (!btn) return;

        const productId = btn.dataset.productId;
        const sellerId = btn.dataset.sellerId;
        const productName = btn.dataset.productName;

        // Place the order via AJAX
        const fd = new FormData();
        fd.append('place_order', '1');
        fd.append('product_id', productId);
        fd.append('seller_id', sellerId);

        fetch('handle_actions.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Order placed! Chat with the seller below.');
                } else {
                    showToast(data.message || 'Could not place order.');
                }
                // Open messaging popup regardless
                openChat(productId, sellerId, productName);
            })
            .catch(() => {
                // Still open chat even on network error
                openChat(productId, sellerId, productName);
            });
    });

    // ─────────────────────────────────────────────
    // 6. CONFIRM DEAL BUTTON (seller)
    // ─────────────────────────────────────────────
    document.addEventListener('click', e => {
        const btn = e.target.closest('.confirm-deal-btn');
        if (!btn) return;

        if (!confirm('Confirm this deal? The product will be marked as Sold.')) return;

        const fd = new FormData();
        fd.append('confirm_deal', '1');
        fd.append('order_id', btn.dataset.orderId);

        fetch('handle_actions.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Deal confirmed!');
                    btn.closest('tr').querySelector('.status-pill').textContent = 'Completed';
                    btn.closest('tr').querySelector('.status-pill').className = 'status-pill success';
                    btn.replaceWith(Object.assign(document.createElement('span'),
                        { textContent: '✓ Done', style: 'color:#27ae60; font-size:0.8rem;' }));
                }
            });
    });

    // --- MESSAGING LOGIC ---
    function openChat(productId, otherUserId, productName) {
        activeProductId = productId;
        activeSellerId = otherUserId;

        el.msgTitle.textContent = productName;
        el.msgConvos.style.display = 'none';
        el.msgThreadView.style.display = 'flex';
        el.msgModal.classList.add('open');

        loadThread();
        clearInterval(msgPollTimer);
        msgPollTimer = setInterval(loadThread, 3000);
    }

    window.openChatUI = openChat;

    function loadThread() {
        if (!activeProductId || !activeSellerId) return;
        fetch(`handle_actions.php?get_messages=1&product_id=${activeProductId}&other_user=${activeSellerId}`)
            .then(r => r.json())
            .then(msgs => {
                const atBottom = el.msgThread.scrollHeight - el.msgThread.scrollTop <= el.msgThread.clientHeight + 50;
                el.msgThread.innerHTML = '';
                if (!msgs.length) {
                    el.msgThread.innerHTML = '<div class="msg-empty">No messages yet. Say hello!</div>';
                    return;
                }
                msgs.forEach(m => {
                    const bubble = document.createElement('div');
                    bubble.className = `msg-bubble ${m.is_mine ? 'mine' : 'theirs'}`;
                    const time = new Date(m.sent_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    bubble.innerHTML = `${escapeHtml(m.message)}<span class="msg-time">${time}</span>`;
                    el.msgThread.appendChild(bubble);
                });
                if (atBottom) el.msgThread.scrollTop = el.msgThread.scrollHeight;
            });
    }

    function loadConversations() {
        el.msgConvos.innerHTML = '<div style="padding:20px; text-align:center; color:#aaa; font-size:0.85rem;">Loading...</div>';
        el.msgConvos.style.display = 'block';
        el.msgThreadView.style.display = 'none';

        fetch('handle_actions.php?get_conversations=1')
            .then(r => r.json())
            .then(convos => {
                if (!convos.length) {
                    el.msgConvos.innerHTML = '<div style="padding:20px; text-align:center; color:#aaa; font-size:0.85rem;">No conversations yet.</div>';
                    return;
                }
                el.msgConvos.innerHTML = '';
                convos.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'convo-item';
                    div.innerHTML = `
                        <div class="convo-avatar" style="background-image: url('${c.profile_pic}');"></div>
                        <div class="convo-details">
                            <strong>${c.other_user_name}</strong>
                            <span class="convo-product">${c.product_name}</span>
                            <span class="convo-last-msg">${c.last_message}</span>
                        </div>`;
                    div.addEventListener('click', () => openChat(c.product_id, c.other_id, c.product_name));
                    el.msgConvos.appendChild(div);
                });
            });
    }

    function sendMessage() {
        const text = el.msgInput.value.trim();
        if (!text || !activeProductId || !activeSellerId) return;
        const fd = new FormData();
        fd.append('send_message', '1');
        fd.append('product_id', activeProductId);
        fd.append('receiver_id', activeSellerId);
        fd.append('message', text);
        el.msgInput.value = '';
        fetch('handle_actions.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => { if (data.success) loadThread(); });
    }

    // --- EVENT ATTACHMENTS ---
    el.msgSend.addEventListener('click', sendMessage);
    el.msgInput.addEventListener('keydown', e => { if (e.key === 'Enter') sendMessage(); });
    el.msgFloat.addEventListener('click', () => {
        el.msgModal.classList.toggle('open');
        if (el.msgModal.classList.contains('open')) loadConversations();
    });
    el.closeMsg.addEventListener('click', () => {
        el.msgModal.classList.remove('open');
        clearInterval(msgPollTimer);
    });
    el.msgBack.addEventListener('click', () => {
        el.msgThreadView.style.display = 'none';
        el.msgConvos.style.display = 'block';
        el.msgTitle.textContent = 'Messages';
        clearInterval(msgPollTimer);
        activeProductId = null;
        activeSellerId = null;
        loadConversations();
    });

    // ─────────────────────────────────────────────
    // 8. SIDEBAR NAVIGATION
    // ─────────────────────────────────────────────
    if (el.dynamicLink) {
        el.dynamicLink.addEventListener('click', e => {
            e.preventDefault();
            hideAllViews();
            const isSeller = el.btnSeller.classList.contains('active');
            if (isSeller) {
                el.viewTxn.style.display = 'block';
            } else {
                el.viewCart.style.display = 'block';
            }
            el.dynamicLink.parentElement.classList.add('active');
        });
    }

    if (el.navMarketplace) {
        el.navMarketplace.querySelector('a').addEventListener('click', e => {
            e.preventDefault();
            switchMode(el.btnSeller.classList.contains('active'));
        });
    }

    el.btnBuyer.addEventListener('click', () => switchMode(false));
    el.btnSeller.addEventListener('click', () => switchMode(true));

    // ─────────────────────────────────────────────
    // 9. FORM HELPERS
    // ─────────────────────────────────────────────
    if (el.categorySelect) {
        el.categorySelect.addEventListener('change', function () {
            el.otherCat.style.display = (this.value === '4') ? 'block' : 'none';
        });
    }

    if (el.uploadArea) {
        el.uploadArea.addEventListener('click', () => el.fileInput.click());
        el.fileInput.addEventListener('change', e => {
            el.previewCont.innerHTML = '';
            Array.from(e.target.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = ev => {
                    const img = document.createElement('img');
                    img.src = ev.target.result;
                    img.className = 'preview-img';
                    el.previewCont.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // ─────────────────────────────────────────────
    // 10. LIGHTBOX
    // ─────────────────────────────────────────────
    document.addEventListener('click', e => {
        if (e.target.classList.contains('clickable-img')) {
            el.overlayImg.src = e.target.src;
            el.overlay.style.display = 'flex';
        }
    });
    if (el.closeOverlay) el.closeOverlay.addEventListener('click', () => el.overlay.style.display = 'none');
    if (el.overlay) el.overlay.addEventListener('click', e => {
        if (e.target === el.overlay) el.overlay.style.display = 'none';
    });

    // ─────────────────────────────────────────────
    // 11. HELPERS
    // ─────────────────────────────────────────────
    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function showToast(msg) {
        const t = document.createElement('div');
        t.textContent = msg;
        Object.assign(t.style, {
            position: 'fixed', bottom: '100px', right: '30px', background: '#1a1a2e', color: 'white',
            padding: '12px 20px', borderRadius: '12px', fontSize: '0.85rem', fontWeight: '600',
            zIndex: '9999', boxShadow: '0 4px 15px rgba(0,0,0,0.2)', transition: 'opacity 0.4s'
        });
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 3000);
    }

    const postReportModal = document.getElementById('postReportModal');
    const closePostReport = document.getElementById('closePostReportModal');
    const cancelPostReport = document.getElementById('cancelPostReportBtn');
    const postReportForm = document.getElementById('postReportForm');

    window.openPostReportModal = function (productId, productName) {
        document.getElementById('postReportLabel').textContent = 'Listing: ' + productName;
        document.getElementById('postReportType').value = '';
        document.getElementById('postReportDetails').value = '';
        postReportForm.dataset.productId = productId;
        if (postReportModal) postReportModal.style.display = 'flex';
    };

    if (closePostReport) closePostReport.onclick = () => postReportModal.style.display = 'none';
    if (cancelPostReport) cancelPostReport.onclick = () => postReportModal.style.display = 'none';

    if (postReportForm) {
        postReportForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('product_id', postReportForm.dataset.productId);
            formData.append('type', document.getElementById('postReportType').value);
            formData.append('details', document.getElementById('postReportDetails').value);

            fetch('report_handler.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === 'Success') {
                        alert('Thank you. Your report has been submitted.');
                        postReportForm.reset();
                        postReportModal.style.display = 'none';
                    } else {
                        alert('Submission failed: ' + data);
                    }
                })
                .catch(err => console.error('Report Error:', err));
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === postReportModal) postReportModal.style.display = 'none';
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('open_chat')) {
        const userId = urlParams.get('user_id');
        const userName = urlParams.get('name');
        const itemName = urlParams.get('item');
        
        // Trigger the chat UI immediately upon landing on the marketplace
        if (window.openChatUI) {
            window.openChatUI(0, userId, "Match: " + itemName);
        }
    }

});
