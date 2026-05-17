<?php
require_once __DIR__ . '/../config/app.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['company_id'])) {
    header('Location: ' . app_url('index.php'));
    exit();
}

$company_id = $_SESSION['company_id'];

$cipher_algo = "aes-256-cbc";
$master_key = "ABC_SecureKey_2026_xYz12345"; // Ensure this exactly matches GuardPortal

// 2. FETCH GUARDS (Store in a dictionary for fast lookup)
$guards_query = "SELECT Company_ID, First_Name, Last_Name, Middle_Name FROM guards";
$guards_result = $conn->query($guards_query);
$guard_dict = [];
if($guards_result && $guards_result->num_rows > 0) {
    while($g = $guards_result->fetch_assoc()){
        $guard_dict[$g['Company_ID']] = $g['Last_Name'] . ", " . $g['First_Name'];
    }
}

// 3. FETCH ALL REPORTS
$reports_query = "SELECT Company_ID, Establishment, Template_Path, Template, Time_of_Report, Status, iv From DGD WHERE Company_ID = '$company_id';";
$reports_result = $conn->query($reports_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABC Security Agency | Inbox</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1e153f;
            --darker-blue: #17162f;
            --accent-gold: #febd59;
            --text-white: #ffffff;
            --alert-red: #ff3333;
            --success-green: #00cc66;
            --info-blue: #00ccff;
            --panel-bg: #110d24;
        }

        /* --- LIGHT MODE THEME OVERRIDES --- */
        body.light-mode {
            --primary-blue: #f4f6f8;   
            --darker-blue: #e2e8f0;    
            --panel-bg: #ffffff;       
            --text-white: #0f172a;     
        }
        
        /* Light mode card and modal adjustments */
        body.light-mode .notif-card { border-color: #cbd5e1; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        body.light-mode .page-title, body.light-mode .notif-title { color: #0f172a; }
        body.light-mode .modal-content { border-color: #cbd5e1; box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
        body.light-mode .modal-info { background: #f1f5f9; border-left-color: var(--info-blue); color: #0f172a; }
        body.light-mode .modal-header { border-bottom-color: #cbd5e1; }
        body.light-mode .close-modal { color: #64748b; }
        body.light-mode .close-modal:hover { color: var(--alert-red); }
        body.light-mode .empty-state { color: #0f172a; opacity: 0.7; }
        body.light-mode .btn-dismiss { color: #94a3b8; }
        body.light-mode .btn-dismiss:hover { color: var(--alert-red); }

        /* The Theme Toggle Button Style */
        .btn-theme {
            background: transparent; border: none; color: var(--accent-gold);
            font-size: 1.2rem; cursor: pointer; transition: 0.3s;
            display: flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: 50%;
        }
        .btn-theme:hover { background: rgba(254, 189, 89, 0.1); }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--primary-blue);
            color: var(--text-white);
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* --- HEADER --- */
        header {
            background-color: var(--darker-blue);
            padding: 0 5%;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid var(--accent-gold);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: background-color 0.3s ease;
        }
        .logo-area { display: flex; align-items: center; gap: 15px; }
        .logo-img { height: 50px; width: 50px; border-radius: 50%; border: 2px solid var(--accent-gold); object-fit: cover;}
        .agency-name { font-family: 'Oswald', sans-serif; font-size: 1.2rem; letter-spacing: 1px; line-height: 1.1; }
        
        .btn-back {
            color: var(--accent-gold); text-decoration: none; font-family: 'Oswald', sans-serif; font-size: 0.9rem;
            border: 1px solid var(--accent-gold); padding: 8px 20px; transition: 0.3s;
            display: flex; align-items: center; gap: 10px; white-space: nowrap;
        }
        .btn-back:hover { background-color: var(--accent-gold); color: var(--darker-blue); font-weight: bold; }

        /* --- LAYOUT --- */
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; width: 100%; flex: 1; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; border-bottom: 1px solid var(--accent-gold); padding-bottom: 10px; }
        .page-title { font-family: 'Oswald', sans-serif; font-size: 2.5rem; text-transform: uppercase; transition: color 0.3s ease; }

        /* --- NOTIFICATION CARDS --- */
        .notif-list { display: flex; flex-direction: column; gap: 15px; }
        .notif-card {
            background: var(--panel-bg); border: 1px solid rgba(255,255,255,0.1); padding: 20px;
            display: flex; gap: 20px; transition: 0.3s; position: relative; cursor: pointer;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .notif-card:hover { transform: translateX(5px); border-color: var(--accent-gold); }

        .icon-box { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; border-radius: 4px; flex-shrink: 0; }
        .content-box { flex: 1; }
        .notif-title { font-family: 'Oswald', sans-serif; font-size: 1.2rem; margin-bottom: 5px; color: var(--text-white); transition: color 0.3s ease; }
        
        .status-badge {
            font-family: 'Roboto Mono', monospace; font-size: 0.65rem; padding: 3px 8px;
            border-radius: 3px; margin-left: 10px; font-weight: bold; letter-spacing: 0.5px;
            vertical-align: middle; text-shadow: none;
        }
        .notif-desc { font-size: 0.9rem; opacity: 0.8; line-height: 1.4; margin-bottom: 10px; color: var(--accent-gold); }
        .timestamp { font-family: 'Roboto Mono', monospace; font-size: 0.75rem; opacity: 0.5; display: flex; gap: 10px; }
        
        .btn-dismiss {
            position: absolute; top: 15px; right: 15px; background: none; border: none;
            color: rgba(255,255,255,0.3); cursor: pointer; font-size: 1.5rem; z-index: 2; transition: color 0.3s ease;
        }
        .btn-dismiss:hover { color: var(--alert-red); }

        .empty-state { text-align: center; padding: 50px; opacity: 0.5; display: none; font-family: 'Roboto Mono'; transition: color 0.3s ease;}

        /* --- MODAL POPUP --- */
        .modal-overlay {
            display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.85); backdrop-filter: blur(5px); align-items: center; justify-content: center;
        }
        .modal-content {
            background-color: var(--panel-bg); border: 1px solid var(--accent-gold); border-radius: 4px;
            width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; position: relative; padding: 25px;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .close-modal {
            color: rgba(255,255,255,0.5); position: absolute; top: 15px; right: 20px;
            font-size: 28px; font-weight: bold; cursor: pointer; transition: 0.3s;
        }
        .close-modal:hover { color: var(--alert-red); }
        
        .modal-header { border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px; transition: border-color 0.3s ease;}
        .modal-header h2 { font-family: 'Oswald', sans-serif; color: var(--accent-gold); margin-bottom: 5px; font-size: 1.8rem; }
        .modal-subtitle { font-family: 'Roboto Mono', monospace; font-size: 0.8rem; opacity: 0.6; }

        .modal-info { font-size: 0.95rem; color: var(--text-white); background: rgba(255,255,255,0.05); padding: 15px; border-left: 3px solid var(--info-blue); font-family: 'Roboto Mono', monospace; line-height: 1.6; transition: background-color 0.3s ease, color 0.3s ease;}

        /* --- FOOTER --- */
        .footer-bottom { background-color: var(--darker-blue); padding: 40px 5%; text-align: center; border-top: 3px solid var(--accent-gold); margin-top: 50px; transition: background-color 0.3s ease;}
        .footer-branding { display: flex; flex-direction: column; align-items: center; gap: 15px; }
        .footer-logo { width: 60px; height: 60px; border-radius: 50%; border: 2px solid var(--accent-gold); margin-bottom: 5px; filter: grayscale(30%); }
        
        /* --- FULLSCREEN IMAGE VIEWER --- */
        .image-viewer-overlay {
            display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.95); align-items: center; justify-content: center;
            cursor: zoom-out; backdrop-filter: blur(5px);
        }
        .image-viewer-overlay img {
            max-width: 95vw; max-height: 95vh; object-fit: contain; 
            border: 2px solid var(--accent-gold); border-radius: 4px;
            box-shadow: 0 0 30px rgba(0,0,0,0.8);
        }
        .close-viewer {
            position: absolute; top: 20px; right: 30px; color: rgba(255,255,255,0.5); 
            font-size: 40px; font-weight: bold; cursor: pointer; transition: 0.3s;
        }
        .close-viewer:hover { color: var(--alert-red); }
        
        /* --- MOBILE RESPONSIVE FIXES --- */
        @media (max-width: 768px) {
            header { flex-direction: column; height: auto; padding: 20px 5%; gap: 15px; text-align: center; }
            .logo-area { flex-direction: column; gap: 10px; }
            .container { margin: 20px auto; padding: 0 15px; }
            .page-header { justify-content: center; margin-bottom: 20px; }
            .page-title { font-size: 2rem; text-align: center; }
            .notif-card { padding: 15px; gap: 15px; }
            .timestamp { flex-direction: column; gap: 5px; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo-area">
            <img src="https://i.imgur.com/uOClOiX.jpeg" alt="Logo" class="logo-img" onerror="this.src='https://via.placeholder.com/50/17162f/febd59?text=ABC'">
            <div class="agency-name">ABC SECURITY AGENCY</div>
        </div>
        
        <nav style="display: flex; align-items: center; gap: 20px;">
            <button id="themeToggle" class="btn-theme" title="Toggle Light/Dark Mode">
                <i class="fa-solid fa-sun"></i>
            </button>
            <a href="portal.php" class="btn-back">
                RETURN TO PORTAL <span>(BALIK SA PORTAL)</span>
            </a>
        </nav>
    </header>

    <div class="container">
        
        <div class="page-header">
            <h1 class="page-title">ALERT CENTER</h1>
        </div>

        <div class="notif-list" id="alert-feed">
            <?php 
            if ($reports_result && $reports_result->num_rows > 0) {
                while($row = $reports_result->fetch_assoc()) {
                    
                    // --- LIVE DECRYPTION WITH FALLBACKS ---
                    $iv = base64_decode($row['iv']);
                    
                    $decrypted_est = openssl_decrypt($row['Establishment'], $cipher_algo, $master_key, 0, $iv) ?: "[Decryption Failed - Check Key]";
                    $decrypted_template = openssl_decrypt($row['Template_Path'], $cipher_algo, $master_key, 0, $iv) ?: "[Decryption Failed - Check Key]";

                    // GUARD LOOKUP
                    $guard_id = $row['Company_ID'];
                    $guard_name = isset($guard_dict[$guard_id]) ? $guard_dict[$guard_id] : "Unknown User";
                    $time_sent = ($row['Time_of_Report']);
                    $status = ($row['Status']);
                
                    // --- DYNAMIC STATUS BADGE COLORS ---
                    $status_text = strtoupper($status);
                    if ($status_text === 'PENDING') {
                        $badge_bg = 'var(--accent-gold)';
                        $badge_color = 'var(--darker-blue)'; 
                    } elseif ($status_text === 'APPROVED') {
                        $badge_bg = 'var(--success-green)';
                        $badge_color = '#ffffff'; 
                    } elseif ($status_text === 'NTE' || $status_text === 'FOR CLARIFICATION') {
                        $badge_bg = 'var(--alert-red)';
                        $badge_color = '#ffffff'; 
                    } else {
                        $badge_bg = 'var(--info-blue)';
                        $badge_color = '#ffffff'; 
                    }
            ?>
            
            <div class="notif-card" onclick="openReportModal(this)"
                 data-guard="<?= htmlspecialchars($guard_name) ?>"
                 data-id="<?= htmlspecialchars($guard_id) ?>"
                 data-est="<?= htmlspecialchars($decrypted_est) ?>"
                 data-time="<?= htmlspecialchars($time_sent) ?>"
                 data-template="<?= htmlspecialchars($decrypted_template) ?>"
                 data-status="<?= htmlspecialchars($status) ?>">
                
                <div class="icon-box">🛡️</div>
                <div class="content-box">
                    <div class="notif-title"> DGD LOGGED</div>
                    <div class="notif-desc"> submitted for <?= htmlspecialchars($decrypted_est) ?>.</div>
                    <div class="timestamp">
                        <span>SENT BY: <?= htmlspecialchars($guard_id) ?></span> | <span><?= htmlspecialchars($time_sent) ?></span>
                        <span class="status-badge" style="background-color: <?= $badge_bg ?>; color: <?= $badge_color ?>;">
                            <?= htmlspecialchars($status_text) ?>
                        </span>
                    </div>
                </div>
                <button class="btn-dismiss" onclick="dismiss(event, this)">×</button>
            </div>

            <?php 
                } 
            } else {
                echo "<script>document.addEventListener('DOMContentLoaded', checkEmpty);</script>";
            }
            ?>
        </div>

        <div id="empty-msg" class="empty-state">
            <div style="font-size: 3rem; margin-bottom: 10px;">✅</div>
            ALL CLEAR. NO NEW ALERTS.<br>
            <span style="font-size: 0.8rem;">SYSTEM SECURE</span>
        </div>
    </div>

    <footer class="footer-bottom">
        <div class="footer-branding">
            <img src="https://i.imgur.com/uOClOiX.jpeg" alt="ABC Logo" class="footer-logo" onerror="this.src='https://via.placeholder.com/60/17162f/febd59?text=ABC'">
            <div style="font-size: 0.8rem; letter-spacing: 2px; font-family: 'Oswald'; color: var(--accent-gold);">
                CENTRAL COMMAND HEADQUARTERS
            </div>
            <h2 style="font-family: 'Oswald', sans-serif; font-size: 1.2rem; letter-spacing: 1px; margin: 0;">Intramuros, Manila, PH | +63 2 8000 0000</h2>
            <p style="opacity: 0.7; font-size: 0.8rem; margin: 0; font-family: 'Roboto', sans-serif;">ABC SECURITY AGENCY</p>
        </div>
    </footer>

    <div id="reportModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div class="modal-header">
                <h2 id="modalTitle">REPORT DETAILS</h2>
                <div id="modalTimestamp" class="modal-subtitle">TIMESTAMP</div>
            </div>
            <div class="modal-body">
                <div style="text-align: center;">
                    <p style="font-family: 'Roboto Mono'; font-size: 0.8rem; color: var(--accent-gold); margin-bottom: 5px;">FILLED OUT FORM (CLICK TO ZOOM)</p>
                    <img id="imgTemp" src="" alt="Template Scan" onclick="openImageViewer(this.src)" style="cursor: zoom-in; max-width: 100%; border: 1px solid var(--accent-gold); border-radius: 4px;"
                         onerror="this.src='https://via.placeholder.com/300x400/110d24/ff3333?text=Image+Not+Found'">
                </div>
                <div class="modal-info" id="modalInfo" style="margin-top: 20px;"></div>
            </div>
        </div>
    </div>
   
    <div id="imageViewer" class="image-viewer-overlay" onclick="closeImageViewer()">
        <span class="close-viewer">&times;</span>
        <img id="fullScreenImg" src="" alt="Full Screen Scan">
    </div>
       
    <script>
        // --- FULLSCREEN VIEWER LOGIC ---
        function openImageViewer(imageSrc) {
            const viewer = document.getElementById('imageViewer');
            const fullImg = document.getElementById('fullScreenImg');
            fullImg.src = imageSrc;
            viewer.style.display = 'flex';
        }

        function closeImageViewer() {
            document.getElementById('imageViewer').style.display = 'none';
        }
        
        // --- MODAL LOGIC ---
        function openReportModal(card) {
            const cleanPath = (path) => {
                if (!path) return '';
                if (path.includes('uploads/')) {
                    return '<?= UPLOADS_URL ?>' + path.split('uploads/').pop();
                }
                return path;
            };

            const guard = card.getAttribute('data-guard');
            const guardId = card.getAttribute('data-id');
            const est = card.getAttribute('data-est');
            const time = card.getAttribute('data-time');
            const status = card.getAttribute('data-status');
            
            const tempPath = cleanPath(card.getAttribute('data-template'));

            document.getElementById('modalTitle').innerText = `DGD sent from ${est}`;
            document.getElementById('modalTimestamp').innerText = `SYSTEM LOGGED: ${time}`;
            document.getElementById('imgTemp').src = tempPath;

            document.getElementById('modalInfo').innerHTML = `
                <p><strong>GUARD ID:</strong> ${guardId}</p>
                <p><strong>PERSONNEL:</strong> ${guard}</p>
                <p><strong>ASSIGNMENT/LOCATION:</strong> ${est}</p>
                <p><strong>REPORT STATUS:</strong> ${status}</p>
                <p style="color: var(--success-green); margin-top: 15px; border-top: 1px dashed rgba(255,255,255,0.2); padding-top: 10px;">
                   [✓] AES-256 DECRYPTION SUCCESSFUL
                </p>
            `;

            document.getElementById('reportModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('reportModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('reportModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        function dismiss(event, btn) {
            event.stopPropagation(); 
            const card = btn.closest('.notif-card');
            card.style.opacity = '0';
            card.style.transform = 'translateX(50px)';
            setTimeout(() => {
                card.remove();
                checkEmpty();
            }, 300);
        }

        function clearAll() {
            document.getElementById('alert-feed').innerHTML = '';
            checkEmpty();
        }

        function checkEmpty() {
            const list = document.getElementById('alert-feed');
            const msg = document.getElementById('empty-msg');
            if (list.children.length === 0) {
                msg.style.display = 'block';
            }
        }

        // --- THEME TOGGLE LOGIC ---
        document.addEventListener("DOMContentLoaded", function() {
            const themeToggleBtn = document.getElementById('themeToggle');
            const themeIcon = themeToggleBtn.querySelector('i');
            const body = document.body;

            const savedTheme = localStorage.getItem('abc_theme');
            if (savedTheme === 'light') {
                body.classList.add('light-mode');
                themeIcon.classList.replace('fa-sun', 'fa-moon');
            }

            themeToggleBtn.addEventListener('click', () => {
                body.classList.toggle('light-mode');
                
                if (body.classList.contains('light-mode')) {
                    localStorage.setItem('abc_theme', 'light');
                    themeIcon.classList.replace('fa-sun', 'fa-moon');
                } else {
                    localStorage.setItem('abc_theme', 'dark');
                    themeIcon.classList.replace('fa-moon', 'fa-sun');
                }
            });
        });
    </script>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>