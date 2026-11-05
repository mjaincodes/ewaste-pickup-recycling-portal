
<?php
require_once "../db.php";
require_role("collector");

$collector_id = (int)$_SESSION["user_id"];
$message = "";
$error = "";

// Update pickup status
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $request_id = (int)($_POST["request_id"] ?? 0);
    $new_status = $_POST["status"] ?? "";

    if (in_array($new_status, ["Collected", "Recycled"], true)) {
        $old_status = $new_status === "Collected" ? "Scheduled" : "Collected";

        $stmt = $conn->prepare(
            "UPDATE pickup_requests SET status=?
             WHERE id=? AND assigned_collector_id=? AND status=?"
        );
        $stmt->bind_param("siis", $new_status, $request_id, $collector_id, $old_status);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Pickup status updated successfully!";
        } else {
            $error = "Unable to update. Please refresh and try again.";
        }
        $stmt->close();
    }
}

// Statistics
$stats_query = $conn->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(status='Scheduled') AS scheduled,
        SUM(status='Collected') AS collected,
        SUM(status='Recycled') AS recycled
     FROM pickup_requests
     WHERE assigned_collector_id=?"
);
$stats_query->bind_param("i", $collector_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
$stats_query->close();

// Assigned requests
$stmt = $conn->prepare(
    "SELECT p.*, u.full_name AS customer_name,
     u.phone AS customer_phone, cat.category_name
     FROM pickup_requests p
     JOIN users u ON p.customer_id=u.id
     JOIN ewaste_categories cat ON p.category_id=cat.id
     WHERE p.assigned_collector_id=?
     ORDER BY
       CASE p.status
         WHEN 'Scheduled' THEN 1
         WHEN 'Collected' THEN 2
         ELSE 3
       END,
       p.created_at DESC"
);
$stmt->bind_param("i", $collector_id);
$stmt->execute();
$result = $stmt->get_result();

$name = htmlspecialchars($_SESSION["full_name"] ?? "Collector");
$initial = strtoupper(substr($_SESSION["full_name"] ?? "C", 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collector Dashboard | EcoCycle</title>
<link rel="stylesheet" href="../style.css">
</head>

<body class="collector-body">

<div class="collector-layout">

    <aside class="collector-sidebar">
        <a href="dashboard.php" class="collector-logo">
            <span>♻</span> EcoCycle
        </a>

        <p class="sidebar-label">WORKSPACE</p>

        <a href="dashboard.php" class="sidebar-link active">
            <span>▦</span> Dashboard
        </a>

        <a href="#my-pickups" class="sidebar-link">
            <span>♧</span> My Pickups
        </a>

        <div class="sidebar-bottom">
            <div class="collector-profile">
                <div class="profile-avatar"><?= $initial ?></div>
                <div>
                    <strong><?= $name ?></strong>
                    <small>Pickup Collector</small>
                </div>
            </div>

            <a href="../logout.php" class="sidebar-link logout-link">
                <span>↪</span> Logout
            </a>
        </div>
    </aside>

    <main class="collector-main">

        <header class="collector-topbar">
            <div>
                <small>ECYCLE / COLLECTOR PANEL</small>
                <h2>Dashboard</h2>
            </div>
            <div class="topbar-date">
                <span>♻</span> Eco-friendly Operations
            </div>
        </header>

        <section class="collector-hero">
            <div>
                <span class="hero-tag">COLLECT. RECYCLE. REPEAT.</span>
                <h1>Hello, <?= $name ?>! 👋</h1>
                <p>Ready to make a difference today? Manage your assigned e-waste pickups from here.</p>
                <a href="#my-pickups" class="hero-button">View My Pickups →</a>
            </div>
            <div class="hero-symbol">♻</div>
        </section>

        <?php if ($message): ?>
            <div class="collector-message success-alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="collector-message error-alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section class="collector-stats">
            <div class="collector-stat-card">
                <div class="stat-icon green-icon">▦</div>
                <p>Total Assigned</p>
                <h2><?= (int)($stats["total"] ?? 0) ?></h2>
                <small>All your pickups</small>
            </div>

            <div class="collector-stat-card">
                <div class="stat-icon orange-icon">◷</div>
                <p>Scheduled</p>
                <h2><?= (int)($stats["scheduled"] ?? 0) ?></h2>
                <small>Awaiting collection</small>
            </div>

            <div class="collector-stat-card">
                <div class="stat-icon blue-icon">✓</div>
                <p>Collected</p>
                <h2><?= (int)($stats["collected"] ?? 0) ?></h2>
                <small>Items picked up</small>
            </div>

            <div class="collector-stat-card">
                <div class="stat-icon purple-icon">♻</div>
                <p>Recycled</p>
                <h2><?= (int)($stats["recycled"] ?? 0) ?></h2>
                <small>Successfully recycled</small>
            </div>
        </section>

        <section class="collector-work-section" id="my-pickups">
            <div class="section-heading">
                <div>
                    <span class="hero-tag">TASK MANAGEMENT</span>
                    <h2>My Assigned Pickups</h2>
                    <p>View customer details and update pickup progress.</p>
                </div>
                <span class="task-count"><?= $result->num_rows ?> Tasks</span>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="collector-task-list">

                <?php while ($row = $result->fetch_assoc()): ?>
                    <article class="collector-task-card">

                        <div class="task-top">
                            <div>
                                <span class="task-id">REQUEST #<?= (int)$row["id"] ?></span>
                                <h3><?= htmlspecialchars($row["category_name"]) ?></h3>
                            </div>

                            <span class="admin-status status-<?= strtolower(htmlspecialchars($row["status"])) ?>">
                                <?= htmlspecialchars($row["status"]) ?>
                            </span>
                        </div>

                        <div class="task-details-grid">
                            <div>
                                <small>CUSTOMER</small>
                                <strong><?= htmlspecialchars($row["customer_name"]) ?></strong>
                                <span><?= htmlspecialchars($row["customer_phone"] ?: "Phone not provided") ?></span>
                            </div>

                            <div>
                                <small>QUANTITY</small>
                                <strong><?= (int)$row["quantity"] ?> Item(s)</strong>
                                <span><?= htmlspecialchars($row["item_condition"] ?: "Not specified") ?></span>
                            </div>

                            <div>
                                <small>PREFERRED DATE</small>
                                <strong><?= htmlspecialchars($row["preferred_date"] ?: "Not specified") ?></strong>
                                <span>Pickup schedule</span>
                            </div>

                            <div>
                                <small>ADDRESS</small>
                                <strong><?= htmlspecialchars($row["pickup_address"]) ?></strong>
                            </div>
                        </div>

                        <?php if (!empty($row["item_description"])): ?>
                            <p class="task-description">
                                <strong>Item Description:</strong>
                                <?= htmlspecialchars($row["item_description"]) ?>
                            </p>
                        <?php endif; ?>

                        <div class="task-footer">
                            <?php if ($row["status"] === "Scheduled"): ?>
                                <span>Pickup is waiting for collection.</span>
                                <form method="POST">
                                    <input type="hidden" name="request_id" value="<?= (int)$row["id"] ?>">
                                    <input type="hidden" name="status" value="Collected">
                                    <button class="task-action-button" type="submit">✓ Mark Collected</button>
                                </form>

                            <?php elseif ($row["status"] === "Collected"): ?>
                                <span>Items have been collected.</span>
                                <form method="POST">
                                    <input type="hidden" name="request_id" value="<?= (int)$row["id"] ?>">
                                    <input type="hidden" name="status" value="Recycled">
                                    <button class="task-action-button recycle-button" type="submit">♻ Mark Recycled</button>
                                </form>

                            <?php else: ?>
                                <span>Task completed successfully.</span>
                                <span class="completed-label">✓ Completed</span>
                            <?php endif; ?>
                        </div>

                    </article>
                <?php endwhile; ?>

                </div>
            <?php else: ?>
                <div class="collector-empty">
                    <div>♻</div>
                    <h3>No Pickups Assigned Yet</h3>
                    <p>New pickup tasks assigned by the admin will appear here.</p>
                </div>
            <?php endif; ?>

        </section>

        <footer class="collector-footer">
            EcoCycle © 2026 | Building a Greener Tomorrow
        </footer>

    </main>
</div>

</body>
</html>

<?php $stmt->close(); ?>
