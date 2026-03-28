<?php
include("../config/db.php");
require_once("../includes/sms_sender.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Handle Broadcast
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['broadcast_msg'])) {
    $msg = $_POST['broadcast_msg'];
    $blood_group = $_POST['blood_group'] ?? 'All';

    $queryStr = "SELECT phone FROM donors WHERE status IN ('Approved', 'Donated')";
    if ($blood_group != 'All') {
        $queryStr .= " AND blood_group = '" . mysqli_real_escape_string($conn, $blood_group) . "'";
    }

    $query = mysqli_query($conn, $queryStr);
    $phones = [];
    while($r = mysqli_fetch_array($query)) {
        $phones[] = $r['phone'];
    }

    if (!empty($phones)) {
        $phoneList = implode(',', $phones);
        $smsResult = sendSMS($phoneList, $msg);
        
        $status = ($smsResult['status'] == 'success') ? 'Sent' : 'Failed';
        $sent_count = count($phones);
        
        // Log to database
        $target_label = ($blood_group == 'All') ? 'All Donors' : "$blood_group Only";
        $stmt = $conn->prepare("INSERT INTO broadcasts (message, target_name, sent_to_count, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $msg, $target_label, $sent_count, $status);
        $stmt->execute();

        if ($smsResult['status'] == 'success') {
            $_SESSION['notif_msg'] = "Success: Broadcast sent to $sent_count recipients (" . ($blood_group == 'All' ? 'All Donors' : "Blood Group: $blood_group") . ").";
        } else {
            $_SESSION['notif_msg'] = "Error: Broadcast failed: " . $smsResult['message'];
        }
    } else {
        $_SESSION['notif_msg'] = "Error: No matching approved donors found for blood group '$blood_group'.";
    }
    header("Location: broadcast.php");
    exit();
}

// Fetch History
$history_query = mysqli_query($conn, "SELECT * FROM broadcasts ORDER BY sent_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Management - Blood Bank</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
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
                <a href="broadcast.php" class="nav-link active">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                    Broadcasts
                </a>
                <a href="manage_donors.php" class="nav-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Manage Donors
                </a>
                <a href="blood_stock.php" class="nav-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    Blood Stock
                </a>
                <a href="email_donors.php" class="nav-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Email Donors
                </a>
            </nav>
            <a href="../login.php?logout=1" class="btn btn-outline w-full" style="justify-content: center;">Logout</a>
        </aside>

        <!-- Main Content -->
        <main class="main-content fade-in-up">
            <?php if(isset($_SESSION['notif_msg'])): 
                $notif = $_SESSION['notif_msg'];
                $notif_class = (strpos($notif, 'Error:') === 0) ? 'alert-error' : 'alert-success';
                unset($_SESSION['notif_msg']);
            ?>
                <div class="alert <?php echo $notif_class; ?>" style="margin-bottom: 2rem;">
                    <?php echo $notif; ?>
                </div>
            <?php endif; ?>

            <header style="margin-bottom: 2rem;">
                <h2 style="font-size: 2rem;">Broadcast Management</h2>
                <p>Send SMS alerts to your donor network</p>
            </header>

            <div class="flex gap-6 wrap">
                <!-- Compose Section -->
                <div class="card-glass" style="flex: 1; min-width: 350px; padding: 2rem;">
                    <h3 style="margin-bottom: 1.5rem;">Compose Message</h3>
                    <form action="" method="POST">
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Target Audience</label>
                            <select name="blood_group" class="form-control" style="width: 100%; padding: 0.8rem; border-radius: 8px; border: 1px solid var(--border);">
                                <option value="All">All Active Donors (Approved & Donated)</option>
                                <?php 
                                $bg_param = $_GET['blood_group'] ?? '';
                                $urgent = isset($_GET['urgent']);
                                $groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                                foreach($groups as $g) {
                                    $sel = ($bg_param == $g) ? 'selected' : '';
                                    echo "<option value='$g' $sel>$g Only</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Message</label>
                            <textarea name="broadcast_msg" class="form-control" style="width: 100%; min-height: 150px; padding: 1rem; border-radius: 8px; border: 1px solid var(--border);" placeholder="Enter your emergency alert or announcement..." required><?php 
                                if($urgent && $bg_param) {
                                    echo "EMERGENCY: Urgent $bg_param blood needed at the Blood Bank. Please contact us immediately if you can donate.";
                                }
                            ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-full" style="justify-content: center; padding: 1rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-inline-end: 0.5rem;"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                            Send Broadcast SMS
                        </button>
                    </form>
                </div>

                <!-- History Section -->
                <div class="card-glass" style="flex: 1.5; min-width: 400px; padding: 0; overflow: hidden;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                        <h3 style="margin: 0;">Recent Broadcasts</h3>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Target</th>
                                    <th>Message</th>
                                    <th>Recipients</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_array($history_query)): ?>
                                <tr>
                                    <td style="font-size: 0.85rem; white-space: nowrap;"><?php echo date('M d, H:i', strtotime($row['sent_at'])); ?></td>
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($row['target_name']); ?></td>
                                    <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($row['message']); ?></td>
                                    <td><?php echo $row['sent_to_count']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($row['status'] == 'Sent') ? 'approved' : 'rejected'; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($history_query) == 0): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-muted);">No broadcast history found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
