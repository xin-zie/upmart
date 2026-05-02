document.addEventListener('DOMContentLoaded', () => {
    const chartCanvas = document.getElementById('myChart');
    const logoutBtn = document.querySelector('.logout-btn');

    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');

        // Data from PHP
        const catLabels = <? php echo json_encode($chart_labels); ?>;
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

function showPreview(title, seller, price, desc, img) {
    const emptyState = document.getElementById('emptyState');
    const content = document.getElementById('previewContent');

    if (emptyState) emptyState.style.display = 'none';
    if (content) {
        content.style.display = 'block';
        document.getElementById('prevTitle').innerText = title;
        document.getElementById('prevSeller').innerText = "Seller: " + seller;
        document.getElementById('prevPrice').innerText = price;
        document.getElementById('prevDesc').innerText = desc;
        document.getElementById('prevImg').src = img;
    }
}