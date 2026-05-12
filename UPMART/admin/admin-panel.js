let currentReportId, currentSellerId, currentProductId;
document.addEventListener('DOMContentLoaded', () => {
    const chartCanvas = document.getElementById('myChart');
    const logoutBtn = document.querySelector('.logout-btn');


    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chart_labels, // Matches the PHP bridge name
                datasets: [{
                    label: 'Post Distribution',
                    data: chart_data, // Matches the PHP bridge name
                    backgroundColor: [
                        'rgba(128, 0, 0, 0.85)',   // 1. Dorm Essentials
                        'rgba(255, 184, 28, 0.85)',  // 2. Arki Mats
                        'rgba(44, 62, 80, 0.85)',    // 3. Lab Essentials
                        'rgba(155, 89, 182, 0.85)',  // 4. Fashion
                        'rgba(52, 152, 219, 0.85)',  // 5. Books
                        'rgba(26, 188, 156, 0.85)',  // 6. Services
                        'rgba(231, 76, 60, 0.85)',   // 7. Foods
                        'rgba(243, 156, 18, 0.85)',  // 8. School Supplies
                        'rgba(39, 174, 96, 0.85)',   // 9. Art Materials
                        'rgba(149, 165, 166, 0.85)'  // 10. Others
                    ],
                    hoverOffset: 15,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '59%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { family: "'Inter', sans-serif", size: 11 }
                        }
                    }
                }
            }
        });

        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm("Are you sure you want to logout of UPMart?")) {
                    window.location.href = "../dashboard/logout.php";
                }
            });
        }
    }
});

// GLOBAL SCOPE FUNCTIONS (So HTML can find them)
function handlePost(postId, action) {
    const postElement = document.getElementById(`post-${postId}`);
    if (!postElement) return;

    postElement.style.transition = '0.3s ease';
    postElement.style.opacity = '0';
    postElement.style.transform = 'translateX(20px)';

    setTimeout(() => {
        postElement.remove();
        console.log(`Action: Post #${postId} was ${action}.`);
    }, 300);
}

window.showPreview = function(title, seller, price, description, imgUrl, category, sImg) {
    console.log("--- PREVIEW DEBUGGER ---");
    console.log("Category received:", category);
    console.log("Seller Image received:", sImg);

    try {
        const emptyState = document.getElementById('emptyState');
        const content = document.getElementById('previewContent');
        const imgElement = document.getElementById('prevImg');
        const userImgElement = document.getElementById('prevUserImg'); // The avatar
        const catElement = document.getElementById('prevCategory'); // The category badge

        if (!emptyState || !content || !imgElement) return;

        // Display logic
        emptyState.style.display = 'none';
        content.style.display = 'block';

        // Set Text Fields
        document.getElementById('prevTitle').innerText = title;
        document.getElementById('prevSeller').innerText = seller; // Removed "Seller:" prefix to match your UI screenshot
        document.getElementById('prevPrice').innerText = price;
        document.getElementById('prevDesc').innerText = description;
        
        if (catElement) catElement.innerText = category;

        // Set Main Product Image
        imgElement.src = imgUrl;

        // Set Seller Avatar
        if (userImgElement) {
            // Logic to step out of /admin if it's an uploaded file
            let cleanSImg = sImg.replace('uploads/', '').replace('../', '').replace('dashboard/', '');
            userImgElement.src = (sImg === 'profile.jpg' || !sImg) ? '../images/profile.jpg' : '../dashboard/uploads/' + cleanSImg;
        }

        console.log("SUCCESS: Preview panel updated.");
    } catch (err) {
        console.error("JS EXECUTION ERROR:", err.message);
    }
};

function approvePost(postId) {
    const postElement = document.getElementById(`post-${postId}`);

    // Send the ID to our PHP script
    fetch('admin_approve.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${postId}`
    })
        .then(response => response.text())
        .then(data => {
            if (data === "success") {
                // Animate and remove from the Admin's view
                postElement.style.transition = '0.4s ease';
                postElement.style.opacity = '0';
                postElement.style.transform = 'scale(0.9)';

                setTimeout(() => {
                    postElement.remove();
                    // Update the pending count badge if you have one
                    updatePendingBadge();
                }, 400);
            }
        });
}

// Updated Admin Notification Toggle
function toggleNotifSidebar() {
    const notifDrawer = document.getElementById('notifDrawer');
    const notifContainer = document.getElementById('notif-list-container');
    const statusText = document.getElementById('notif-status-text');

    if (!notifDrawer) return;

    // Toggle visibility class
    notifDrawer.classList.toggle('open');

    if (notifDrawer.classList.contains('open')) {
        // Fetch using the same controller as the user dashboard
        fetch('../dashboard/notif_controller.php?action=fetch')
            .then(res => res.text())
            .then(data => {
                notifContainer.innerHTML = data;
                if (data.includes('notif-item')) {
                    statusText.innerText = "You have new updates";
                }
            })
            .catch(err => console.error("Admin Notif Error:", err));
    }
}

// Add event listener for the close button
document.getElementById('closeNotifBtn').addEventListener('click', () => {
    document.getElementById('notifDrawer').classList.remove('open');
});


