<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

include("config/db.php");
$error = "";
if (isset($_POST['login'])) {
    $u = mysqli_real_escape_string($conn, $_POST['u']);
    $p = mysqli_real_escape_string($conn, $_POST['p']);
    $q = mysqli_query($conn, "SELECT * FROM admin WHERE username='$u' AND password='$p'");
    if (mysqli_num_rows($q) > 0) {
        $_SESSION['admin_logged_in'] = true;
        header("location:admin/dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Blood Bank</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-wrapper {
            width: 100%;
            max-width: 480px;
            padding: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="login-wrapper fade-in-up">
        <div class="card-glass">
            <div class="text-center" style="margin-bottom: 2.5rem;">
                <a href="index.php" class="logo justify-center" style="margin-bottom: 1.5rem;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                    </svg>
                    Blood<span>Bank</span>
                </a>
                <h2>Welcome Back</h2>
                <p>Sign in to manage blood inventory</p>
            </div>


            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method='post'>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input name='u' type="text" class="form-control" placeholder="admin" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input name='p' type='password' class="form-control" placeholder="••••••••" required>
                </div>
                <button name='login' class="btn btn-primary w-full" style="margin-top: 1rem;">
                    Login to Dashboard
                </button>
            </form>

            <div class="text-center" style="margin-top: 2rem;">
                <a href="index.php" class="btn btn-outline" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                    ← Return to Home
                </a>
            </div>
        </div>
    </div>
</body>

</html>