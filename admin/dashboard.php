<?php
// Start session FIRST before any $_SESSION usage
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

include("../config/db.php");
require_once("../includes/sms_sender.php");
require_once("../includes/email_sender.php");

// Handle Actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'notify_all' && isset($_POST['broadcast_msg'])) {
        $msg = $_POST['broadcast_msg'];
        $query = mysqli_query($conn, "SELECT phone FROM donors WHERE status IN ('Approved', 'Donated')");
        $phones = [];
        while ($r = mysqli_fetch_array($query)) {
            $phones[] = $r['phone'];
        }

        if (!empty($phones)) {
            $phoneList = implode(',', $phones);
            $smsResult = sendSMS($phoneList, $msg);

            // Log to database
            $sent_count = count($phones);
            $status = ($smsResult['status'] == 'success') ? 'Sent' : 'Failed';
            $target_label = "All Active Donors";

            $stmt = $conn->prepare("INSERT INTO broadcasts (message, target_name, sent_to_count, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $msg, $target_label, $sent_count, $status);
            $stmt->execute();

            if ($smsResult['status'] == 'success') {
                $_SESSION['notif_msg'] = "Success: Broadcast sent to $sent_count active donors. <br>✅ Gateway Response: " . htmlspecialchars($smsResult['response']);
            } else {
                $_SESSION['notif_msg'] = "Error: Broadcast failed: " . $smsResult['message'];
            }
        } else {
            $_SESSION['notif_msg'] = "Error: No active donors (Approved/Donated) found to notify.";
        }
        header("Location: dashboard.php");
        exit();
    }

    if ($_GET['action'] == 'send_individual' && isset($_POST['individual_msg']) && isset($_POST['donor_phone'])) {
        $msg = $_POST['individual_msg'];
        $phone = $_POST['donor_phone'];
        $donor_name = $_POST['donor_name'] ?? 'Donor';

        $smsResult = sendSMS($phone, $msg);

        // Log to database as a broadcast with count 1
        $sent_count = 1;
        $status = ($smsResult['status'] == 'success') ? 'Sent' : 'Failed';

        $stmt = $conn->prepare("INSERT INTO broadcasts (message, target_name, sent_to_count, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $msg, $donor_name, $sent_count, $status);
        $stmt->execute();

        if ($smsResult['status'] == 'success') {
            $_SESSION['notif_msg'] = "Success: Message sent to $donor_name.";
        } else {
            $_SESSION['notif_msg'] = "Error: Failed to send message to $donor_name: " . $smsResult['message'];
        }
        header("Location: dashboard.php");
        exit();
    }

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $action = '';

        if ($_GET['action'] == 'donated') {
            $action = 'Donated';
            $donor = mysqli_fetch_array(mysqli_query($conn, "SELECT name, email, phone FROM donors WHERE id=$id"));

            $notifParts = [];

            // SMS Notification
            $smsResult = sendSMS($donor['phone'], "Hello " . $donor['name'] . ", Thank you for donating blood! Your contribution saves lives. - Blood Bank");
            $notifParts[] = ($smsResult['status'] == 'success')
                ? "✅ SMS sent to <b>" . $donor['phone'] . "</b>"
                : "⚠️ SMS failed: " . $smsResult['message'];

            // Email Notification
            if (!empty($donor['email'])) {
                $emailBody = buildEmailTemplate(
                    $donor['name'],
                    "<p style='color:#333;font-size:15px;'>🩸 Your blood donation has been <strong style='color:#e53e3e;'>confirmed</strong>!</p>
                    <p style='color:#555;'>Your generous contribution helps save lives and strengthens our community. We are grateful for your support.</p>
                    <div style='background:#fff5f5;border-left:4px solid #e53e3e;padding:16px;margin:16px 0;border-radius:4px;'>
                        <strong>Donation: Confirmed</strong>
                    </div>"
                );
                $emailResult = sendEmail(
                    $donor['email'],
                    $donor['name'],
                    'Blood Donation Confirmed - Blood Bank',
                    'Your blood donation has been confirmed. Thank you for saving lives!',
                    $emailBody
                );
                $notifParts[] = ($emailResult['status'] == 'success')
                    ? "✅ Email sent to <b>" . $donor['email'] . "</b>"
                    : "⚠️ Email failed: " . $emailResult['message'];
            }

            $_SESSION['notif_msg'] = "Success: Blood donation confirmed for <b>" . $donor['name'] . "</b>.<br>" . implode('<br>', $notifParts);

        } else if ($_GET['action'] == 'approve') {
            $action = 'Approved';
            $donor = mysqli_fetch_array(mysqli_query($conn, "SELECT name, email, phone FROM donors WHERE id=$id"));

            // Email on Approval
            if (!empty($donor['email'])) {
                $emailBody = buildEmailTemplate(
                    $donor['name'],
                    "<p style='color:#333;font-size:15px;'>🎉 Congratulations! Your donor registration has been <strong style='color:#38a169;'>approved</strong>!</p>
                    <p style='color:#555;'>You are now an active donor. Please stay healthy and await further communication when your blood is urgently needed.</p>
                    <div style='background:#f0fff4;border-left:4px solid #38a169;padding:16px;margin:16px 0;border-radius:4px;'>
                        <strong>Status: Approved ✅</strong>
                    </div>"
                );
                sendEmail(
                    $donor['email'],
                    $donor['name'],
                    'Your Donor Registration is Approved - Blood Bank',
                    'Congratulations! Your blood donor registration has been approved.',
                    $emailBody
                );
            }

            // SMS on Approval
            if (!empty($donor['phone'])) {
                $smsResult = sendSMS($donor['phone'], "Congratulations " . $donor['name'] . "! Your donor registration has been approved. You are now an active donor. - Blood Bank");

                // If not already in a try/catch or if we want to add to notification message
                if (isset($_SESSION['notif_msg'])) {
                    $_SESSION['notif_msg'] .= "<br>" . (($smsResult['status'] == 'success')
                        ? "✅ SMS sent to <b>" . $donor['phone'] . "</b>"
                        : "⚠️ SMS failed: " . $smsResult['message']);
                } else {
                    $_SESSION['notif_msg'] = "Success: Donor <b>" . $donor['name'] . "</b> approved.<br>" .
                        (($smsResult['status'] == 'success')
                            ? "✅ SMS sent to <b>" . $donor['phone'] . "</b>"
                            : "⚠️ SMS failed: " . $smsResult['message']);
                }
            } else {
                $_SESSION['notif_msg'] = "Success: Donor <b>" . $donor['name'] . "</b> approved.";
            }

        } else if ($_GET['action'] == 'reject') {
            $action = 'Rejected';
        }

        if ($action != '') {
            mysqli_query($conn, "UPDATE donors SET status='$action' WHERE id=$id");
        }
        header("Location: dashboard.php");
        exit();
    }
}

