<?php
require_once __DIR__ . '/../config/app.php';

auth_require_permission('admin.legacy_portal');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Golden Z-5 Security | Admin Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Antic&family=Oswald:wght@300;400;500;700&family=Roboto:wght@300;400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #00264d;
            --darker-blue: #001a35;
            --accent-gold: #c5a028;
            --text-white: #ffffff;
            --text-gray: #e0e0e0;
            --bg-light: #f4f6f8;
            --alert-red: #ff3333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, button, input, textarea, select {
            font-family: 'Attica', 'Antic', 'Roboto', 'Arial', sans-serif; 
            background-color: var(--bg-light);
            color: #333;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, .nav-link, .btn, .section-header {
            font-family: 'Oswald', 'Attica', 'Arial Narrow', sans-serif; 
            text-transform: uppercase;
        }

        .top-bar {
            background-color: var(--darker-blue);
            color: var(--text-white);
            padding: 8px 0;
            font-size: 0.9rem; 
            white-space: nowrap;
            overflow: hidden;
            border-bottom: 1px solid #334;
            font-family: 'Roboto Mono', monospace;
        }

        .marquee-content {
            display: inline-block;
            animation: scroll-left 25s linear infinite;
            padding-left: 100%;
        }

        @keyframes scroll-left {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }

        header {
            background-color: var(--primary-blue);
            padding: 0 5%;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 4px solid var(--accent-gold);
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .logo-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-gold);
        }

        .nav-links {
            display: flex;
            gap: 30px;
        }

        .nav-link {
            color: var(--text-white);
            text-decoration: none;
            font-size: 1rem;
            letter-spacing: 0.5px;
            opacity: 0.8;
            transition: 0.3s;
            font-weight: bold;
        }

        .nav-link.active, .nav-link:hover {
            opacity: 1;
            text-decoration: underline;
            text-underline-offset: 5px;
            color: var(--accent-gold);
        }

        .login-link {
            color: var(--accent-gold);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-portal {
            background-color: var(--accent-gold);
            color: var(--darker-blue);
            padding: 10px 25px;
            text-decoration: none;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
            padding: 40px 5%;
            background-color: var(--primary-blue);
            color: var(--text-white);
        }

        .hero-left h1 {
            font-size: 4rem;
            line-height: 1;
            margin-bottom: 20px;
        }

        .data-stream-box {
            background-color: var(--text-white);
            color: #333;
            height: 300px;
            padding: 20px;
            font-family: 'Roboto Mono', monospace; 
            font-size: 0.9rem;
            border-top: 5px solid var(--accent-gold);
            overflow-y: hidden;
        }

        .data-header {
            display: flex;
            justify-content: space-between;
            color: var(--accent-gold);
            font-weight: bold;
            margin-bottom: 20px;
        }

        .hero-right {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .status-card {
            background-color: var(--text-white);
            padding: 30px;
            text-align: center;
            color: #333;
        }

        .status-text {
            color: var(--accent-gold);
            font-weight: bold;
            font-family: 'Roboto Mono', monospace;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .action-card {
            background-color: var(--darker-blue);
            padding: 30px;
            border-top: 1px solid #ffffff20;
        }

        .action-title {
            color: var(--accent-gold);
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .action-desc {
            font-size: 0.9rem;
            margin-bottom: 20px;
            opacity: 0.8;
            line-height: 1.4;
        }

        .btn-generate {
            background-color: var(--accent-gold);
            border: none;
            padding: 10px 20px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            text-align: center;
            font-size: 1rem;
        }

        .admin-suite {
            padding: 60px 5%;
            background-color: var(--bg-light);
        }

        .section-header {
            color: var(--accent-gold);
            font-size: 1.2rem;
            margin-bottom: 10px;
            font-family: 'Roboto Mono', monospace;
        }

        .section-title {
            font-size: 3rem;
            color: var(--primary-blue);
            margin-bottom: 40px;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .suite-card {
            background-color: var(--primary-blue);
            color: var(--text-white);
            padding: 40px 30px;
            position: relative;
            transition: transform 0.2s;
        }

        .suite-card:hover {
            transform: translateY(-5px);
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background-color: #ffffff20;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .card-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--accent-gold);
            padding-bottom: 10px;
            display: inline-block;
        }

        .card-text {
            font-size: 1rem;
            opacity: 0.9;
            line-height: 1.5;
            font-family: 'Roboto', sans-serif;
        }

        .legacy-section {
            display: grid;
            grid-template-columns: 55% 45%;
            align-items: stretch;
        }

        .legacy-content {
            background-color: var(--primary-blue);
            color: var(--text-white);
            padding: 80px 10%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .legacy-image {
            background-color: #ddd;
            height: 100%;
            overflow: hidden;
        }

        .legacy-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .footer-info {
            background-color: var(--primary-blue);
            color: var(--text-white);
            padding: 60px 5%;
            display: grid;
            grid-template-columns: 1fr 1fr 1.2fr;
            gap: 40px;
            border-top: 1px solid var(--accent-gold);
        }

        .info-col h3 {
            color: var(--accent-gold);
            margin-bottom: 20px;
            font-size: 1.2rem;
            letter-spacing: 1px;
            font-family: 'Roboto Mono', monospace;
        }

        .info-item {
            margin-bottom: 15px;
            font-size: 1rem;
            border-bottom: 1px solid #ffffff20;
            padding-bottom: 10px;
        }

        .info-item strong {
            display: block;
            margin-bottom: 5px;
            color: var(--accent-gold);
        }

        .personnel-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-family: 'Roboto Mono', monospace;
            font-size: 0.9rem;
        }

        .status-active { color: #fff; }
        .status-sos { color: var(--alert-red); font-weight: bold; }

        .terminal-box {
            border: 1px solid #ffffff50;
            padding: 20px;
            font-family: 'Roboto Mono', monospace;
            font-size: 0.9rem;
        }

        .terminal-box span {
            color: var(--accent-gold);
        }

        .footer-bottom {
            background-color: var(--primary-blue);
            padding: 40px 5%;
            text-align: center;
            color: var(--text-white);
            border-top: 1px solid var(--accent-gold);
        }

        .hash-code {
            font-family: 'Roboto Mono', monospace;
            font-size: 0.8rem;
            margin-bottom: 30px;
            letter-spacing: 2px;
            opacity: 0.7;
            word-break: break-all;
        }

        .footer-branding {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .footer-logo {
            width: 80px;
            height: 80px;
            border: none;
            margin-bottom: 10px;
        }

        .footer-branding h2 {
            font-size: 1.5rem;
        }
        
        .footer-branding p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            header { flex-direction: column; height: auto; padding: 20px; gap: 20px; }
            .nav-links { flex-direction: column; text-align: center; gap: 15px; }
            .hero { grid-template-columns: 1fr; }
            .card-grid { grid-template-columns: 1fr; }
            .legacy-section { grid-template-columns: 1fr; }
            .footer-info { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="marquee-content">
            SECURE ACCESS TERMINAL // STATUS: ENCRYPTED // ALERT OVERRIDE: [RA 10175 COMPLIANT] — [ZERO SLEEP POLICY ENFORCED] — [BOLO: WHITE VAN #NAE-4921] — SECURE ACCESS TERMINAL // TRIANGULATING SATELLITE SIGNAL...
        </div>
    </div>

    <header>
        <div class="nav-left">
            <img src="https://scontent.fmnl4-7.fna.fbcdn.net/v/t39.30808-6/469365841_584252490961700_2176435748873480703_n.jpg?_nc_cat=104&ccb=1-7&_nc_sid=6ee11a&_nc_ohc=Hf-Cu-S2RM0Q7kNvwFGjNsZ&_nc_oc=AdkquvHAbeb-ZI8BdGqJFm7SVhjzIbVysGBb2nZopHs1ENF_KgSeeA74p0qqRynWm69b6lCeY_d9fVB8XRHKy4ZB&_nc_zt=23&_nc_ht=scontent.fmnl4-7.fna&_nc_gid=q-6v21AfQlO7Dnr_sOdQrw&oh=00_AftLZzShwlhDSMUU0HIgDGx-c39IpMAdRroWArS-s4PIqQ&oe=699A107A" alt="Golden Z-5 Logo" class="logo-img">
            <nav class="nav-links">
                <a href="dashboard.php" class="nav-link">DATABASE</a>
                <a href="inbox.php" class="nav-link">INBOX</a>
                <form method="POST" action="../auth/logout-admin.php" style="display:inline;margin:0;">
                    <?= csrf_field() ?>
                    <button type="submit" class="nav-link" style="background:none;border:none;cursor:pointer;font:inherit;color:inherit;">LOGOUT</button>
                </form>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-left">
            <h1>SYSTEM<br>ANALYTICS</h1>
            <div class="data-stream-box">
                <div class="data-header">
                    <span>DATA_STREAM_GZ5_ACTIVE</span>
                    <span>REFRESH_RATE: 0.5s</span>
                </div>
                <p style="opacity: 0.7; font-size: 0.8rem; line-height: 1.6;">
                    [10:42:01] CONNECTING TO SECURE RELAY...<br>
                    [10:42:02] HANDSHAKE VERIFIED.<br>
                    [10:42:03] FETCHING SITE LOGS: INTRAMUROS... OK.<br>
                    [10:42:04] FETCHING SITE LOGS: QUIAPO... PENDING.<br>
                    [10:42:05] BIOMETRIC SCAN: NEGATIVE.<br>
                    [10:42:06] ENCRYPTION KEY: ROTATING.<br>
                    ... AWAITING INPUT
                </p>
            </div>
        </div>
        <div class="hero-right">
            <div class="status-card">
                <div class="status-text">[ LINK_ACTIVE ]</div>
                <div style="font-weight: bold;">TERMINAL_ACCESS_GRANTED</div>
            </div>
            <div class="action-card action-group">
                <div class="action-title">ACTION GROUP</div>
                <div class="action-desc">EXECUTE SYSTEM OVERRIDE OR EXPORT TELEMETRY</div>
                <button class="btn btn-generate">GENERATE REPORT</button>
            </div>
        </div>
    </section>

    <section class="admin-suite">
        <div class="section-header">[ SYSTEM_ADMIN_MODULE ]</div>
        <h2 class="section-title">ADMINISTRATIVE SUITE</h2>
        
        <div class="card-grid">
            <div class="suite-card">
                <div class="card-icon">👮</div>
                <h3 class="card-title">FLEET & PERSONNEL HUB</h3>
                <p class="card-text">Integrated monitoring for all active units and site checkpoints. Real-time shift verification tracking.</p>
            </div>
            <div class="suite-card">
                <div class="card-icon">🕸️</div>
                <h3 class="card-title">STRATEGIC MATRIX</h3>
                <p class="card-text">Secure encrypted channel for command directives, emergency broadcasts, and report vetting protocols.</p>
            </div>
            <div class="suite-card">
                <div class="card-icon">📂</div>
                <h3 class="card-title">INTELLIGENCE HUB</h3>
                <p class="card-text">Central repository for site reports, incident media logs, and cross-terminal diagnostic feeds.</p>
            </div>
        </div>
    </section>

    <section class="legacy-section">
        <div class="legacy-content">
            <div class="section-header">OUR LEGACY</div>
            <h2 class="section-title" style="color: white; margin-bottom: 20px;">GOLDEN Z-5 SECURITY</h2>
            <p style="line-height: 1.8; opacity: 0.9; font-size: 1.1rem;">
                As a leading authority in asset protection and risk management, Golden Z-5 Security and Investigation Agency, Inc. delivers elite investigative and physical security solutions. Our operation remains dedicated to uncompromising professional standards and the safety of our corporate partners.
            </p>
        </div>
        <div class="legacy-image">
            <img src="https://scontent.fmnl4-4.fna.fbcdn.net/v/t39.30808-6/631847716_888215453966664_6599673601985811249_n.jpg?stp=cp6_dst-jpg_tt6&_nc_cat=102&ccb=1-7&_nc_sid=127cfc&_nc_ohc=bJBg6IQH2cUQ7kNvwEC4bVO&_nc_oc=Adkc0beyB063-2spH2IHH5Jf7aAnir4BlNy84sYZk6FCjhEEGxcx2BM8Uq_wAYbp9AbnSI7mRKjMKlr4QW5a59dx&_nc_zt=23&_nc_ht=scontent.fmnl4-4.fna&_nc_gid=-1AHfekOf3rHP3zT24EfKQ&oh=00_AftSnsSvf0FD4rmW5-rNfcOQE3hRZc8bniuXPe_Oep3zlA&oe=699A2752">
        </div>
    </section>

   <section class="footer-info">
        <div class="info-col">
            <h3>// URGENT MEMOS</h3>
            <div class="info-item">
                <strong>DIR-02-2026:</strong> No Sleeping on Duty policy enforced. Strict penalties apply.
            </div>
            <div class="info-item">
                <strong>BOLO:</strong> WHITE HIACE [ABC-1234]. Scouting site activity detected.
            </div>
            <div class="info-item">
                <strong>NOTICE:</strong> Uniform inspection set for Feb 25, 0800 HRS.
            </div>
        </div>

        <div class="info-col">
            <h3>// ACTIVE PERSONNEL</h3>
            <div class="personnel-row">
                <span>M. SANTOS (24)</span>
                <span class="status-active">ACTIVE | 85%</span>
            </div>
            <div class="personnel-row">
                <span>J. DELA CRUZ (52)</span>
                <span class="status-sos">SOS | 12%</span>
            </div>
            <div class="personnel-row">
                <span>A. LUNA (48)</span>
                <span class="status-active">ACTIVE | 92%</span>
            </div>
        </div>

        <div class="info-col">
            <h3>// TERMINAL STATUS</h3>
            <div class="terminal-box">
                <p>Triangulating Satellite Signal... Signal Found.</p>
                <br>
                <span>TRANSMITTED TO BRANCH REPOSITORY.</span>
            </div>
            <div style="margin-top: 15px; font-size: 0.8rem; font-family: 'Roboto Mono';">
                RPT-2026-001 | RESOLVED | INTRAMUROS<br>
                RPT-2026-003 | <span style="color: var(--alert-red)">PENDING</span> | QUIAPO SITE
            </div>
        </div>
    </section>

    <footer class="footer-bottom">
        <div class="hash-code">
            SECURE SESSION HASH (ENCRYPTION_DEMO_KEY):<br>
            U2FsdGVkX19H8j9fH6m0L9pQ2n4r6t8v0x2z4A6B8C0D2E4F6G8H0J2K4L6M8N
        </div>
        <div class="footer-branding">
            <img src="https://scontent.fmnl4-7.fna.fbcdn.net/v/t39.30808-6/469365841_584252490961700_2176435748873480703_n.jpg?_nc_cat=104&ccb=1-7&_nc_sid=6ee11a&_nc_ohc=Hf-Cu-S2RM0Q7kNvwFGjNsZ&_nc_oc=AdkquvHAbeb-ZI8BdGqJFm7SVhjzIbVysGBb2nZopHs1ENF_KgSeeA74p0qqRynWm69b6lCeY_d9fVB8XRHKy4ZB&_nc_zt=23&_nc_ht=scontent.fmnl4-7.fna&_nc_gid=q-6v21AfQlO7Dnr_sOdQrw&oh=00_AftLZzShwlhDSMUU0HIgDGx-c39IpMAdRroWArS-s4PIqQ&oe=699A107A" alt="Golden Z-5 Logo" class="logo-img footer-logo">
            <div style="font-size: 0.8rem; letter-spacing: 1px; font-family: 'Oswald';">CENTRAL COMMAND HEADQUARTERS // [STATUS: ENCRYPTED]</div>
            <h2>Mandaluyong, PH | +63 2 8000 0000</h2>
            <p>GOLDEN Z-5 SECURITY AND INVESTIGATION AGENCY, INC.</p>
            <br>
            <p style="font-size: 0.7rem; opacity: 0.5;">© 2026 GOLDEN Z-5 SECURITY. ALL ENCRYPTED DATA CHANNELS MONITORED.</p>
        </div>
    </footer>

</body>
</html>