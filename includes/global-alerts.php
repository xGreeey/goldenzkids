<style>
    .toast-container {
        position: fixed;
        bottom: max(16px, env(safe-area-inset-bottom));
        right: -110%;
        background: var(--bg-surface, #f9fafb);
        border-left: 4px solid var(--brand-accent, #a89b7a);
        color: var(--text-primary, #003049);
        padding: 16px;
        border-radius: 4px;
        box-shadow: -5px 10px 30px rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 9999;
        transition: right 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        font-family: var(--font-body-family, 'Inter', system-ui, sans-serif);
        width: min(350px, calc(100vw - 32px));
        max-width: 100%;
    }
    .toast-container.show {
        right: max(16px, env(safe-area-inset-right));
    }
    @media (max-width: 480px) {
        .toast-container { padding: 14px; }
        .toast-icon { font-size: 1.5rem; }
    }
    .toast-icon {
        font-size: 2rem;
        color: var(--brand-accent-text, var(--brand-accent, #9a8200));
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
            fetch('<?= app_url('api/fetch-alerts.php') ?>', { credentials: 'same-origin' })
                .then(response => {
                    const type = response.headers.get('content-type') || '';
                    if (!response.ok || !type.includes('application/json')) {
                        throw new Error('Invalid alert response');
                    }
                    return response.json();
                })
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