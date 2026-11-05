
<?php

require_once "db.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Customer access only
if ($_SESSION["role"] !== "customer") {
    die("Access denied.");
}

$user_id = (int) $_SESSION["user_id"];
$name = $_SESSION["full_name"];

// Get dashboard statistics
$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'Pending'), 0) AS pending,
        COALESCE(SUM(status = 'Approved'), 0) AS approved,
        COALESCE(SUM(status IN ('Collected', 'Recycled')), 0) AS completed
    FROM pickup_requests
    WHERE customer_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent requests
$stmt = $conn->prepare("
    SELECT
        p.id,
        c.category_name,
        p.item_description,
        p.quantity,
        p.status,
        p.created_at
    FROM pickup_requests p
    JOIN ewaste_categories c
        ON p.category_id = c.id
    WHERE p.customer_id = ?
    ORDER BY p.created_at DESC
    LIMIT 5
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$requests = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Customer Dashboard | E-Waste Portal</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<!-- Navigation -->

<header class="dash-navbar">

    <a href="index.php" class="dash-logo">
        ♻ E-Waste Portal
    </a>

    <div class="dash-user">

        <div class="user-avatar">
            <?php echo strtoupper(substr($name, 0, 1)); ?>
        </div>

        <div>
            <strong><?php echo e($name); ?></strong>
            <small>Customer</small>
        </div>

        <a href="logout.php" class="dash-logout">
            Logout
        </a>

    </div>

</header>


<div class="dashboard-layout">

    <!-- Sidebar -->

    <aside class="dashboard-sidebar">

        <h3>MENU</h3>

        <a href="dashboard.php" class="sidebar-link active">
            <span>▦</span> Dashboard
        </a>

        <a href="request_pickup.php" class="sidebar-link">
            <span>＋</span> Request Pickup
        </a>

        <a href="my_requests.php" class="sidebar-link">
            <span>▤</span> My Requests
        </a>

        <a href="index.php" class="sidebar-link">
            <span>⌂</span> Home
        </a>

        <a href="logout.php" class="sidebar-link logout-link">
            <span>↪</span> Logout
        </a>

    </aside>


    <!-- Main Dashboard -->

    <main class="dashboard-main">

        <div class="dash-heading">

            <div>
                <span class="dash-label">
                    CUSTOMER DASHBOARD
                </span>

                <h1>
                    Welcome, <?php echo e($name); ?>!
                </h1>

                <p>
                    Manage your e-waste pickups and
                    track your recycling requests.
                </p>
            </div>

            <a href="request_pickup.php" class="dash-primary-btn">
                + Request Pickup
            </a>

        </div>


        <!-- Statistics Cards -->

        <section class="stats-grid">

            <div class="dash-stat-card total-card">
                <div class="stat-icon">▦</div>
                <p>Total Requests</p>
                <h2><?php echo $stats["total"]; ?></h2>
                <small>All your requests</small>
            </div>


            <div class="dash-stat-card pending-card">
                <div class="stat-icon">◷</div>
                <p>Pending</p>
                <h2><?php echo $stats["pending"]; ?></h2>
                <small>Waiting for approval</small>
            </div>


            <div class="dash-stat-card approved-card">
                <div class="stat-icon">✓</div>
                <p>Approved</p>
                <h2><?php echo $stats["approved"]; ?></h2>
                <small>Approved requests</small>
            </div>


            <div class="dash-stat-card completed-card">
                <div class="stat-icon">♻</div>
                <p>Completed</p>
                <h2><?php echo $stats["completed"]; ?></h2>
                <small>Collected or recycled</small>
            </div>

        </section>


        <!-- Quick Actions -->

        <section class="quick-section">

            <h2>Quick Actions</h2>

            <div class="quick-grid">

                <a href="request_pickup.php" class="quick-card">

                    <div class="quick-icon">♻</div>

                    <h3>Request E-Waste Pickup</h3>

                    <p>
                        Submit details about old
                        electronic items.
                    </p>

                    <span>Start Request →</span>

                </a>


                <a href="my_requests.php" class="quick-card">

                    <div class="quick-icon">📋</div>

                    <h3>Track My Requests</h3>

                    <p>
                        Check the latest status of
                        your pickup requests.
                    </p>

                    <span>View Requests →</span>

                </a>

            </div>

        </section>


        <!-- Recent Requests -->

        <section class="recent-section">

            <div class="recent-heading">

                <div>
                    <h2>Recent Pickup Requests</h2>

                    <p>
                        Your latest e-waste collection activity.
                    </p>
                </div>

                <a href="my_requests.php">
                    View All →
                </a>

            </div>


            <div class="dash-table-wrapper">

                <table class="dash-table">

                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Category</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($requests->num_rows > 0): ?>

                        <?php while ($row = $requests->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    #EW<?php echo $row["id"]; ?>
                                </td>

                                <td>
                                    <?php echo e($row["category_name"]); ?>
                                </td>

                                <td>
                                    <?php echo e($row["item_description"] ?: "-"); ?>
                                </td>

                                <td>
                                    <?php echo $row["quantity"]; ?>
                                </td>

                                <td>
                                    <?php echo date(
                                        "d M Y",
                                        strtotime($row["created_at"])
                                    ); ?>
                                </td>

                                <td>
                                    <span class="status-badge <?php
                                        echo strtolower($row["status"]);
                                    ?>">
                                        <?php echo e($row["status"]); ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-message">
                                No pickup requests yet.
                                Submit your first request!
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- Green Message -->

        <section class="green-message">

            <span>🌱</span>

            <div>
                <h3>Every small action makes a difference!</h3>

                <p>
                    Responsible e-waste collection helps
                    build a cleaner and greener future.
                </p>
            </div>

        </section>

    </main>

</div>


<footer class="dash-footer">
    © 2026 E-Waste Pickup and Recycling Portal
</footer>

</body>
</html>
