
<?php
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    header("Location: login.php");
    exit;
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$user_id = (int)$_SESSION["user_id"];
$error = "";
$success = isset($_GET["success"]);

$categories = $conn->query(
    "SELECT id, category_name FROM ewaste_categories ORDER BY category_name"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $category_id = (int)($_POST["category_id"] ?? 0);
    $description = trim($_POST["item_description"] ?? "");
    $quantity = (int)($_POST["quantity"] ?? 0);
    $condition = $_POST["item_condition"] ?? "";
    $address = trim($_POST["pickup_address"] ?? "");
    $date = $_POST["preferred_date"] ?? "";

    $allowed_conditions = ["Working", "Damaged", "Not Working"];

    $check = $conn->prepare(
        "SELECT id FROM ewaste_categories WHERE id = ?"
    );
    $check->bind_param("i", $category_id);
    $check->execute();
    $valid_category = $check->get_result()->num_rows > 0;
    $check->close();

    if (!$valid_category || $quantity < 1 || $quantity > 100) {
        $error = "Please select a valid category and quantity.";
    } elseif (!in_array($condition, $allowed_conditions, true)) {
        $error = "Please select a valid item condition.";
    } elseif ($address === "" || $date === "") {
        $error = "Please enter your address and pickup date.";
    } elseif (strtotime($date) === false || $date < date("Y-m-d")) {
        $error = "Please select today or a future date.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO pickup_requests
            (customer_id, category_id, item_description, quantity,
             item_condition, pickup_address, preferred_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iisisss",
            $user_id,
            $category_id,
            $description,
            $quantity,
            $condition,
            $address,
            $date
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: request_pickup.php?success=1");
            exit;
        } else {
            $error = "Unable to submit request. Please try again.";
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Pickup | E-Waste Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="dash-navbar">
    <a href="dashboard.php" class="dash-logo">♻ E-Waste Portal</a>
    <a href="logout.php" class="dash-logout">Logout</a>
</header>

<main class="pickup-container">

    <a href="dashboard.php" class="pickup-back">← Back to Dashboard</a>

    <div class="pickup-heading">
        <span>RECYCLE RESPONSIBLY</span>
        <h1>Request E-Waste Pickup</h1>
        <p>Tell us about your old electronic items. We'll help you recycle them responsibly.</p>
    </div>

    <?php if ($success): ?>
        <div class="pickup-success">
            ✓ Your pickup request has been submitted successfully!
            Status: Pending
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="pickup-error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="pickup-form">

        <h2>Item Information</h2>

        <label for="category_id">E-Waste Category *</label>
        <select name="category_id" id="category_id" required>
            <option value="">Select category</option>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <option value="<?php echo (int)$cat["id"]; ?>"
                    <?php echo (($_POST["category_id"] ?? "") == $cat["id"]) ? "selected" : ""; ?>>
                    <?php echo h($cat["category_name"]); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="item_description">Item Description</label>
        <textarea name="item_description" id="item_description"
            placeholder="Example: Samsung old smartphone, Dell laptop..."
            rows="3"><?php echo h($_POST["item_description"] ?? ""); ?></textarea>

        <div class="pickup-row">

            <div>
                <label for="quantity">Quantity *</label>
                <input type="number" name="quantity" id="quantity"
                    min="1" max="100" required
                    value="<?php echo h($_POST["quantity"] ?? "1"); ?>">
            </div>

            <div>
                <label for="item_condition">Item Condition *</label>
                <select name="item_condition" id="item_condition" required>
                    <option value="">Select condition</option>
                    <?php foreach (["Working", "Damaged", "Not Working"] as $c): ?>
                        <option value="<?php echo h($c); ?>"
                            <?php echo (($_POST["item_condition"] ?? "") === $c) ? "selected" : ""; ?>>
                            <?php echo h($c); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <h2 class="pickup-second-heading">Pickup Details</h2>

        <label for="pickup_address">Complete Pickup Address *</label>
        <textarea name="pickup_address" id="pickup_address"
            placeholder="House number, street, city, PIN code..."
            rows="3" required><?php echo h($_POST["pickup_address"] ?? ""); ?></textarea>

        <label for="preferred_date">Preferred Pickup Date *</label>
        <input type="date" name="preferred_date" id="preferred_date"
            min="<?php echo date("Y-m-d"); ?>"
            value="<?php echo h($_POST["preferred_date"] ?? ""); ?>"
            required>

        <button type="submit" class="pickup-submit">
            Submit Pickup Request →
        </button>

        <p class="pickup-note">
            Your request will initially be marked as Pending.
        </p>

    </form>
</main>

</body>
</html>
