<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Bank & Donor Management System</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <nav class="navbar">
        <div class="container flex justify-between items-center" style="position:relative;">
            <a href="index.php" class="logo">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                Blood<span>Bank</span>
            </a>
            <!-- Desktop nav -->
            <div class="flex gap-4 navbar-desktop-links">
                <a href="login.php" class="btn btn-outline">Admin Login</a>
                <a href="#emergency" class="btn btn-primary" style="background: var(--error);">Emergency</a>
                <a href="donor/register.php" class="btn btn-primary">Donate Now</a>
            </div>
            <!-- Mobile toggle -->
            <button id="mobile-nav-toggle" aria-label="Open menu" aria-expanded="false"
                style="display:none; background:none; border:none; cursor:pointer; color:var(--secondary); padding:0.5rem;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <!-- Mobile dropdown menu -->
            <div id="mobile-nav-menu">
                <a href="login.php" class="btn btn-outline" style="width:100%; justify-content:center;">Admin Login</a>
                <a href="#emergency" class="btn btn-primary" style="background:var(--error); width:100%; justify-content:center;">Emergency</a>
                <a href="donor/register.php" class="btn btn-primary" style="width:100%; justify-content:center;">Donate Now</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <section class="hero">
            <div class="hero-text fade-in-up">
                <h1 style="margin-bottom: 1.5rem;">Donate Blood, <br><span class="text-primary">Save Lives.</span></h1>
                <p class="delay-100" style="margin-bottom: 2.5rem; max-width: 600px;">
                    Your small contribution can make a big difference. Join our network of heroes today and help those in need across the community. Every drop counts.
                </p>
                <div class="flex gap-4 delay-200">
                    <a href="donor/register.php" class="btn btn-primary">
                        Register as Donor
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#about" class="btn btn-outline">Learn More</a>
                </div>
            </div>
            <div class="hero-visual fade-in-up delay-300">
                <img src="assets/images/blood_bank_hero.png" alt="Blood Donation Facility" class="hero-img">
            </div>
        </section>

        <!-- Stats Section -->
        <?php
        include("config/db.php");
        $donor_count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM donors"))[0];
        $display_count = $donor_count > 0 ? $donor_count : "500+";
        ?>
        <section class="container" style="margin-bottom: 6rem;">
            <div class="flex wrap justify-between gap-6">
                <div class="card-glass flex-col items-center text-center" style="flex: 1; min-width: 250px;">
                    <h3 class="text-primary" style="font-size: 2.5rem; margin-bottom: 0.5rem;">100%</h3>
                    <p style="margin: 0;">Safe Process</p>
                </div>
                <div class="card-glass flex-col items-center text-center" style="flex: 1; min-width: 250px;">
                    <h3 class="text-primary" style="font-size: 2.5rem; margin-bottom: 0.5rem;">24/7</h3>
                    <p style="margin: 0;">Support Available</p>
                </div>
                <div class="card-glass flex-col items-center text-center" style="flex: 1; min-width: 250px;">
                    <h3 class="text-primary" style="font-size: 2.5rem; margin-bottom: 0.5rem;"><?php echo $display_count; ?></h3>
                    <p style="margin: 0;">Happy Donors</p>
                </div>
            </div>
        </section>

        <!-- Emergency Section -->
        <section id="emergency" class="emergency-section">
            <div class="emergency-card fade-in-up">
                <h2 class="text-primary">Need Blood Urgently?</h2>
                <p>Call our 24/7 emergency support line for immediate assistance. Our network of donors is ready to help at a moment's notice.</p>
                <a href="tel:8105382948" class="emergency-phone">8105382948</a>
                <p style="font-weight: 600; color: var(--secondary);">Every second counts. Don't hesitate to reach out.</p>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <a href="index.php" class="footer-logo logo">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        Blood<span>Bank</span>
                    </a>
                    <p style="max-width: 300px; font-size: 0.95rem;">Dedicated to connecting blood donors with those in need. Join us in our mission to save lives, one drop at a time.</p>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="donor/register.php">Register Donor</a></li>
                        <li><a href="login.php">Admin Login</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="tel:8105382948">Emergency: 8105382948</a></li>
                        <li><a href="mailto:support@bloodbank.org">support@bloodbank.org</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> Blood Bank Management System. Built for community service.
            </div>
        </div>
    </footer>
    <script src="assets/js/main.js"></script>
</body>
</html>