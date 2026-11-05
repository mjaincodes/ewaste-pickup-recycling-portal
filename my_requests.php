
<?php
require_once "db.php";
require_role("customer");

$customerId = $_SESSION["user_id"];

$stmt = $conn->prepare("
    SELECT
        p.*,
        c.category_name
    FROM pickup_requests p
    JOIN ewaste_categories c ON p.category_id = c.id
    WHERE p.customer_id = ?
    ORDER BY p.created_at DESC
");

$stmt->bind_param("i", $customerId);
$stmt->execute();
$requests = $stmt->get_result();


$steps = [
    "Pending",
    "Approved",
    "Scheduled",
    "Collected",
    "Recycled"
];

$icons = ["📝", "✓", "🚚", "📦", "♻"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests | EcoCycle</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="mytrack-body">

<header class="mytrack-header">
    <a href="index.php" class="mytrack-brand">♻ EcoCycle</a>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="request_pickup.php" class="mytrack-new-btn">+ New Request</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="mytrack-container">

    <section class="mytrack-heading">
        <div>
            <span class="mytrack-tag">CUSTOMER PORTAL</span>
            <h1>My Pickup Requests</h1>
            <p>Track your e-waste pickup and recycling journey.</p>
        </div>

        <a href="request_pickup.php" class="mytrack-main-btn">
            + Create New Request
        </a>
    </section>

    <?php if ($requests->num_rows > 0): ?>

        <div class="mytrack-list">

        <?php while ($row = $requests->fetch_assoc()): ?>

            <?php
                $status = $row["status"];
                $isRejected = ($status === "Rejected");

                $currentStep = array_search($status, $steps);
                if ($currentStep === false) {
                    $currentStep = 0;
                }

                $statusClass = strtolower($status);
            ?>

            <article class="mytrack-card">

                <div class="mytrack-card-top">
                    <div>
                        <span class="mytrack-request-id">
                            REQUEST #<?php echo (int)$row["id"]; ?>
                        </span>

                        <h2><?php echo e($row["category_name"]); ?></h2>
                    </div>

                    <span class="mytrack-badge <?php echo e($statusClass); ?>">
                        <?php echo e($status); ?>
                    </span>
                </div>

                <div class="mytrack-details">

                    <div>
                        <small>Quantity</small>
                        <strong><?php echo (int)$row["quantity"]; ?> Items</strong>
                    </div>

                    <div>
                        <small>Condition</small>
                        <strong><?php echo e($row["item_condition"] ?: "Not specified"); ?></strong>
                    </div>

                    <div>
                        <small>Pickup Date</small>
                        <strong>
                            <?php
                            echo !empty($row["preferred_date"])
                                ? date("d M Y", strtotime($row["preferred_date"]))
                                : "Not selected";
                            ?>
                        </strong>
                    </div>

                </div>

                <div class="mytrack-info">
                    <p><strong>Pickup Address:</strong> <?php echo nl2br(e($row["pickup_address"])); ?></p>

                    <?php if (!empty($row["item_description"])): ?>
                        <p><strong>Description:</strong> <?php echo e($row["item_description"]); ?></p>
                    <?php endif; ?>

                    <small>
                        Submitted:
                        <?php echo date("d M Y", strtotime($row["created_at"])); ?>
                    </small>
                </div>

                <div class="mytrack-progress">

                    <h3>Pickup Progress</h3>

                    <?php if ($isRejected): ?>

                        <div class="mytrack-rejected">
                            <span>✕</span>
                            <div>
                                <strong>Request Rejected</strong>
                                <p>Your pickup request was rejected by the administrator.</p>
                            </div>
                        </div>

                    <?php else: ?>

                        <div class="mytrack-steps">

                            <?php foreach ($steps as $index => $step): ?>

                                <?php
                                    $done = ($index <= $currentStep);
                                    $active = ($index === $currentStep);
                                ?>

                                <div class="mytrack-step <?php echo $done ? "done" : ""; ?>">

                                    <div class="mytrack-step-circle">
                                        <?php echo $done ? "✓" : $icons[$index]; ?>
                                    </div>

                                    <span><?php echo e($step); ?></span>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <div class="mytrack-current">
                            Current Status:
                            <strong><?php echo e($status); ?></strong>
                        </div>

                    <?php endif; ?>

                </div>

            </article>

        <?php endwhile; ?>

        </div>

    <?php else: ?>

        <section class="mytrack-empty">
            <div>📦</div>
            <h2>No Pickup Requests Yet</h2>
            <p>You haven't submitted any e-waste pickup requests.</p>
            <a href="request_pickup.php" class="mytrack-main-btn">
                Create Your First Request
            </a>
        </section>

    <?php endif; ?>

</main>

<footer class="mytrack-footer">
    © <?php echo date("Y"); ?> EcoCycle | Together for a Greener Future 🌱
</footer>

</body>
</html>
