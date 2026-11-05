
<?php

require_once "db.php";

$error = "";

if (!empty($_SESSION["user_id"])) {
    if ($_SESSION["role"] === "admin") {
        header("Location: admin/dashboard.php");
    } elseif ($_SESSION["role"] === "collector") {
        header("Location: collector/dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    // Validate customer details

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid name and email.";

    } elseif (strlen($password) < 8) {

        $error = "Password must contain at least 8 characters.";

    } elseif ($password !== $confirm) {

        $error = "Passwords do not match.";

    } else {

        // Check whether email already exists

        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->bind_param("s", $email);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error = "This email is already registered.";

        } else {

            // Secure password hashing

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Public registration is for customers only

            $role = "customer";

            // Insert data into database

            $stmt = $conn->prepare(
                "INSERT INTO users
                (full_name, email, password, phone, role)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssss",
                $name,
                $email,
                $hashed_password,
                $phone,
                $role
            );

            $stmt->execute();

            $stmt->close();
            $check->close();

            header("Location: login.php?registered=1");
            exit;
        }

        $check->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Register | E-Waste Portal</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<!-- Navigation -->

<header class="navbar">

    <a href="index.php" class="logo">
        ♻ E-Waste Portal
    </a>

    <div class="nav-buttons">

        <span class="register-question">
            Already have an account?
        </span>

        <a href="login.php" class="login-btn">
            Login
        </a>

    </div>

</header>


<!-- Registration Section -->

<main class="register-page">

    <section class="register-shell">


        <!-- Left Image Section -->

        <div class="register-visual">

            <img src="assests/images/register-ewaste.png" alt="E-waste recycling">

            <div class="register-overlay"></div>

            <div class="register-visual-content">

                <span class="register-tag">
                    ♻ JOIN THE GREEN MOVEMENT
                </span>

                <h1>
                    Start Your
                    <span>Recycling Journey</span>
                </h1>

                <p>
                    Create your account and make electronic
                    waste collection easier. Every responsible
                    action helps us move towards a cleaner future.
                </p>

                <div class="register-points">

                    <p>✓ Easy online pickup requests</p>

                    <p>✓ Track your collection status</p>

                    <p>✓ Support responsible recycling</p>

                </div>

            </div>

        </div>


        <!-- Right Registration Form -->

        <div class="register-form-section">

            <div class="register-form-box">

                <div class="register-icon">
                    ♻
                </div>

                <span class="register-form-tag">
                    CREATE YOUR ACCOUNT
                </span>

                <h2>Join E-Waste Portal</h2>

                <p class="register-description">
                    Enter your details to create
                    your customer account.
                </p>


                <?php if ($error !== ""): ?>

                    <div class="error">
                        <?php echo e($error); ?>
                    </div>

                <?php endif; ?>


                <form method="POST" action="">

                    <!-- Full Name -->

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="Enter your full name"
                        maxlength="100"
                        autocomplete="name"
                        required
                        value="<?php echo e($_POST['full_name'] ?? ''); ?>"
                    >


                    <!-- Email -->

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        maxlength="120"
                        autocomplete="email"
                        required
                        value="<?php echo e($_POST['email'] ?? ''); ?>"
                    >


                    <!-- Phone -->

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        placeholder="Enter your phone number"
                        maxlength="15"
                        autocomplete="tel"
                        value="<?php echo e($_POST['phone'] ?? ''); ?>"
                    >


                    <!-- Password -->

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 8 characters"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >


                    <!-- Confirm Password -->

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-enter your password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >


                    <!-- Show Password -->

                    <label class="register-show-password">

                        <input
                            type="checkbox"
                            id="showRegisterPassword"
                        >

                        Show Password

                    </label>


                    <!-- Submit Button -->

                    <button
                        type="submit"
                        class="register-submit"
                    >
                        Create My Account →
                    </button>

                </form>


                <div class="register-divider">
                    <span>OR</span>
                </div>


                <p class="register-login-text">

                    Already have an account?

                    <a href="login.php">
                        Login Now
                    </a>

                </p>


                <a href="index.php" class="register-back">
                    ← Back to Homepage
                </a>

            </div>

        </div>

    </section>

</main>


<!-- Password Visibility -->

<script>

const checkbox = document.getElementById(
    "showRegisterPassword"
);

const password = document.getElementById("password");

const confirmPassword = document.getElementById(
    "confirm_password"
);

checkbox.addEventListener("change", function () {

    const type = this.checked ? "text" : "password";

    password.type = type;

    confirmPassword.type = type;

});

</script>

</body>
</html>
