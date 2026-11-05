<?php
require_once "../db.php";
require_role("admin");

if (!function_exists("h")) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
    }
}

$message = "";
$error = "";

// Handle admin actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $request_id = (int)($_POST["request_id"] ?? 0);

    if ($request_id <= 0) {
        $error = "Invalid request.";
    }

    // APPROVE REQUEST
    elseif ($action === "approve") {
        $stmt = $conn->prepare(
            "UPDATE pickup_requests SET status='Approved'
             WHERE id=? AND status='Pending'"
        );
        $stmt->bind_param("i", $request_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Request approved successfully.";
        } else {
            $error = "Request could not be approved.";
        }
        $stmt->close();
    }

    // REJECT REQUEST
    elseif ($action === "reject") {
        $stmt = $conn->prepare(
            "UPDATE pickup_requests SET status='Rejected'
             WHERE id=? AND status='Pending'"
        );
        $stmt->bind_param("i", $request_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Request rejected successfully.";
        } else {
            $error = "Request could not be rejected.";
        }
        $stmt->close();
    }

    // ASSIGN COLLECTOR
    elseif ($action === "assign") {
        $collector_id = (int)($_POST["collector_id"] ?? 0);

        $check = $conn->prepare(
            "SELECT id FROM users WHERE id=? AND role='collector'"
        );
        $check->bind_param("i", $collector_id);
        $check->execute();
        $valid_collector = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$valid_collector) {
            $error = "Please select a valid collector.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE pickup_requests
                 SET assigned_collector_id=?, status='Scheduled'
                 WHERE id=? AND status IN ('Approved','Scheduled')"
            );
            $stmt->bind_param("ii", $collector_id, $request_id);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message = "Collector assigned successfully.";
            } else {
                $error = "Approve the request before assigning a collector.";
            }
            $stmt->close();
        }
    }

    // UPDATE PICKUP STATUS
    elseif ($action === "update_status") {
        $new_status = $_POST["new_status"] ?? "";

        if ($new_status === "Collected") {
            $stmt = $conn->prepare(
                "UPDATE pickup_requests
                 SET status='Collected'
                 WHERE id=? AND status='Scheduled'
                 AND assigned_collector_id IS NOT NULL"
            );
        } elseif ($new_status === "Recycled") {
            $stmt = $conn->prepare(
                "UPDATE pickup_requests
                 SET status='Recycled'
                 WHERE id=? AND status='Collected'
                 AND assigned_collector_id IS NOT NULL"
            );
        } else {
            $stmt = false;
        }

        if ($stmt) {
            $stmt->bind_param("i", $request_id);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $message = "Request status updated successfully.";
            } else {
                $error = "Invalid status change. Check the current request status.";
            }
            $stmt->close();
        } else {
            $error = "Invalid status selected.";
        }
    }

    else {
        $error = "Invalid action.";
    }
}

// Get all collectors
$collectors = [];
$collector_result = $conn->query(
    "SELECT id, full_name FROM users
     WHERE role='collector' ORDER BY full_name"
);

if ($collector_result) {
    while ($collector = $collector_result->fetch_assoc()) {
        $collectors[] = $collector;
    }
}

// Get all pickup requests
$sql = "
    SELECT
        p.id,
        p.item_description,
        p.quantity,
        p.item_condition,
        p.pickup_address,
        p.preferred_date,
        p.status,
        p.assigned_collector_id,
        u.full_name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,
        cat.category_name,
        c.full_name AS collector_name
    FROM pickup_requests p
    JOIN users u ON p.customer_id = u.id
    JOIN ewaste_categories cat ON p.category_id = cat.id
    LEFT JOIN users c ON p.assigned_collector_id = c.id
    ORDER BY p.created_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Database error: " . h($conn->error));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests | EcoCycle</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body class="admin-page">

<header class="admin-navbar">
    <a href="dashboard.php" class="brand">
        <span class="brand-mark">♻</span> EcoCycle
    </a>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_requests.php">Manage Requests</a>
        <a href="create_collector.php">Create Collector</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<main class="admin-container">

    <div class="admin-welcome">
        <span class="eyebrow">ADMIN PANEL</span>
        <h1>Manage Pickup Requests</h1>
        <p>Review requests, assign collectors and track recycling progress.</p>
    </div>

    <?php if ($message): ?>
        <div class="admin-alert success-alert">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="admin-alert error-alert">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <div class="admin-content-box">
        <h2>All Pickup Requests</h2>

        <div class="request-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Item Details</th>
                        <th>Pickup Details</th>
                        <th>Status</th>
                        <th>Collector</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= h($row["id"]) ?></td>

                            <td>
                                <strong><?= h($row["customer_name"]) ?></strong><br>
                                <small><?= h($row["customer_email"]) ?></small><br>
                                <small><?= h($row["customer_phone"] ?: "No phone") ?></small>
                            </td>

                            <td>
                                <strong><?= h($row["category_name"]) ?></strong><br>
                                Quantity: <?= h($row["quantity"]) ?><br>
                                Condition: <?= h($row["item_condition"] ?: "Not specified") ?><br>
                                <small><?= h($row["item_description"] ?: "") ?></small>
                            </td>

                            <td>
                                <?= h($row["pickup_address"]) ?><br>
                                <small>Preferred: <?= h($row["preferred_date"] ?: "Not specified") ?></small>
                            </td>

                            <td>
                                <span class="admin-status status-<?= strtolower(h($row["status"])) ?>">
                                    <?= h($row["status"]) ?>
                                </span>
                            </td>

                            <td>
                                <?= h($row["collector_name"] ?: "Not assigned") ?>
                            </td>

                            <td>
                                <div class="request-actions">

                                    <?php if ($row["status"] === "Pending"): ?>
                                        <form method="POST">
                                            <input type="hidden" name="request_id" value="<?= h($row["id"]) ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button class="approve-btn" type="submit">Approve</button>
                                        </form>

                                        <form method="POST">
                                            <input type="hidden" name="request_id" value="<?= h($row["id"]) ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button class="reject-btn" type="submit">Reject</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (in_array($row["status"], ["Approved", "Scheduled"], true)): ?>

                                        <?php if (count($collectors) > 0): ?>
                                            <form method="POST" class="assign-form">
                                                <input type="hidden" name="request_id" value="<?= h($row["id"]) ?>">
                                                <input type="hidden" name="action" value="assign">

                                                <select name="collector_id" required>
                                                    <option value="">Select Collector</option>

                                                    <?php foreach ($collectors as $collector): ?>
                                                        <option
                                                            value="<?= h($collector["id"]) ?>"
                                                            <?= ((int)$row["assigned_collector_id"] === (int)$collector["id"]) ? "selected" : "" ?>>
                                                            <?= h($collector["full_name"]) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <button type="submit" class="assign-btn">
                                                    Assign
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <small>No collectors available.</small>
                                        <?php endif; ?>

                                    <?php endif; ?>

                                    <?php if ($row["status"] === "Scheduled"): ?>
                                        <form method="POST">
                                            <input type="hidden" name="request_id" value="<?= h($row["id"]) ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="new_status" value="Collected">
                                            <button type="submit" class="assign-btn">Mark Collected</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($row["status"] === "Collected"): ?>
                                        <form method="POST">
                                            <input type="hidden" name="request_id" value="<?= h($row["id"]) ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="new_status" value="Recycled">
                                            <button type="submit" class="approve-btn">Mark Recycled</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (in_array($row["status"], ["Rejected", "Recycled"], true)): ?>
                                        <small>Completed</small>
                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-table">
                            No pickup requests found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

</body>
</html>