// Session already started at top of file

// Fetch Stats
$total_donors = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM donors"))[0];
$pending_requests = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM donors WHERE status='Pending'"))[0];
$active_donors = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM donors WHERE status IN ('Approved', 'Donated')"))[0];
$total_units = $active_donors * 450; // 450ml per qualified donor

// Fetch Recent Registrations
$donors_query = mysqli_query($conn, "SELECT * FROM donors ORDER BY registration_date DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Blood Bank</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="app-container">
        <!-- Mobile Menu Button -->
        <button id="menu-toggle" class="menu-toggle"
            style="position: fixed; top: 1.5rem; inset-inline-end: 1.5rem; background: white; box-shadow: var(--shadow); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; z-index: 1000;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="dashboard.php" class="logo" style="margin-bottom: 3rem;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                </svg>
                Blood<span>Bank</span>
            </a>

            <nav style="flex: 1;">
                <a href="dashboard.php" class="nav-link active">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Dashboard
                </a>
                <a href="broadcast.php" class="nav-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"></path>
                    </svg>
                    Broadcasts
                </a>
                <a href="manage_donors.php" class="nav-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Manage Donors
                </a>
                <a href="blood_stock.php" class="nav-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                    </svg>
                    Blood Stock
                </a>
                <a href="email_donors.php" class="nav-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                        <polyline points="22,6 12,13 2,6" />
                    </svg>
                    Email Donors
                </a>
            </nav>

            <a href="../login.php?logout=1" class="btn btn-outline w-full" style="justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </a>
        </aside>

        <!-- Main Content -->
        <main class="main-content fade-in-up">
            <?php if (isset($_SESSION['notif_msg'])): ?>
                <div class="alert alert-success fade-in-up" style="margin-bottom: 2rem;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <div><?php echo $_SESSION['notif_msg']; ?></div>
                </div>
                <?php unset($_SESSION['notif_msg']); ?>
            <?php endif; ?>

            <?php if (defined('EMAIL_ENABLED') && EMAIL_ENABLED && defined('BREVO_API_KEY') && (strpos(BREVO_API_KEY, 'xkeysib') !== false)): ?>
            <div class="alert alert-warning fade-in-up" style="margin-bottom: 1.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <div>Email notifications may not be working. Update your Brevo API key in <code>config/email_config.php</code> or set <code>EMAIL_ENABLED = false</code> to suppress this warning.</div>
            </div>
            <?php endif; ?>

            <header class="flex justify-between items-center" style="margin-bottom: 2rem;">
                <div>
                    <h2 style="font-size: 2rem;">Overview</h2>
                    <p>Welcome back, Admin</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="broadcast.php" class="btn btn-outline"
                        style="border-color: var(--accent); color: var(--accent);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
                        </svg>
                        Broadcast Management
                    </a>
                    <span class="badge badge-approved">System Active</span>
                </div>
            </header>

            <!-- Broadcast Modal -->
            <div id="broadcast-modal"
                style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center;">
                <div class="card-glass" style="width: 100%; max-width: 500px; padding: 2rem;">
                    <h3 style="margin-bottom: 1.5rem;">Broadcast Message</h3>
                    <form action="?action=notify_all" method="POST">
                        <textarea name="broadcast_msg" class="form-control"
                            style="width: 100%; min-height: 120px; margin-bottom: 1.5rem; padding: 1rem; border-radius: 8px; border: 1px solid var(--border);"
                            placeholder="Enter message for all active donors (Approved & Donated)..."></textarea>
                        <div class="flex gap-4">
                            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">Send
                                Now</button>
                            <button type="button"
                                onclick="document.getElementById('broadcast-modal').style.display='none'"
                                class="btn btn-outline" style="flex: 1; justify-content: center;">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stats Grid -->
            <?php
            // Identify low stocks for dashboard alert
            $urgent_needs = [];
            $all_groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
            foreach ($all_groups as $group) {
                $count = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM donors WHERE blood_group='$group' AND status IN ('Approved', 'Donated')"))[0];
                $ml = $count * 450;
                if ($ml <= 1000) {
                    $urgent_needs[] = ['group' => $group, 'ml' => $ml];
                }
            }

            if (!empty($urgent_needs)):
                ?>
                <div class="card-glass" style="margin-bottom: 2rem; border-left: 4px solid var(--error); padding: 1.5rem;">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3
                                style="color: var(--error); margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                    <line x1="12" y1="9" x2="12" y2="13" />
                                    <line x1="12" y1="17" x2="12.01" y2="17" />
                                </svg>
                                Urgent Inventory Needs
                            </h3>
                            <p style="margin: 0.5rem 0 0; font-size: 0.9rem;">The following blood groups have low or
                                critical stock levels:</p>
                        </div>
                    </div>
                    <div class="flex wrap gap-3" style="margin-top: 1rem;">
                        <?php foreach ($urgent_needs as $need): ?>
                            <div
                                style="background: rgba(var(--primary-rgb), 0.05); padding: 0.6rem 1rem; border-radius: 8px; display: flex; align-items: center; gap: 1rem; border: 1px solid var(--border);">
                                <span class="badge badge-blood"><?php echo $need['group']; ?></span>
                                <span style="font-weight: 600; font-size: 0.9rem;"><?php echo number_format($need['ml']); ?>
                                    ml</span>
                                <a href="broadcast.php?blood_group=<?php echo urlencode($need['group']); ?>&urgent=true"
                                    class="btn btn-primary"
                                    style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: var(--error); box-shadow: none;">Alert
                                    Donors</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flex wrap gap-6" style="margin-bottom: 3rem;">
                <div class="card-glass flex-col" style="flex: 1; min-width: 200px; padding: 1.5rem;">
                    <div class="flex justify-between items-start">
                        <div>
                            <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">Total Donors</p>
                            <h3 style="color: var(--primary); font-size: 2rem; margin: 0;"><?php echo $total_donors; ?>
                            </h3>
                        </div>
                        <div
                            style="background: rgba(var(--primary-rgb), 0.1); p-2; border-radius: 8px; padding: 0.5rem; color: var(--primary);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="card-glass flex-col" style="flex: 1; min-width: 200px; padding: 1.5rem;">
                    <div class="flex justify-between items-start">
                        <div>
                            <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">Blood Units (ml)</p>
                            <h3 style="color: var(--success); font-size: 2rem; margin: 0;">
                                <?php echo number_format($total_units); ?>
                            </h3>
                        </div>
                        <div
                            style="background: #ecfdf5; p-2; border-radius: 8px; padding: 0.5rem; color: var(--success);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M12 2.69l5.74 5.74c.98.98 1.52 2.27 1.52 3.65 0 2.85-2.31 5.16-5.16 5.16-4.2 0-2.1-7.55-2.1-7.55S10 17.24 5.8 17.24c-2.85 0-5.16-2.31-5.16-5.16 0-1.38.54-2.67 1.52-3.65L7.9 2.69a2.9 2.9 0 0 1 4.1 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="card-glass flex-col" style="flex: 1; min-width: 200px; padding: 1.5rem;">
                    <div class="flex justify-between items-start">
                        <div>
                            <p style="font-size: 0.875rem; margin-bottom: 0.5rem;">Pending Requests</p>
                            <h3 style="color: var(--warning); font-size: 2rem; margin: 0;">
                                <?php echo $pending_requests; ?>
                            </h3>
                        </div>
                        <div
                            style="background: #fffbeb; p-2; border-radius: 8px; padding: 0.5rem; color: var(--warning);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Registrations -->
            <div class="card-glass" style="padding: 0; overflow: hidden;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                    <h3 style="font-size: 1.25rem; margin: 0;">Recent Registrations</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Group</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_array($donors_query)): ?>
                                <tr>
                                    <td style="font-weight: 500;">
                                        <?php echo $row['name']; ?>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">
                                            <?php echo date('M d, Y', strtotime($row['registration_date'])); ?>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-blood"><?php echo $row['blood_group']; ?></span></td>
                                    <td>
                                        <div style="font-size: 0.9rem;"><?php echo $row['email']; ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                                            <?php echo $row['phone']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'Pending'): ?>
                                            <div class="flex gap-2">
                                                <a href="?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-primary"
                                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: var(--success); box-shadow: none;">Approve</a>
                                                <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-primary"
                                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: var(--error); box-shadow: none;">Reject</a>
                                            </div>
                                        <?php elseif ($row['status'] == 'Approved'): ?>
                                            <div class="flex gap-2">
                                                <a href="?action=donated&id=<?php echo $row['id']; ?>" class="btn btn-primary"
                                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: var(--accent); box-shadow: none;">Confirm
                                                    Donation</a>
                                                <button
                                                    onclick="openMessageModal('<?php echo $row['phone']; ?>', '<?php echo addslashes($row['name']); ?>')"
                                                    class="btn btn-outline"
                                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem; border-color: var(--primary); color: var(--primary);">Message</button>
                                            </div>
                                        <?php else: ?>
                                            <div class="flex gap-2 items-center">
                                                <span style="color: var(--text-light); font-size: 0.875rem;">Completed
                                                    (<?php echo $row['status']; ?>)</span>
                                                <button
                                                    onclick="openMessageModal('<?php echo $row['phone']; ?>', '<?php echo addslashes($row['name']); ?>')"
                                                    class="btn btn-outline"
                                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem; border-color: var(--primary); color: var(--primary);">Message</button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Individual Message Modal -->
    <div id="individual-modal"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center;">
        <div class="card-glass" style="width: 100%; max-width: 450px; padding: 2rem;">
            <h3 id="modal-title" style="margin-bottom: 1.5rem;">Send Message</h3>
            <form action="?action=send_individual" method="POST">
                <input type="hidden" name="donor_phone" id="modal-phone">
                <input type="hidden" name="donor_name" id="modal-donor-name">
                <div style="margin-bottom: 1.5rem;">
                    <p id="modal-target" style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem;"></p>
                    <textarea name="individual_msg" class="form-control"
                        style="width: 100%; min-height: 100px; padding: 1rem; border-radius: 8px; border: 1px solid var(--border);"
                        placeholder="Type your message here..." required></textarea>
                </div>
                <div class="flex gap-4">
                    <button type="button" onclick="sendViaWhatsApp()" class="btn btn-primary"
                        style="flex: 1; justify-content: center; background: #25D366;">📲 Send via WhatsApp</button>
                    <button type="button" onclick="document.getElementById('individual-modal').style.display='none'"
                        class="btn btn-outline" style="flex: 1; justify-content: center;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function sendViaWhatsApp() {
            const modal = document.getElementById('individual-modal');
            const rawPhone = document.getElementById('modal-phone').value.trim();
            const msg = modal.querySelector('textarea[name="individual_msg"]').value.trim();

            if (!msg) { alert("Please enter a message"); return; }

            // Normalize phone: strip leading 0, add India country code if not present
            let phone = rawPhone.replace(/\D/g, ''); // digits only
            if (phone.startsWith('0')) phone = phone.substring(1);
            if (!phone.startsWith('91') || phone.length <= 10) phone = '91' + phone.slice(-10);

            const waUrl = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
            window.open(waUrl, '_blank');

            modal.style.display = 'none';
        }lize phone: strip leading 0, add India country code if not present
            let phone = rawPhone.replace(/\D/g, ''); // digits only
            if (phone.startsWith('0')) phone = phone.substring(1);
            if (!phone.startsWith('91') || phone.length <= 10) phone = '91' + phone.slice(-10);

            const waUrl = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
            window.open(waUrl, '_blank');

            modal.style.display = 'none';
        }
    </script>

    <script>
        function openMessageModal(phone, name) {
            document.getElementById('modal-phone').value = phone;
            document.getElementById('modal-donor-name').value = name;
            document.getElementById('modal-title').innerText = "Message to " + name;
            document.getElementById('modal-target').innerText = "Sending to: " + phone;
            document.getElementById('individual-modal').style.display = 'flex';
        }
    </script>
</body>

</html>