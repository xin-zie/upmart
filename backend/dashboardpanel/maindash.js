document.addEventListener('DOMContentLoaded', () => {
    const chartCanvas = document.getElementById('myChart');
    const logoutBtn = document.querySelector('.logout-btn');
    const postBtn = document.getElementById('postBtn');
    const bulletinInput = document.getElementById('bulletinText');
    const bulletinList = document.getElementById('bulletinList');

    // --- 1. CHART LOGIC ---
    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Academic', 'Electronics', 'Dorm Essentials', 'Food'],
                datasets: [{
                    data: [6, 9, 11, 15],
                    backgroundColor: [
                        'rgba(128, 0, 0, 0.85)',  
                        'rgba(255, 184, 28, 0.85)', 
                        'rgba(225, 245, 218, 1)',  
                        'rgba(26, 26, 46, 0.85)'
                    ],
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true } }
                }
            }
        });
    }

    // --- 2. LOGOUT LOGIC ---
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if(confirm("Are you sure you want to logout of UPMart?")) {
                window.location.href = "logout.php"; 
            }
        });
    }

    // --- 3. BULLETIN LOGIC ---

    // Function to FETCH posts from DB
    function loadBulletin() {
        fetch('bulletin_controller.php?action=fetch')
            .then(response => response.text())
            .then(data => {
                bulletinList.innerHTML = data;
            })
            .catch(err => console.error("Update error:", err));
    }

    // Single click event for validation + database storage
    if (postBtn) {
        postBtn.addEventListener('click', () => {
            const message = bulletinInput.value.trim();

            if (message === "") {
                alert("Please type something first!");
                return;
            }

            // Profanity Filter
            const forbiddenWords = ["spam", "fuck", "nigga", "sex", "tangina", "bobo"];
            if (forbiddenWords.some(word => message.toLowerCase().includes(word))) {
                alert("Please keep the bulletin friendly and professional.");
                return;
            }

            // AJAX Request to bulletin_controller.php
            const formData = new FormData();
            formData.append('action', 'post');
            formData.append('message', message);

            fetch('bulletin_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "Success") {
                    bulletinInput.value = ""; // Clear input ONLY after it is saved in DB
                    loadBulletin(); // Refresh the list so you see the new post
                } else {
                    alert("System Error: " + data);
                }
            })
            .catch(err => alert("Network Error: " + err.message));
        });
    }

    // Auto-refresh the bulletin every 5 seconds
    loadBulletin();
    setInterval(loadBulletin, 5000);
});