<?php
$donor_count = 0; $donated = 0; $conn = null;
try {
    $host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
    $user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
    $name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';
    $port = (int)(getenv('MYSQLPORT') ?: 3306);
    $conn = @mysqli_connect($host, $user, $pass, $name, $port);
    if ($conn) {
        $donor_count = (int)mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM donors"))[0];
        $donated = (int)mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM donors WHERE status='Donated'"))[0];
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Bank — Donate Blood, Save Lives</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
        :root{--red:#e63946;--red-dark:#c1121f;--red-light:#fff0f0;--navy:#1d3557;--navy-light:#457b9d;--bg:#fafafa;--white:#fff;--text:#1e293b;--muted:#64748b;--border:#e2e8f0}
        html{scroll-behavior:smooth}
        body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);overflow-x:hidden}
        nav{position:fixed;top:0;left:0;right:0;z-index:100;padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,0.9);backdrop-filter:blur(16px);border-bottom:1px solid rgba(0,0,0,0.06)}
        .nav-logo{display:flex;align-items:center;gap:.6rem;font-size:1.5rem;font-weight:800;color:var(--navy);text-decoration:none;letter-spacing:-.03em}
        .nav-logo svg{color:var(--red)}.nav-logo span{color:var(--red)}
        .nav-links{display:flex;align-items:center;gap:1rem}
        .nav-links a{text-decoration:none;font-weight:500;font-size:.95rem}
        .btn-ghost{color:var(--muted);padding:.5rem 1rem;border-radius:8px;transition:all .2s}
        .btn-ghost:hover{color:var(--navy);background:var(--border)}
        .btn-red{background:var(--red);color:#fff;padding:.6rem 1.4rem;border-radius:10px;font-weight:600;transition:all .2s;box-shadow:0 4px 12px rgba(230,57,70,.3)}
        .btn-red:hover{background:var(--red-dark);transform:translateY(-1px);box-shadow:0 6px 20px rgba(230,57,70,.4)}
        .btn-outline-nav{color:var(--navy);border:2px solid var(--border);padding:.5rem 1.2rem;border-radius:10px;font-weight:600;transition:all .2s}
        .btn-outline-nav:hover{border-color:var(--navy);background:var(--navy);color:#fff}
        .hamburger{display:none;background:none;border:none;cursor:pointer;padding:.4rem}
        .mobile-menu{display:none;position:fixed;top:70px;left:0;right:0;background:#fff;padding:1.5rem 2rem;flex-direction:column;gap:.75rem;border-bottom:1px solid var(--border);box-shadow:0 4px 24px rgba(0,0,0,.08);z-index:99}
        .mobile-menu.open{display:flex}
        .mobile-menu a{font-size:1rem;font-weight:600;color:var(--navy);text-decoration:none;padding:.75rem 1rem;border-radius:10px;transition:background .2s}
        .mobile-menu a:hover{background:var(--red-light);color:var(--red)}
        @media(max-width:768px){.nav-links{display:none}.hamburger{display:block}}

        /* HERO */
        .hero{min-height:100vh;display:flex;align-items:center;padding:7rem 2rem 4rem;max-width:1280px;margin:0 auto;gap:4rem}
        .hero-text{flex:1;min-width:300px}
        .hero-badge{display:inline-flex;align-items:center;gap:.5rem;background:var(--red-light);color:var(--red);padding:.4rem 1rem;border-radius:999px;font-size:.85rem;font-weight:600;margin-bottom:1.5rem;border:1px solid rgba(230,57,70,.2)}
        .hero-badge svg{width:14px;height:14px}
        h1{font-size:clamp(2.5rem,5vw,4rem);font-weight:900;line-height:1.05;letter-spacing:-.04em;color:var(--navy);margin-bottom:1.5rem}
        h1 .highlight{color:var(--red);position:relative;display:inline-block}
        h1 .highlight::after{content:'';position:absolute;bottom:-4px;left:0;right:0;height:4px;background:var(--red);border-radius:2px;opacity:.3}
        .hero-desc{font-size:1.15rem;color:var(--muted);line-height:1.7;margin-bottom:2.5rem;max-width:520px}
        .hero-cta{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:3rem}
        .btn-hero-primary{display:inline-flex;align-items:center;gap:.5rem;background:var(--red);color:#fff;padding:.9rem 2rem;border-radius:12px;font-weight:700;font-size:1rem;text-decoration:none;transition:all .25s;box-shadow:0 8px 24px rgba(230,57,70,.35)}
        .btn-hero-primary:hover{background:var(--red-dark);transform:translateY(-2px);box-shadow:0 12px 32px rgba(230,57,70,.45)}
        .btn-hero-secondary{display:inline-flex;align-items:center;gap:.5rem;background:#fff;color:var(--navy);padding:.9rem 2rem;border-radius:12px;font-weight:700;font-size:1rem;text-decoration:none;transition:all .25s;border:2px solid var(--border);box-shadow:0 4px 12px rgba(0,0,0,.06)}
        .btn-hero-secondary:hover{border-color:var(--navy);transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
        .hero-stats{display:flex;gap:2.5rem;flex-wrap:wrap}
        .stat{display:flex;flex-direction:column}
        .stat-num{font-size:1.8rem;font-weight:800;color:var(--navy);letter-spacing:-.03em}
        .stat-label{font-size:.85rem;color:var(--muted);font-weight:500}
        .stat-divider{width:1px;background:var(--border);align-self:stretch}
        .hero-visual{flex:1;min-width:300px;min-height:400px;position:relative;display:flex;justify-content:center;align-items:center}
        .hero-img-wrap{position:relative;width:100%;max-width:520px}
        .hero-img-wrap img{width:100%;border-radius:24px;box-shadow:0 32px 80px rgba(0,0,0,.15);display:block}
        .hero-float-card{position:absolute;background:#fff;border-radius:14px;padding:1rem 1.25rem;box-shadow:0 8px 32px rgba(0,0,0,.12);display:flex;align-items:center;gap:.75rem;font-weight:600;font-size:.9rem;color:var(--navy)}
        .hero-float-card.card-1{top:-1rem;right:-1rem;animation:float 3s ease-in-out infinite}
        .hero-float-card.card-2{bottom:2rem;left:-1rem;animation:float 3s ease-in-out infinite .5s}
        .float-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
        @media(max-width:900px){.hero{flex-direction:column;text-align:center;padding-top:6rem}.hero-desc{margin:0 auto 2.5rem}.hero-cta{justify-content:center}.hero-stats{justify-content:center}.hero-float-card.card-1{top:-0.5rem;right:0}.hero-float-card.card-2{bottom:1rem;left:0}}

        /* BLOOD TYPES */
        .section{padding:5rem 2rem;max-width:1280px;margin:0 auto}
        .section-label{display:inline-block;background:var(--red-light);color:var(--red);padding:.3rem .9rem;border-radius:999px;font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:1rem}
        .section-title{font-size:clamp(1.8rem,3vw,2.5rem);font-weight:800;color:var(--navy);letter-spacing:-.03em;margin-bottom:.75rem}
        .section-sub{color:var(--muted);font-size:1.05rem;max-width:560px}
        .blood-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:1rem;margin-top:3rem}
        .blood-card{background:#fff;border-radius:16px;padding:1.5rem 1rem;text-align:center;border:2px solid var(--border);transition:all .25s;cursor:default}
        .blood-card:hover{border-color:var(--red);transform:translateY(-4px);box-shadow:0 12px 32px rgba(230,57,70,.12)}
        .blood-type{font-size:2rem;font-weight:900;color:var(--red);letter-spacing:-.04em}
        .blood-label{font-size:.8rem;color:var(--muted);font-weight:500;margin-top:.25rem}

        /* HOW IT WORKS */
        .how-bg{background:linear-gradient(135deg,var(--navy) 0%,#2d5a8e 100%);padding:5rem 2rem}
        .how-inner{max-width:1280px;margin:0 auto}
        .how-bg .section-label{background:rgba(255,255,255,.15);color:#fff}
        .how-bg .section-title{color:#fff}
        .how-bg .section-sub{color:rgba(255,255,255,.7)}
        .steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:2rem;margin-top:3rem}
        .step{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:2rem;transition:all .25s}
        .step:hover{background:rgba(255,255,255,.14);transform:translateY(-4px)}
        .step-num{width:48px;height:48px;background:var(--red);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:800;color:#fff;margin-bottom:1.25rem}
        .step h3{font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:.5rem}
        .step p{font-size:.9rem;color:rgba(255,255,255,.65);line-height:1.6}

        /* STATS BAND */
        .stats-band{background:#fff;border-top:1px solid var(--border);border-bottom:1px solid var(--border);padding:3rem 2rem}
        .stats-inner{max-width:1280px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:2rem;text-align:center}
        .band-stat-num{font-size:2.5rem;font-weight:900;color:var(--red);letter-spacing:-.04em}
        .band-stat-label{font-size:.9rem;color:var(--muted);font-weight:500;margin-top:.25rem}

        /* EMERGENCY */
        .emergency-wrap{padding:5rem 2rem;max-width:1280px;margin:0 auto}
        .emergency-card{background:linear-gradient(135deg,#fff5f5 0%,#ffe4e4 100%);border:2px solid rgba(230,57,70,.2);border-radius:28px;padding:4rem;text-align:center;position:relative;overflow:hidden}
        .emergency-card::before{content:'';position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:rgba(230,57,70,.06);border-radius:50%}
        .emergency-card::after{content:'';position:absolute;bottom:-40px;left:-40px;width:150px;height:150px;background:rgba(230,57,70,.06);border-radius:50%}
        .emergency-icon{width:72px;height:72px;background:var(--red);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;box-shadow:0 8px 24px rgba(230,57,70,.35)}
        .emergency-icon svg{color:#fff}
        .emergency-card h2{font-size:2rem;font-weight:800;color:var(--navy);margin-bottom:.75rem}
        .emergency-card p{color:var(--muted);font-size:1.05rem;max-width:500px;margin:0 auto 2rem}
        .emergency-phone{display:inline-block;font-size:3rem;font-weight:900;color:var(--red);text-decoration:none;letter-spacing:-.04em;transition:all .2s;margin-bottom:1rem}
        .emergency-phone:hover{transform:scale(1.04);color:var(--red-dark)}
        .emergency-note{font-size:.9rem;color:var(--muted);font-weight:500}

        /* FOOTER */
        footer{background:var(--navy);color:rgba(255,255,255,.8);padding:4rem 2rem 2rem}
        .footer-inner{max-width:1280px;margin:0 auto}
        .footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr;gap:4rem;margin-bottom:3rem}
        .footer-brand{display:flex;align-items:center;gap:.6rem;font-size:1.4rem;font-weight:800;color:#fff;text-decoration:none;margin-bottom:1rem}
        .footer-brand svg{color:var(--red)}
        .footer-brand span{color:var(--red)}
        .footer-desc{font-size:.9rem;line-height:1.7;color:rgba(255,255,255,.55);max-width:280px}
        .footer-col h4{font-size:.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.4);margin-bottom:1.25rem}
        .footer-col ul{list-style:none}
        .footer-col li{margin-bottom:.6rem}
        .footer-col a{color:rgba(255,255,255,.65);text-decoration:none;font-size:.9rem;transition:color .2s}
        .footer-col a:hover{color:#fff}
        .footer-bottom{border-top:1px solid rgba(255,255,255,.1);padding-top:1.5rem;text-align:center;font-size:.85rem;color:rgba(255,255,255,.35)}
        @media(max-width:768px){.footer-grid{grid-template-columns:1fr;gap:2rem}.emergency-card{padding:2.5rem 1.5rem}.emergency-phone{font-size:2rem}}

        /* ANIMATIONS */
        .reveal{opacity:0;transform:translateY(24px);transition:opacity .7s ease,transform .7s ease}
        .reveal.visible{opacity:1;transform:translateY(0)}
    </style>
</head>
<body>

<!-- NAV -->
<nav>
    <a href="index.php" class="nav-logo">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        Blood<span>Bank</span>
    </a>
    <div class="nav-links">
        <a href="#how-it-works" class="btn-ghost">How it Works</a>
        <a href="#emergency" class="btn-ghost">Emergency</a>
        <a href="login.php" class="btn-outline-nav">Admin Login</a>
        <a href="donor/register.php" class="btn-red">Donate Now</a>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1d3557" stroke-width="2.5">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>
</nav>
<div class="mobile-menu" id="mobile-menu">
    <a href="#how-it-works">How it Works</a>
    <a href="#emergency">Emergency</a>
    <a href="login.php">Admin Login</a>
    <a href="donor/register.php" style="background:var(--red);color:#fff;text-align:center;">Donate Now</a>
</div>

<!-- HERO -->
<section class="hero">
    <div class="hero-text">
        <div class="hero-badge">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            Every Drop Saves a Life
        </div>
        <h1>Donate Blood,<br><span class="highlight">Save Lives.</span></h1>
        <p class="hero-desc">Join our community of heroes. Your single donation can save up to 3 lives. Register today and be the reason someone gets to go home.</p>
        <div class="hero-cta">
            <a href="donor/register.php" class="btn-hero-primary">
                Become a Donor
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <a href="#how-it-works" class="btn-hero-secondary">
                How it Works
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 9l-7 7-7-7"/></svg>
            </a>
        </div>
        <div class="hero-stats">
            <div class="stat">
                <span class="stat-num"><?php echo $donor_count > 0 ? $donor_count.'+' : '500+'; ?></span>
                <span class="stat-label">Registered Donors</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat">
                <span class="stat-num"><?php echo $donated > 0 ? $donated.'+' : '200+'; ?></span>
                <span class="stat-label">Lives Saved</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat">
                <span class="stat-num">24/7</span>
                <span class="stat-label">Emergency Support</span>
            </div>
        </div>
    </div>
    <div class="hero-visual">
        <div class="hero-img-wrap">
            <img src="assets/images/blood_bank_hero.png" alt="Blood Donation">
            <div class="hero-float-card card-1">
                <div class="float-icon" style="background:#fff0f0;">🩸</div>
                <div><div style="font-size:.75rem;color:#64748b;font-weight:500;">Blood Type</div>O+ Universal</div>
            </div>
            <div class="hero-float-card card-2">
                <div class="float-icon" style="background:#f0fdf4;">✅</div>
                <div><div style="font-size:.75rem;color:#64748b;font-weight:500;">Status</div>100% Safe</div>
            </div>
        </div>
    </div>
</section>

<!-- STATS BAND -->
<div class="stats-band reveal">
    <div class="stats-inner">
        <div><div class="band-stat-num">8</div><div class="band-stat-label">Blood Types Supported</div></div>
        <div><div class="band-stat-num">100%</div><div class="band-stat-label">Safe & Screened</div></div>
        <div><div class="band-stat-num">3x</div><div class="band-stat-label">Lives Per Donation</div></div>
        <div><div class="band-stat-num">24/7</div><div class="band-stat-label">Emergency Helpline</div></div>
    </div>
</div>

<!-- BLOOD TYPES -->
<section class="section reveal">
    <div class="section-label">Blood Groups</div>
    <h2 class="section-title">We Accept All Blood Types</h2>
    <p class="section-sub">No matter your blood group, your donation is valuable. Every type is needed to save lives.</p>
    <div class="blood-grid">
        <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $g):
            $c = 0;
            if ($conn) { $rc = @mysqli_query($conn, "SELECT COUNT(*) FROM donors WHERE blood_group='$g' AND status IN ('Approved','Donated')"); if ($rc) $c = (int)mysqli_fetch_array($rc)[0]; }
        ?>
        <div class="blood-card">
            <div class="blood-type"><?php echo $g; ?></div>
            <div class="blood-label"><?php echo $c; ?> donor<?php echo $c!=1?'s':''; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- HOW IT WORKS -->
<div class="how-bg reveal" id="how-it-works">
    <div class="how-inner">
        <div class="section-label">Process</div>
        <h2 class="section-title">How It Works</h2>
        <p class="section-sub">Donating blood is simple, safe, and takes less than an hour of your time.</p>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Register Online</h3>
                <p>Fill out a quick registration form with your basic details and blood group. Takes under 2 minutes.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>Get Approved</h3>
                <p>Our admin team reviews your registration and approves eligible donors. You'll be notified instantly.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Donate Blood</h3>
                <p>Visit our center when called. The donation process is quick, safe, and fully supervised by professionals.</p>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <h3>Save a Life</h3>
                <p>Your donated blood is processed and delivered to patients in need. You've just saved up to 3 lives.</p>
            </div>
        </div>
    </div>
</div>

<!-- EMERGENCY -->
<div class="emergency-wrap reveal" id="emergency">
    <div class="emergency-card">
        <div class="emergency-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.41 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.82a16 16 0 0 0 6.29 6.29l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </div>
        <h2>Need Blood Urgently?</h2>
        <p>Our emergency helpline is available 24/7. Call us immediately and we'll connect you with the nearest available donor.</p>
        <a href="tel:8105382948" class="emergency-phone">📞 8105382948</a>
        <p class="emergency-note">Available round the clock — every second counts.</p>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="footer-inner">
        <div class="footer-grid">
            <div>
                <a href="index.php" class="footer-brand">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    Blood<span>Bank</span>
                </a>
                <p class="footer-desc">Dedicated to connecting blood donors with those in need. Join our mission to save lives, one drop at a time.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#how-it-works">How it Works</a></li>
                    <li><a href="donor/register.php">Register as Donor</a></li>
                    <li><a href="login.php">Admin Login</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <li><a href="tel:8105382948">Emergency: 8105382948</a></li>
                    <li><a href="mailto:support@bloodbank.org">support@bloodbank.org</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">&copy; <?php echo date('Y'); ?> Blood Bank Management System. Built for community service.</div>
    </div>
</footer>

<script>
    // Mobile menu
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobile-menu');
    hamburger.addEventListener('click', () => mobileMenu.classList.toggle('open'));
    document.addEventListener('click', e => {
        if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) mobileMenu.classList.remove('open');
    });

    // Scroll reveal
    const reveals = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); } });
    }, { threshold: 0.1 });
    reveals.forEach(el => observer.observe(el));
</script>
</body>
</html>
