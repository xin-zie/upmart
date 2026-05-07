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
