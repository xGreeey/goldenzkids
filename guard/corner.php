<?php
require_once __DIR__ . '/../config/app.php';

// 3. Fetch the latest 5 memos for the UI
 /* $memo_query = "SELECT * FROM memos ORDER BY date_published DESC LIMIT 5"; */
 /* $memos_result = $conn->query($memo_query); */

// 4. The Decryption Key
$encryption_key = "ABC_SecureKey_2026_xYz12345"; // Must match the admin key!
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABC Security Agency | Guard Corner</title>
    <link href="https://fonts.googleapis.com/css2?family=Antic&family=Oswald:wght@300;400;500;700&family=Roboto:wght@300;400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1e153f;
            --darker-blue: #17162f;
            --accent-gold: #febd59;
            --text-white: #ffffff;
            --text-gray: #e0e0e0;
            --alert-red: #ff3333;
            --info-blue: #00ccff;
            --panel-bg: #110d24;
        }

        /* --- LIGHT MODE THEME OVERRIDES --- */
        body.light-mode {
            --primary-blue: #f4f6f8;   
            --darker-blue: #e2e8f0;    
            --panel-bg: #ffffff;       
            --text-white: #0f172a;     
            --text-gray: #475569;
        }
        
        body.light-mode .info-card, body.light-mode .scroll-card { border-color: #cbd5e1; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        body.light-mode .memo-item { border-bottom-color: #cbd5e1; }
        body.light-mode .page-title, body.light-mode .card-header { color: #0f172a; }
        body.light-mode .tag-sub { color: #475569; }
        
        /* Special light mode adjustment for the dark gun safety card */
        body.light-mode .gun-safety-card { background-color: #fef2f2; border-color: #f87171; }
        body.light-mode .gun-safety-card .safety-title { color: #dc2626; }
        body.light-mode .gun-safety-card .safety-rule { color: #0f172a; border-bottom-color: #fca5a5; }
        body.light-mode .gun-safety-card .tag-sub { color: #ef4444; }

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
            font-family: 'Attica', 'Antic', 'Roboto', 'Arial', sans-serif; 
            background-color: var(--primary-blue);
            color: var(--text-white);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        h1, h2, h3, h4, .btn { font-family: 'Oswald', sans-serif; text-transform: uppercase; }

        /* --- HEADER STYLES --- */
        header {
            background-color: var(--darker-blue); padding: 0 5%; height: 80px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 3px solid var(--accent-gold); position: sticky; top: 0; z-index: 1000;
            transition: background-color 0.3s ease;
        }

        .logo-area { display: flex; align-items: center; gap: 15px; }
        .logo-img { 
            width: 55px !important; 
            height: 55px !important; 
            border-radius: 50% !important; 
            object-fit: cover !important; 
            aspect-ratio: 1 / 1 !important; 
            border: 2px solid var(--accent-gold);
            flex-shrink: 0; 
            background-color: var(--panel-bg);
            color: transparent; 
        }
        .agency-name { font-family: 'Oswald', sans-serif; font-size: 1.2rem; letter-spacing: 1px; line-height: 1.1; }

        .btn-back {
            color: var(--accent-gold); text-decoration: none; font-family: 'Oswald', sans-serif; font-size: 0.9rem;
            border: 1px solid var(--accent-gold); padding: 8px 20px; transition: 0.3s;
            display: flex; align-items: center; gap: 10px; white-space: nowrap;
        }
        .btn-back:hover { background-color: var(--accent-gold); color: var(--darker-blue); font-weight: bold; }

        /* --- LAYOUT STYLES --- */
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }

        .page-title { font-size: 3rem; color: var(--accent-gold); margin-bottom: 10px; border-bottom: 1px solid #ffffff20; padding-bottom: 20px; transition: color 0.3s ease;}
        .page-subtitle { font-family: 'Roboto Mono', monospace; opacity: 0.7; margin-bottom: 40px; }

        .tag-sub { display: block; font-size: 0.8em; opacity: 0.6; font-style: italic; font-weight: normal; margin-top: 2px; font-family: 'Roboto', sans-serif; transition: color 0.3s ease;}

        .top-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .info-card { background-color: var(--panel-bg); border: 1px solid #ffffff20; padding: 30px; position: relative; margin-bottom: 30px; transition: background-color 0.3s ease, border-color 0.3s ease;}
        .card-header { color: var(--accent-gold); font-size: 1.5rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; transition: color 0.3s ease;}

        .memo-item { margin-bottom: 15px; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 15px; transition: border-color 0.3s ease;}
        .memo-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

        .knowledge-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 30px; margin-bottom: 50px; }

        .scroll-card { background-color: var(--panel-bg); border-top: 3px solid var(--accent-gold); padding: 30px; height: 600px; overflow-y: auto; margin-bottom: 0; transition: background-color 0.3s ease, border-color 0.3s ease;}
        .scroll-card::-webkit-scrollbar { width: 8px; }
        .scroll-card::-webkit-scrollbar-thumb { background-color: var(--accent-gold); }
        .scroll-card::-webkit-scrollbar-track { background-color: #000; }

        .order-list { list-style: none; counter-reset: order-counter; }
        .order-list li { margin-bottom: 25px; padding-left: 40px; position: relative; line-height: 1.4; opacity: 0.95; font-weight: bold; }
        .order-list li::before {
            counter-increment: order-counter; content: counter(order-counter); position: absolute; left: 0; top: 0;
            font-family: 'Oswald', sans-serif; font-size: 1.2rem; color: var(--accent-gold); font-weight: bold;
        }

        .gun-safety-card { background-color: #1a0505; border: 1px solid var(--alert-red); padding: 30px; margin-bottom: 30px; transition: background-color 0.3s ease, border-color 0.3s ease;}
        .safety-title { color: var(--alert-red); font-size: 2rem; margin-bottom: 20px; display: flex; align-items: center; gap: 15px; transition: color 0.3s ease;}
        .safety-rule { margin-bottom: 15px; font-size: 1.1rem; font-weight: bold; border-bottom: 1px dashed #ff333350; padding-bottom: 10px; transition: color 0.3s ease, border-color 0.3s ease;}
        
        /* --- FOOTER STYLES --- */
        .footer-bottom { background-color: var(--darker-blue); padding: 40px 5%; text-align: center; border-top: 3px solid var(--accent-gold); margin-top: 50px; transition: background-color 0.3s ease;}
        .footer-branding { display: flex; flex-direction: column; align-items: center; gap: 15px; }
        .footer-logo { width: 60px; height: 60px; border-radius: 50%; border: 2px solid var(--accent-gold); margin-bottom: 5px; filter: grayscale(30%); }

        /* --- THE MOBILE FIX --- */
        @media (max-width: 768px) {
            header { padding: 15px 5%; height: auto; gap: 15px; flex-direction: column; text-align: center;}
            .logo-area { flex-direction: column; gap: 10px; }
            .container { padding: 20px 15px; } 
            .page-title { font-size: 2.2rem; }
            .page-subtitle { margin-bottom: 25px; }
            .btn-back { padding: 8px 12px; font-size: 0.8rem; }
            .btn-back span { display: none; } 
            .top-grid { grid-template-columns: 1fr; }
            .knowledge-grid { grid-template-columns: 1fr; }
            .scroll-card { height: auto; max-height: 400px; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo-area">
            <img src="https://i.imgur.com/uOClOiX.jpeg" 
                 alt="ABC Security Logo" 
                 class="logo-img"
                 onerror="this.src='https://via.placeholder.com/55/17162f/febd59?text=ABC'">
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
        
        <h1 class="page-title">GUARD CORNER <span class="tag-sub" style="display:inline; font-size: 0.5em; vertical-align: middle;">(SULOK NG GUWARDIYA)</span></h1>
        <p class="page-subtitle">// OFFICE RESOURCES & COMPLIANCE DATA <br><span style="opacity:0.6;">(MGA GAMIT SA OPISINA AT DATOS NG PAGSUNOD)</span></p>

        <div class="info-card" style="border-left: 4px solid var(--alert-red);">
            <h2 class="card-header" style="color: var(--alert-red);"><i class="fa-solid fa-bullhorn"></i> URGENT MEMOS <span class="tag-sub" style="margin-left: 10px; color: inherit;">(MGA MAHALAGANG MEMO)</span></h2>
            
            <?php 
          /** if ($memos_result && $memos_result->num_rows > 0) {
                while($memo = $memos_result->fetch_assoc()) { 
                    
                    $encrypted_data = $memo['encrypted_content'];
                    $iv = base64_decode($memo['iv_salt']);
                    
                    $decrypted_content = openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv) ?: "[Decryption Failed]";
            ?>
                    <div class="memo-item">
                        <strong style="color: var(--accent-gold);"><?= htmlspecialchars($memo['memo_type']) ?>:</strong> <?= htmlspecialchars($memo['subject']) ?>
                        <br><span style="font-size: 0.75rem; opacity: 0.8;">To: <?= htmlspecialchars($memo['addressee']) ?> | <?= date('M d, Y H:i', strtotime($memo['date_published'])) ?></span>
                        <span class="tag-sub" style="margin-top: 5px; opacity: 0.9;"><?= htmlspecialchars($decrypted_content) ?></span>
                    </div>
            <?php 
                }
            } else {
                echo "<div class='memo-item'><span class='tag-sub'>No active memos at this time. (Walang bagong memo sa ngayon.)</span></div>";
            } **/
            ?>
        </div>  

        <div class="top-grid">
            
            <div class="info-card" style="border-top: 3px solid var(--info-blue); padding: 0; margin-bottom: 0;">
                <h2 class="card-header" style="color: var(--info-blue); padding: 20px 20px 10px 20px; margin-bottom: 0;"><i class="fa-solid fa-satellite-dish"></i> PNP-SOSIA <span class="tag-sub" style="margin-left: 10px; color: inherit;">(MGA ANUNSYO)</span></h2>
                
                <div style="padding: 0 20px 20px 20px; text-align: center;">
                    <iframe src="https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2Fcsg.sosia.pnp&tabs=timeline&width=500&height=400&small_header=true&adapt_container_width=true&hide_cover=false&show_facepile=false" 
                        width="100%" 
                        height="400" 
                        style="border:none; overflow:hidden; border-radius: 8px; background: white; margin: 0 auto 15px auto; display: block; max-width: 500px;" 
                        scrolling="no" 
                        frameborder="0" 
                        allowfullscreen="true" 
                        allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
                    </iframe>
                    
                    <a href="https://www.facebook.com/csg.sosia.pnp" target="_blank" style="display: block; padding: 12px; background: #1877f2; color: white; text-decoration: none; border-radius: 5px; font-family: 'Roboto Mono', monospace; font-size: 0.9rem; font-weight: bold; border: 1px solid #ffffff40;">
                        &#8594; OPEN SOSIA OFFICIAL PAGE
                    </a>
                </div>
            </div>

            <div class="info-card" style="border-top: 3px solid var(--info-blue); padding: 0; margin-bottom: 0;">
                <h2 class="card-header" style="color: var(--info-blue); padding: 20px 20px 10px 20px; margin-bottom: 0;"><i class="fa-solid fa-satellite-dish"></i> PADPAO INC. <span class="tag-sub" style="margin-left: 10px; color: inherit;">(MGA ALERTO)</span></h2>
                
                <div style="padding: 0 20px 20px 20px; text-align: center;">
                    <iframe src="https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2FPADPAOINCSINCE1958&tabs=timeline&width=500&height=400&small_header=true&adapt_container_width=true&hide_cover=false&show_facepile=false" 
                        width="100%" 
                        height="400" 
                        style="border:none; overflow:hidden; border-radius: 8px; background: white; margin: 0 auto 15px auto; display: block; max-width: 500px;" 
                        scrolling="no" 
                        frameborder="0" 
                        allowfullscreen="true" 
                        allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
                    </iframe>
                    
                    <a href="https://www.facebook.com/PADPAOINCSINCE1958" target="_blank" style="display: block; padding: 12px; background: #1877f2; color: white; text-decoration: none; border-radius: 5px; font-family: 'Roboto Mono', monospace; font-size: 0.9rem; font-weight: bold; border: 1px solid #ffffff40;">
                        &#8594; OPEN PADPAO OFFICIAL PAGE
                    </a>
                </div>
            </div>

        </div>

        <div class="info-card" style="margin-bottom: 50px;">
            <h2 class="card-header"><i class="fa-solid fa-building"></i> OFFICE CORNER <span class="tag-sub" style="margin-left: 10px;">(MGA PALATUNTUNAN)</span></h2>
            <p style="line-height: 1.6; opacity: 0.9; margin-bottom: 20px;">
                This section is for personnel currently reporting to the Head Office for disciplinary action, briefing, or status updates. Please maintain professional conduct while waiting for the Operations Manager.
                <span class="tag-sub">Ang seksyong ito ay para sa mga tauhan na ipinatawag sa Head Office para sa disciplinary action, briefing, o status updates. Panatilihin ang propesyonal na asal habang naghihintay sa Operations Manager.</span>
            </p>
            <div style="background: rgba(255,255,255,0.05); padding: 15px; border-left: 2px solid var(--accent-gold);">
                <strong>REMINDER:</strong> Ensure your uniform is complete before entering the Admin Office. Prepare your Daily Time Record (DTR) and Duty Detail Order (DDO).
                <span class="tag-sub">PAALALA: Siguraduhing kumpleto ang iyong uniporme bago pumasok sa Admin Office. Ihanda ang iyong Daily Time Record (DTR) at Duty Detail Order (DDO).</span>
            </div>
        </div>

        <div class="knowledge-grid">
            
            <div class="scroll-card">
                <h2 class="card-header">11 GENERAL ORDERS <br><span class="tag-sub">(11 PANGKALAHATANG UTOS)</span></h2>
                <ol class="order-list">
                    <li>
                        To take charge of this post and all government property in view.
                        <span class="tag-sub">Pangasiwaan ang post na ito at lahat ng pag-aari ng gobyerno na nakikita.</span>
                    </li>
                    <li>
                        To walk my post in a military manner, keeping always on the alert and observing everything that takes place within sight or hearing.
                        <span class="tag-sub">Lumakad sa aking post sa paraang militar, laging alerto at nagmamasid sa lahat ng nagaganap sa abot ng paningin o pandinig.</span>
                    </li>
                    <li>
                        To report all violations of orders I am instructed to enforce.
                        <span class="tag-sub">Iulat ang lahat ng paglabag sa mga utos na ipinatutupad sa akin.</span>
                    </li>
                    <li>
                        To repeat all calls from posts more distant from the guardhouse than my own.
                        <span class="tag-sub">Ulitin ang lahat ng tawag mula sa mga post na mas malayo sa guardhouse kaysa sa aking sarili.</span>
                    </li>
                    <li>
                        To quit my post only when properly relieved.
                        <span class="tag-sub">Huwag lisanin ang post hanggang hindi maayos na napalitan.</span>
                    </li>
                    <li>
                        To receive, obey, and pass on to the sentry who relieves me all orders from the commanding officer, officer of the day, and officers and non-commissioned officers of the guard only.
                        <span class="tag-sub">Tanggapin, sundin, at ipasa sa papalit na guwardiya ang lahat ng utos mula sa commanding officer, officer of the day, at mga opisyal ng guwardiya lamang.</span>
                    </li>
                    <li>
                        To talk to no one except in the line of duty.
                        <span class="tag-sub">Huwag makipag-usap kaninuman maliban kung may kinalaman sa tungkulin.</span>
                    </li>
                    <li>
                        To give the alarm in case of fire or disorder.
                        <span class="tag-sub">Magbigay ng alarma kung may sunog o gulo.</span>
                    </li>
                    <li>
                        To call the corporal of the guard in any case not covered by instructions.
                        <span class="tag-sub">Tumawag sa corporal ng guwardiya sa anumang kaso na hindi saklaw ng mga tagubilin.</span>
                    </li>
                    <li>
                        To salute all officers and all colors and standards not cased.
                        <span class="tag-sub">Sumaludo sa lahat ng opisyal at sa lahat ng kulay at pamantayan na hindi nakasilid.</span>
                    </li>
                    <li>
                        To be especially watchful at night, and during the time for challenging, to challenge all persons on or near my post, and to allow no one to pass without proper authority.
                        <span class="tag-sub">Maging lalong mapagmasid sa gabi, at sa oras ng paghamon, hamunin ang lahat ng tao sa o malapit sa aking post, at huwag pahintulutan ang sinuman na dumaan nang walang wastong awtoridad.</span>
                    </li>
                </ol>
            </div>

            <div class="scroll-card">
                <h2 class="card-header">GUARD'S CREED & ETHICS <br><span class="tag-sub">(KREDO AT ETIKA)</span></h2>
                
                <h3 style="color:var(--accent-gold); margin-bottom: 5px;">SECURITY GUARD'S CREED</h3>
                <span class="tag-sub" style="margin-bottom: 15px; display:block;">(KREDO NG GUWARDIYA)</span>
                
                <p style="margin-bottom: 30px; line-height: 1.6; font-style: italic;">
                    "As a security guard, my fundamental duty is to protect lives and property and maintain order within my place of duty; protect the interest of my employer and our clients and the security and stability of our government and country without compromise and prejudice, honest in my actions, words and thought; and do my best to uphold the principle: MAKADIYOS, MAKABAYAN, MAKATAO at MAKAKALIKASAN."
                    <span class="tag-sub" style="margin-top:10px;">(Bilang isang security guard, ang aking pangunahing tungkulin ay protektahan ang buhay at ari-arian at panatilihin ang kaayusan sa aking lugar ng tungkulin... tapat sa aking mga kilos, salita at isip; at gawin ang aking makakaya upang itaguyod ang prinsipyo: MAKADIYOS, MAKABAYAN, MAKATAO at MAKAKALIKASAN.)</span>
                </p>

                <h3 style="color:var(--accent-gold); margin-bottom: 5px;">CODE OF CONDUCT (Excerpt)</h3>
                <span class="tag-sub" style="margin-bottom: 15px; display:block;">(Halaw)</span>

                <ul style="list-style: none; padding: 0; line-height: 1.6;">
                    <li style="margin-bottom: 15px;">
                        ► Shall carry with him at all times his license and identification card.
                        <span class="tag-sub">Dapat dalhin sa lahat ng oras ang kanyang lisensya at identification card.</span>
                    </li>
                    <li style="margin-bottom: 15px;">
                        ► Shall not use his license and privileges to the prejudice of the public.
                        <span class="tag-sub">Hindi dapat gamitin ang kanyang lisensya at pribilehiyo sa ikapipinsala ng publiko.</span>
                    </li>
                    <li style="margin-bottom: 15px;">
                        ► Shall not engage in any unnecessary conversation with anybody except in the discharge of his duties.
                        <span class="tag-sub">Hindi dapat makisali sa anumang hindi kinakailangang pakikipag-usap kaninuman maliban sa pagtupad ng kanyang mga tungkulin.</span>
                    </li>
                    <li style="margin-bottom: 15px;">
                        ► Shall not read newspapers, magazines, books, etc., while on duty.
                        <span class="tag-sub">Hindi dapat magbasa ng dyaryo, magasin, libro, atbp., habang nasa duty.</span>
                    </li>
                    <li style="margin-bottom: 15px;">
                        ► Shall not drink any alcoholic beverages immediately before and during his tour of duty.
                        <span class="tag-sub">Hindi dapat uminom ng anumang nakalalasing na inumin bago at habang nasa oras ng duty.</span>
                    </li>
                </ul>
            </div>

        </div>

        <div class="gun-safety-card">
            <div class="safety-title">
                <span><i class="fa-solid fa-triangle-exclamation"></i></span> CARDINAL RULES OF GUN SAFETY <br>
                <span class="tag-sub" style="margin-left: 10px; font-size: 0.5em;">(MGA PANGUNAHING TUNTUNIN SA KALIGTASAN NG BARIL)</span>
            </div>
            <div style="opacity: 0.9;">
                <div class="safety-rule">
                    1. TREAT EVERY GUN AS IF IT IS LOADED.
                    <span class="tag-sub">(TRATUHIN ANG BAWAT BARIL NA PARANG MAY BALA.)</span>
                </div>
                <div class="safety-rule">
                    2. NEVER POINT THE MUZZLE AT ANYTHING YOU ARE NOT WILLING TO DESTROY.
                    <span class="tag-sub">(HUWAG ITUTOK ANG NGUSO NG BARIL SA ANUMANG BAGAY NA HINDI MO GUSTONG SIRAIN.)</span>
                </div>
                <div class="safety-rule">
                    3. KEEP YOUR FINGER OFF THE TRIGGER UNTIL YOUR SIGHTS ARE ON THE TARGET.
                    <span class="tag-sub">(ILAYO ANG DALIRI SA GASA (TRIGGER) HANGGA'T HINDI NAKATUTOK ANG SIGHTS SA TARGET.)</span>
                </div>
                <div class="safety-rule" style="border:none;">
                    4. BE SURE OF YOUR TARGET AND WHAT IS BEYOND IT.
                    <span class="tag-sub">(SIGURADUHIN ANG IYONG TARGET AT ANG NASA LIKOD NITO.)</span>
                </div>
            </div>
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

    <script>
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
</body>
</html>