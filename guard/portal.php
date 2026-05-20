<?php
require_once __DIR__ . '/../config/app.php';
auth_require_permission('guard.portal.access');
require __DIR__ . '/submit-report.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABC Security Agency | Guard Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Antic&family=Oswald:wght@300;400;500;700&family=Roboto:wght@300;400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1e153f;
            --darker-blue: #17162f;
            --accent-gold: #febd59;
            --text-white: #ffffff;
            --text-gray: #e0e0e0;
            --alert-red: #ff3333;
            --panel-bg: #110d24;
            --input-bg: #110d24;
        }

        /* --- LIGHT MODE THEME OVERRIDES --- */
        body.light-mode {
            --primary-blue: #f4f6f8;   
            --darker-blue: #e2e8f0;    
            --panel-bg: #ffffff;       
            --text-white: #0f172a;     
            --input-bg: #f8fafc;       
        }
        
        body.light-mode .portal-section { border-color: #cbd5e1; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        body.light-mode .form-control { border-color: #cbd5e1; color: #0f172a; }
        body.light-mode .section-header-text { color: #0f172a; }
        body.light-mode .form-label { color: #0f172a; }
        body.light-mode .nav-link { color: #0f172a; }
        body.light-mode .top-bar { background-color: #cbd5e1; border-bottom: 1px solid #94a3b8; color: #0f172a; }

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
            font-family: 'Antic', 'Roboto', 'Arial', sans-serif; 
            background-color: var(--primary-blue);
            color: var(--text-white);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        h1, h2, h3, h4, .btn { font-family: 'Oswald', sans-serif; text-transform: uppercase; }

        /* --- TOP BAR MARQUEE --- */
        .top-bar {
            background-color: var(--darker-blue); color: var(--text-white);
            padding: 8px 0; font-size: 0.8rem; white-space: nowrap; overflow: hidden;
            border-bottom: 1px solid #333344; font-family: 'Roboto Mono', monospace;
            transition: background-color 0.3s ease;
        }
        .marquee-content {
            display: inline-block; animation: scroll-left 25s linear infinite; padding-left: 100%;
        }
        @keyframes scroll-left { 0% { transform: translateX(0); } 100% { transform: translateX(-100%); } }

        /* --- HEADER --- */
        header {
            background-color: var(--primary-blue); padding: 0 5%; height: 80px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 4px solid var(--accent-gold);
            position: sticky; top: 0; z-index: 1000;
            transition: background-color 0.3s ease;
        }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .logo-img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-gold); }
        .agency-name { font-family: 'Oswald', sans-serif; font-size: 1.2rem; letter-spacing: 1px; line-height: 1.1; }
        
        .nav-links { display: flex; gap: 30px; align-items: center;}
        .nav-link {
            color: var(--text-white); text-decoration: none; font-size: 0.9rem;
            letter-spacing: 1px; opacity: 0.7; transition: 0.3s; font-weight: 500; font-family: 'Oswald', sans-serif;
        }
        .nav-link:hover { opacity: 1; color: var(--accent-gold); }
        .btn-portal {
            background-color: var(--accent-gold); color: var(--darker-blue);
            padding: 10px 25px; text-decoration: none; font-weight: 700;
            letter-spacing: 1px; font-size: 0.9rem; font-family: 'Oswald', sans-serif; transition: 0.3s;
        }
        .btn-portal:hover { background-color: #ffcf87; box-shadow: 0 0 10px rgba(254, 189, 89, 0.5); }

        /* --- MAIN CONTENT --- */
        .main-container { padding: 40px 5%; max-width: 1000px; margin: 0 auto; }

        .portal-section {
            border: 1px solid #ffffff30; background-color: var(--panel-bg);
            padding: 30px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .section-tag { color: var(--accent-gold); font-family: 'Roboto Mono', monospace; font-size: 0.8rem; margin-bottom: 15px; display: block; text-transform: uppercase; }
        .section-header-text { font-size: 2rem; line-height: 1.2; margin-bottom: 25px; color: var(--text-white); transition: color 0.3s ease; }
        
        /* --- FORMS --- */
        .form-group { margin-bottom: 25px; }
        .form-label { display: block; font-family: 'Oswald', sans-serif; font-size: 1.1rem; margin-bottom: 8px; letter-spacing: 1px; color: var(--text-white); transition: color 0.3s ease; }
        .form-label-sub { display: block; font-family: 'Roboto Mono', monospace; font-size: 0.75rem; color: var(--text-gray); margin-bottom: 10px; opacity: 0.8; }

        .form-control {
            width: 100%; padding: 15px; background-color: var(--input-bg);
            border: 1px solid var(--accent-gold); color: var(--text-white);
            font-family: 'Roboto Mono', monospace; font-size: 1rem; outline: none; appearance: none;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
        .form-control:focus { border-color: #fff; box-shadow: 0 0 10px rgba(254, 189, 89, 0.5); }

        select.form-control {
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23febd59%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat; background-position: right 15px top 50%; background-size: 12px auto;
        }

        .upload-btn-wrapper { position: relative; overflow: hidden; display: block; margin-bottom: 15px; }
        .upload-btn {
            border: 1px dashed var(--accent-gold); color: var(--text-white); background-color: transparent;
            padding: 20px; width: 100%; font-family: 'Oswald', sans-serif; font-size: 1rem; letter-spacing: 1px;
            cursor: pointer; transition: 0.3s; text-align: center;
        }
        .upload-btn:hover { background-color: rgba(254, 189, 89, 0.1); }
        .upload-btn-wrapper input[type=file] { font-size: 100px; position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; height: 100%; }

        .submit-btn {
            width: 100%; background-color: var(--accent-gold); color: var(--darker-blue);
            border: none; padding: 20px; font-family: 'Oswald', sans-serif; font-weight: 700;
            font-size: 1.2rem; letter-spacing: 2px; cursor: pointer; transition: 0.3s; margin-top: 20px;
        }
        .submit-btn:hover { background-color: #ffcf87; box-shadow: 0 0 15px rgba(254, 189, 89, 0.8); }

        /* --- FOOTER --- */
        .footer-bottom { background-color: var(--darker-blue); padding: 40px 5%; text-align: center; border-top: 3px solid var(--accent-gold); margin-top: 50px; transition: background-color 0.3s ease; }
        .footer-branding { display: flex; flex-direction: column; align-items: center; gap: 15px; }
        .footer-logo { width: 60px; height: 60px; border-radius: 50%; border: 2px solid var(--accent-gold); margin-bottom: 5px; filter: grayscale(30%); }
        
        /* --- MOBILE RESPONSIVE FIXES --- */
<?= mobile_base_css() ?>
        @media (max-width: 768px) {
            header { flex-direction: column; height: auto; padding: 20px; gap: 15px; text-align: center; } 
            .nav-left { flex-direction: column; gap: 10px; }
            .nav-links { 
                display: flex; 
                flex-direction: column; 
                width: 100%; 
                gap: 15px; 
            } 
            .btn-portal, .nav-link { width: 100%; text-align: center; display: block; padding: 12px; }
            .section-header-text { font-size: 1.8rem; } 
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="marquee-content" style="text-transform: uppercase; font-weight: bold; color: var(--accent-gold);">
            <?= e($marquee_text ?? 'ALL SYSTEMS SECURE // NO CRITICAL ALERTS') ?> <?= e($marquee_text ?? 'ALL SYSTEMS SECURE // NO CRITICAL ALERTS') ?>
        </div>
    </div>

    <header>
        <div class="nav-left">
            <img src="https://i.imgur.com/uOClOiX.jpeg" alt="Logo" class="logo-img" onerror="this.src='https://via.placeholder.com/50/17162f/febd59?text=ABC'">
            <div class="agency-name">ABC SECURITY AGENCY</div>
        </div>
       
        <nav class="nav-links">
            <a href="corner.php" class="nav-link">GUARD'S CORNER</a>
            <a href="inbox.php" class="nav-link">GUARD'S INBOX</a>
            
            <button id="themeToggle" class="btn-theme" title="Toggle Light/Dark Mode">
                <i class="fa-solid fa-sun"></i>
            </button>

            <form method="POST" action="../auth/logout-guard.php" style="display:inline;margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-portal" style="border:none;cursor:pointer;font:inherit;">LOGOUT</button>
            </form>
        </nav>
    </header>

    <div class="main-container">

        <form action="" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
       
            <div class="portal-section">
                <span class="section-tag">[HAKBANG 01 / STEP 01]</span>
                <h1 class="section-header-text">PUMILI NG ESTABLISHMENT<br><span style="font-size: 1.2rem; color: var(--text-gray);">(SELECT ESTABLISHMENT)</span></h1>
                
                <div class="form-group">
                    <label class="form-label">LUGAR NG DUTY</label>
                    <span class="form-label-sub">POST / ESTABLISHMENT</span>
                    <select class="form-control" name="Establishment" id="Establishment" required>
                        <option value="" disabled selected>-- PUMILI DITO (SELECT HERE) --</option>
                        <option value="Post-1">Post 1</option>
                        <option value="Post-2">Post 2</option>
                        <option value="Post-3">Post 3</option>
                    </select>
                </div>
            </div>

            <div class="portal-section" style="border-top: 2px solid var(--alert-red);">
                <span class="section-tag" style="color: var(--alert-red);">[HAKBANG 02 / STEP 02] // DGD UPLOAD</span>
                <h1 class="section-header-text">MGA EBIDENSYA<br><span style="font-size: 1.2rem; color: var(--text-gray);">(EVIDENCE & MEDIA ATTACHMENTS)</span></h1>

                <div class="upload-btn-wrapper">
                    <div class="upload-btn">
                         <i class="fa-solid fa-camera"></i> 1. LITRATO NG DGD TEMPLATE<br>
                        <span style="font-size: 0.8rem; font-family: 'Roboto Mono'; opacity: 0.7;">(TAKE PIC OF DGD TEMPLATE)</span>
                    </div>
                    <input type="file" id="report_scan" class="report_scan" name="report_scan" accept="image/*" capture="environment" required onchange="updateFileName(this)" />
                </div>
            </div>

            <div class="portal-section" style="background: transparent; border: none; padding: 0; box-shadow: none;">
                <button type="submit" class="submit-btn">
                    I-SUBMIT SA ADMIN (BUMUO NG HASH)<br>
                    <span style="font-size: 0.9rem; font-family: 'Roboto Mono'; font-weight: normal;">SUBMIT TO ADMIN WITH HASH & END OPERATION</span>
                </button>
            </div>
            
        </form>

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

    <?php
    if (!empty($file_error)) {
        echo "<style>.upload-btn-wrapper::after { content: '" . addslashes($file_error) . "'; display: block; color: var(--alert-red); text-align: center; margin-top: 10px; font-family: 'Roboto Mono'; padding: 10px; background: rgba(255,51,51,0.1); border: 1px dashed var(--alert-red);}</style>";
    }
    
    if (!empty($error)) {
        echo "<div style='color: var(--alert-red); text-align: center; margin: 15px auto; max-width: 1000px; font-family: \"Roboto Mono\"; padding: 15px; background: rgba(255,51,51,0.1); border: 1px dashed var(--alert-red); font-size: 1.1rem;'>" . htmlspecialchars($error) . "</div>";
    }
    ?>

    <script>
        // FILE UPLOAD SCRIPT
        function updateFileName(input) {
            if (input.files && input.files[0]) {
                const fileName = input.files[0].name;
                const btnDiv = input.previousElementSibling;
                btnDiv.style.backgroundColor = 'rgba(254, 189, 89, 0.2)'; 
                btnDiv.style.borderColor = '#00ff00';
                
                if (!btnDiv.dataset.originalHtml) {
                    btnDiv.dataset.originalHtml = btnDiv.innerHTML;
                }
                const safeName = document.createElement('div');
                safeName.textContent = fileName;
                btnDiv.innerHTML = '✅ UPLOADED NA (UPLOADED):<br><span style="font-family: Roboto Mono; font-size: 0.8rem; color: #00ff00;">' + safeName.innerHTML + '</span>';
            }
        }

        // --- THEME TOGGLE LOGIC ---
        document.addEventListener("DOMContentLoaded", function() {
            const themeToggleBtn = document.getElementById('themeToggle');
            const themeIcon = themeToggleBtn.querySelector('i');
            const body = document.body;

            // 1. Check the browser's memory for their saved theme
            const savedTheme = localStorage.getItem('abc_theme');
            
            // 2. If they previously chose light mode, apply it immediately
            if (savedTheme === 'light') {
                body.classList.add('light-mode');
                themeIcon.classList.replace('fa-sun', 'fa-moon');
            }

            // 3. Listen for clicks on the Sun/Moon button
            themeToggleBtn.addEventListener('click', () => {
                body.classList.toggle('light-mode');
                
                // Save the preference
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