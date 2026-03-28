<?php
include("../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Fetch Blood Stock Stats
$stock_query = mysqli_query($conn, "
    SELECT 
        blood_group, 
        COUNT(*) as donor_count,
        SUM(CASE WHEN status = 'Approved' OR status = 'Donated' THEN 1 ELSE 0 END) as active_donors
    FROM donors 
    GROUP BY blood_group
");

$stocks = [];
while ($row = mysqli_fetch_array($stock_query)) {
    $stocks[$row['blood_group']] = [
        'donors' => $row['donor_count'],
        'units' => $row['active_donors'] * 450 // 450ml per qualified donor
    ];
}

$all_groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Stock - Blood Bank</title>
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
                <a href="manage_donors.php" class="nav-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Manage Donors
                </a>
                <a href="blood_stock.php" class="nav-link active">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    Blood Stock
                </a>
            </nav>
            <a href="../login.php?logout=1" class="btn btn-outline w-full" style="justify-content: center;">Logout</a>
        </aside>

        <!-- Main Content -->
        <main class="main-content fade-in-up">
            <header style="margin-bottom: 2rem;">
                <h2 style="font-size: 2rem;">Blood Inventory</h2>
                <p>Real-time blood availability across all groups</p>
            </header>

            <div class="flex wrap gap-6">
                <!-- Stock Summary Table -->
                <div class="card-glass" style="flex: 2; padding: 0; overflow: hidden; min-width: 400px;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                        <h3 style="margin: 0;">Inventory Summary</h3>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Blood Group</th>
                                    <th>Total Donors</th>
                                    <th>Active/Qualified</th>
                                    <th>Available Stock (ml)</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($all_groups as $group): 
                                    $data = $stocks[$group] ?? ['donors' => 0, 'units' => 0];
                                    $status_class = ($data['units'] > 1000) ? 'approved' : (($data['units'] > 0) ? 'pending' : 'rejected');
                                    $status_label = ($data['units'] > 1000) ? 'Sufficient' : (($data['units'] > 0) ? 'Low' : 'Critical');
                                ?>
                                <tr>
                                    <td><span class="badge badge-blood" style="font-size: 1rem; padding: 0.5rem 1rem;"><?php echo $group; ?></span></td>
                                    <td><?php echo $data['donors']; ?></td>
                                    <td><?php echo $data['units'] / 450; ?></td>
                                    <td style="font-weight: 700; color: var(--secondary);"><?php echo number_format($data['units']); ?> ml</td>
                                    <td>
                                        <span class="badge badge-<?php echo $status_class; ?>">
                                            <?php echo $status_label; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($status_label !== 'Sufficient'): ?>
                                            <a href="broadcast.php?blood_group=<?php echo urlencode($group); ?>&urgent=true" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: var(--error); box-shadow: none;">Initiate Broadcast</a>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">No Action Needed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="card-glass" style="flex: 1; min-width: 300px; padding: 2rem; background: linear-gradient(135deg, var(--secondary) 0%, #102a43 100%); color: white;">
                    <h3 style="color: white; margin-bottom: 1.5rem;">Inventory Notes</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 1rem; display: flex; gap: 0.75rem; font-size: 0.95rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; color: #60a5fa;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            Stock is calculated based on Approved and Donated status donors.
                        </li>
                        <li style="margin-bottom: 1rem; display: flex; gap: 0.75rem; font-size: 0.95rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; color: #60a5fa;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            Standard unit size is 450ml per qualified donor.
                        </li>
                        <li style="display: flex; gap: 0.75rem; font-size: 0.95rem;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; color: #60a5fa;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            Urgent broadcasts should be initiated if stock levels drop below "Sufficient".
                        </li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
