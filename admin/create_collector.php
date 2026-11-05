
<?php
require_once "../db.php";
require_role("admin");

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" || $phone === "" || $password === "") {
        $message = "Please fill all fields.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "error";

    } elseif (strlen($password) < 6) {
        $message = "Password must contain at least 6 characters.";
        $messageType = "error";

    } else {

        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $existing = $check->get_result();

        if ($existing->num_rows > 0) {
            $message = "This email is already registered.";
            $messageType = "error";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = "collector";

            $stmt = $conn->prepare("
                INSERT INTO users (full_name, email, phone, password, role)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssss",
                $name,
                $email,
                $phone,
                $hashedPassword,
                $role
            );

            if ($stmt->execute()) {
                $message = "Collector account created successfully!";
                $messageType = "success";
            } else {
                $message = "Something went wrong. Please try again.";
                $messageType = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}

$adminName = $_SESSION["full_name"] ?? "Admin";
$initial = strtoupper(substr($adminName, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Collector | EcoCycle</title>
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
            <a href="dashboard.php">
                <span>▦</span> Dashboard
            </a>

            <a href="manage_requests.php">
                <span>📋</span> Manage Requests
            </a>

            <a href="create_collector.php" class="active">
                <span>👤</span> Add Collector
            </a>

            <a href="../index.php">
                <span>🌐</span> Visit Website
            </a>
        </nav>

        <div class="adminx-sidebar-bottom">
            <div class="adminx-profile">
                <div class="adminx-avatar">
                    <?php echo htmlspecialchars($initial); ?>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($adminName); ?></strong>
                    <small>Administrator</small>
                </div>
            </div>

            <a href="../logout.php" class="adminx-logout">↪ Logout</a>
        </div>

    </aside>

    <!-- Main Content -->
    <main class="adminx-main">

        <header class="adminx-topbar">
            <div>
                <h2>Add New Collector</h2>
                <p>Create and manage your pickup collection team</p>
            </div>

            <div class="adminx-top-user">
                <div class="adminx-avatar">
                    <?php echo htmlspecialchars($initial); ?>
                </div>
                <span><?php echo htmlspecialchars($adminName); ?></span>
            </div>
        </header>

        <!-- Heading Banner -->
        <section class="collector-form-hero">
            <div>
                <span>TEAM MANAGEMENT</span>
                <h1>Build Your Collection Team 👷</h1>
                <p>
                    Register a new collector to manage assigned
                    e-waste pickup requests.
                </p>
            </div>
            <div class="collector-form-hero-icon">👥</div>
        </section>

        <div class="collector-form-layout">

            <!-- Form -->
            <section class="collector-form-card">

                <div class="collector-form-heading">
                    <div class="collector-form-icon">👤</div>
                    <div>
                        <h2>Collector Information</h2>
                        <p>Enter the details to create an account</p>
                    </div>
                </div>

                <?php if ($message !== ""): ?>
                    <div class="collector-message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">

                    <div class="collector-input-group">
                        <label>Full Name</label>
                        <input
                            type="text"
                            name="full_name"
                            placeholder="Enter collector's full name"
                            value="<?php echo htmlspecialchars($_POST["full_name"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="collector-input-group">
                        <label>Email Address</label>
                        <input
                            type="email"
                            name="email"
                            placeholder="collector@example.com"
                            value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="collector-input-group">
                        <label>Phone Number</label>
                        <input
                            type="tel"
                            name="phone"
                            placeholder="Enter phone number"
                            value="<?php echo htmlspecialchars($_POST["phone"] ?? ""); ?>"
                            maxlength="15"
                            required
                        >
                    </div>

                    <div class="collector-form-row">

                        <div class="collector-input-group">
                            <label>Password</label>
                            <input
                                type="password"
                                name="password"
                                placeholder="Minimum 6 characters"
                                minlength="6"
                                required
                            >
                        </div>

                        <div class="collector-input-group">
                            <label>Confirm Password</label>
                            <input
                                type="password"
                                name="confirm_password"
                                placeholder="Re-enter password"
                                minlength="6"
                                required
                            >
                        </div>

                    </div>

                    <div class="collector-role-note">
                        <span>🛡</span>
                        <div>
                            <strong>Account Role: Collector</strong>
                            <p>This account will have access to the Collector Dashboard only.</p>
                        </div>
                    </div>

                    <div class="collector-form-buttons">
                        <button type="submit" class="collector-submit-btn">
                            + Create Collector Account
                        </button>

                        <a href="dashboard.php" class="collector-cancel-btn">
                            Cancel
                        </a>
                    </div>

                </form>
            </section>

            <!-- Side Information -->
            <aside class="collector-info-card">
                <div class="collector-info-illustration">🚚</div>

                <h2>Collector Responsibilities</h2>
                <p>Once registered, collectors can:</p>

                <ul>
                    <li>✓ View assigned pickup requests</li>
                    <li>✓ Check customer details and addresses</li>
                    <li>✓ Update pickup status</li>
                    <li>✓ Mark items as collected</li>
                    <li>✓ Update recycling progress</li>
                </ul>

                <div class="collector-info-bottom">
                    <strong>♻ EcoCycle</strong>
                    <p>Making e-waste recycling easier and smarter.</p>
                </div>
            </aside>

        </div>

        <footer class="adminx-footer">
            © <?php echo date("Y"); ?> EcoCycle. Admin Panel.
        </footer>

    </main>

</div>

</body>
</html>
