<style>
    .toast-container {
        position: fixed;
        bottom: 30px;
        right: -400px; /* Hidden off-screen initially */
        background: var(--bg-surface, #f9fafb);
        border-left: 4px solid var(--brand-accent, #a89b7a);
        color: var(--text-primary, #3d4a5c);
        padding: 20px;
        border-radius: 4px;
        box-shadow: -5px 10px 30px rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        gap: 15px;
        z-index: 9999;
        transition: right 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        width: 350px;
    }
    .toast-container.show {
        right: 30px; /* Slides into view */
    }
    .toast-icon {
        font-size: 2rem;
        color: var(--brand-accent, #a89b7a);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    .toast-content h4 {
        margin: 0 0 5px 0;
        font-family: inherit;
        letter-spacing: 1px;
    }
    .toast-content p {
        margin: 0;
        font-size: 0.85rem;
        opacity: 0.8;
    }
</style>

<div id="globalToast" class="toast-container">
    <div class="toast-icon">
        <i class="fa-solid fa-bell"></i>
    </div>
    <div class="toast-content">
        <h4>NEW SYSTEM ALERT</h4>
        <p id="toastMessage">You have new secure data requiring attention.</p>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Check LocalStorage for the last known alert count
        let lastAlertCount = localStorage.getItem('abc_alert_count');
        if (lastAlertCount === null) {
            lastAlertCount = 0;
        }

        // 2. The Knocking Function
        function checkNewAlerts() {
            fetch('<?= app_url('api/fetch-alerts.php') ?>')
                .then(response => response.json())
                .then(data => {
                    let currentCount = data.count;

                    // If the number of pending items went UP since we last checked...
                    if (currentCount > lastAlertCount) {
                        
                        // Update the message dynamically
                        document.getElementById('toastMessage').innerText = `You have ${currentCount} item(s) pending review.`;
                        
                        // Slide the toast onto the screen
                        const toast = document.getElementById('globalToast');
                        toast.classList.add('show');

                        // (Optional) Play a tiny beep sound
                        // let beep = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
                        // beep.play();

                        // Hide the toast automatically after 7 seconds
                        setTimeout(() => {
                            toast.classList.remove('show');
                        }, 7000);
                    }

                    // Save the new state to LocalStorage so we don't spam the user when they change pages
                    localStorage.setItem('abc_alert_count', currentCount);
                    lastAlertCount = currentCount;
                })
                .catch(error => console.error("Polling Error:", error));
        }

        // 3. Run the check immediately on page load, then every 10 seconds
        checkNewAlerts();
        setInterval(checkNewAlerts, 10000); 
    });
</script>