<?php
include("../config/db.php");
require_once("../includes/email_sender.php");
require_once("../includes/sms_sender.php");
$msg = "";
$status_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $query = "INSERT INTO donors (name, blood_group, email, phone, address, status) VALUES ('$name', '$blood_group', '$email', '$phone', '$address', 'Pending')";

    if (mysqli_query($conn, $query)) {
        // Send registration acknowledgment email
        if (!empty($email)) {
            $html = buildEmailTemplate(
                $name,
                "<p style='color:#333;font-size:16px;'>Thank you for registering as a blood donor!</p>
                 <p style='color:#333;font-size:16px;'>Your request is currently <strong>Pending</strong> admin approval. Once approved, you will be notified, and your information will be available for lifesaving blood searches.</p>
                 <p style='color:#666;font-size:14px;margin-top:20px;'><strong>Your Details:</strong><br>
                 Name: $name<br>
                 Blood Group: $blood_group<br>
                 Phone: $phone</p>"
            );
            sendEmail($email, $name, "Donor Registration Received", "Thank you for registering! Your request is pending admin approval.", $html);
        }

        // Send registration acknowledgment SMS
        if (!empty($phone)) {
            $smsMessage = "Hello $name, thank you for registering as a donor. Your request is pending admin approval. - Blood Bank";
            sendSMS($phone, $smsMessage);
        }

        $msg = "Registration successful! Your request is pending admin approval.";
        $status_type = "success";
    } else {
        $msg = "Registration failed. Please try again.";
        $status_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Registration - Blood Bank</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .register-wrapper {
            width: 100%;
            max-width: 600px;
        }
    </style>
</head>

<body>
    <div class="register-wrapper fade-in-up">
        <div class="card-glass">
            <div class="text-center" style="margin-bottom: 2rem;">
                <a href="../index.php" class="logo justify-center" style="margin-bottom: 1rem;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                    </svg>
                    Blood<span>Bank</span>
                </a>
                <h2>Become a Donor</h2>
                <p>Join our community and save lives today.</p>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $status_type; ?>">
                    <?php if ($status_type == 'success'): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    <?php endif; ?>
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="flex wrap gap-4" style="margin-bottom: 1.5rem;">
                    <div class="form-group" style="flex: 1; min-width: 240px; margin-bottom: 0;">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 120px; margin-bottom: 0;">
                        <label class="form-label">Blood Group</label>
                        <div style="position: relative;">
                            <select name="blood_group" class="form-control" style="appearance: none;" required>
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                            </select>
                            <div
                                style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-muted);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <polyline points="6 9 12 15 18 9" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="3" class="form-control" placeholder="Enter your full address"
                        style="resize: vertical;" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-full" style="margin-top: 1rem;">
                    Register as Donor
                </button>
            </form>

            <div class="text-center" style="margin-top: 2rem;">
                <p style="margin-bottom: 1rem; font-size: 0.9rem;">
                    Already registered? <a href="../login.php"
                        style="color: var(--primary); font-weight: 600; text-decoration: none;">Admin Login</a>
                </p>
                <a href="../index.php" class="btn btn-outline" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                    ← Return to Home
                </a>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>

</html>