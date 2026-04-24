document.addEventListener('DOMContentLoaded', () => {
    const chartCanvas = document.getElementById('myChart');
    const logoutBtn = document.querySelector('.logout-btn');

    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        const data = {
            labels: ['Academic', 'Electronics', 'Dorm Essentials', 'Food'],
            datasets: [{
                label: 'Top Categories',
                data: [6, 9, 11, 15],
                backgroundColor: [
                    'rgba(128, 0, 0, 0.85)',
                    'rgba(255, 184, 28, 0.85)',
                    'rgba(225, 245, 218, 1)',
                    'rgba(26, 26, 46, 0.85)'
                ],
                hoverOffset: 15
            }]
        };

        const config = {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true, // Circular markers in legend
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12,
                                weight: '600'
                            }
                        }
                    }
                }
            }
        };
        new Chart(ctx, config);

        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                if (confirm("Are you sure you want to logout of UPMart?")) {
                    window.location.href = "login.html"; // Redirects to login
                }
            });
        }

        function handlePost(postId, action) {
            const postElement = document.getElementById(`post-${postId}`);

            // 1. Animation for Admin
            postElement.style.opacity = '0';
            postElement.style.transform = 'translateX(20px)';

            setTimeout(() => {
                postElement.remove();

                // 2. LOGIC FOR BACKEND:
                // This is where the backend dev will send a notification to the seller.
                if (action === 'approved') {
                    console.log(`Sending Notif to Seller: "Your post #${postId} is now live!"`);
                } else {
                    console.log(`Sending Notif to Seller: "Your post #${postId} was declined."`);
                }
            }, 300);
        }

        function showPreview(title, seller, price, desc, img) {
            // Hide empty state, show content
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('previewContent').style.display = 'block';
            document.getElementById('previewPanel').style.border = 'none';

            // Update the values
            document.getElementById('prevTitle').innerText = title;
            document.getElementById('prevSeller').innerText = "Seller: " + seller;
            document.getElementById('prevPrice').innerText = price;
            document.getElementById('prevDesc').innerText = desc;
            document.getElementById('prevImg').src = img;
        }
    }
});