<?php
include("../config/db.php");
require_once("../includes/email_sender.php");

if (session_status() === PHP_SESSION_NONE) session_start();

// Auth guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Handle email send
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_email'])) {
    $donor_id = intval($_POST['donor_id']);
    $subject  = trim($_POST['subject']);
    $msg      = trim($_POST['message_body']);

    $donor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, email FROM donors WHERE id=$donor_id"));

    if ($donor && !empty($donor['email'])) {
        $html = buildEmailTemplate($donor['name'],
            "<p style='color:#333;font-size:15px;'>$msg</p>"
        );
        $result = sendEmail($donor['email'], $donor['name'], $subject, strip_tags($msg), $html);

        if ($result['status'] == 'success') {
            $_SESSION['email_notif'] = "success|Email sent successfully to <b>" . htmlspecialchars($donor['name']) . "</b> (" . htmlspecialchars($donor['email']) . ")";
        } else {
            $_SESSION['email_notif'] = "error|Failed to send email: " . htmlspecialchars($result['message']);
        }
    } else {
        $_SESSION['email_notif'] = "error|Donor not found or has no email address.";
    }
    header("Location: email_donors.php");
    exit();
}

// Fetch all donors with emails
$donors_result = mysqli_query($conn, "SELECT id, name, email, blood_group, status FROM donors WHERE email != '' AND email IS NOT NULL ORDER BY name ASC");
$selected_donor = null;
if (isset($_GET['donor_id'])) {
    $did = intval($_GET['donor_id']);
    $selected_donor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM donors WHERE id=$did"));
}

