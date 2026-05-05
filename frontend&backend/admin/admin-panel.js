document.addEventListener('DOMContentLoaded', () => {
    const chartCanvas = document.getElementById('myChart');
    const logoutBtn = document.querySelector('.logout-btn');

    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');

        // Data from PHP
        const catLabels = <?php echo json_encode($chart_labels); ?>;
        const catData = <?php echo json_encode($chart_data); ?>;

        const config = {
            type: 'doughnut',
            data: {
                // If database is empty, show "No Data" slice
                labels: catLabels.length > 0 ? catLabels : ['No Items Yet'],
                datasets: [{
                    label: 'Top Categories',
                    data: catData.length > 0 ? catData : [1],
                    backgroundColor: catData.length > 0 ? [
                        '#800000', // Maroon
                        '#ffb81c', // Gold
                        '#1a1a2e', // Navy
                        '#e1f5da', // Green
                        '#4A0000'  // Dark Maroon
                    ] : ['#eeeeee'], 
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%', // Cleaner look
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { family: "'Inter', sans-serif", size: 12, weight: '600' }
                        }
                    }
                }
            }
        };

        new Chart(ctx, config);
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm("Are you sure you want to logout of UPMart?")) {
                window.location.href = "../includes/logout.php"; 
            }
        });
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

// Check for new items every 60 seconds
setInterval(updateAdminBadges, 60000);
