<?php
include("../config/db.php");
require_once("../includes/email_sender.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_donors.php");
    exit();
}

$id = intval($_GET['id']);
$donor = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM donors WHERE id=$id"));

if (!$donor) {
    header("Location: manage_donors.php");
    exit();
}

$msg = "";
$status_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $query = "UPDATE donors SET 
              name='$name', 
              blood_group='$blood_group', 
              email='$email', 
              phone='$phone', 
              address='$address', 
              status='$status' 
              WHERE id=$id";

    if (mysqli_query($conn, $query)) {
        // Send email if status changed
        if ($status !== $donor['status'] && !empty($email)) {
            $subject = "Donor Status Updated";
            $message = "";
            
            if ($status === 'Approved') {
                $subject = "Donor Registration Approved";
                $message = "<p style='color:#333;font-size:16px;'>Your registration as a donor has been <strong>Approved</strong>!</p>
                            <p style='color:#333;font-size:16px;'>You are now part of our lifesaving community. We will notify you when there is an urgent need for your blood group.</p>";
            } else if ($status === 'Rejected') {
                $subject = "Updates on your Donor Registration";
                $message = "<p style='color:#333;font-size:16px;'>We regret to inform you that your donor registration has been declined at this time.</p>
                            <p style='color:#333;font-size:16px;'>If you have any questions, please contact our support team.</p>";
            } else if ($status === 'Donated') {
                $subject = "Thank you for your donation!";
                $message = "<p style='color:#333;font-size:16px;'>We have successfully recorded your recent blood donation.</p>
                            <p style='color:#333;font-size:16px;'>Your contribution is a gift of life. Thank you for being a hero!</p>";
            } else {
                $message = "<p style='color:#333;font-size:16px;'>Your donor status has been updated to <strong>$status</strong>.</p>";
            }
            
            if ($message !== "") {
                $html = buildEmailTemplate($name, $message);
                sendEmail($email, $name, $subject, strip_tags($message), $html);
            }
        }
        
        $_SESSION['notif_msg'] = "Success: Donor information updated successfully.";
        header("Location: manage_donors.php");
        exit();
    } else {
        $msg = "Update failed. Please try again.";
        $status_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Donor - Blood Bank</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .edit-wrapper { width: 100%; max-width: 600px; margin: 4rem auto; }
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
            <div class="edit-wrapper">
                <div class="card-glass">
                    <div style="margin-bottom: 2rem;">
                        <h2 style="font-size: 1.75rem;">Edit Donor Details</h2>
                        <p>Updating record for ID: #<?php echo $id; ?></p>
                    </div>

                    <?php if($msg): ?>
                        <div class="alert alert-error"><?php echo $msg; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="flex wrap gap-4" style="margin-bottom: 1.5rem;">
                            <div class="form-group" style="flex: 2; min-width: 250px; margin-bottom: 0;">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($donor['name']); ?>" required>
                            </div>
                            <div class="form-group" style="flex: 1; min-width: 120px; margin-bottom: 0;">
                                <label class="form-label">Blood Group</label>
                                <select name="blood_group" class="form-control" required>
                                    <?php 
                                    $groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                                    foreach($groups as $g) {
                                        $sel = ($donor['blood_group'] == $g) ? 'selected' : '';
                                        echo "<option value='$g' $sel>$g</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($donor['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($donor['phone']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea name="address" rows="2" class="form-control" style="resize: vertical;" required><?php echo htmlspecialchars($donor['address']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control" required>
                                <option value="Pending" <?php echo ($donor['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo ($donor['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo ($donor['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                <option value="Donated" <?php echo ($donor['status'] == 'Donated') ? 'selected' : ''; ?>>Donated</option>
                            </select>
                        </div>

                        <div class="flex gap-4" style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">Save Changes</button>
                            <a href="manage_donors.php" class="btn btn-outline" style="flex: 1; justify-content: center;">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
