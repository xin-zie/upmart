document.addEventListener('DOMContentLoaded', () => {
    // 1. Element Selectors
    const logoutBtn = document.querySelector('.logout-btn');
    const postBtn = document.getElementById('postBtn');
    const bulletinInput = document.getElementById('bulletinText');
    const bulletinList = document.getElementById('bulletinList');
    const notifDrawer = document.getElementById('notifDrawer');
    const notifTrigger = document.getElementById('notifTrigger');
    const closeNotifBtn = document.getElementById('closeNotifBtn');

    // Modals
    const reportModal = document.getElementById('reportModal');
    const reportClose = document.getElementById('closeModal');
    const reportCancel = document.getElementById('cancelBtn');
    const reportForm = document.getElementById('reportForm');

    const wishModal = document.getElementById('wishModal');
    const addWishBtn = document.querySelector('.add-wish-btn');
    const closeWishModal = document.getElementById('closeWishModal');
    const wishForm = document.getElementById('wishForm');

    // Reporting specific elements
    const nameInput = document.getElementById('reported_name_input');
    const dataList = document.getElementById('user_suggestions');
    const hiddenIdInput = document.getElementById('report_seller_id');

    // 2. Navigation & Logout
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm("Are you sure you want to logout of UPMart?")) {
                window.location.href = "logout.php";
            }
        });
    }

    // Notification Toggle
    if (notifTrigger && notifDrawer) {
        notifTrigger.addEventListener('click', () => notifDrawer.classList.toggle('open'));
        if (closeNotifBtn) closeNotifBtn.addEventListener('click', () => notifDrawer.classList.remove('open'));

        document.addEventListener('click', (e) => {
            if (!notifDrawer.contains(e.target) && !notifTrigger.contains(e.target)) {
                notifDrawer.classList.remove('open');
            }
        });
    }

    // 3. Bulletin Logic
    function loadBulletin() {
        fetch('bulletin_controller.php?action=fetch')
            .then(res => res.text())
            .then(data => { if (bulletinList) bulletinList.innerHTML = data; })
            .catch(err => console.error("Bulletin Error:", err));
    }

    if (postBtn) {
        postBtn.addEventListener('click', () => {
            const message = bulletinInput.value.trim();
            if (!message) return;

            const forbiddenWords = ["spam", "fuck", "nigga", "sex", "tangina", "bobo"];
            if (forbiddenWords.some(word => message.toLowerCase().includes(word))) {
                alert("Your post contains restricted language.");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'post');
            formData.append('message', message);

            fetch('bulletin_controller.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === "Success") {
                        bulletinInput.value = "";
                        loadBulletin();
                    }
                });
        });
    }

    // 4. Wishlist Logic
    function loadWishes() {
        fetch('wishlist_controller.php?action=fetch')
            .then(res => res.text())
            .then(data => {
                const wishGrid = document.querySelector('.wish-grid');
                if (wishGrid) wishGrid.innerHTML = data;
            })
            .catch(err => console.error("Wishlist Error:", err));
    }

    // Unified Match Handler
    window.handleMatch = function (wishId) {
        if (!confirm("Confirm that you have this item? This will notify the requester.")) return;

        const fd = new FormData();
        fd.append('action', 'match_wish');
        fd.append('wish_id', wishId);

        fetch('wishlist_controller.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(`Match sent! You can now find this in your messages.`);
                    if (typeof openChat === "function") {
                        openChat(0, data.requester_id, "Wish: " + data.item_name);
                    }
                } else {
                    alert(data.message || "Could not send match.");
                }
            })
            .catch(err => console.error("Match Error:", err));
    };

    if (addWishBtn) {
        addWishBtn.addEventListener('click', () => { wishModal.style.display = 'flex'; });
    }

    const hideWishModal = () => {
        wishModal.style.display = 'none';
        if (wishForm) wishForm.reset();
    };

    if (closeWishModal) closeWishModal.onclick = hideWishModal;

    if (wishForm) {
        wishForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(wishForm);
            formData.append('action', 'add');

            fetch('wishlist_controller.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === "Success") {
                        hideWishModal();
                        loadWishes();
                    } else {
                        alert("Error: " + data);
                    }
                });
        });
    }

    // --- 5. Unified Report Modal Logic ---
    window.openReportModal = function(sellerId, sellerName, productId) {
        if (nameInput) nameInput.value = sellerName || "";
        if (hiddenIdInput) hiddenIdInput.value = sellerId || ""; 
        const prodField = document.getElementById('report_product_id');
        if (prodField) prodField.value = productId || 0;

        if (reportModal) reportModal.style.display = 'flex';
    };

    // Consolidated Search & ID Capture
    if (nameInput) {
        // 1. Fetch suggestions as the user types
        nameInput.addEventListener('input', function(e) {
            const query = this.value;
            if (query.length < 1) return; 

            fetch(`search_user.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(users => {
                    dataList.innerHTML = ''; 
                    users.forEach(user => {
                        const option = document.createElement('option');
                        // The 'value' is what the user sees/types
                        option.value = user.full_name;
                        // The 'data-id' is what the database needs
                        option.setAttribute('data-id', user.user_id);
                        dataList.appendChild(option);
                    });

                    // 2. IMMEDIATE CHECK: 
                    // If the user just clicked a suggestion, sync the ID immediately
                    const selectedOption = Array.from(dataList.options).find(opt => opt.value === query);
                    if (selectedOption) {
                        const userId = selectedOption.getAttribute('data-id');
                        if (hiddenIdInput) hiddenIdInput.value = userId;
                        console.log("ID Synced on Input:", userId);
                    }
                })
                .catch(err => console.error("Search fetch error:", err));
        });

        // 3. SECONDARY CHECK:
        // This catches cases where the user leaves the field or clicks away
        nameInput.addEventListener('change', function() {
            const options = dataList.options;
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === this.value) {
                    const userId = options[i].getAttribute('data-id');
                    if (hiddenIdInput) {
                        hiddenIdInput.value = userId;
                        console.log("ID Verified on Change:", userId);
                    }
                    break;
                }
            }
        });
    }

    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('submit_report_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Report submitted to Admin.");
                    reportForm.reset();
                    reportModal.style.display = 'none';
                } else {
                    alert("Error: " + (data.message || "Unknown error"));
                }
            })
            .catch(err => {
                console.error("Fetch Error:", err);
                alert("System Error: Submission failed.");
            });
        });
    }

    // Modal Closing
    if (reportClose) reportClose.onclick = () => reportModal.style.display = 'none';
    if (reportCancel) reportCancel.onclick = () => reportModal.style.display = 'none';

    window.onclick = (event) => {
        if (event.target === reportModal) reportModal.style.display = 'none';
        if (event.target === wishModal) hideWishModal();
    };

    // 6. Notifications Logic
    function loadNotifications() {
        const notifContainer = document.getElementById('notif-list-container');
        if (!notifContainer) return;

        fetch('notif_controller.php?action=fetch')
            .then(res => res.text())
            .then(data => {
                notifContainer.innerHTML = data;
                const statusText = document.getElementById('notif-status-text');
                if (data.includes('notif-item') && statusText) {
                    statusText.innerText = "You have new updates";
                }
            })
            .catch(err => console.error("Notification Error:", err));
    }

    window.handleNotifClick = function (type, senderId, senderName, itemName) {
        if (type === 'wish_match' || type === 'message') {
            window.location.href = `marketplace.php?open_chat=1&user_id=${senderId}&item=${encodeURIComponent(itemName)}`;
        } else if (type === 'order') {
            window.location.href = "marketplace.php?view=orders";
        }
    };

    // 7. Initial Loads & Intervals
    loadBulletin();
    loadWishes();
    loadNotifications();
    
    setInterval(loadBulletin, 5000);
    setInterval(loadWishes, 30000);
    setInterval(loadNotifications, 30000);
});