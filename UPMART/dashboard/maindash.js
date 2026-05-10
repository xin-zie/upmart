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

    const wishModal = document.getElementById('wishModal');
    const addWishBtn = document.querySelector('.add-wish-btn');
    const closeWishModal = document.getElementById('closeWishModal');
    const cancelWishBtn = document.getElementById('cancelWishBtn');
    const wishForm = document.getElementById('wishForm');

    const reportForm = document.getElementById('reportForm');

    // 3. Navigation & Logout
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

    // 4. Bulletin Logic
    function loadBulletin() {
        // Changed back to root path
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

    // 5. Wishlist Logic
    function loadWishes() {
        // Changed back to root path
        fetch('wishlist_controller.php?action=fetch')
            .then(res => res.text())
            .then(data => {
                const wishGrid = document.querySelector('.wish-grid');
                if (wishGrid) wishGrid.innerHTML = data;
            })
            .catch(err => console.error("Wishlist Error:", err));
    }

    function handleMatch(wishId) {
        if (!confirm("Confirm that you have this item? This will notify the requester.")) return;

        const fd = new FormData();
        fd.append('action', 'match_wish');
        fd.append('wish_id', wishId);

        fetch('wishlist_controller.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(`Match sent! You can now chat with ${data.requester}.`);
                    // Automatically open the chat for Natasha so she can start the conversation
                    openChat(0, data.requester_id, "Wish: " + data.item_name);
                } else {
                    showToast(data.message || "Could not send match.");
                }
            })
            .catch(err => {
                console.log(err); // This will print the actual error to your F12 console
                alert("Check the console for the error details.");
            });
    }

    window.handleMatch = function (wishId) {
        if (!confirm("Notify the requester that you have this?")) return;
        const fd = new FormData();
        fd.append('action', 'match_wish');
        fd.append('wish_id', wishId);

        fetch('wishlist_controller.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert("Requester notified! You can now find this in your messages.");
                }
            });
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

    // 6. Report Modal Logic
    window.openReportModal = function () {
        if (reportModal) reportModal.style.display = 'flex';
    };

    if (reportClose) reportClose.onclick = () => reportModal.style.display = 'none';
    if (reportCancel) reportCancel.onclick = () => reportModal.style.display = 'none';

    if (reportForm) {
        reportForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(reportForm);
            // Ensuring fields match report_handler.php
            formData.append('type', document.getElementById('reportType').value);
            formData.append('details', document.getElementById('reportDetails').value);

            fetch('report_handler.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === "Success") {
                        alert("Thank you. Your report has been submitted.");
                        reportForm.reset();
                        reportModal.style.display = 'none';
                    } else {
                        alert("Submission failed: " + data);
                    }
                })
                .catch(err => console.error("Report Error:", err));
        });
    }

    // Global click handler to close modals
    window.onclick = (event) => {
        if (event.target === reportModal) reportModal.style.display = 'none';
        if (event.target === wishModal) hideWishModal();
    };

    // 7. Initial Loads
    loadBulletin();
    loadWishes();
    setInterval(loadBulletin, 5000);
    setInterval(loadWishes, 30000);

    // New function to load notifications
    function loadNotifications() {
        const notifContainer = document.getElementById('notif-list-container');
        if (!notifContainer) return;

        fetch('notif_controller.php?action=fetch')
            .then(res => res.text())
            .then(data => {
                notifContainer.innerHTML = data;

                // Update the "Recent updates" text if there are notifications
                const statusText = document.getElementById('notif-status-text');
                if (data.includes('notif-item')) {
                    statusText.innerText = "You have new updates";
                }
            })
            .catch(err => console.error("Notification Error:", err));
    }

    // Initial load
    loadNotifications();
    // Refresh every 30 seconds
    setInterval(loadNotifications, 30000);
});

window.handleNotifClick = function (type, senderId, senderName, itemName) {
    if (type === 'wish_match' || type === 'message') {
        // user_id and item are the keys we look for in script.js
        const url = `marketplace.php?open_chat=1&user_id=${senderId}&item=${encodeURIComponent(itemName)}`;
        window.location.href = url;
    } else if (type === 'order') {
        window.location.href = "marketplace.php?view=orders";
    }
};