// Email history log (last 10 lines from log file)
$log_file = __DIR__ . '/../logs/sms_log.txt';
$email_logs = [];
if (file_exists($log_file)) {
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $email_lines = array_filter($lines, fn($l) => str_contains($l, '| EMAIL'));
    $email_logs = array_slice(array_reverse(array_values($email_lines)), 0, 10);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Donors - Blood Bank</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .donor-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.2rem;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s;
            border: 2px solid transparent;
        }
        .donor-card:hover { background: rgba(var(--primary-rgb), 0.07); }
        .donor-card.selected { border-color: var(--primary); background: rgba(var(--primary-rgb), 0.08); }
        .donor-avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .donor-info { flex: 1; min-width: 0; }
        .donor-info strong { display: block; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .donor-info span { font-size: 0.78rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .compose-area { flex: 1; }
        .log-entry { font-size: 0.78rem; padding: 0.5rem 0.8rem; border-radius: 6px; background: rgba(255,255,255,0.05); margin-bottom: 0.4rem; color: var(--text-muted); font-family: monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .donors-list { max-height: 480px; overflow-y: auto; }
        #search-box { width: 100%; padding: 0.7rem 1rem; border: 1px solid var(--border); border-radius: 8px; background: transparent; color: var(--text); margin-bottom: 1rem; }

        .placeholder-state {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: 360px; color: var(--text-muted); text-align: center; gap: 1rem;
        }
        .placeholder-state svg { opacity: 0.25; }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="dashboard.php" class="logo" style="margin-bottom: 3rem;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            Blood<span>Bank</span>
        </a>
        <nav style="flex: 1;">
            <a href="dashboard.php" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Dashboard
            </a>
            <a href="broadcast.php" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                Broadcasts
            </a>
            <a href="email_donors.php" class="nav-link active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Email Donors
            </a>
            <a href="manage_donors.php" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Manage Donors
            </a>
            <a href="blood_stock.php" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                Blood Stock
            </a>
        </nav>
        <a href="../login.php?logout=1" class="btn btn-outline w-full" style="justify-content: center;">Logout</a>
    </aside>

    <!-- Main Content -->
    <main class="main-content fade-in-up">
        <?php if (isset($_SESSION['email_notif'])): 
            [$type, $msg_notif] = explode('|', $_SESSION['email_notif'], 2);
            unset($_SESSION['email_notif']);
        ?>
        <div class="alert <?php echo $type === 'success' ? 'alert-success' : 'alert-error'; ?>" style="margin-bottom:2rem;">
            <?php echo $msg_notif; ?>
        </div>
        <?php endif; ?>

        <header style="margin-bottom: 2rem;">
            <h2 style="font-size: 2rem;">Email Donors</h2>
            <p>Send a personal email message to a specific donor</p>
        </header>

        <div class="flex gap-6 wrap" style="align-items: flex-start;">

            <!-- Left: Donor List -->
            <div class="card-glass" style="width: 300px; flex-shrink: 0; padding: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1rem;">Select Donor</h3>
                <input type="text" id="search-box" placeholder="Search by name or email..." oninput="filterDonors(this.value)">
                <div class="donors-list" id="donors-list">
                    <?php if (mysqli_num_rows($donors_result) == 0): ?>
                        <p style="color: var(--text-muted); font-size: 0.85rem;">No donors with email addresses found.</p>
                    <?php else:
                        while ($d = mysqli_fetch_assoc($donors_result)):
                            $initial = strtoupper(substr($d['name'], 0, 1));
                            $isSelected = $selected_donor && $selected_donor['id'] == $d['id'];
                    ?>
                    <a href="?donor_id=<?php echo $d['id']; ?>" class="donor-card <?php echo $isSelected ? 'selected' : ''; ?>" data-name="<?php echo strtolower($d['name']); ?>" data-email="<?php echo strtolower($d['email']); ?>">
                        <div class="donor-avatar"><?php echo $initial; ?></div>
                        <div class="donor-info">
                            <strong><?php echo htmlspecialchars($d['name']); ?></strong>
                            <span><?php echo htmlspecialchars($d['email']); ?></span>
                            <span>
                                <span class="badge badge-blood" style="font-size:0.68rem;padding:2px 6px;"><?php echo $d['blood_group']; ?></span>
                                &nbsp;
                                <span class="badge badge-<?php echo strtolower($d['status']); ?>" style="font-size:0.68rem;padding:2px 6px;"><?php echo $d['status']; ?></span>
                            </span>
                        </div>
                    </a>
                    <?php endwhile; endif; ?>
                </div>
            </div>

            <!-- Right: Compose + Log -->
            <div class="compose-area flex-col" style="flex:1; min-width: 320px; display:flex; flex-direction:column; gap:1.5rem;">

                <!-- Compose Card -->
                <div class="card-glass" style="padding: 2rem;">
                    <?php if ($selected_donor): ?>
                        <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; padding-bottom:1.5rem; border-bottom: 1px solid var(--border);">
                            <div class="donor-avatar" style="width:52px;height:52px;font-size:1.4rem;"><?php echo strtoupper(substr($selected_donor['name'],0,1)); ?></div>
                            <div>
                                <div style="font-weight:700;font-size:1.1rem;"><?php echo htmlspecialchars($selected_donor['name']); ?></div>
                                <div style="color:var(--text-muted);font-size:0.9rem;">📧 <?php echo htmlspecialchars($selected_donor['email']); ?></div>
                                <div style="color:var(--text-muted);font-size:0.85rem;">📱 <?php echo htmlspecialchars($selected_donor['phone']); ?> &nbsp;|&nbsp; 🩸 <?php echo $selected_donor['blood_group']; ?></div>
                            </div>
                        </div>

                        <form method="POST" action="email_donors.php">
                            <input type="hidden" name="donor_id" value="<?php echo $selected_donor['id']; ?>">

                            <div style="margin-bottom:1.2rem;">
                                <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Subject</label>
                                <input type="text" name="subject" class="form-control"
                                    style="width:100%;padding:0.8rem 1rem;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);"
                                    placeholder="e.g., Urgent Blood Donation Request"
                                    value="Blood Bank Message" required>
                            </div>

                            <div style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Message</label>
                                <textarea name="message_body" class="form-control"
                                    style="width:100%;min-height:180px;padding:1rem;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);resize:vertical;"
                                    placeholder="Type your email message to <?php echo htmlspecialchars($selected_donor['name']); ?>..." required></textarea>
                            </div>

                            <div class="flex gap-4">
                                <button type="submit" name="send_email" class="btn btn-primary" style="flex:1; justify-content:center; padding:0.9rem;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:0.5rem;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    Send Email
                                </button>
                                <a href="email_donors.php" class="btn btn-outline" style="justify-content:center; padding:0.9rem;">Clear</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="placeholder-state">
                            <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <div>
                                <p style="font-size:1.1rem;font-weight:600;margin:0;">Select a donor</p>
                                <p style="font-size:0.9rem;margin:0.4rem 0 0;">Choose a donor from the list to compose an email</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Email Log Card -->
                <?php if (!empty($email_logs)): ?>
                <div class="card-glass" style="padding: 1.5rem;">
                    <h3 style="font-size:1rem; margin-bottom:1rem;">📬 Recent Email Activity</h3>
                    <?php foreach ($email_logs as $log): ?>
                        <div class="log-entry" title="<?php echo htmlspecialchars($log); ?>">
                            <?php echo htmlspecialchars($log); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </main>
</div>

<script>
function filterDonors(query) {
    query = query.toLowerCase();
    document.querySelectorAll('#donors-list .donor-card').forEach(card => {
        const name  = card.dataset.name  || '';
        const email = card.dataset.email || '';
        card.style.display = (name.includes(query) || email.includes(query)) ? 'flex' : 'none';
    });
}
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>