function closeSidebar() {
    const sidebar = document.getElementById('investigationSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
}

window.adminAction = function (type) {
    if (!currentReportId) {
        alert("Error: No report selected.");
        return;
    }

    // 1. Determine the correct confirmation text
    let actionText = "";
    let actionValue = ""; // This is what we send to PHP

    if (type === 'ban') {
        actionText = "permanently BAN this seller and remove the post?";
        actionValue = "ban_user";
    } else if (type === 'warning') {
        actionText = "issue a formal WARNING to this user?";
        actionValue = "warning";
    } else {
        actionText = "dismiss this report?";
        actionValue = "dismiss_report";
    }

    if (!confirm("Are you sure you want to " + actionText)) return;

    // 2. Prepare the Data
    const fd = new FormData();
    fd.append('action', actionValue); // Matches the 'if' blocks in admin_actions.php
    fd.append('report_id', currentReportId);
    fd.append('seller_id', currentSellerId);
    fd.append('product_id', currentProductId || 0);

    // 3. Send to Server
    fetch('admin_actions.php', {
        method: 'POST',
        body: fd
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeSidebar();

                // 4. Visual Cleanup: Remove the report card from the list with animation
                // Using a safer selector to find the card that was acted upon
                const allButtons = document.querySelectorAll('.report-card button');
                let card = null;

                allButtons.forEach(btn => {
                    if (btn.getAttribute('onclick')?.includes(currentReportId)) {
                        card = btn.closest('.report-card');
                    }
                });

                if (card) {
                    card.style.transition = "0.4s ease";
                    card.style.opacity = "0";
                    card.style.transform = "translateX(30px)";
                    setTimeout(() => {
                        card.remove();
                        // If no more cards exist, show the "No reports" message
                        const list = document.querySelector('.reports-list');
                        if (list && list.querySelectorAll('.report-card').length === 0) {
                            list.innerHTML = "<p style='text-align:center; color:#888; padding:20px;'>No pending reports.</p>";
                        }
                    }, 400);
                }
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            console.error("Admin Action Failed:", err);
            alert("An error occurred while processing the request.");
        });
};

function dismissReport(reportId, reportCard) {
    if (!confirm("Remove this report from the list?")) return;

    const fd = new FormData();
    fd.append('action', 'dismiss_report');
    fd.append('report_id', reportId);

    fetch('admin_actions.php', {
        method: 'POST',
        body: fd
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Smoothly remove the inappropriate report card
                reportCard.style.transition = "0.3s ease";
                reportCard.style.opacity = "0";
                reportCard.style.transform = "scale(0.9)";

                setTimeout(() => {
                    reportCard.remove();

                    // Optional: Check if the list is now empty
                    if (document.querySelectorAll('.report-card').length === 0) {
                        document.querySelector('.reports-list').innerHTML =
                            "<p style='text-align:center; color:#888; padding:20px;'>No pending reports.</p>";
                    }
                }, 300);
            }
        })
}

window.openInvestigate = function (title, desc, img, reason, details, reportId, sellerId, productId, price, cat, sName, sImg, sWarning) {
    console.log("Investigate triggered for Report:", reportId);

    // 1. Elements Selectors
    const nameEl = document.getElementById('seller-name');
    const priceContainer = document.getElementById('side-price-container'); // The wrapper for Price label + value
    const actionBtn = document.getElementById('ban-delete-btn');
    const sideCatEl = document.getElementById('side-category-container');
    const imgEl = document.getElementById('side-img');
    const sImgEl = document.getElementById('side-seller-img');

    // 2. Save IDs for action buttons
    currentReportId = reportId;
    currentSellerId = sellerId;
    currentProductId = productId;

    // Helper to set text safely
    const setTxt = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.innerText = val || "";
    };

    // 3. UI logic for General vs Product Reports
    if (!productId || productId == "0" || productId === "NULL") {
        // --- GENERAL USER REPORT MODE ---
        setTxt('side-title', "General User Report");
        setTxt('side-desc', "Report directed at user behavior.");

        // Hide elements that don't belong in a General Report
        if (imgEl) imgEl.style.display = "none";
        if (sideCatEl) sideCatEl.style.display = "none";

        // HIDE THE ENTIRE PRICE SECTION (Label + Amount)
        if (priceContainer) priceContainer.style.visibility = "hidden";

        // Update Button Text to be context-aware
        if (actionBtn) actionBtn.innerHTML = '<i class="fas fa-ban"></i> Ban User';

    } else {
        // --- PRODUCT REPORT MODE ---
        setTxt('side-title', title);
        setTxt('side-desc', desc);
        setTxt('side-price', price); // Only the number/amount
        setTxt('side-category', cat);

        if (imgEl) {
            imgEl.style.display = "block";
            let cleanProdImg = img.replace('uploads/', '').replace('../', '').replace('dashboard/', '');
            imgEl.src = '../dashboard/uploads/' + cleanProdImg;
        }

        // SHOW THE PRICE SECTION
        if (priceContainer) priceContainer.style.visibility = "visible";
        if (sideCatEl) sideCatEl.style.display = "block";

        // Reset Button Text
        if (actionBtn) actionBtn.innerHTML = '<i class="fas fa-ban"></i> Ban Seller & Delete Post';
    }

    // 4. Fill Seller Info
    setTxt('side-seller-name', sName || "User ID: " + sellerId);
    setTxt('side-warning-count', sWarning || "0");
    setTxt('side-reason', reason);
    setTxt('side-details', details);

    // 5. Handle Seller Image
    if (sImgEl) {
        let cleanSImg = sImg ? sImg.replace('uploads/', '').replace('../', '').replace('dashboard/', '') : '';
        sImgEl.src = (sImg === 'profile.jpg' || !sImg) ? '../images/profile.jpg' : '../dashboard/uploads/' + cleanSImg;
    }

    // 6. Reveal Sidebar
    const sidebar = document.getElementById('investigationSidebar');
    if (sidebar) {
        sidebar.classList.add('open', 'active');
    }
}

// Make it global so other scripts can call it
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

// Add this to your Admin Dashboard initialization
setInterval(() => {
    loadNotifications(); // This calls your notif_controller.php
}, 10000); // Check every 10 seconds for new reports