document.addEventListener('DOMContentLoaded', () => {
    // 1. Element Selectors
    const chartCanvas = document.getElementById('myChart');
    const logoutBtn = document.querySelector('.logout-btn');
    const postBtn = document.getElementById('postBtn');
    const bulletinInput = document.getElementById('bulletinText');
    const bulletinList = document.getElementById('bulletinList');
    const notifDrawer = document.getElementById('notifDrawer');
    const notifTrigger = document.getElementById('notifTrigger');
    const closeNotifBtn = document.getElementById('closeNotifBtn');
    
    const reportModal = document.getElementById('reportModal');
    const reportClose = document.getElementById('closeModal');
    const reportCancel = document.getElementById('cancelBtn');
    const reportForm = document.getElementById('reportForm');

    const wishModal = document.getElementById('wishModal');
    const addWishBtn = document.querySelector('.add-wish-btn');
    const closeWishModal = document.getElementById('closeWishModal');
    const cancelWishBtn = document.getElementById('cancelWishBtn');
    const wishForm = document.getElementById('wishForm');

    // 2. Chart.js Implementation
    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Academic', 'Electronics', 'Dorm Essentials', 'Food'],
                datasets: [{
                    data: [6, 9, 11, 15],
                    backgroundColor: ['rgba(128, 0, 0, 0.85)', 'rgba(255, 184, 28, 0.85)', 'rgba(225, 245, 218, 1)', 'rgba(26, 26, 46, 0.85)'],
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } }
            }
        });
    }

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
        fetch('bulletin_controller.php?action=fetch') 
            .then(res => res.text())
            .then(data => { if(bulletinList) bulletinList.innerHTML = data; })
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
                    if (data.includes("Success")) {
                        bulletinInput.value = "";
                        loadBulletin();
                    }
                });
        });
    }

    // 5. Wishlist Logic
    function loadWishes() { 
        fetch('wishlist_controller.php?action=fetch')
            .then(res => res.text())
            .then(data => {
                const wishGrid = document.querySelector('.wish-grid');
                if (wishGrid) wishGrid.innerHTML = data;
            })
            .catch(err => console.error("Wishlist Error:", err));
    }

    window.handleMatch = function(wishId) {
        const formData = new FormData();
        formData.append('action', 'match_wish');
        formData.append('wish_id', wishId);

        fetch('wishlist_controller.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Use your existing toast function for better UX than an alert
                showToast("Match noted! Opening chat with " + data.requester_name + "...");
                
                // Trigger your existing chat UI
                // We pass 0 for product_id as this is a wish match, not a direct product listing
                if (window.openChatUI) {
                    window.openChatUI(0, data.requester_id, "Wish Match: " + data.item_name);
                }
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => console.error("Wish Match Error:", err));
    };

    
    if (addWishBtn) {
        addWishBtn.addEventListener('click', () => { if(wishModal) wishModal.style.display = 'flex'; });
    }

    const hideWishModal = () => { 
        if(wishModal) wishModal.style.display = 'none'; 
        if(wishForm) wishForm.reset(); 
    };

    if (closeWishModal) closeWishModal.onclick = hideWishModal;
    if (cancelWishBtn) cancelWishBtn.onclick = hideWishModal;

    if (wishForm) {
        wishForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(wishForm);
            formData.append('action', 'add');

            fetch('wishlist_controller.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(data => {
                if (data.includes("Success")) {
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

            fetch('report_handler.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(data => {
                    if (data.includes("Success")) {
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

    function loadNotifications() {
        const notifContainer = document.getElementById('notif-list-container');
        if (!notifContainer) return;

        // Fetch from your new controller
        fetch('notif_controller.php?action=fetch')
            .then(res => res.text())
            .then(html => {
                notifContainer.innerHTML = html;
            })
            .catch(err => console.error("Notification Fetch Error:", err));
    }

    // Initial load
    loadNotifications();

    // Optional: Refresh every 60 seconds to check for new admin approvals
    setInterval(loadNotifications, 60000);
});