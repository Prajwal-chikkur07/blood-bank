<?php
include("../config/db.php");
require_once("../includes/sms_sender.php");
require_once("../includes/email_sender.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Handle Actions (inherited from dashboard or specific to this page)
if(isset($_GET['action']) && isset($_GET['id'])){
    $id = intval($_GET['id']);
    $action = '';
    
    if ($_GET['action'] == 'donated') {
        $action = 'Donated';
        $donor = mysqli_fetch_array(mysqli_query($conn, "SELECT name, phone, email FROM donors WHERE id=$id"));
        
        // Send SMS
        sendSMS($donor['phone'], "Hello " . $donor['name'] . ", Thank you for donating blood! Your contribution saves lives. - Blood Bank");
        
        // Send Email
        if (!empty($donor['email'])) {
            $html = buildEmailTemplate($donor['name'], 
                "<p style='color:#333;font-size:16px;'>We have successfully recorded your recent blood donation.</p>
                 <p style='color:#333;font-size:16px;'>Your contribution is a gift of life. Thank you for being a hero!</p>"
            );
            sendEmail($donor['email'], $donor['name'], "Thank you for your donation!", "Thank you for your donation! Your contribution saves lives.", $html);
        }
        
        $_SESSION['notif_msg'] = "Success: Donation confirmed and notifications sent to " . $donor['name'];
    } else if ($_GET['action'] == 'approve') {
        $action = 'Approved';
        $donor = mysqli_fetch_array(mysqli_query($conn, "SELECT name, email FROM donors WHERE id=$id"));
        if (!empty($donor['email'])) {
            $html = buildEmailTemplate($donor['name'], 
                "<p style='color:#333;font-size:16px;'>Your registration as a donor has been <strong>Approved</strong>!</p>
                 <p style='color:#333;font-size:16px;'>You are now part of our lifesaving community. We will notify you when there is an urgent need for your blood group.</p>"
            );
            sendEmail($donor['email'], $donor['name'], "Donor Registration Approved", "Your registration as a donor has been approved!", $html);
        }
    } else if ($_GET['action'] == 'reject') {
        $action = 'Rejected';
        $donor = mysqli_fetch_array(mysqli_query($conn, "SELECT name, email FROM donors WHERE id=$id"));
        if (!empty($donor['email'])) {
            $html = buildEmailTemplate($donor['name'], 
                "<p style='color:#333;font-size:16px;'>We regret to inform you that your donor registration has been declined at this time.</p>
                 <p style='color:#333;font-size:16px;'>If you have any questions, please contact our support team.</p>"
            );
            sendEmail($donor['email'], $donor['name'], "Updates on your Donor Registration", "Your donor registration status has been updated.", $html);
        }
    } else if ($_GET['action'] == 'delete') {
        mysqli_query($conn, "DELETE FROM donors WHERE id=$id");
        $_SESSION['notif_msg'] = "Success: Donor record deleted.";
        header("Location: manage_donors.php");
        exit();
    }

    if ($action != '') {
        mysqli_query($conn, "UPDATE donors SET status='$action' WHERE id=$id");
    }
    header("Location: manage_donors.php");
    exit();
}

// Filtering and Search
$where = "WHERE 1=1";
$search = $_GET['search'] ?? '';
if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (name LIKE '%$s%' OR phone LIKE '%$s%' OR email LIKE '%$s%')";
}

$blood_group = $_GET['blood_group'] ?? '';
if ($blood_group) {
    $bg = mysqli_real_escape_string($conn, $blood_group);
    $where .= " AND blood_group = '$bg'";
}

$status = $_GET['status'] ?? '';
if ($status) {
    $st = mysqli_real_escape_string($conn, $status);
    $where .= " AND status = '$st'";
}

$donors_query = mysqli_query($conn, "SELECT * FROM donors $where ORDER BY registration_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Donors - Blood Bank</title>
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
                <a href="broadcast.php" class="nav-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"></path></svg>
                    Broadcasts
                </a>
                <a href="manage_donors.php" class="nav-link active">
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
            <?php if(isset($_SESSION['notif_msg'])): ?>
                <div class="alert alert-success" style="margin-bottom: 2rem;">
                    <?php echo $_SESSION['notif_msg']; unset($_SESSION['notif_msg']); ?>
                </div>
            <?php endif; ?>

            <header class="flex justify-between items-center" style="margin-bottom: 2rem;">
                <div>
                    <h2 style="font-size: 2rem;">Manage Donors</h2>
                    <p>View and manage all donor registrations</p>
                </div>
                <a href="../donor/register.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Add New Donor
                </a>
            </header>

            <!-- Filters -->
            <div class="card-glass" style="margin-bottom: 2rem; padding: 1.5rem;">
                <form method="GET" class="flex wrap gap-4 items-end">
                    <div style="flex: 2; min-width: 200px;">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Name, Phone or Email...">
                    </div>
                    <div style="flex: 1; min-width: 120px;">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-control">
                            <option value="">All</option>
                            <?php 
                            $groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                            foreach($groups as $g) {
                                $sel = ($blood_group == $g) ? 'selected' : '';
                                echo "<option value='$g' $sel>$g</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 120px;">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            <option value="Pending" <?php echo ($status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo ($status == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo ($status == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                            <option value="Donated" <?php echo ($status == 'Donated') ? 'selected' : ''; ?>>Donated</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="manage_donors.php" class="btn btn-outline">Reset</a>
                </form>
            </div>

            <!-- Donor Table -->
            <div class="card-glass" style="padding: 0; overflow: hidden;">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Blood Group</th>
                                <th>Contact Info</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_array($donors_query)): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><span class="badge badge-blood"><?php echo $row['blood_group']; ?></span></td>
                                <td>
                                    <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['email']); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['phone']); ?></div>
                                </td>
                                <td style="max-width: 200px; font-size: 0.85rem;"><?php echo htmlspecialchars($row['address']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <?php if($row['status'] == 'Pending'): ?>
                                            <a href="?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: var(--success); box-shadow: none;">Approve</a>
                                            <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: var(--error); box-shadow: none;">Reject</a>
                                        <?php elseif($row['status'] == 'Approved'): ?>
                                            <a href="?action=donated&id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: var(--accent); box-shadow: none;">Confirm Donation</a>
                                        <?php endif; ?>
                                        <a href="edit_donor.php?id=<?php echo $row['id']; ?>" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; border-color: var(--accent); color: var(--accent);">Edit</a>
                                        <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-outline" onclick="return confirm('Are you sure you want to delete this donor?')" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; border-color: var(--error); color: var(--error);">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($donors_query) == 0): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 4rem; color: var(--text-muted);">No donors found matching your criteria.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
