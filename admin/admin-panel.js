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

    function showPreview(title, seller, price, description, imgUrl) {
        // 1. Reveal the preview container and hide the empty state
        document.getElementById('emptyState').style.display = 'none';
        const content = document.getElementById('previewContent');
        content.style.display = 'block';

        // 2. Map the text data
        document.getElementById('prevTitle').innerText = title;
        document.getElementById('prevSeller').innerText = "Seller: " + seller;
        document.getElementById('prevPrice').innerText = price;
        document.getElementById('prevDesc').innerText = description;

        // 3. Set the image source
        // Ensure you target the ID in your right-hand panel
        const imgElement = document.getElementById('prevImg'); 
        if (imgElement) {
            imgElement.src = imgUrl;
        }

        // 4. Reset scroll position to top
        document.getElementById('previewPanel').scrollTop = 0;
    }

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

    function updateAdminBadges() {
        fetch('get_counts.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notif-badge');
                if (data.total > 0) {
                    badge.innerText = data.total;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
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
// Check for new items every 60 seconds
setInterval(updateAdminBadges, 60000);

let currentReportId, currentSellerId, currentProductId;

// Add price, category, and sellerName to the arguments list here:
function openInvestigate(title, desc, img, reason, details, rId, sId, pId, price, category, sellerName) {
    // Fill data
    document.getElementById('side-title').textContent = title;
    document.getElementById('side-desc').textContent = desc;
    
    // Safety check for image
    document.getElementById('side-img').src = img ? '../uploads/products/' + img : '../images/default_product.png';
    
    document.getElementById('side-reason').textContent = reason;
    document.getElementById('side-details').textContent = details;

    // These now work because they are defined in the arguments above
    document.getElementById('side-price').textContent = price;
    document.getElementById('side-seller-name').textContent = sellerName;
    document.getElementById('side-category').textContent = category;

    // Store IDs for the ban action
    currentReportId = rId;
    currentSellerId = sId;
    currentProductId = pId;

    // Show sidebar
    document.getElementById('investigationSidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('active');
}

function closeSidebar() {
    document.getElementById('investigationSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('active');
}

function adminAction(type) {
    if (!currentReportId) {
        alert("Error: No report selected.");
        return;
    }

    // Fix: Confirmation for BOTH Ban and Dismiss
    const actionText = type === 'ban' ? "permanently BAN this seller and remove the post?" : "dismiss this report?";
    if (!confirm("Are you sure you want to " + actionText)) return;

    const fd = new FormData();
    // Correctly bridges to your admin_actions.php cases
    fd.append('action', type === 'ban' ? 'ban_user' : 'dismiss_report');
    fd.append('report_id', currentReportId);
    fd.append('seller_id', currentSellerId);
    fd.append('product_id', currentProductId);

    fetch('admin_actions.php', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // This displays your "Report marked as Resolved" or "Seller Banned" message
            alert(data.message);
            closeSidebar();

            // The "Visual Cleanup" logic
            // Finds the original card on the main page using the ID we saved
            const card = document.querySelector(`button[onclick*="'${currentReportId}'"]`)?.closest('.report-card');
            
            if (card) {
                card.style.transition = "0.3s ease";
                card.style.opacity = "0";
                card.style.transform = "translateX(20px)";
                setTimeout(() => {
                    card.remove();
                    // Check if the list is now empty to show "No reports"
                    if (document.querySelectorAll('.report-card').length === 0) {
                        document.querySelector('.reports-list').innerHTML = 
                            "<p style='text-align:center; color:#888; padding:20px;'>No pending reports.</p>";
                    }
                }, 300);
            }
        } else {
            alert("Error: " + data.message);
        }
    })
}

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
