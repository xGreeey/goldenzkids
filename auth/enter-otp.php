<?php require_once __DIR__ . '/../config/app.php'; require __DIR__ . '/forgot-access-api.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABC Security Agency | Login Terminal</title>
    <link href="https://fonts.googleapis.com/css2?family=Antic&family=Oswald:wght@300;400;500;700&family=Roboto:wght@300;400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            /* BRAND COLORS */
            --primary-blue: #1e153f;
            --darker-blue: #17162f;
            --accent-gold: #febd59;
            
            /* UI COLORS */
            --text-white: #ffffff;
            --text-gray: #e0e0e0;
            --bg-light: #f4f6f8;
            --panel-bg: #110d24;
            --input-bg: #110d24;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Antic', 'Roboto', 'Arial', sans-serif; 
            background-color: var(--primary-blue);
            color: var(--text-white);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, .nav-link, .btn {
            font-family: 'Oswald', sans-serif; 
            text-transform: uppercase;
        }

        header {
            background-color: var(--darker-blue);
            padding: 0 5%;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid var(--accent-gold);
            z-index: 100;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-gold);
        }

        .agency-name {
            font-family: 'Oswald', sans-serif; 
            font-size: 1.2rem; 
            letter-spacing: 1px;
            line-height: 1.1;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-blue);
            padding: 40px 20px;
        }

        .login-card {
            background-color: var(--panel-bg);
            border: 1px solid rgba(254, 189, 89, 0.3);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            position: relative;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent-gold), transparent);
        }

        .login-title {
            color: var(--accent-gold);
            font-size: 2rem;
            margin-bottom: 5px;
            letter-spacing: 1px;
            border-bottom: 1px solid #ffffff30;
            padding-bottom: 15px;
        }

        .login-subtitle {
            font-family: 'Roboto Mono', monospace;
            font-size: 0.75rem;
            color: var(--text-white);
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .role-toggle {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .btn-role {
            flex: 1;
            padding: 15px;
            font-family: 'Oswald', sans-serif;
            font-size: 1rem;
            letter-spacing: 1px;
            cursor: pointer;
            text-align: center;
            border: 1px solid #ffffff30;
            background: transparent;
            color: #ffffff;
            transition: 0.3s;
        }

        .btn-role.active {
            background-color: var(--accent-gold);
            color: var(--darker-blue);
            border-color: var(--accent-gold);
            font-weight: bold;
        }

        .btn-role:hover:not(.active) {
            border-color: var(--accent-gold);
        }

        .input-group {
            margin-bottom: 25px;
        }

       

        .input-label {
            display: flex;
            justify-content: space-between;
            color: var(--text-white);
            font-family: 'Roboto', sans-serif;
            font-size: 0.85rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .index {
            color: var(--accent-gold);
            text-decoration: underline;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            background-color: var(--input-bg);
            border: 1px solid #ffffff30;
            color: var(--text-white);
            font-family: 'Roboto', sans-serif;
            font-size: 1rem;
            outline: none;
            transition: 0.3s;
        }

        .form-input:focus {
            border-color: var(--accent-gold);
            background-color: var(--darker-blue);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
            font-style: italic;
            font-size: 0.9rem;
        }

        .btn-authorize {
            width: 100%;
            padding: 15px;
            background-color: var(--accent-gold);
            color: var(--darker-blue);
            border: none;
            font-family: 'Oswald', sans-serif;
            font-size: 1.1rem;
            font-weight: bold;
            letter-spacing: 2px;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }

        .btn-authorize:hover {
            background-color: #ffcf87;
            box-shadow: 0 0 15px rgba(254, 189, 89, 0.4);
        }

        .first-time-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            color: var(--accent-gold);
            font-family: 'Roboto Mono', monospace;
            font-size: 0.8rem;
            text-decoration: none;
            opacity: 0.8;
            padding-top: 20px;
            border-top: 1px dotted #ffffff20;
        }

        .first-time-link:hover {
            opacity: 1;
            text-decoration: underline;
        }

        /* Hide admin inputs by default */
        #admin-inputs {
            display: none;
        }

        @media (max-width: 768px) {
            header { flex-direction: column; height: auto; padding: 20px; text-align: center; }
            .nav-left { flex-direction: column; }
            .login-card { padding: 25px; }
        }
    </style>
</head>
<body>

    <header>
        <div class="nav-left">
            <img src="https://i.imgur.com/uOClOiX.jpeg" alt="Logo" class="logo-img">
            <div class="agency-name">ABC SECURITY AGENCY
        </div>
    </header>

    <div class="main-content">
        
        <div class="login-card">
            <h1 class="login-title">Pin Recovery</h1>
          

            <form id="forgotpin" action="" method="POST">
                <input type="hidden" id="login">
                    <div class="input-group">
                        <div class="input-label">
                            <span>Company ID</span>
                        </div>
                         <input type="text" name="company_id" id="company_id" class="form-input" placeholder="I-type ang Email na binigay ng HQ" required>
                             <div class="input-label">
                            <span>One Time Password</span>
                        </div>
                            <input type="password" name="otp" id="otp" class="form-input" placeholder="• • • • • •" maxlength="6" required inputmode="numeric" pattern="[0-9]*" oninput="this.value = this.value.replace(/[^0-9]/g,'');">
                         <a href="<?= app_url('index.php') ?>" class="index">Back</a>
                <button type="submit" class="btn-authorize" id="submit-btn-text">AUTHORIZE FIELD ACCESS</button>
                    </div>
                </div>
                 
                
            </form>
        </div>

    </div>
<?php
  if ($email_Err != null) {
    ?><style>.company_id-error{display: block; color: red;}</style><?php
  }


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 
  

?>
    <script>
       /* function selectRole(role) {
            // Get Elements
            const btnGuard = document.getElementById('btnGuard');
            const btnAdmin = document.getElementById('btnAdmin');
            const guardInputs = document.getElementById('guard-inputs');
            const adminInputs = document.getElementById('admin-inputs');
            const roleInput = document.getElementById('login_role');
            const subtitleText = document.getElementById('subtitle-text');
            const submitBtnText = document.getElementById('submit-btn-text');
            const firstTimeLink = document.getElementById('first-time-link');

            // Form Fields for toggling 'required' attribute
            const gId = document.getElementById('guard_id');
            const gPin = document.getElementById('guard_pin');
            const aUser = document.getElementById('admin_user');
            const aPass = document.getElementById('admin_pass');

            if (role === 'guard') {
                // UI Changes
                btnGuard.classList.add('active');
                btnAdmin.classList.remove('active');
                guardInputs.style.display = 'block';
                adminInputs.style.display = 'none';
                firstTimeLink.style.display = 'block';
                
                // Text Updates
                subtitleText.innerText = '// AUTHENTICATION SEQUENCE // FIELD UNIT';
                submitBtnText.innerText = 'AUTHORIZE FIELD ACCESS';
                
                // Backend Value
                roleInput.value = 'guard';

                // Toggle Required Attributes (Prevents HTML5 validation errors on hidden fields)
                gId.required = true;
                gPin.required = true;
                aUser.required = false;
                aPass.required = false;

            } else {
                // UI Changes
                btnAdmin.classList.add('active');
                btnGuard.classList.remove('active');
                adminInputs.style.display = 'block';
                guardInputs.style.display = 'none';
                firstTimeLink.style.display = 'none'; // Admins don't need the PIN creation link
                
                // Text Updates
                subtitleText.innerText = '// AUTHENTICATION SEQUENCE // CENTRAL COMMAND';
                submitBtnText.innerText = 'AUTHORIZE CENTRAL ACCESS';
                
                // Backend Value
                roleInput.value = 'admin';

                // Toggle Required Attributes
                aUser.required = true;
                aPass.required = true;
                gId.required = false;
                gPin.required = false;
            }
        } */
    </script>

</body>
</html>