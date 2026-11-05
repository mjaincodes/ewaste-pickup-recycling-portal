
<?php
require_once "../db.php";
require_role("admin");

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Dashboard statistics
$statsQuery = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Pending') AS pending,
        SUM(status = 'Scheduled') AS scheduled,
        SUM(status = 'Collected') AS collected,
        SUM(status = 'Recycled') AS recycled
    FROM pickup_requests
");

$stats = $statsQuery->fetch_assoc();

// Recent pickup requests
$recentQuery = $conn->query("
    SELECT
        p.id,
        p.quantity,
        p.status,
        p.preferred_date,
        p.created_at,
        u.full_name,
        c.category_name
    FROM pickup_requests p
    JOIN users u ON p.customer_id = u.id
    JOIN ewaste_categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 6
");

$adminName = $_SESSION["full_name"] ?? "Admin";
$initial = strtoupper(substr($adminName, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | EcoCycle</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="adminx-body">

<div class="adminx-layout">

    <!-- Sidebar -->
    <aside class="adminx-sidebar">
        <div class="adminx-brand">
            <div class="adminx-logo">♻</div>
            <div>
                <h2>EcoCycle</h2>
                <span>ADMIN PANEL</span>
            </div>
        </div>

        <p class="adminx-menu-label">MAIN MENU</p>

        <nav class="adminx-nav">
            <a href="dashboard.php" class="active">
                <span>▦</span> Dashboard
            </a>

            <a href="manage_requests.php">
                <span>📋</span> Manage Requests
            </a>

            <a href="create_collector.php">
                <span>👤</span> Create Collector
            </a>

            <a href="../index.php">
                <span>🌐</span> Visit Website
            </a>
        </nav>

        <div class="adminx-sidebar-bottom">
            <div class="adminx-profile">
                <div class="adminx-avatar"><?php echo h($initial); ?></div>
                <div>
                    <strong><?php echo h($adminName); ?></strong>
                    <small>Administrator</small>
                </div>
            </div>

            <a href="../logout.php" class="adminx-logout">
                ↪ Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="adminx-main">

        <header class="adminx-topbar">
            <div>
                <h2>Dashboard Overview</h2>
                <p>Manage your e-waste recycling activities</p>
            </div>

            <div class="adminx-top-user">
                <div class="adminx-avatar"><?php echo h($initial); ?></div>
                <span><?php echo h($adminName); ?></span>
            </div>
        </header>

        <!-- Welcome Banner -->
        <section class="adminx-hero">
            <div>
                <span class="adminx-hero-tag">ADMIN CONTROL CENTER</span>
                <h1>Welcome back, <?php echo h($adminName); ?>! 👋</h1>
                <p>
                    Track pickup requests, manage collectors and help
                    build a cleaner, greener environment.
                </p>

                <a href="manage_requests.php" class="adminx-hero-btn">
                    Manage Requests →
                </a>
            </div>

            <div class="adminx-hero-icon">♻</div>
        </section>

        <!-- Statistics -->
        <section class="adminx-section">
            <div class="adminx-section-heading">
                <div>
                    <h2>Platform Statistics</h2>
                    <p>Overview of your recycling platform</p>
                </div>
            </div>

            <div class="adminx-stats-grid">

                <div class="adminx-stat-card">
                    <div class="adminx-stat-top">
                        <span>Total Requests</span>
                        <div class="adminx-stat-icon green">📦</div>
                    </div>
                    <h2><?php echo (int)($stats["total"] ?? 0); ?></h2>
                    <small>All pickup requests</small>
                </div>

                <div class="adminx-stat-card">
                    <div class="adminx-stat-top">
                        <span>Pending Requests</span>
                        <div class="adminx-stat-icon orange">⏳</div>
                    </div>
                    <h2><?php echo (int)($stats["pending"] ?? 0); ?></h2>
                    <small>Waiting for approval</small>
                </div>

                <div class="adminx-stat-card">
                    <div class="adminx-stat-top">
                        <span>Scheduled Pickups</span>
                        <div class="adminx-stat-icon blue">🚚</div>
                    </div>
                    <h2><?php echo (int)($stats["scheduled"] ?? 0); ?></h2>
                    <small>Assigned to collectors</small>
                </div>

                <div class="adminx-stat-card">
                    <div class="adminx-stat-top">
                        <span>Collected Items</span>
                        <div class="adminx-stat-icon purple">♻</div>
                    </div>
                    <h2><?php echo (int)($stats["collected"] ?? 0); ?></h2>
                    <small>Items collected</small>
                </div>

            </div>
        </section>

        <!-- Recent Requests -->
        <section class="adminx-section">
            <div class="adminx-section-heading">
                <div>
                    <h2>Recent Pickup Requests</h2>
                    <p>Latest activity from customers</p>
                </div>

                <a href="manage_requests.php" class="adminx-view-all">
                    View All →
                </a>
            </div>

            <div class="adminx-table-wrap">
                <table class="adminx-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Customer</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Pickup Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($recentQuery && $recentQuery->num_rows > 0): ?>
                        <?php while ($row = $recentQuery->fetch_assoc()): ?>
                            <?php
                            $statusClass = strtolower($row["status"]);
                            ?>
                            <tr>
                                <td><strong>#<?php echo (int)$row["id"]; ?></strong></td>
                                <td><?php echo h($row["full_name"]); ?></td>
                                <td><?php echo h($row["category_name"]); ?></td>
                                <td><?php echo (int)$row["quantity"]; ?></td>
                                <td>
                                    <?php
                                    echo !empty($row["preferred_date"])
                                        ? date("d M Y", strtotime($row["preferred_date"]))
                                        : "Not selected";
                                    ?>
                                </td>
                                <td>
                                    <span class="adminx-status <?php echo h($statusClass); ?>">
                                        <?php echo h($row["status"]); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="adminx-empty">
                                No pickup requests available yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="adminx-section">
            <div class="adminx-section-heading">
                <div>
                    <h2>Quick Actions</h2>
                    <p>Frequently used admin operations</p>
                </div>
            </div>

            <div class="adminx-actions-grid">
                <a href="manage_requests.php" class="adminx-action-card">
                    <span>📋</span>
                    <div>
                        <h3>Manage Requests</h3>
                        <p>Approve, reject and assign pickups</p>
                    </div>
                    <b>→</b>
                </a>

                <a href="create_collector.php" class="adminx-action-card">
                    <span>👷</span>
                    <div>
                        <h3>Add Collector</h3>
                        <p>Create a new collector account</p>
                    </div>
                    <b>→</b>
                </a>
            </div>
        </section>

        <footer class="adminx-footer">
            © <?php echo date("Y"); ?> EcoCycle. Admin Dashboard.
            Together for a greener future 🌱
        </footer>

    </main>
</div>

</body>
</html>
