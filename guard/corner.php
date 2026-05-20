<?php
require_once __DIR__ . '/php/bootstrap.php';

auth_require_permission('guard.corner.view');

// 3. Fetch the latest 5 memos for the UI
 /* $memo_query = "SELECT * FROM memos ORDER BY date_published DESC LIMIT 5"; */
 /* $memos_result = $conn->query($memo_query); */

// 4. The Decryption Key
$encryption_key = "ABC_SecureKey_2026_xYz12345"; // Must match the admin key!
?>
<?php guard_head('Guard Corner', 'guard-portal guard-corner');
guard_layout_header_back();
?>
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
                    
                    <a href="https://www.facebook.com/csg.sosia.pnp" target="_blank" style="display: block; padding: 12px; background: #1877f2; color: white; text-decoration: none; border-radius: 5px; font-family: var(--font-body-family); font-size: 0.9rem; font-weight: bold; border: 1px solid #ffffff40;">
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
                    
                    <a href="https://www.facebook.com/PADPAOINCSINCE1958" target="_blank" style="display: block; padding: 12px; background: #1877f2; color: white; text-decoration: none; border-radius: 5px; font-family: var(--font-body-family); font-size: 0.9rem; font-weight: bold; border: 1px solid #ffffff40;">
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
                        &bull; Shall carry with him at all times his license and identification card.
                        <span class="tag-sub">Dapat dalhin sa lahat ng oras ang kanyang lisensya at identification card.</span>
                    </li>
                    <li style="margin-bottom: 15px;">
                        &bull; Shall not use his license and privileges to the prejudice of the public.
                        <span class="tag-sub">Hindi dapat gamitin ang kanyang lisensya at pribilehiyo sa ikapipinsala ng publiko.</span>
                    </li>
                    <li style="margin-bottom: 15px;">
                        &bull; Shall not engage in any unnecessary conversation with anybody except in the discharge of his duties.
                        <span class="tag-sub">Hindi dapat makisali sa anumang hindi kinakailangang pakikipag-usap kaninuman maliban sa pagtupad ng kanyang mga tungkulin.</span>
                    </li>
                    <li style="margin-bottom: 15px;">
                        &bull; Shall not read newspapers, magazines, books, etc., while on duty.
                        <span class="tag-sub">Hindi dapat magbasa ng dyaryo, magasin, libro, atbp., habang nasa duty.</span>
                    </li>
                    <li style="margin-bottom: 15px;">
                        &bull; Shall not drink any alcoholic beverages immediately before and during his tour of duty.
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
<?php guard_footer();